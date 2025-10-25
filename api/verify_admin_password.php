<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Ikke autorisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Ugyldig forespørsel']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Passord er påkrevd']);
    exit;
}

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Get admin password hash from tenant_companies
    $stmt = $db->prepare("SELECT admin_password_hash FROM tenant_companies WHERE id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$company || empty($company['admin_password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Admin-passord er ikke konfigurert']);
        exit;
    }
    
    // Verify password
    if (password_verify($password, $company['admin_password_hash'])) {
        // Store unlock flag in session (temporary unlock)
        $_SESSION['website_unlocked'] = true;
        $_SESSION['website_unlock_time'] = time();
        
        echo json_encode([
            'success' => true,
            'message' => 'Låst opp! Du kan nå redigere nettside-mal.'
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Feil passord']);
    }
    
} catch (Exception $e) {
    error_log("Admin password verification error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Serverfeil']);
}
