<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];

try {
    $db = getDBConnection();
    
    // Get total membership income (MYSQL ONLY)
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            COALESCE(SUM(selling_price * quantity), 0) as total_income,
            COALESCE(SUM(CASE WHEN MONTH(transaction_date) = MONTH(CURRENT_DATE) 
                         AND YEAR(transaction_date) = YEAR(CURRENT_DATE) 
                         THEN selling_price * quantity ELSE 0 END), 0) as month_income,
            COALESCE(SUM(CASE WHEN YEAR(transaction_date) = YEAR(CURRENT_DATE) 
                         THEN selling_price * quantity ELSE 0 END), 0) as year_income
        FROM unified_ledger
        WHERE company_id = ? 
        AND module_source = 'membership'
        AND payment_status IN ('completed', 'pending')
    ");
    $stmt->execute([$company_id]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get active members count
    $stmt = $db->prepare("
        SELECT COUNT(*) as active_members
        FROM membership_members
        WHERE company_id = ?
        AND status = 'active'
        AND (expiry_date IS NULL OR expiry_date >= CURRENT_DATE)
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
            (ul.selling_price * ul.quantity) as total,
            ul.payment_method,
            ul.payment_status,
            ul.transaction_date
        FROM unified_ledger ul
        LEFT JOIN tenant_customers c ON ul.customer_id = c.id
        WHERE ul.company_id = ?
        AND ul.module_source = 'membership'
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
            COALESCE(SUM(selling_price * quantity), 0) as plan_income
        FROM unified_ledger
        WHERE company_id = ?
        AND module_source = 'membership'
        AND payment_status IN ('completed', 'pending')
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
