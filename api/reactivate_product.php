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
    
    // Reactivate product (set is_visible = 1)
    $updateStmt = $db->prepare("
        UPDATE output_products 
        SET is_visible = 1, updated_at = NOW()
        WHERE id = ?
    ");
    
    $updateStmt->execute([$data['product_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Produkt reaktivert'
    ]);
    
} catch (Exception $e) {
    error_log("Reactivate product error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved reaktivering av produkt'
    ]);
}
?>
