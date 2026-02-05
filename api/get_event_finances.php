<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    echo json_encode(['success' => false, 'message' => 'Event ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Get event info (title and standard price)
    $eventStmt = $db->prepare("
        SELECT title, ticket_price 
        FROM events_eventdata 
        WHERE id = ? AND company_id = ?
    ");
    $eventStmt->execute([$event_id, $company_id]);
    $eventInfo = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$eventInfo) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    $eventTitle = $eventInfo['title'];
    $standardPrice = floatval($eventInfo['ticket_price']);
    
    // Get paid ticket income from registrations (same as before)
    $registrationQuery = "
        SELECT 
            CASE WHEN mm.membership_plan_id IS NOT NULL THEN 'Medlem' ELSE 'Ikke-medlem' END as member_type,
            COALESCE(emp.ticket_price, e.ticket_price) AS ticket_price
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id
        INNER JOIN tenant_customers c ON r.customer_id = c.id AND c.company_id = r.company_id
        LEFT JOIN membership_members mm ON mm.customer_id = c.id AND mm.company_id = r.company_id AND mm.status = 'active'
        LEFT JOIN event_membership_plans emp ON emp.event_id = r.event_id AND emp.membership_plan_id = mm.membership_plan_id
        WHERE r.event_id = ? AND r.company_id = ? AND r.approval_status = 'approved' AND r.payment_status = 'paid'
    ";
    $registrationStmt = $db->prepare($registrationQuery);
    $registrationStmt->execute([$event_id, $company_id]);
    $registrations = $registrationStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build ticket income groups
    $checkinIncome = [];
    $totalCheckinIncome = 0;
    $memberTotals = ['Medlem' => ['count' => 0, 'total' => 0], 'Ikke-medlem' => ['count' => 0, 'total' => 0]];
    
    foreach ($registrations as $reg) {
        $label = $reg['member_type'];
        $price = floatval($reg['ticket_price']);
        
        if ($price > 0) {
            $totalCheckinIncome += $price;
            $memberTotals[$label]['count']++;
            $memberTotals[$label]['total'] += $price;
        }
    }
    
    foreach ($memberTotals as $label => $data) {
        if ($data['count'] > 0) {
            $checkinIncome[] = [
                'label' => $label,
                'count' => $data['count'],
                'price' => $data['count'] > 0 ? round($data['total'] / $data['count'], 2) : 0,
                'total' => $data['total']
            ];
        }
    }
    
    // Get all entries from events_expenses (new table with transaction_type and payment_status)
    // Join through events_eventdata for proper company_id scoping
    $expensesStmt = $db->prepare("
        SELECT 
            ee.id,
            ee.description,
            ee.amount,
            ee.transaction_type,
            ee.payment_status,
            ee.unified_ledger_id,
            ee.category,
            ee.expense_date,
            ee.created_at
        FROM events_expenses ee
        INNER JOIN events_eventdata e ON ee.event_id = e.id AND e.company_id = ?
        WHERE ee.event_id = ?
        ORDER BY ee.expense_date DESC, ee.created_at DESC
    ");
    $expensesStmt->execute([$company_id, $event_id]);
    $entries = $expensesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals from events_expenses
    $manual_income_paid = 0;
    $manual_income_pending = 0;
    $manual_expense_paid = 0;
    $manual_expense_pending = 0;
    
    $income_entries = [];
    $expense_entries = [];
    
    foreach ($entries as $entry) {
        $amount = floatval($entry['amount']);
        if ($entry['transaction_type'] === 'income') {
            if ($entry['payment_status'] === 'paid') {
                $manual_income_paid += $amount;
            } else {
                $manual_income_pending += $amount;
            }
            $income_entries[] = $entry;
        } else {
            if ($entry['payment_status'] === 'paid') {
                $manual_expense_paid += $amount;
            } else {
                $manual_expense_pending += $amount;
            }
            $expense_entries[] = $entry;
        }
    }
    
    // Group expenses by category for summary display
    $expense_groups = [];
    foreach ($expense_entries as $entry) {
        $key = $entry['category'] ?: 'other';
        if (!isset($expense_groups[$key])) {
            $expense_groups[$key] = ['count' => 0, 'total_paid' => 0, 'total_pending' => 0];
        }
        $expense_groups[$key]['count']++;
        if ($entry['payment_status'] === 'paid') {
            $expense_groups[$key]['total_paid'] += floatval($entry['amount']);
        } else {
            $expense_groups[$key]['total_pending'] += floatval($entry['amount']);
        }
    }
    
    // Calculate totals
    $total_income_paid = $totalCheckinIncome + $manual_income_paid;
    $total_income_pending = $manual_income_pending;
    $total_expense_paid = $manual_expense_paid;
    $total_expense_pending = $manual_expense_pending;
    
    echo json_encode([
        'success' => true,
        'event_title' => $eventTitle,
        'standard_price' => $standardPrice,
        'checkin_income' => $checkinIncome,
        'income_entries' => $income_entries,
        'expense_entries' => $expense_entries,
        'expense_groups' => $expense_groups,
        'summary' => [
            'ticket_income' => $totalCheckinIncome,
            'manual_income_paid' => $manual_income_paid,
            'manual_income_pending' => $manual_income_pending,
            'manual_expense_paid' => $manual_expense_paid,
            'manual_expense_pending' => $manual_expense_pending,
            'total_income_paid' => $total_income_paid,
            'total_income_pending' => $total_income_pending,
            'total_expense_paid' => $total_expense_paid,
            'total_expense_pending' => $total_expense_pending,
            'net_paid' => $total_income_paid - $total_expense_paid,
            'net_total' => ($total_income_paid + $total_income_pending) - ($total_expense_paid + $total_expense_pending)
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get event finances error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av arrangementsøkonomi: ' . $e->getMessage()
    ]);
}
