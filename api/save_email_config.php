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

$companyId = $_SESSION['company_id'];

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
    
    $from_email = $data['from_email'] ?? $smtp_username;
    $from_name = $data['from_name'] ?? null;
    
    $db = getDBConnection();
    $db->beginTransaction();
    
    // Check if config exists
    $stmt = $db->prepare("SELECT id FROM email_configs WHERE company_id = ?");
    $stmt->execute([$companyId]);
    $existingConfig = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingConfig) {
        // Update existing
        $stmt = $db->prepare("
            UPDATE email_configs 
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
                from_email = ?,
                from_name = ?,
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
            $from_email,
            $from_name,
            $companyId
        ]);
        
        $message = 'E-postkonfigurasjon oppdatert';
    } else {
        // Insert new
        $configId = bin2hex(random_bytes(18));
        $stmt = $db->prepare("
            INSERT INTO email_configs (
                id,
                company_id,
                smtp_host,
                smtp_port,
                smtp_username,
                smtp_password,
                smtp_encryption,
                smtp_ssl,
                imap_host,
                imap_port,
                imap_username,
                imap_password,
                imap_ssl,
                from_email,
                from_name
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $configId,
            $companyId,
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
            $from_email,
            $from_name
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
