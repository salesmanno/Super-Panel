<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$db = getDBConnection();
$admin_id = $_SESSION['admin_id'];
$company_id = $_SESSION['company_id'];

$data = json_decode(file_get_contents('php://input'), true);
$payment_method = $data['payment_method'] ?? 'cash';
$customer_name = $data['customer_name'] ?? '';
$notes = $data['notes'] ?? '';

try {
    $db->beginTransaction();
    
    // Get cart items
    $stmt = $db->prepare("
        SELECT 
            sc.id as cart_id,
            sc.output_product_id,
            sc.quantity,
            op.name as product_name,
            op.product_id,
            sp.selling_price as price
        FROM sales_cart sc
        JOIN output_products op ON sc.output_product_id = op.id
        LEFT JOIN sales_products sp ON op.product_id = sp.id AND op.product_type = 'product'
        WHERE sc.admin_id = ? AND sc.company_id = ?
    ");
    $stmt->execute([$admin_id, $company_id]);
    $items = $stmt->fetchAll();
    
    if (empty($items)) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Handlekurven er tom']);
        exit;
    }
    
    $total = 0;
    
    // Process each item
    foreach ($items as $item) {
        $subtotal = $item['quantity'] * $item['price'];
        $total += $subtotal;
        
        // Create sales transaction
        $stmt = $db->prepare("
            INSERT INTO sales_transactions 
            (company_id, sales_product_id, quantity, total_price, payment_method, customer_name, notes, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $company_id,
            $item['product_id'],
            $item['quantity'],
            $subtotal,
            $payment_method,
            $customer_name,
            $notes,
            $admin_id
        ]);
        
        // Update stock quantity
        $stmt = $db->prepare("UPDATE sales_products SET quantity = quantity - ? WHERE id = ?");
        $stmt->execute([$item['quantity'], $item['product_id']]);
    }
    
    // Clear cart
    $stmt = $db->prepare("DELETE FROM sales_cart WHERE admin_id = ? AND company_id = ?");
    $stmt->execute([$admin_id, $company_id]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Salg gjennomført',
        'total' => $total,
        'items_sold' => count($items)
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Checkout error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved betaling']);
}
