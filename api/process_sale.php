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
        
        if ($outputProduct['product_type'] === 'events') {
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
        $transactionId = bin2hex(random_bytes(16));
        $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr($transactionId, 0, 6));
        $currentTimestamp = date('Y-m-d H:i:s'); // Database-agnostic timestamp
        
        // CLEAN: Opprett sales_transaction (simple structure - no separate items table)
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
        
        $paymentStatus = $data['payment_method'] === 'cash' ? 'completed' : 'pending';
        
        $stmt->execute([
            $transactionId,
            $_SESSION['company_id'],
            $_SESSION['tenant_admin_id'],
            $orderNumber,
            $data['customer_name'] ?? 'Walk-in customer',
            $data['customer_email'] ?? null,
            $data['quantity'],
            $totalAmount,
            $data['payment_method'] ?? 'cash',
            $paymentStatus,
            false, // receipt_sent
            $data['notes'] ?? null,
            $currentTimestamp,
            $currentTimestamp
        ]);
        
        // NOTE: Stock management removed - sales_products has no quantity column
        // Stock tracking can be added separately via sales_products_stock table if needed
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Salg registrert',
            'transaction_id' => $transactionId,
            'order_id' => $orderNumber,
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
