<?php
session_start();
require_once '../config/database.php';
require_once '../includes/EmailService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$db = getDBConnection();
$admin_id = $_SESSION['tenant_admin_id'];
$company_id = $_SESSION['company_id'];

$data = json_decode(file_get_contents('php://input'), true);
$payment_method = $data['payment_method'] ?? 'cash';
$customer_name = $data['customer_name'] ?? '';
$customer_email = $data['customer_email'] ?? '';
$send_receipt = $data['send_receipt'] ?? false;
$notes = $data['notes'] ?? '';

try {
    $db->beginTransaction();
    
    // Get cart items (cart only contains products, with fallback pricing)
    $stmt = $db->prepare("
        SELECT 
            sc.id as cart_id,
            sc.output_product_id,
            sc.quantity,
            COALESCE(sp.name, op.name) as product_name,
            op.product_id,
            COALESCE(sp.selling_price, op.price, 0) as price
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
    
    // Generate order ID and transaction ID
    $transactionId = bin2hex(random_bytes(16));
    $orderId = 'ORD-' . date('Ymd') . '-' . strtoupper(substr($transactionId, 0, 6));
    $currentTimestamp = date('Y-m-d H:i:s');
    
    // Calculate total
    $total = 0;
    $itemCount = 0;
    foreach ($items as $item) {
        $total += $item['quantity'] * $item['price'];
        $itemCount += $item['quantity'];
    }
    
    // Create single aggregated sales transaction (CLEAN SCHEMA)
    $stmt = $db->prepare("
        INSERT INTO sales_transactions (
            id,
            company_id,
            admin_id,
            order_id,
            customer_name,
            customer_email,
            items_sold,
            total_amount,
            payment_method,
            payment_status,
            receipt_sent,
            notes,
            transaction_date,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $paymentStatus = $payment_method === 'cash' ? 'completed' : 'pending';
    
    $stmt->execute([
        $transactionId,
        $company_id,
        $admin_id,
        $orderId,
        $customer_name,
        $customer_email,
        $itemCount,
        $total,
        $payment_method,
        $paymentStatus,
        $send_receipt ? 1 : 0,
        $notes,
        $currentTimestamp,
        $currentTimestamp
    ]);
    
    // Clear cart
    $stmt = $db->prepare("DELETE FROM sales_cart WHERE admin_id = ? AND company_id = ?");
    $stmt->execute([$admin_id, $company_id]);
    
    $db->commit();
    
    // Send email receipt if requested
    $emailSent = false;
    if ($send_receipt && !empty($customer_email)) {
        try {
            $emailService = new EmailService($db, $company_id);
            $emailResult = $emailService->send([
                'template' => 'sale_receipt',
                'to' => $customer_email,
                'data' => [
                    'order_id' => $orderId,
                    'customer_name' => $customer_name,
                    'items_sold' => count($items),
                    'total_amount' => number_format($total, 2, ',', ' '),
                    'payment_method' => $payment_method
                ],
                'module_source' => 'sales',
                'source_id' => $orderId
            ]);
            $emailSent = $emailResult['success'];
        } catch (Exception $e) {
            error_log("Email sending error: " . $e->getMessage());
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Salg gjennomført',
        'total' => $total,
        'items_sold' => count($items),
        'email_sent' => $emailSent,
        'order_id' => $orderId
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Checkout error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved betaling']);
}
