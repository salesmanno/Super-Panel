<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];
$filter = $_GET['filter'] ?? 'all';
$currentYear = date('Y');

try {
    $db = getDBConnection();
    
    // MYSQL ONLY - Join with tenant_customers for personal data
    $stmt = $db->prepare("
        SELECT 
            mm.id as member_id,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            c.address,
            c.address_city as poststed,
            c.date_of_birth as dateofbirth,
            mm.join_date,
            mm.expiration_date as expiry_date,
            mm.status as member_status,
            mm.auto_renew,
            COALESCE(mp.name, 'Ingen plan') as plan_name,
            COALESCE(mp.price, 0) as yearly_price,
            COALESCE(payment_summary.total_paid, 0) as total_paid,
            COALESCE(mp.price, 0) - COALESCE(payment_summary.total_paid, 0) as remaining,
            payment_summary.last_payment_date,
            payment_summary.payment_count
        FROM membership_members mm
        LEFT JOIN tenant_customers c ON mm.customer_id = c.id
        LEFT JOIN membership_plans mp 
            ON mm.membership_plan_id = mp.id
        LEFT JOIN (
            SELECT 
                customer_email,
                SUM(selling_price * quantity) as total_paid,
                MAX(transaction_date) as last_payment_date,
                COUNT(*) as payment_count
            FROM unified_ledger
            WHERE company_id = ?
            AND module_source = 'membership'
            AND payment_status = 'completed'
            AND YEAR(transaction_date) = ?
            GROUP BY customer_email
        ) payment_summary ON c.email = payment_summary.customer_email
        WHERE mm.company_id = ?
        ORDER BY c.last_name, c.first_name
    ");
    
    $stmt->execute([$company_id, $currentYear, $company_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $filteredMembers = [];
    foreach ($members as $member) {
        $yearlyPrice = floatval($member['yearly_price']);
        $totalPaid = floatval($member['total_paid']);
        $remaining = floatval($member['remaining']);
        
        $status = 'unpaid';
        if ($totalPaid >= $yearlyPrice && $yearlyPrice > 0) {
            $status = 'paid';
        } elseif ($totalPaid > 0) {
            $status = 'partial';
        }
        
        $member['payment_status'] = $status;
        $member['yearly_price'] = $yearlyPrice;
        $member['total_paid'] = $totalPaid;
        $member['remaining'] = $remaining;
        
        if ($filter === 'all' || $filter === $status) {
            $filteredMembers[] = $member;
        }
    }
    
    // Calculate summary totals from FULL dataset (not filtered) for consistent KPIs
    $totalPaid = array_sum(array_column($members, 'total_paid'));
    $totalRemaining = array_sum(array_column($members, 'remaining'));
    
    // Calculate expected annual income (all members x their yearly price)
    $expectedAnnualIncome = array_sum(array_column($members, 'yearly_price'));
    
    // Count only active members
    $activeMembers = array_filter($members, fn($m) => $m['member_status'] === 'active');
    $activeCount = count($activeMembers);
    
    $transactionCount = array_sum(array_column($members, 'payment_count'));
    
    echo json_encode([
        'success' => true,
        'members' => $filteredMembers,
        'summary' => [
            'total_paid' => $totalPaid,
            'total_remaining' => $totalRemaining,
            'expected_annual_income' => $expectedAnnualIncome,
            'active_count' => $activeCount,
            'transaction_count' => $transactionCount
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching membership income tracking: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av medlemsinntekter: ' . $e->getMessage()
    ]);
}
