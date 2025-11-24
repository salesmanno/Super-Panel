<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['product_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler product_id']);
        exit;
    }
    
    $db = getDBConnection();
    
    // Verify product belongs to user's company
    $stmt = $db->prepare("SELECT id FROM output_products WHERE id = ? AND company_id = ?");
    $stmt->execute([$data['product_id'], $_SESSION['company_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Produkt ikke funnet']);
        exit;
    }
    
    // Hard delete product from output_products
    $deleteStmt = $db->prepare("DELETE FROM output_products WHERE id = ?");
    $deleteStmt->execute([$data['product_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Produkt slettet permanent'
    ]);
    
} catch (Exception $e) {
    error_log("Delete product error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting av produkt'
    ]);
}
?>
