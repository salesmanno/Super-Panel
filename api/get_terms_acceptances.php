<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$db = getDBConnection();
$company_id = $_SESSION['company_id'];
$terms_id = $_GET['terms_id'] ?? null;

try {
    if (!$terms_id) {
        echo json_encode(['success' => false, 'message' => 'Manglende terms_id']);
        exit;
    }
    
    // Verify terms belong to this company
    $verifyStmt = $db->prepare("
        SELECT id FROM tenant_terms_conditions 
        WHERE id = ? AND company_id = ?
    ");
    $verifyStmt->execute([$terms_id, $company_id]);
    if (!$verifyStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Betingelser ikke funnet']);
        exit;
    }
    
    // Get acceptances
    $stmt = $db->prepare("
        SELECT 
            id, user_name, user_email, accepted_at, module_context
        FROM tenant_terms_acceptances 
        WHERE terms_conditions_id = ?
        ORDER BY accepted_at DESC
        LIMIT 100
    ");
    $stmt->execute([$terms_id]);
    $acceptances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $acceptances]);
    
} catch (Exception $e) {
    error_log("Get acceptances error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved henting av aksepteringer']);
}
