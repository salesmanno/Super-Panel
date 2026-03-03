<?php
session_start();
session_write_close();
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
    
    // Get acceptances with customer data from tenant_customers
    $stmt = $db->prepare("
        SELECT 
            ta.id,
            ta.accepted_at,
            ta.module_context,
            c.first_name,
            c.last_name,
            c.email,
            c.phone
        FROM tenant_terms_acceptances ta
        LEFT JOIN tenant_customers c ON ta.customer_id = c.id
        WHERE ta.terms_conditions_id = ?
        ORDER BY ta.accepted_at DESC
        LIMIT 100
    ");
    $stmt->execute([$terms_id]);
    $acceptances = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $acceptances]);
    
} catch (Exception $e) {
    error_log("Get acceptances error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved henting av aksepteringer']);
}
