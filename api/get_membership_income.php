<?php
session_start();
session_write_close();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ledger_reporting.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];

try {
    $db = getDBConnection();
    $paidSql = ledger_reporting_paid_sql();
    $netSql = '(' . ledger_reporting_income_sql() . ') - (' . ledger_reporting_expense_sql() . ')';
    
    // Get total membership income (MYSQL ONLY)
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            COALESCE(SUM({$netSql}), 0) as total_income,
            COALESCE(SUM(CASE WHEN MONTH(transaction_date) = MONTH(CURRENT_DATE) 
                         AND YEAR(transaction_date) = YEAR(CURRENT_DATE) 
                         THEN {$netSql} ELSE 0 END), 0) as month_income,
            COALESCE(SUM(CASE WHEN YEAR(transaction_date) = YEAR(CURRENT_DATE) 
                         THEN {$netSql} ELSE 0 END), 0) as year_income
        FROM unified_ledger
        WHERE company_id = ? 
        AND module_source = 'membership'
        AND {$paidSql}
    ");
    $stmt->execute([$company_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get active members count
    $stmt = $db->prepare("
        SELECT COUNT(*) as active_members
        FROM membership_members
        WHERE company_id = ?
        AND status = 'active'
        AND (expiration_date IS NULL OR expiration_date >= CURRENT_DATE)
    ");
    $stmt->execute([$company_id]);
    $memberCount = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get recent membership transactions with customer info from tenant_customers
    $stmt = $db->prepare("
        SELECT 
            ul.order_id,
            ul.item_name,
            ul.item_type,
            CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, '')) as customer_name,
            c.email as email,
            ul.quantity,
            ul.selling_price,
            ul.selling_price as total,
            ul.payment_method,
            ul.payment_status,
            ul.transaction_date
        FROM unified_ledger ul
        LEFT JOIN tenant_customers c ON ul.customer_id = c.id AND c.company_id = ul.company_id
        WHERE ul.company_id = ?
        AND ul.module_source = 'membership'
        AND ul.status = 'active'
        AND ul.payment_status = 'completed'
        ORDER BY ul.transaction_date DESC
        LIMIT 50
    ");
    $stmt->execute([$company_id]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get income by membership plan
    $stmt = $db->prepare("
        SELECT 
            item_name,
            COUNT(*) as sales_count,
            COALESCE(SUM({$netSql}), 0) as plan_income
        FROM unified_ledger
        WHERE company_id = ?
        AND module_source = 'membership'
        AND {$paidSql}
        GROUP BY item_name
        ORDER BY plan_income DESC
    ");
    $stmt->execute([$company_id]);
    $incomeByPlan = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_income' => floatval($stats['total_income']),
            'month_income' => floatval($stats['month_income']),
            'year_income' => floatval($stats['year_income']),
            'total_transactions' => intval($stats['total_transactions']),
            'active_members' => intval($memberCount['active_members'])
        ],
        'transactions' => $transactions,
        'income_by_plan' => $incomeByPlan
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching membership income: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av medlemsinntekter: ' . $e->getMessage()
    ]);
}
