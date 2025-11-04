<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

$companyId = $_SESSION['company_id'];

try {
    $db = getDBConnection();
    
    // Check SMTP configuration
    $stmt = $db->prepare("
        SELECT 
            smtp_host,
            smtp_port,
            smtp_username,
            smtp_password,
            imap_host,
            imap_port
        FROM x_webmailconfiguration
        WHERE company_id = ?
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $smtpConfigured = false;
    $imapConfigured = false;
    
    if ($config) {
        $smtpConfigured = !empty($config['smtp_host']) && 
                          !empty($config['smtp_port']) && 
                          !empty($config['smtp_username']) && 
                          !empty($config['smtp_password']);
        
        $imapConfigured = !empty($config['imap_host']) && 
                          !empty($config['imap_port']);
    }
    
    echo json_encode([
        'success' => true,
        'smtp_configured' => $smtpConfigured,
        'imap_configured' => $imapConfigured,
        'webmail_configured' => $smtpConfigured && $imapConfigured
    ]);

} catch (Exception $e) {
    error_log("Get email config status error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Kunne ikke hente e-postkonfigurasjon: ' . $e->getMessage()
    ]);
}
