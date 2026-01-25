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
    
    $eventTitle = $eventInfo['title'] ?? 'Ukjent arrangement';
    $standardPrice = floatval($eventInfo['ticket_price'] ?? 0);
    
    // Get check-in data directly from events_checkin, grouped by membership type
    $checkinQuery = "
        SELECT 
            c.membership_plan_id,
            mp.name as membership_name,
            c.ticket_price,
            COUNT(*) as count,
            SUM(c.ticket_price) as total_amount
        FROM events_checkin c
        LEFT JOIN membership_plans mp ON c.membership_plan_id = mp.id
        WHERE c.event_id = ? AND c.company_id = ?
        GROUP BY c.membership_plan_id, mp.name, c.ticket_price
        ORDER BY c.ticket_price DESC
    ";
    $checkinStmt = $db->prepare($checkinQuery);
    $checkinStmt->execute([$event_id, $company_id]);
    $checkinGroups = $checkinStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build income groups from check-ins
    $checkinIncome = [];
    $totalCheckinIncome = 0;
    
    foreach ($checkinGroups as $group) {
        $price = floatval($group['ticket_price']);
        $count = intval($group['count']);
        $total = floatval($group['total_amount']);
        $totalCheckinIncome += $total;
        
        // Determine label based on membership
        if ($group['membership_plan_id'] === null) {
            // No membership = non-member (standard price)
            $label = 'Ikke-medlem';
        } else {
            // Has membership = member
            $label = $group['membership_name'] ?: 'Medlem';
        }
        
        $checkinIncome[] = [
            'label' => $label,
            'count' => $count,
            'price' => $price,
            'total' => $total,
            'is_member' => $group['membership_plan_id'] !== null
        ];
    }
    
    // Build JSON query for other income/expenses from unified_ledger
    $jsonEventCheck = "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.event_id')) = ?";
    
    // Get OTHER income from unified_ledger (manual entries, not check-ins)
    $otherIncomeQuery = "
        SELECT 
            id,
            item_name,
            selling_price,
            payment_method,
            transaction_date
        FROM unified_ledger
        WHERE company_id = ? 
        AND status = 'active'
        AND selling_price > 0
        AND entry_type = 'income'
        AND (
            $jsonEventCheck
            OR (module_source = 'events' AND source_id = ?)
        )
        ORDER BY transaction_date DESC
    ";
    $otherIncomeStmt = $db->prepare($otherIncomeQuery);
    $otherIncomeStmt->execute([$company_id, $event_id, $event_id]);
    $otherIncome = $otherIncomeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group other income by item_name
    $otherIncomeGroups = [];
    $totalOtherIncome = 0;
    foreach ($otherIncome as $item) {
        $key = $item['item_name'] ?: 'Annet';
        if (!isset($otherIncomeGroups[$key])) {
            $otherIncomeGroups[$key] = ['count' => 0, 'total' => 0];
        }
        $otherIncomeGroups[$key]['count']++;
        $otherIncomeGroups[$key]['total'] += floatval($item['selling_price']);
        $totalOtherIncome += floatval($item['selling_price']);
    }
    
    // Get expenses from unified_ledger
    $expenseQuery = "
        SELECT 
            id,
            item_name,
            selling_price,
            payment_method,
            transaction_date
        FROM unified_ledger
        WHERE company_id = ? 
        AND status = 'active'
        AND selling_price < 0
        AND (
            $jsonEventCheck
            OR (module_source = 'events' AND source_id = ?)
        )
        ORDER BY transaction_date DESC
    ";
    $expenseStmt = $db->prepare($expenseQuery);
    $expenseStmt->execute([$company_id, $event_id, $event_id]);
    $expenses = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group expenses by item_name
    $expenseGroups = [];
    $totalExpense = 0;
    foreach ($expenses as $item) {
        $key = $item['item_name'] ?: 'Ukjent utgift';
        if (!isset($expenseGroups[$key])) {
            $expenseGroups[$key] = ['count' => 0, 'total' => 0];
        }
        $expenseGroups[$key]['count']++;
        $expenseGroups[$key]['total'] += abs(floatval($item['selling_price']));
        $totalExpense += abs(floatval($item['selling_price']));
    }
    
    $totalIncome = $totalCheckinIncome + $totalOtherIncome;
    
    echo json_encode([
        'success' => true,
        'event_title' => $eventTitle,
        'standard_price' => $standardPrice,
        'checkin_income' => $checkinIncome,
        'other_income' => $otherIncomeGroups,
        'expense_groups' => $expenseGroups,
        'summary' => [
            'total_checkin_income' => $totalCheckinIncome,
            'total_other_income' => $totalOtherIncome,
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_result' => $totalIncome - $totalExpense
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get event finances error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av arrangementsøkonomi: ' . $e->getMessage()
    ]);
}
