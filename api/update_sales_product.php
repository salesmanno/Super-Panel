<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$product_id = $data['id'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Produkt ID mangler']);
    exit;
}

$db = getDBConnection();
$company_id = $_SESSION['company_id'];

try {
    // Verify product belongs to company
    $stmt = $db->prepare("SELECT id FROM sales_products WHERE id = ? AND company_id = ?");
    $stmt->execute([$product_id, $company_id]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Produktet finnes ikke']);
        exit;
    }
    
    $db->beginTransaction();
    
    // Update sales_products
    $stmt = $db->prepare("
        UPDATE sales_products 
        SET 
            name = ?,
            description = ?,
            cost_price = ?,
            selling_price = ?,
            img_product = ?,
            tax_rate = ?,
            item_taxable = ?,
            unit = ?,
            reorder_level = ?,
            is_active = ?,
            updated_at = NOW()
        WHERE id = ? AND company_id = ?
    ");
    
    $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['cost_price'] ?? 0,
        $data['selling_price'],
        !empty($data['img_product']) ? $data['img_product'] : null,
        $data['tax_rate'] ?? 25,
        isset($data['item_taxable']) ? 1 : 0,
        $data['unit'] ?? 'stk',
        $data['reorder_level'] ?? 0,
        isset($data['is_active']) ? 1 : 0,
        $product_id,
        $company_id
    ]);
    
    // Update output_products name, description, and price
    $stmt = $db->prepare("
        UPDATE output_products 
        SET 
            name = ?,
            description = ?,
            price = ?,
            updated_at = NOW()
        WHERE product_type = 'product' AND product_id = ? AND company_id = ?
    ");
    
    $stmt->execute([
        $data['name'],
        $data['description'] ?? null,
        $data['selling_price'],
        $product_id,
        $company_id
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Produktet ble oppdatert'
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Update sales product error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering av produkt: ' . $e->getMessage()
    ]);
}
