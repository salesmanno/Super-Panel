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

require_once __DIR__ . '/../includes/db_config.php';
require_once __DIR__ . '/../includes/SMTPClient.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['to']) || empty($data['subject'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Mottaker og emne er påkrevd']);
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT smtp_host, smtp_port, smtp_username, smtp_password_encrypted, email, display_name 
        FROM email_configs 
        WHERE company_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config || empty($config['smtp_host']) || empty($config['smtp_username'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'SMTP ikke konfigurert']);
        exit;
    }
    
    $to = filter_var($data['to'], FILTER_VALIDATE_EMAIL);
    if (!$to) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Ugyldig e-postadresse']);
        exit;
    }
    
    $subject = $data['subject'];
    $body = $data['body'] ?? '';
    $fromEmail = $config['email'] ?? $config['smtp_username'];
    $fromName = $config['display_name'] ?? '';
    
    $smtp = new SMTPClient(
        $config['smtp_host'],
        $config['smtp_port'] ?? 465,
        $config['smtp_username'],
        $config['smtp_password_encrypted']
    );
    
    $result = $smtp->send($fromEmail, $fromName, $to, $subject, $body);
    
    if ($result['success']) {
        $logId = bin2hex(random_bytes(16));
        $stmt = $db->prepare("
            INSERT INTO email_logs (id, company_id, module_source, recipient_email, subject, status, created_at, sent_at)
            VALUES (?, ?, 'webmail', ?, ?, 'sent', NOW(), NOW())
        ");
        $stmt->execute([$logId, $companyId, $to, $subject]);
        
        ob_end_clean();
        echo json_encode(['success' => true, 'message' => 'E-post sendt']);
    } else {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => $result['error'] ?? 'Kunne ikke sende e-post']);
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
