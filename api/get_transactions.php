<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Get all transactions grouped by created_at (order)
    $stmt = $db->prepare("
        SELECT 
            MIN(st.id) as transaction_id,
            st.customer_name,
            st.customer_email,
            st.payment_method,
            st.created_at,
            SUM(st.total_price) as total_amount,
            GROUP_CONCAT(
                CONCAT(sp.name, ' (', st.quantity, 'x)')
                SEPARATOR ', '
            ) as products
        FROM sales_transactions st
        LEFT JOIN sales_products sp ON st.sales_product_id = sp.id
        WHERE st.company_id = ?
        GROUP BY st.customer_name, st.customer_email, st.payment_method, DATE_FORMAT(st.created_at, '%Y-%m-%d %H:%i:%s')
        ORDER BY st.created_at DESC
        LIMIT 100
    ");
    
    $stmt->execute([$company_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'transactions' => $transactions
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching transactions: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av transaksjoner'
    ]);
}
