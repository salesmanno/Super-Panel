<?php
ob_start();
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Server-feil: ' . $error['message']]);
        exit;
    }
});

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Ugyldig forespørsel']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/email_delivery_safety.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $requestId = $data['idempotency_key'] ?? null;
    if ($requestId !== null && (!is_string($requestId)
        || !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $requestId))) {
        throw new InvalidArgumentException('Ugyldig utsendingsreferanse.');
    }
    if ($requestId !== null) $requestId = strtolower($requestId);

    if (empty($data['subject']) || empty($data['body'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Emne og innhold er påkrevd']);
        exit;
    }

    $config = $db->prepare("SELECT email, display_name FROM email_configs WHERE company_id = ? LIMIT 1");
    $config->execute([$companyId]);
    $emailConfig = $config->fetch(PDO::FETCH_ASSOC);

    if (!$emailConfig) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'E-postkonfigurasjon ikke funnet']);
        exit;
    }

    $fromEmail = $emailConfig['email'];
    $templateId = null;
    if (!empty($data['template_id'])) {
        $templateStmt = $db->prepare("
            SELECT id
            FROM email_templates
            WHERE id = ? AND is_active = 1
              AND (company_id = ? OR company_id IS NULL)
            LIMIT 1
        ");
        $templateStmt->execute([$data['template_id'], $companyId]);
        $templateId = $templateStmt->fetchColumn();
        if (!$templateId) {
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'E-postmalen er ikke tilgjengelig']);
            exit;
        }
    }
    $subject = $templateId
        ? (string) $data['subject']
        : trim((string) $data['subject']);
    $body = $templateId
        ? (string) $data['body']
        : trim((string) $data['body']);

    $recipients = $data['to'] ?? [];
    if (is_string($recipients)) {
        $recipients = array_filter(array_map('trim', explode(',', $recipients)));
    }
    if (empty($recipients)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Ingen mottakere oppgitt']);
        exit;
    }

    $validRecipients = array_values(array_unique(array_map(
        static fn(string $email): string => trim($email),
        array_filter(
            $recipients,
            fn($r) => is_string($r) && filter_var(trim($r), FILTER_VALIDATE_EMAIL)
        )
    )));
    // Treat address case/whitespace variants as one recipient within this send.
    $byAddress = [];
    foreach ($validRecipients as $address) $byAddress[strtolower($address)] = $address;
    $validRecipients = array_values($byAddress);
    if (empty($validRecipients)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Ingen gyldige e-postadresser']);
        exit;
    }

    $memberRecipients = $data['member_recipients'] ?? [];
    if (!is_array($memberRecipients)) {
        http_response_code(400);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Medlemsmottakerne er ugyldige']);
        exit;
    }
    foreach ($memberRecipients as $memberRecipient) {
        if (!is_string($memberRecipient) || !filter_var(trim($memberRecipient), FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Medlemsmottakerne er ugyldige']);
            exit;
        }
    }
    $memberRecipients = array_values(array_unique(array_map(
        static fn(string $email): string => strtolower(trim($email)),
        $memberRecipients
    )));
    $toEmailSet = array_fill_keys(array_map(
        static fn(string $email): string => strtolower(trim($email)),
        $validRecipients
    ), true);
    foreach ($memberRecipients as $memberRecipient) {
        if (!isset($toEmailSet[$memberRecipient])) {
            http_response_code(400);
            ob_end_clean();
            echo json_encode(['success' => false, 'error' => 'Medlemsmottakerne må også være valgt som mottakere']);
            exit;
        }
    }

    $insertStmt = $db->prepare("
        INSERT INTO email_queue
            (id, company_id, recipient_email, from_email, subject, body,
             template_id, module_source, source_id, status, priority, scheduled_at, created_at, updated_at)
        VALUES
            (UUID(), ?, ?, ?, ?, ?, ?, 'webmail', ?, 'queued', 5, NOW(), NOW(), NOW())
    ");

    $customerStmt = $db->prepare("
        SELECT first_name, last_name
        FROM tenant_customers
        WHERE email = ? AND company_id = ?
        LIMIT 1
    ");

    $queued = 0;
    $duplicates = 0;
    $db->beginTransaction();
    // Serialize queue creation for this company, including simultaneous requests.
    // A locking read is essential under MySQL REPEATABLE READ.
    $companyLock = $db->prepare("SELECT name FROM tenant_companies WHERE id = ? FOR UPDATE");
    $companyLock->execute([$companyId]);
    $companyName = $companyLock->fetchColumn();
    if ($companyName === false) throw new RuntimeException('Bedriften finnes ikke.');

    if ($memberRecipients) {
        $memberPlaceholders = implode(',', array_fill(0, count($memberRecipients), '?'));
        $activeMembersStmt = $db->prepare("
            SELECT mm.id, LOWER(TRIM(tc.email)) AS email
            FROM membership_members mm
            INNER JOIN tenant_customers tc
              ON tc.id = mm.customer_id
             AND tc.company_id = mm.company_id
            WHERE mm.company_id = ?
              AND mm.status = 'active'
              AND (mm.cancellation_active IS NULL OR mm.cancellation_active = 0)
              AND (mm.expiration_date IS NULL OR mm.expiration_date >= CURDATE())
              AND tc.email IS NOT NULL
              AND tc.email != ''
              AND LOWER(TRIM(tc.email)) IN ($memberPlaceholders)
            FOR UPDATE
        ");
        $activeMembersStmt->execute(array_merge([$companyId], $memberRecipients));
        $eligibleMemberEmails = array_fill_keys(
            array_column($activeMembersStmt->fetchAll(PDO::FETCH_ASSOC), 'email'),
            true
        );
        $noLongerEligible = array_values(array_filter(
            $memberRecipients,
            static fn(string $email): bool => !isset($eligibleMemberEmails[$email])
        ));
        if ($noLongerEligible) {
            $db->rollBack();
            http_response_code(409);
            ob_end_clean();
            echo json_encode([
                'success' => false,
                'error' => 'Minst ett valgt medlem er utløpt, kansellert eller ikke lenger aktivt. Oppdater medlemslisten før du sender.'
            ]);
            exit;
        }
    }

    foreach ($validRecipients as $recipient) {
        $customerStmt->execute([$recipient, $companyId]);
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

        $rendered = renderWebmailMessage($subject, $body, $customer ?: [], (string) $companyName);
        $previous = existingWebmailRequest($db, [
            'company_id' => $companyId, 'recipient_email' => $recipient,
            'from_email' => $fromEmail, 'subject' => $rendered['subject'],
            'body' => $rendered['body'], 'template_id' => $templateId,
            'module_source' => 'webmail', 'source_id' => $requestId,
        ]);
        if ($previous !== null) {
            $duplicates++;
            continue;
        }

        $queueBindings = [
            $companyId,
            $recipient,
            $fromEmail,
            $rendered['subject'],
            $rendered['body'],
            $templateId,
            $requestId,
        ];
        $auditLinks = [
            'correlation_id' => $requestId,
            'message_subject_sha256' => hash('sha256', (string) $rendered['subject']),
            'message_body_sha256' => hash('sha256', (string) $rendered['body']),
        ];
        if (method_exists($db, 'withAuditLinks')) {
            $db->withAuditLinks($auditLinks, static fn() => $insertStmt->execute($queueBindings));
        } else {
            $insertStmt->execute($queueBindings);
        }
        $queued++;
    }

    $db->commit();
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'queued_count' => $queued,
        'duplicate_count' => $duplicates,
        'message' => "{$queued} e-post(er) lagt i kø"
            . ($duplicates ? ". {$duplicates} duplikat(er) ble ikke lagt til på nytt." : '')
    ]);

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    if ($e instanceof InvalidArgumentException) http_response_code(400);
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
