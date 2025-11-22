<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['product_id'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Produkt ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Verify product belongs to this company
    $stmt = $db->prepare("
        SELECT id FROM output_products 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$product_id, $company_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Oppføring ikke funnet']);
        exit;
    }
    
    // Delete the product
    $stmt = $db->prepare("
        DELETE FROM output_products 
        WHERE id = ? AND company_id = ?
    ");
    
    $result = $stmt->execute([$product_id, $company_id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Oppføring slettet'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Feil ved sletting'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Delete output product error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting: ' . $e->getMessage()
    ]);
}
