<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Kun POST tillatt']);
    exit;
}

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $smtp_host = $data['smtp_host'] ?? null;
    $smtp_port = intval($data['smtp_port'] ?? 587);
    $smtp_username = $data['smtp_username'] ?? null;
    $smtp_password = $data['smtp_password'] ?? null;
    $smtp_encryption = $data['smtp_encryption'] ?? '';
    $smtp_ssl = isset($data['smtp_ssl']) ? (bool)$data['smtp_ssl'] : false;
    
    $imap_host = $data['imap_host'] ?? null;
    $imap_port = intval($data['imap_port'] ?? 993);
    $imap_username = $data['imap_username'] ?? null;
    $imap_password = $data['imap_password'] ?? null;
    $imap_ssl = isset($data['imap_ssl']) ? (bool)$data['imap_ssl'] : false;
    
    $db = getDBConnection();
    $db->beginTransaction();
    
    // Check if configuration already exists
    $stmt = $db->prepare("SELECT config_id FROM x_webmailconfiguration WHERE company_id = ?");
    $stmt->execute([$company_id]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Update existing configuration
        $stmt = $db->prepare("
            UPDATE x_webmailconfiguration 
            SET smtp_host = ?,
                smtp_port = ?,
                smtp_username = ?,
                smtp_password = ?,
                smtp_encryption = ?,
                smtp_ssl = ?,
                imap_host = ?,
                imap_port = ?,
                imap_username = ?,
                imap_password = ?,
                imap_ssl = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE company_id = ?
        ");
        $stmt->execute([
            $smtp_host,
            $smtp_port,
            $smtp_username,
            $smtp_password,
            $smtp_encryption,
            $smtp_ssl,
            $imap_host,
            $imap_port,
            $imap_username,
            $imap_password,
            $imap_ssl,
            $company_id
        ]);
        
        $message = 'E-postkonfigurasjon oppdatert';
    } else {
        // Insert new configuration
        $stmt = $db->prepare("
            INSERT INTO x_webmailconfiguration 
            (company_id, smtp_host, smtp_port, smtp_username, smtp_password, smtp_encryption, smtp_ssl,
             imap_host, imap_port, imap_username, imap_password, imap_ssl)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $company_id,
            $smtp_host,
            $smtp_port,
            $smtp_username,
            $smtp_password,
            $smtp_encryption,
            $smtp_ssl,
            $imap_host,
            $imap_port,
            $imap_username,
            $imap_password,
            $imap_ssl
        ]);
        
        $message = 'E-postkonfigurasjon opprettet';
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $message
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Save email config error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}
