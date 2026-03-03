<?php
session_start();
session_write_close();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

try {
    $db = getDBConnection();
    $admin_id = $_SESSION['tenant_admin_id'];
    $company_id = $_SESSION['company_id'];
    $stmt = $db->prepare("
        SELECT 
            sc.id as cart_id,
            sc.output_product_id,
            sc.quantity,
            COALESCE(sp.name, op.name) as product_name,
            COALESCE(sp.selling_price, op.price, 0) as price,
            (sc.quantity * COALESCE(sp.selling_price, op.price, 0)) as subtotal
        FROM sales_cart sc
        JOIN output_products op ON sc.output_product_id = op.id
        LEFT JOIN sales_products sp ON op.product_id = sp.id AND op.product_type = 'product'
        WHERE sc.admin_id = ? AND sc.company_id = ?
        ORDER BY sc.added_at DESC
    ");
    $stmt->execute([$admin_id, $company_id]);
    $items = $stmt->fetchAll();
    
    $total = array_sum(array_column($items, 'subtotal'));
    $totalQuantity = array_sum(array_column($items, 'quantity'));
    
    echo json_encode([
        'success' => true,
        'items' => $items,
        'total' => $total,
        'count' => $totalQuantity
    ]);
    
} catch (Exception $e) {
    error_log("Get cart error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved henting av handlekurv']);
}
