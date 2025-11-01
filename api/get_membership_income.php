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
    
    // Get total membership income
    $stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_transactions,
            COALESCE(SUM(selling_price * quantity), 0) as total_income,
            COALESCE(SUM(CASE WHEN EXTRACT(MONTH FROM transaction_date) = EXTRACT(MONTH FROM CURRENT_DATE) 
                         AND EXTRACT(YEAR FROM transaction_date) = EXTRACT(YEAR FROM CURRENT_DATE) 
                         THEN selling_price * quantity ELSE 0 END), 0) as month_income,
            COALESCE(SUM(CASE WHEN EXTRACT(YEAR FROM transaction_date) = EXTRACT(YEAR FROM CURRENT_DATE) 
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
    
    // Get recent membership transactions
    $stmt = $db->prepare("
        SELECT 
            order_id,
            item_name,
            item_type,
            customer_name,
            customer_email,
            quantity,
            selling_price,
            (selling_price * quantity) as total,
            payment_method,
            payment_status,
            transaction_date
        FROM unified_ledger
        WHERE company_id = ?
        AND module_source = 'membership'
        ORDER BY transaction_date DESC
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
