<?php
session_start();
session_write_close();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$product_id = $_GET['id'] ?? null;

if (!$product_id) {
    echo json_encode(['success' => false, 'message' => 'Produkt ID mangler']);
    exit;
}

$db = getDBConnection();
$company_id = $_SESSION['company_id'];

try {
    $stmt = $db->prepare("
        SELECT 
            id,
            name,
            sku,
            description,
            cost_price,
            selling_price,
            img_product,
            tax_rate,
            item_taxable,
            unit,
            reorder_level,
            is_active
        FROM sales_products 
        WHERE id = ? AND company_id = ?
    ");
    
    $stmt->execute([$product_id, $company_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Produktet finnes ikke']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'product' => $product
    ]);
    
} catch (Exception $e) {
    error_log("Get single product error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av produkt'
    ]);
}
