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
    
    $module_source = $_GET['module_source'] ?? null;
    $source_id = $_GET['source_id'] ?? null;
    $transaction_type = $_GET['transaction_type'] ?? null;
    
    $sql = "
        SELECT 
            id, tenant_id, transaction_type, amount, description, category,
            unified_ledger_id, source_id, module_source, notes, created_by,
            created_at, updated_at
        FROM global_income_expenses
        WHERE tenant_id = ?
    ";
    
    $params = [$company_id];
    
    if ($module_source) {
        $sql .= " AND module_source = ?";
        $params[] = $module_source;
    }
    
    if ($source_id) {
        $sql .= " AND source_id = ?";
        $params[] = $source_id;
    }
    
    if ($transaction_type) {
        $sql .= " AND transaction_type = ?";
        $params[] = $transaction_type;
    }
    
    $sql .= " ORDER BY created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $total_income = 0;
    $total_expense = 0;
    
    foreach ($records as $record) {
        if ($record['transaction_type'] === 'income') {
            $total_income += (float)$record['amount'];
        } else {
            $total_expense += (float)$record['amount'];
        }
    }
    
    echo json_encode([
        'success' => true,
        'records' => $records,
        'summary' => [
            'total_income' => $total_income,
            'total_expense' => $total_expense,
            'net' => $total_income - $total_expense
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get global income/expenses error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
