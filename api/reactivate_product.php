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
    
    // Get new status from request (default to 1 for backward compatibility)
    $newStatus = isset($data['new_status']) ? intval($data['new_status']) : 1;
    
    $db = getDBConnection();
    
    // Verify product belongs to user's company
    $stmt = $db->prepare("SELECT id, is_visible FROM output_products WHERE id = ? AND company_id = ?");
    $stmt->execute([$data['product_id'], $_SESSION['company_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produkt ikke funnet']);
        exit;
    }
    
    // Check if status is already set (prevent unnecessary updates)
    if ($product['is_visible'] == $newStatus) {
        echo json_encode([
            'success' => true,
            'message' => $newStatus == 1 ? 'Produkt er allerede aktivt' : 'Produkt er allerede deaktivert'
        ]);
        exit;
    }
    
    // Update product visibility status
    $updateStmt = $db->prepare("
        UPDATE output_products 
        SET is_visible = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $result = $updateStmt->execute([$newStatus, $data['product_id']]);
    
    if (!$result) {
        throw new Exception("Databaseupdate mislyktes");
    }
    
    $statusMessage = $newStatus == 1 ? 'Produkt aktivert' : 'Produkt deaktivert';
    
    echo json_encode([
        'success' => true,
        'message' => $statusMessage,
        'new_status' => $newStatus
    ]);
    
} catch (Exception $e) {
    error_log("Toggle product status error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering av produktstatus: ' . $e->getMessage()
    ]);
}
?>
