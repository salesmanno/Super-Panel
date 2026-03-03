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
require_once __DIR__ . '/../includes/SMTPClient.php';
require_once __DIR__ . '/../includes/EmailTemplate.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['to']) || empty($data['subject'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Mottaker og emne er påkrevd']);
        exit;
    }

    $to = filter_var($data['to'], FILTER_VALIDATE_EMAIL);
    if (!$to) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Ugyldig e-postadresse']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT smtp_address, smtp_port, smtp_username, smtp_password_encrypted, email, display_name
        FROM email_configs
        WHERE company_id = ?
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config || empty($config['smtp_address']) || empty($config['smtp_username'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'SMTP ikke konfigurert (mangler smtp_address)']);
        exit;
    }

    $companyStmt = $db->prepare("
        SELECT tc.name, tc.logo_url,
               COALESCE(twg.header_bg_color, '#ffffff') AS header_bg_color
        FROM tenant_companies tc
        LEFT JOIN tenant_website_general twg ON twg.company_id = tc.id
        WHERE tc.id = ?
        LIMIT 1
    ");
    $companyStmt->execute([$companyId]);
    $company = $companyStmt->fetch(PDO::FETCH_ASSOC);
    $companyName   = $company['name'] ?? '';
    $logoUrl       = $company['logo_url'] ?? '';
    $headerBgColor = $company['header_bg_color'] ?? '#ffffff';

    $subject   = $data['subject'];
    $rawBody   = $data['body'] ?? '';
    $htmlBody  = EmailTemplate::wrap(nl2br(htmlspecialchars($rawBody)), $companyName, $logoUrl, $headerBgColor);

    $fromEmail = $config['email'] ?? $config['smtp_username'];
    $fromName  = $config['display_name'] ?? $companyName;

    $smtp = new SMTPClient(
        $config['smtp_address'],
        $config['smtp_port'] ?? 465,
        $config['smtp_username'],
        $config['smtp_password_encrypted']
    );

    $result = $smtp->send($fromEmail, $fromName, $to, $subject, $htmlBody);

    if ($result['success']) {
        $logStmt = $db->prepare("
            INSERT INTO email_logs
                (id, company_id, from_email, from_name, to_email, subject, body_html, module_source, status, sent_at)
            VALUES
                (UUID(), ?, ?, ?, ?, ?, ?, 'webmail', 'sent', NOW())
        ");
        $logStmt->execute([$companyId, $fromEmail, $fromName, $to, $subject, $htmlBody]);

        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'E-post sendt']);
    } else {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Kunne ikke sende e-post']);
    }

} catch (\Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
