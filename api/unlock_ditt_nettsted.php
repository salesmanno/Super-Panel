<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$password = $input['password'] ?? '';

if (empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Passord kan ikke være tomt']);
    exit;
}

try {
    $db = getDBConnection();
    $db->query("SET collation_connection='utf8mb4_general_ci'");
    
    // Hent admin password hash fra tenant_companies
    $stmt = $db->prepare("SELECT admin_password_hash FROM tenant_companies WHERE id = ?");
    $stmt->execute([$_SESSION['company_id']]);
    $company = $stmt->fetch();
    
    if (!$company) {
        echo json_encode(['success' => false, 'error' => 'Firma ikke funnet']);
        exit;
    }
    
    if (empty($company['admin_password_hash'])) {
        echo json_encode(['success' => false, 'error' => 'Administratorpassord er ikke satt for dette firmaet']);
        exit;
    }
    
    // Valider passord
    if (password_verify($password, $company['admin_password_hash'])) {
        // Sett session-variabel for at brukeren har tilgang til "Ditt nettsted" i 30 minutter
        $_SESSION['ditt_nettsted_unlocked'] = time();
        $_SESSION['ditt_nettsted_expires'] = time() + (30 * 60); // 30 minutter
        
        echo json_encode([
            'success' => true,
            'message' => 'Tilgang gitt',
            'expires_in' => 1800 // 30 minutter i sekunder
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Feil passord']);
    }
    
} catch (Exception $e) {
    error_log("Unlock ditt nettsted error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'En feil oppstod']);
}
