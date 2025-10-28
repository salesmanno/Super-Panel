<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autorisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

$email = $input['email'] ?? null;
$displayName = $input['display_name'] ?? null;
$smtpHost = $input['smtp_host'] ?? null;
$smtpPort = $input['smtp_port'] ?? 587;
$smtpUsername = $input['smtp_username'] ?? null;
$smtpPassword = $input['smtp_password'] ?? null;
$imapHost = $input['imap_host'] ?? null;
$imapPort = $input['imap_port'] ?? 993;

if (!$email || !$displayName || !$smtpHost || !$smtpUsername || !$smtpPassword) {
    echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Encrypt password (simple base64 for now, should use better encryption in production)
    $encryptedPassword = base64_encode($smtpPassword);
    
    // Check if config already exists
    $stmt = $db->prepare("SELECT id FROM email_configs WHERE company_id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing config
        $stmt = $db->prepare("
            UPDATE email_configs 
            SET email = ?,
                display_name = ?,
                smtp_host = ?,
                smtp_port = ?,
                smtp_username = ?,
                smtp_password_encrypted = ?,
                updated_at = NOW()
            WHERE company_id = ?
        ");
        $stmt->execute([
            $email,
            $displayName,
            $smtpHost,
            $smtpPort,
            $smtpUsername,
            $encryptedPassword,
            $_SESSION['company_id']
        ]);
    } else {
        // Insert new config
        $stmt = $db->prepare("
            INSERT INTO email_configs (
                id,
                company_id,
                email,
                display_name,
                smtp_host,
                smtp_port,
                smtp_username,
                smtp_password_encrypted,
                is_active,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, TRUE, NOW())
        ");
        $stmt->execute([
            bin2hex(random_bytes(16)),
            $_SESSION['company_id'],
            $email,
            $displayName,
            $smtpHost,
            $smtpPort,
            $smtpUsername,
            $encryptedPassword
        ]);
    }
    
    // Store IMAP settings in session for now (could be added to table later)
    $_SESSION['imap_host'] = $imapHost;
    $_SESSION['imap_port'] = $imapPort;
    
    echo json_encode([
        'success' => true,
        'message' => 'E-postkonfigurasjon lagret'
    ]);
    
} catch (Exception $e) {
    error_log("Save email config error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database feil: ' . $e->getMessage()]);
}
