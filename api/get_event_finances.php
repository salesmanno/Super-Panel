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
    // Group by ticket_price to determine member vs non-member
    $checkinQuery = "
        SELECT 
            c.ticket_price,
            COUNT(*) as count,
            SUM(c.ticket_price) as total_amount
        FROM events_checkin c
        WHERE c.event_id = ? AND c.company_id = ?
        GROUP BY c.ticket_price
        ORDER BY c.ticket_price DESC
    ";
    $checkinStmt = $db->prepare($checkinQuery);
    $checkinStmt->execute([$event_id, $company_id]);
    $checkinGroups = $checkinStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build income groups - compare price to standard price
    $checkinIncome = [];
    $totalCheckinIncome = 0;
    
    foreach ($checkinGroups as $group) {
        $price = floatval($group['ticket_price']);
        $count = intval($group['count']);
        $total = floatval($group['total_amount']);
        $totalCheckinIncome += $total;
        
        // If price matches standard price = non-member, otherwise = member
        if (abs($price - $standardPrice) < 0.01) {
            $label = 'Ikke-medlem';
        } else {
            $label = 'Medlem';
        }
        
        $checkinIncome[] = [
            'label' => $label,
            'count' => $count,
            'price' => $price,
            'total' => $total
        ];
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
