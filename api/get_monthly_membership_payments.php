<?php
session_start();
session_write_close();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];
$year = intval($_GET['year'] ?? date('Y'));
$month = isset($_GET['month']) ? intval($_GET['month']) : null;

try {
    $db = getDBConnection();
    
    // Get all active members with their membership plans
    $stmt = $db->prepare("
        SELECT 
            mm.id as member_id,
            mm.customer_id,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            mm.join_date,
            mm.expiration_date as expiry_date,
            mm.status as member_status,
            COALESCE(mp.name, 'Ukjent plan') as plan_name,
            COALESCE(mp.price, 0) as yearly_price,
            mp.billing_cycle
        FROM membership_members mm
        LEFT JOIN tenant_customers c ON mm.customer_id = c.id AND c.company_id = mm.company_id
        LEFT JOIN membership_plans mp ON mm.membership_plan_id = mp.id AND mp.company_id = mm.company_id
        WHERE mm.company_id = ?
        AND (mm.status = 'active' OR mm.status = 'pending')
        ORDER BY c.last_name, c.first_name
    ");
    
    $stmt->execute([$company_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Match stable customer IDs, never shared/empty e-mail addresses.
    $paymentStmt = $db->prepare("
        SELECT 
            ul.customer_id,
            SUM(ul.selling_price) as total_paid,
            MONTH(ul.transaction_date) as payment_month
        FROM unified_ledger ul
        WHERE ul.company_id = ?
        AND ul.module_source = 'membership'
        AND ul.payment_status = 'completed'
        AND ul.status = 'active'
        AND YEAR(ul.transaction_date) = ?
        GROUP BY ul.customer_id, MONTH(ul.transaction_date)
    ");
    
    $paymentStmt->execute([$company_id, $year]);
    $payments = $paymentStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create payment lookup by customer and month.
    $paymentLookup = [];
    foreach ($payments as $p) {
        $key = (string)($p['customer_id'] ?? '') . '_' . $p['payment_month'];
        $paymentLookup[$key] = floatval($p['total_paid']);
    }
    
    // Calculate monthly expected amounts based on billing cycle
    $monthlyData = [];
    for ($m = 1; $m <= 12; $m++) {
        $monthlyData[$m] = [
            'expected_amount' => 0,
            'paid_amount' => 0,
            'remaining_amount' => 0,
            'member_count' => 0,
            'paid_count' => 0,
            'unpaid_count' => 0,
            'members' => []
        ];
    }
    
    foreach ($members as $member) {
        $joinDate = $member['join_date'] ? new DateTime($member['join_date']) : null;
        $expiryDate = $member['expiry_date'] ? new DateTime($member['expiry_date']) : null;
        $yearlyPrice = floatval($member['yearly_price']);
        $billingCycle = $member['billing_cycle'] ?? 'yearly';
        
        // Calculate payment schedule based on billing cycle
        $paymentMonths = [];
        $amountPerPayment = $yearlyPrice;
        
        if ($billingCycle === 'monthly') {
            $amountPerPayment = $yearlyPrice / 12;
            for ($m = 1; $m <= 12; $m++) {
                $paymentMonths[] = $m;
            }
        } elseif ($billingCycle === 'quarterly') {
            $amountPerPayment = $yearlyPrice / 4;
            $paymentMonths = [1, 4, 7, 10]; // Q1, Q2, Q3, Q4
        } else {
            // Yearly - use join month or January
            $joinMonth = $joinDate ? intval($joinDate->format('n')) : 1;
            $paymentMonths = [$joinMonth];
        }
        
        foreach ($paymentMonths as $paymentMonth) {
            // Check if member was active during this month
            $monthStart = new DateTime("$year-$paymentMonth-01");
            $monthEnd = clone $monthStart;
            $monthEnd->modify('last day of this month');
            
            // Skip if member joined after this month or expired before
            if ($joinDate && $joinDate > $monthEnd) continue;
            if ($expiryDate && $expiryDate < $monthStart) continue;
            
            $monthlyData[$paymentMonth]['member_count']++;
            $monthlyData[$paymentMonth]['expected_amount'] += $amountPerPayment;
            
            // Check if paid
            $paymentKey = (string)$member['customer_id'] . '_' . $paymentMonth;
            $paidAmount = $paymentLookup[$paymentKey] ?? 0;
            
            $isPaid = $paidAmount >= $amountPerPayment * 0.9; // 90% threshold
            
            $monthlyData[$paymentMonth]['paid_amount'] += $paidAmount;
            
            if ($isPaid) {
                $monthlyData[$paymentMonth]['paid_count']++;
            } else {
                $monthlyData[$paymentMonth]['unpaid_count']++;
            }
            
            // Store member data for detail view
            $monthlyData[$paymentMonth]['members'][] = [
                'member_id' => $member['member_id'],
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
                'email' => $member['email'],
                'phone' => $member['phone'],
                'plan_name' => $member['plan_name'],
                'amount' => $amountPerPayment,
                'is_paid' => $isPaid,
                'paid_amount' => $paidAmount,
                'due_date' => $monthStart->format('Y-m-d')
            ];
        }
    }
    
    // Calculate remaining amounts
    foreach ($monthlyData as $m => &$data) {
        $data['remaining_amount'] = max(0, $data['expected_amount'] - $data['paid_amount']);
    }
    
    // If specific month requested, return member details
    if ($month !== null && $month >= 1 && $month <= 12) {
        $monthData = $monthlyData[$month];
        echo json_encode([
            'success' => true,
            'month' => $month,
            'year' => $year,
            'members' => $monthData['members'],
            'summary' => [
                'expected' => $monthData['expected_amount'],
                'paid' => $monthData['paid_amount'],
                'remaining' => $monthData['remaining_amount'],
                'member_count' => $monthData['member_count'],
                'paid_count' => $monthData['paid_count'],
                'unpaid_count' => $monthData['unpaid_count']
            ]
        ]);
    } else {
        // Return all months overview
        $monthsOverview = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthsOverview[] = [
                'month' => $m,
                'expected_amount' => $monthlyData[$m]['expected_amount'],
                'paid_amount' => $monthlyData[$m]['paid_amount'],
                'remaining_amount' => $monthlyData[$m]['remaining_amount'],
                'member_count' => $monthlyData[$m]['member_count'],
                'paid_count' => $monthlyData[$m]['paid_count'],
                'unpaid_count' => $monthlyData[$m]['unpaid_count']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'year' => $year,
            'months' => $monthsOverview
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error fetching monthly membership payments: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av månedlig betalingsoversikt: ' . $e->getMessage()
    ]);
}
