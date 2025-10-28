<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
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
            (selling_price - COALESCE(cost_price, 0)) as profit_margin,
            img_product,
            item_taxable,
            category_id,
            is_active,
            created_at
        FROM sales_products 
        WHERE company_id = ?
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$company_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
    
} catch (Exception $e) {
    error_log("Get sales products error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av produkter: ' . $e->getMessage()
    ]);
}
