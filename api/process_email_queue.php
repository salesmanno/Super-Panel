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

    try {
        updateEmailStatus($db, $email['id'], 'sending');

        $smtpConfig = getSMTPConfig($db, $email['company_id']);
        if (!$smtpConfig) {
            throw new Exception("SMTP-konfigurasjon ikke funnet for firma {$email['company_id']}");
        }

        $companyInfo = getCompanyInfo($db, $email['company_id']);
        $companyName = $companyInfo['name'] ?? '';
        $logoUrl     = $companyInfo['logo_url'] ?? '';

        $htmlBody = EmailTemplate::wrap($email['body'], $companyName, $logoUrl);

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
            $stmt = $db->prepare("
                UPDATE email_queue
                SET status = 'sent', sent_at = NOW(), updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$email['id']]);

            $logStmt = $db->prepare("
                INSERT INTO email_logs
                    (id, company_id, from_email, from_name, to_email, subject, body_html, status, sent_at)
                VALUES
                    (UUID(), ?, ?, ?, ?, ?, ?, 'sent', NOW())
            ");
            $logStmt->execute([
                $email['company_id'],
                $fromEmail,
                $fromName,
                $email['recipient_email'],
                $email['subject'],
                $htmlBody
            ]);

            echo "✓ Sendt: {$email['id']} til {$email['recipient_email']}\n";

        } else {
            throw new Exception($result['error'] ?? 'Ukjent SMTP-feil');
        }

    } catch (\Throwable $e) {
        $attempts   = ($email['attempts'] ?? 0) + 1;
        $maxAttempts = $email['max_attempts'] ?? 3;

        if ($attempts < $maxAttempts) {
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
            $stmt->execute([$attempts, $e->getMessage(), $email['id']]);
            echo "✗ Feilet (forsøk {$attempts}/{$maxAttempts}): {$email['id']} - {$e->getMessage()}\n";
        } else {
            $stmt = $db->prepare("
                UPDATE email_queue
                SET status = 'failed',
                    attempts = ?,
                    error_message = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$attempts, $e->getMessage(), $email['id']]);
            echo "✗✗ Permanent feil: {$email['id']} - {$e->getMessage()}\n";
        }

        error_log("E-post sendefeil (ID: {$email['id']}): " . $e->getMessage());
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
    $stmt = $db->prepare("SELECT name, logo_url FROM tenant_companies WHERE id = ? LIMIT 1");
    $stmt->execute([$companyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

if (php_sapi_name() === 'cli') {
    processQueue();
} else {
    header('Content-Type: application/json');
    processQueue();
    echo json_encode(['success' => true, 'message' => 'Kø prosessert']);
}
