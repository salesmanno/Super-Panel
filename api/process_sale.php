<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['output_product_id']) || !isset($data['quantity'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }
    
    $db = getDBConnection();
    $db->beginTransaction();
    
    try {
        // Hent produkt info fra output_products
        $stmt = $db->prepare("SELECT product_type, product_id FROM output_products WHERE id = ?");
        $stmt->execute([$data['output_product_id']]);
        $outputProduct = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$outputProduct) {
            throw new Exception('Produkt ikke funnet');
        }
        
        // Hent produkt detaljer basert på type
        $productName = '';
        $unitPrice = 0;
        
        if ($outputProduct['product_type'] === 'event') {
            $stmt = $db->prepare("SELECT title as name, ticket_price as price FROM events_eventdata WHERE id = ?");
            $stmt->execute([$outputProduct['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            $productName = $product['name'] ?? '';
            $unitPrice = $product['price'] ?? 0;
        } elseif ($outputProduct['product_type'] === 'product') {
            $stmt = $db->prepare("SELECT name, selling_price as price FROM sales_products WHERE id = ?");
            $stmt->execute([$outputProduct['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            $productName = $product['name'] ?? '';
            $unitPrice = $product['price'] ?? 0;
        } elseif ($outputProduct['product_type'] === 'membership') {
            $stmt = $db->prepare("SELECT name, price FROM membership_plans WHERE id = ?");
            $stmt->execute([$outputProduct['product_id']]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            $productName = $product['name'] ?? '';
            $unitPrice = $product['price'] ?? 0;
        }
        
        $totalAmount = $data['quantity'] * $unitPrice;
        
        // Generer ordre-ID og ordrenummer
        $orderId = bin2hex(random_bytes(16));
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr($orderId, 0, 6));
        $orderDate = date('Y-m-d'); // Database-agnostic date
        $currentTimestamp = date('Y-m-d H:i:s'); // Database-agnostic timestamp
        
        // Opprett sales_order
        $stmt = $db->prepare("
            INSERT INTO sales_orders (
                id,
                company_id,
                order_number,
                order_date,
                status,
                payment_status,
                subtotal,
                total_amount,
                currency,
                notes,
                created_by,
                created_at
            ) VALUES (?, ?, ?, ?, 'completed', ?, ?, ?, 'NOK', ?, ?, ?)
        ");
        
        $paymentStatus = $data['payment_method'] === 'online' ? 'paid' : 'pending';
        
        $stmt->execute([
            $orderId,
            $_SESSION['company_id'],
            $orderNumber,
            $orderDate,
            $paymentStatus,
            $totalAmount,
            $totalAmount,
            $data['notes'] ?? null,
            $_SESSION['tenant_admin_id'],
            $currentTimestamp
        ]);
        
        // Opprett sales_orders_items
        $orderItemId = bin2hex(random_bytes(16));
        $stmt = $db->prepare("
            INSERT INTO sales_orders_items (
                id,
                order_id,
                product_id,
                product_name,
                quantity,
                unit_price,
                line_total,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $orderItemId,
            $orderId,
            $outputProduct['product_id'],
            $productName,
            $data['quantity'],
            $unitPrice,
            $totalAmount,
            $currentTimestamp
        ]);
        
        // Oppdater stock hvis det er et sales_product
        if ($outputProduct['product_type'] === 'product') {
            $stmt = $db->prepare("
                UPDATE sales_products_stock 
                SET quantity_in_stock = quantity_in_stock - ? 
                WHERE product_id = ?
            ");
            $stmt->execute([$data['quantity'], $outputProduct['product_id']]);
        }
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Salg registrert',
            'order_id' => $orderId,
            'order_number' => $orderNumber,
            'total_amount' => $totalAmount
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error processing sale: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved registrering av salg: ' . $e->getMessage()
    ]);
}
