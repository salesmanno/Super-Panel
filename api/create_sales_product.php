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

$name = trim($data['name'] ?? '');
$description = trim($data['description'] ?? '');
$category_id = $data['category_id'] ?? null;
$cost_price = floatval($data['cost_price'] ?? 0);
$selling_price = floatval($data['selling_price'] ?? 0);
$member_price = isset($data['member_price']) && $data['member_price'] !== '' ? floatval($data['member_price']) : null;
$currency = $data['currency'] ?? 'NOK';
$tax_rate = floatval($data['tax_rate'] ?? 25); // Standard MVA 25%
$unit = $data['unit'] ?? 'stk';
$reorder_level = intval($data['reorder_level'] ?? 0);
$is_active = isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1;

if (empty($name) || $selling_price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Navn og pris er påkrevd']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Auto-generate IDs
    $product_id = bin2hex(random_bytes(18)); // Generate UUID for product
    $sku = 'PRD-' . time() . '-' . rand(1000, 9999);
    $barcode = ''; // Empty barcode since not used
    
    // Insert sales_product
    $stmt = $db->prepare("
        INSERT INTO sales_products 
        (id, company_id, category_id, sku, name, description, barcode, cost_price, 
         selling_price, member_price, currency, tax_rate, unit, reorder_level, 
         is_active, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $product_id,
        $company_id,
        $category_id,
        $sku,
        $name,
        $description,
        $barcode,
        $cost_price,
        $selling_price,
        $member_price,
        $currency,
        $tax_rate,
        $unit,
        $reorder_level,
        $is_active
    ]);
    
    // Auto-generate output_products entry
    $output_product_id = bin2hex(random_bytes(16)); // Generate UUID
    
    $stmt = $db->prepare("
        INSERT INTO output_products 
        (id, company_id, product_type, product_id, name, price, is_visible, is_public, created_at, updated_at)
        VALUES (?, ?, 'product', ?, ?, ?, 1, 1, NOW(), NOW())
    ");
    
    $stmt->execute([
        $output_product_id,
        $company_id,
        $product_id,
        $name,
        $selling_price
    ]);
    
    // Update sales_products with output_product_id
    $stmt = $db->prepare("UPDATE sales_products SET output_product_id = ? WHERE id = ?");
    $stmt->execute([$output_product_id, $product_id]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Produkt opprettet',
        'product_id' => $product_id,
        'output_product_id' => $output_product_id
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Create sales product error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved opprettelse: ' . $e->getMessage()]);
}
