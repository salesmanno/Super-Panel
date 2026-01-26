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
    
    // Get all entries from events_checkin for this event
    // Group by membership status (membership_plan_id IS NOT NULL = member)
    // Only include entries with ticket_price > 0 (paid registrations)
    // Note: events_checkin does not have company_id column - event_id is sufficient
    $checkinQuery = "
        SELECT 
            CASE WHEN c.membership_plan_id IS NOT NULL THEN 'Medlem' ELSE 'Ikke-medlem' END as member_type,
            c.ticket_price,
            COUNT(*) as count,
            SUM(c.ticket_price) as total_amount
        FROM events_checkin c
        WHERE c.event_id = ? AND c.ticket_price > 0
        GROUP BY member_type, c.ticket_price
        ORDER BY member_type DESC, c.ticket_price DESC
    ";
    $checkinStmt = $db->prepare($checkinQuery);
    $checkinStmt->execute([$event_id]);
    $checkinGroups = $checkinStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build income groups - use membership_plan_id to determine member vs non-member
    $checkinIncome = [];
    $totalCheckinIncome = 0;
    
    // Aggregate by member type
    $memberTotals = ['Medlem' => ['count' => 0, 'total' => 0], 'Ikke-medlem' => ['count' => 0, 'total' => 0]];
    
    foreach ($checkinGroups as $group) {
        $label = $group['member_type'];
        $count = intval($group['count']);
        $total = floatval($group['total_amount']);
        $totalCheckinIncome += $total;
        
        $memberTotals[$label]['count'] += $count;
        $memberTotals[$label]['total'] += $total;
    }
    
    // Build output array
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
    
    // Get expenses from unified_ledger
    $jsonEventCheck = "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.event_id')) = ?";
    
    $expenseQuery = "
        SELECT 
            item_name,
            selling_price
        FROM unified_ledger
        WHERE company_id = ? 
        AND status = 'active'
        AND selling_price < 0
        AND (
            $jsonEventCheck
            OR (module_source = 'events' AND source_id = ?)
        )
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
    
    $totalIncome = $totalCheckinIncome;
    
    echo json_encode([
        'success' => true,
        'event_title' => $eventTitle,
        'standard_price' => $standardPrice,
        'checkin_income' => $checkinIncome,
        'expense_groups' => $expenseGroups,
        'summary' => [
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
