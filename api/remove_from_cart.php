<?php
require __DIR__.'/sales_legacy_disabled.php';
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$db = getDBConnection();
$admin_id = $_SESSION['tenant_admin_id'];
$company_id = $_SESSION['company_id'];

$data = json_decode(file_get_contents('php://input'), true);
$cart_id = $data['cart_id'] ?? null;

if (!$cart_id) {
    echo json_encode(['success' => false, 'message' => 'Ugyldig data']);
    exit;
}

try {
    $stmt = $db->prepare("
        DELETE FROM sales_cart 
        WHERE id = ? AND admin_id = ? AND company_id = ?
    ");
    $stmt->execute([$cart_id, $admin_id, $company_id]);
    
    echo json_encode(['success' => true, 'message' => 'Produkt fjernet']);
    
} catch (Exception $e) {
    error_log("Remove from cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved fjerning']);
}
