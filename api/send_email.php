<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Ugyldig forespørsel']);
    exit;
}

require_once __DIR__ . '/../includes/db_config.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['to']) || empty($data['subject'])) {
        echo json_encode(['success' => false, 'error' => 'Mottaker og emne er påkrevd']);
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT smtp_host, smtp_port, smtp_username, smtp_password_encrypted, from_email, from_name 
        FROM email_configs 
        WHERE company_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config || empty($config['smtp_host']) || empty($config['smtp_username'])) {
        echo json_encode(['success' => false, 'error' => 'SMTP ikke konfigurert']);
        exit;
    }
    
    $to = filter_var($data['to'], FILTER_VALIDATE_EMAIL);
    if (!$to) {
        echo json_encode(['success' => false, 'error' => 'Ugyldig e-postadresse']);
        exit;
    }
    
    $subject = htmlspecialchars($data['subject']);
    $body = $data['body'] ?? '';
    $fromEmail = $config['from_email'] ?? $config['smtp_username'];
    $fromName = $config['from_name'] ?? '';
    
    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/html; charset=utf-8";
    $headers[] = "From: " . ($fromName ? "$fromName <$fromEmail>" : $fromEmail);
    $headers[] = "Reply-To: $fromEmail";
    $headers[] = "X-Mailer: PHP/" . phpversion();
    
    ini_set('SMTP', $config['smtp_host']);
    ini_set('smtp_port', $config['smtp_port'] ?? 465);
    
    $success = @mail($to, $subject, $body, implode("\r\n", $headers));
    
    if ($success) {
        $logId = bin2hex(random_bytes(16));
        $stmt = $db->prepare("
            INSERT INTO email_logs (id, company_id, module_source, recipient_email, subject, status, created_at, sent_at)
            VALUES (?, ?, 'webmail', ?, ?, 'sent', NOW(), NOW())
        ");
        $stmt->execute([$logId, $companyId, $to, $subject]);
        
        echo json_encode(['success' => true, 'message' => 'E-post sendt']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Kunne ikke sende e-post']);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
