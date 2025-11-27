<?php
session_start();
require_once '../config/database.php';
require_once '../includes/EmailService.php';
require_once '../includes/UnifiedLedgerService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$payment_method = $data['payment_method'] ?? 'cash';
$customer_name = $data['customer_name'] ?? '';
$customer_email = $data['customer_email'] ?? '';
$send_receipt = $data['send_receipt'] ?? false;
$notes = $data['notes'] ?? '';
$event_id = $data['event_id'] ?? null;

try {
    $db = getDBConnection();
    $admin_id = $_SESSION['tenant_admin_id'];
    $company_id = $_SESSION['company_id'];
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
    
    // Create sales transaction record for each item (current schema)
    // Also log to unified_ledger for centralized economy tracking
    $ledgerService = new UnifiedLedgerService($db, $company_id);
    
    // Check if stock tracking exists (no validation, just detection)
    $hasStockTracking = true;
    try {
        $testStmt = $db->prepare("SELECT stock_quantity FROM sales_products LIMIT 1");
        $testStmt->execute();
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), 'Column not found') !== false) {
            $hasStockTracking = false;
        }
    }
    
    // Process each item (no stock validation, allow negative stock)
    foreach ($items as $item) {
        $itemTotal = $item['quantity'] * $item['price'];
        
        $stmt = $db->prepare("
            INSERT INTO sales_transactions (
                company_id,
                sales_product_id,
                quantity,
                total_price,
                payment_method,
                customer_name,
                customer_email,
                send_receipt,
                notes,
                created_by,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $company_id,
            $item['product_id'],
            $item['quantity'],
            $itemTotal,
            $payment_method,
            $customer_name ?: 'Walk-in',
            $customer_email ?: null,
            $send_receipt ? 1 : 0,
            $notes,
            $admin_id,
            $currentTimestamp
        ]);
        
        $itemTransactionId = $db->lastInsertId();
        
        // Get cost_price for profit tracking and reduce stock (if tracking exists)
        $costPrice = null;
        if ($item['product_id']) {
            try {
                $costStmt = $db->prepare("SELECT cost_price, stock_quantity FROM sales_products WHERE id = ?");
                $costStmt->execute([$item['product_id']]);
                $costResult = $costStmt->fetch(PDO::FETCH_ASSOC);
                if ($costResult) {
                    $costPrice = $costResult['cost_price'] ? ($costResult['cost_price'] * $item['quantity']) : null;
                    
                    // Reduce stock quantity (already validated above, only if tracking exists)
                    if ($hasStockTracking && $costResult['stock_quantity'] !== null) {
                        $updateStockStmt = $db->prepare("
                            UPDATE sales_products 
                            SET stock_quantity = stock_quantity - ? 
                            WHERE id = ?
                        ");
                        $updateStockStmt->execute([$item['quantity'], $item['product_id']]);
                    }
                }
            } catch (PDOException $e) {
                // If stock_quantity column doesn't exist, just get cost_price
                if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), 'Column not found') !== false) {
                    $costStmt = $db->prepare("SELECT cost_price FROM sales_products WHERE id = ?");
                    $costStmt->execute([$item['product_id']]);
                    $costResult = $costStmt->fetch(PDO::FETCH_ASSOC);
                    if ($costResult) {
                        $costPrice = $costResult['cost_price'] ? ($costResult['cost_price'] * $item['quantity']) : null;
                    }
                } else {
                    throw $e;
                }
            }
        }
        
        // Log to unified_ledger
        $metadata = [
            'product_id' => $item['product_id'],
            'unit_price' => $item['price'],
            'notes' => $notes
        ];
        
        // Add event_id to metadata if sale is linked to an event
        if ($event_id) {
            $metadata['event_id'] = $event_id;
        }
        
        // Set payment_status based on payment_method
        // "on_arrival" (Oppmøte) means payment will be confirmed later at check-in
        $payment_status = ($payment_method === 'on_arrival') ? 'pending' : 'completed';
        
        $ledgerResult = $ledgerService->logTransaction([
            'order_id' => $orderId,
            'module_source' => 'sales',
            'item_type' => 'product',
            'source_id' => $itemTransactionId,
            'item_name' => $item['product_name'],
            'quantity' => $item['quantity'],
            'selling_price' => $itemTotal,
            'cost_price' => $costPrice,
            'customer_name' => $customer_name ?: 'Walk-in',
            'customer_email' => $customer_email ?: null,
            'payment_method' => $payment_method,
            'payment_status' => $payment_status,
            'status' => 'active',
            'transaction_date' => $currentTimestamp,
            'category' => 'Salg',
            'transaction_type' => $payment_status === 'completed' ? 'income' : null,
            'metadata' => $metadata
        ]);
        
        if (!$ledgerResult['success']) {
            $db->rollBack();
            $stmt = $db->prepare("DELETE FROM sales_cart WHERE admin_id = ? AND company_id = ?");
            $stmt->execute([$admin_id, $company_id]);
            error_log("Failed to log transaction: " . $ledgerResult['message']);
            echo json_encode(['success' => false, 'message' => 'Feil ved logging']);
            exit;
        }
    }
    
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
