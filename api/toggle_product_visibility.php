<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['product_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler produkt-ID']);
        exit;
    }
    
    $db = getDBConnection();
    
    $stmt = $db->prepare("SELECT is_visible FROM output_products WHERE id = ? AND company_id = ?");
    $stmt->execute([$data['product_id'], $_SESSION['company_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produkt ikke funnet']);
        exit;
    }
    
    $newStatus = $product['is_visible'] ? 0 : 1;
    
    $updateStmt = $db->prepare("UPDATE output_products SET is_visible = ? WHERE id = ?");
    $updateStmt->execute([$newStatus, $data['product_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => $newStatus ? 'Produkt aktivert' : 'Produkt deaktivert',
        'is_visible' => $newStatus
    ]);
    
} catch (Exception $e) {
    error_log("Error toggling product visibility: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil: ' . $e->getMessage()
    ]);
}
