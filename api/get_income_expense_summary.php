<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Sammendrag per modul
    $stmt = $db->prepare("
        SELECT 
            module_source,
            source_id,
            SUM(CASE WHEN transaction_type = 'income' THEN amount ELSE 0 END) as total_income,
            SUM(CASE WHEN transaction_type = 'expense' THEN amount ELSE 0 END) as total_expense,
            COUNT(*) as transaction_count
        FROM global_income_expenses
        WHERE tenant_id = ?
        GROUP BY module_source, source_id
        ORDER BY module_source, source_id
    ");
    
    $stmt->execute([$company_id]);
    $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Samlet oversikt
    $total_stmt = $db->prepare("
        SELECT 
            transaction_type,
            COUNT(*) as count,
            SUM(amount) as total
        FROM global_income_expenses
        WHERE tenant_id = ?
        GROUP BY transaction_type
    ");
    
    $total_stmt->execute([$company_id]);
    $totals = $total_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_income = 0;
    $total_expense = 0;
    
    foreach ($totals as $total) {
        if ($total['transaction_type'] === 'income') {
            $total_income = (float)$total['total'];
        } else {
            $total_expense = (float)$total['total'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'by_source' => $summary,
        'totals' => [
            'income' => $total_income,
            'expense' => $total_expense,
            'net' => $total_income - $total_expense
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get income/expense summary error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
