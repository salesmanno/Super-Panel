<?php
session_start();
session_write_close();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Kun GET tillatt']);
    exit;
}

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];

try {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT 
            id,
            order_id,
            module_source,
            item_type,
            source_id,
            item_name,
            quantity,
            selling_price,
            customer_name,
            customer_email,
            payment_method,
            transaction_date,
            category
        FROM unified_ledger 
        WHERE company_id = ? 
          AND payment_status = 'pending'
          AND status = 'active'
          AND selling_price > 0
        ORDER BY transaction_date DESC
    ");
    $stmt->execute([$company_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPending = 0;
    foreach ($transactions as $t) {
        $totalPending += floatval($t['selling_price']);
    }
    
    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'count' => count($transactions),
        'total_pending' => $totalPending
    ]);
    
} catch (Exception $e) {
    error_log("Get pending payments error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting: ' . $e->getMessage()
    ]);
}
