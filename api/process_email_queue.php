<?php
/**
 * Email Queue Processor
 * Sender e-poster fra køen via autentisert SMTP (SMTPClient)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/SMTPClient.php';
require_once __DIR__ . '/../includes/EmailTemplate.php';

define('MAX_EMAILS_PER_HOUR_PER_COMPANY', 100);

$RETRY_DELAYS = [5, 15, 60];

function processQueue() {
    try {
        $db = getDBConnection();
        $companies = getCompaniesWithQueuedEmails($db);

        foreach ($companies as $companyId) {
            if (!checkRateLimit($db, $companyId)) {
                echo "Rate limit nådd for firma {$companyId}, hopper over...\n";
                continue;
            }

            $emails = getEmailsToSend($db, $companyId);
            echo "Prosesserer " . count($emails) . " e-poster for firma {$companyId}\n";

            foreach ($emails as $email) {
                processEmail($db, $email);
            }
        }

        echo "Køprosessering fullført\n";

    } catch (\Throwable $e) {
        error_log("Køprosessor feil: " . $e->getMessage());
        echo "FEIL: " . $e->getMessage() . "\n";
    }
}

function getCompaniesWithQueuedEmails($db) {
    $stmt = $db->prepare("
        SELECT DISTINCT company_id
        FROM email_queue
        WHERE status = 'queued'
          AND scheduled_at <= NOW()
        ORDER BY company_id
    ");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function checkRateLimit($db, $companyId) {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM email_queue
        WHERE company_id = ?
          AND status = 'sent'
          AND sent_at >= NOW() - INTERVAL 1 HOUR
    ");
    $stmt->execute([$companyId]);
    return (int)$stmt->fetchColumn() < MAX_EMAILS_PER_HOUR_PER_COMPANY;
}

function getEmailsToSend($db, $companyId) {
    $stmt = $db->prepare("
        SELECT COUNT(*)
        FROM email_queue
        WHERE company_id = ?
          AND status = 'sent'
          AND sent_at >= NOW() - INTERVAL 1 HOUR
    ");
    $stmt->execute([$companyId]);
    $sentLastHour = (int)$stmt->fetchColumn();
    $remaining = MAX_EMAILS_PER_HOUR_PER_COMPANY - $sentLastHour;

    if ($remaining <= 0) return [];

    $stmt = $db->prepare("
        SELECT *
        FROM email_queue
        WHERE company_id = ?
          AND status = 'queued'
          AND scheduled_at <= NOW()
        ORDER BY priority ASC, scheduled_at ASC
        LIMIT ?
    ");
    $stmt->execute([$companyId, $remaining]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function processEmail($db, $email) {
    global $RETRY_DELAYS;

    $claim = $db->prepare("
        UPDATE email_queue
        SET status = 'sending', updated_at = NOW()
        WHERE id = ? AND company_id = ? AND status = 'queued'
    ");
    $claim->execute([$email['id'], $email['company_id']]);
    if ($claim->rowCount() !== 1) {
        return;
    }

    try {
        $smtpConfig = getSMTPConfig($db, $email['company_id']);
        if (!$smtpConfig) {
            throw new Exception("SMTP-konfigurasjon ikke funnet for firma {$email['company_id']}");
        }

        $companyInfo   = getCompanyInfo($db, $email['company_id']);
        $companyName   = $companyInfo['name'] ?? '';
        $logoUrl       = $companyInfo['logo_url'] ?? '';
        $headerBgColor = $companyInfo['header_bg_color'] ?? '#ffffff';

        $rawBody  = $email['body'];
        $isFullHtml = stripos(ltrim($rawBody), '<!DOCTYPE') === 0 || stripos(ltrim($rawBody), '<html') === 0;
        $htmlBody = $isFullHtml
            ? $rawBody
            : EmailTemplate::wrap($rawBody, $companyName, $logoUrl, $headerBgColor);

        $smtp = new SMTPClient(
            $smtpConfig['smtp_address'] ?: $smtpConfig['smtp_host'],
            $smtpConfig['smtp_port'] ?? 465,
            $smtpConfig['smtp_username'],
            $smtpConfig['smtp_password_encrypted']
        );

        $fromEmail = $email['from_email'] ?: $smtpConfig['email'];
        $fromName  = $smtpConfig['display_name'] ?? $companyName;

        $result = $smtp->send($fromEmail, $fromName, $email['recipient_email'], $email['subject'], $htmlBody);

        if ($result['success']) {
            $messageId = $result['message_id'] ?? 'ukjent';
            $smtpResponse = preg_replace('/\s+/', ' ', (string) ($result['smtp_response'] ?? '250 akseptert'));
            $acceptanceDetails = "SMTP akseptert | Message-ID: {$messageId} | Svar: {$smtpResponse}";

            $db->beginTransaction();
            try {
                $stmt = $db->prepare("
                    UPDATE email_queue
                    SET status = 'sent', sent_at = NOW(), error_message = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$acceptanceDetails, $email['id']]);
                if (($email['module_source'] ?? '') === 'rental_checkin') {
                    $db->prepare("
                        UPDATE rental_schedule
                        SET checkin_email_sent_at = NOW()
                        WHERE id = ? AND company_id = ? AND checkin_email_sent_at IS NULL
                    ")->execute([$email['source_id'], $email['company_id']]);
                }
                $db->commit();
            } catch (Throwable $statusError) {
                if ($db->inTransaction()) $db->rollBack();
                throw $statusError;
            }

            try {
                $logStmt = $db->prepare("
                    INSERT INTO email_logs
                        (id, company_id, from_email, from_name, to_email, subject, body_html,
                         module_source, status, error_message, sent_at)
                    VALUES
                        (UUID(), ?, ?, ?, ?, ?, ?, ?, 'sent', ?, NOW())
                ");
                $logStmt->execute([
                    $email['company_id'],
                    $fromEmail,
                    $fromName,
                    $email['recipient_email'],
                    $email['subject'],
                    $htmlBody,
                    $email['module_source'] ?? 'system',
                    $acceptanceDetails,
                ]);
            } catch (\PDOException $logEx) {
                if (stripos($logEx->getMessage(), 'module_source') !== false) {
                    $logStmt = $db->prepare("
                        INSERT INTO email_logs
                            (id, company_id, from_email, from_name, to_email, subject,
                             body_html, status, error_message, sent_at)
                        VALUES
                            (UUID(), ?, ?, ?, ?, ?, ?, 'sent', ?, NOW())
                    ");
                    $logStmt->execute([
                        $email['company_id'],
                        $fromEmail,
                        $fromName,
                        $email['recipient_email'],
                        $email['subject'],
                        $htmlBody,
                        $acceptanceDetails,
                    ]);
                }
            }

            echo "✓ SMTP akseptert: {$email['id']} til {$email['recipient_email']}"
                . " | Message-ID: {$messageId} | Svar: {$smtpResponse}\n";

        } else {
            throw new Exception($result['error'] ?? 'Ukjent SMTP-feil');
        }

    } catch (\Throwable $e) {
        $attempts    = ($email['attempts'] ?? 0) + 1;
        $maxAttempts = $email['max_attempts'] ?? 3;
        $errorMsg    = $e->getMessage();

        // 5xx SMTP-koder = permanent feil (ugyldig adresse, bruker finnes ikke, osv.)
        // Ikke forsøk på nytt — sett direkte til 'failed'
        $isPermanentSmtpError = (bool)preg_match('/fikk:\s*5\d\d/i', $errorMsg);

        if (!$isPermanentSmtpError && $attempts < $maxAttempts) {
            $delayIndex   = min($attempts - 1, count($RETRY_DELAYS) - 1);
            $delayMinutes = $RETRY_DELAYS[$delayIndex];

            $stmt = $db->prepare("
                UPDATE email_queue
                SET status = 'queued',
                    attempts = ?,
                    next_retry_at = NOW() + INTERVAL {$delayMinutes} MINUTE,
                    error_message = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$attempts, $errorMsg, $email['id']]);
            echo "✗ Feilet (forsøk {$attempts}/{$maxAttempts}): {$email['id']} - {$errorMsg}\n";
        } else {
            $stmt = $db->prepare("
                UPDATE email_queue
                SET status = 'failed',
                    attempts = ?,
                    error_message = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$attempts, $errorMsg, $email['id']]);
            $reason = $isPermanentSmtpError ? 'Permanent SMTP-feil (5xx)' : 'Maks forsøk nådd';
            echo "✗✗ {$reason}: {$email['id']} - {$errorMsg}\n";
        }

        error_log("E-post sendefeil (ID: {$email['id']}): " . $errorMsg);
    }
}

function updateEmailStatus($db, $emailId, $status) {
    $stmt = $db->prepare("UPDATE email_queue SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$status, $emailId]);
}

function getSMTPConfig($db, $companyId) {
    $stmt = $db->prepare("SELECT * FROM email_configs WHERE company_id = ? LIMIT 1");
    $stmt->execute([$companyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getCompanyInfo($db, $companyId) {
    $stmt = $db->prepare("
        SELECT tc.name, tc.logo_url,
               COALESCE(twg.header_bg_color, '#ffffff') AS header_bg_color
        FROM tenant_companies tc
        LEFT JOIN tenant_website_general twg ON twg.company_id = tc.id
        WHERE tc.id = ?
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

if (!defined('EMAIL_QUEUE_PROCESSOR_NO_AUTO_RUN')) {
    if (php_sapi_name() === 'cli') {
        processQueue();
    } else {
        header('Content-Type: application/json');
        processQueue();
        echo json_encode(['success' => true, 'message' => 'Kø prosessert']);
    }
}
