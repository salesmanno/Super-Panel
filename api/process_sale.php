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
    
    if (!isset($data['product_id']) || !isset($data['quantity']) || !isset($data['price'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }
    
    $db = getDBConnection();
    $db->beginTransaction();
    
    try {
        // Generate sale ID
        $saleId = bin2hex(random_bytes(16));
        
        // Create sale record (you may need to create sales_transactions table)
        $stmt = $db->prepare("
            INSERT INTO sales_transactions (
                id,
                company_id,
                product_id,
                quantity,
                unit_price,
                total_amount,
                customer_name,
                payment_method,
                notes,
                created_by,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $totalAmount = $data['quantity'] * $data['price'];
        
        $stmt->execute([
            $saleId,
            $_SESSION['company_id'],
            $data['product_id'],
            $data['quantity'],
            $data['price'],
            $totalAmount,
            $data['customer_name'] ?? null,
            $data['payment_method'] ?? 'cash',
            $data['notes'] ?? null,
            $_SESSION['tenant_admin_id']
        ]);
        
        // Update stock if available
        $stmt = $db->prepare("
            UPDATE sales_items 
            SET stock_quantity = stock_quantity - ? 
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$data['quantity'], $data['product_id'], $_SESSION['company_id']]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Salg registrert',
            'sale_id' => $saleId,
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
