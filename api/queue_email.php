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

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $data = json_decode(file_get_contents('php://input'), true);

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
    $subject   = trim($data['subject']);
    $body      = trim($data['body']);

    $recipients = $data['to'] ?? [];
    if (is_string($recipients)) {
        $recipients = array_filter(array_map('trim', explode(',', $recipients)));
    }
    if (empty($recipients)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Ingen mottakere oppgitt']);
        exit;
    }

    $validRecipients = array_filter($recipients, fn($r) => filter_var($r, FILTER_VALIDATE_EMAIL));
    if (empty($validRecipients)) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Ingen gyldige e-postadresser']);
        exit;
    }

    $insertStmt = $db->prepare("
        INSERT INTO email_queue 
            (id, company_id, recipient_email, from_email, subject, body, module_source, status, priority, scheduled_at, created_at, updated_at)
        VALUES 
            (UUID(), ?, ?, ?, ?, ?, 'webmail', 'queued', 5, NOW(), NOW(), NOW())
    ");

    $customerStmt = $db->prepare("
        SELECT first_name, last_name
        FROM tenant_customers
        WHERE email = ? AND company_id = ?
        LIMIT 1
    ");

    $queued = 0;
    foreach ($validRecipients as $recipient) {
        $customerStmt->execute([$recipient, $companyId]);
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);

        $personalBody = str_replace(
            ['{{cus_first_name}}', '{{cus_last_name}}'],
            [$customer['first_name'] ?? '', $customer['last_name'] ?? ''],
            $body
        );

        $insertStmt->execute([$companyId, $recipient, $fromEmail, $subject, $personalBody]);
        $queued++;
    }

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'queued_count' => $queued,
        'message' => "{$queued} e-post(er) lagt i kø"
    ]);

} catch (\Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
