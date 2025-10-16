<?php
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
$quantity = intval($data['quantity'] ?? 0);

if (!$cart_id || $quantity < 1) {
    echo json_encode(['success' => false, 'message' => 'Ugyldig data']);
    exit;
}

try {
    $stmt = $db->prepare("
        UPDATE sales_cart 
        SET quantity = ? 
        WHERE id = ? AND admin_id = ? AND company_id = ?
    ");
    $stmt->execute([$quantity, $cart_id, $admin_id, $company_id]);
    
    echo json_encode(['success' => true, 'message' => 'Antall oppdatert']);
    
} catch (Exception $e) {
    error_log("Update cart quantity error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved oppdatering']);
}
