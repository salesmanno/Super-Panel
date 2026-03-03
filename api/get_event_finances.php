<?php
session_start();
session_write_close();
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
    
    // Get all approved registrations for this event
    // Uses EXACT same logic as get_all_registrations.php for consistency:
    // - Membership: membership_members with active status
    // - Ticket price: COALESCE(emp.ticket_price, e.ticket_price) - same as "alle påmeldinger" display
    // This ensures counts AND totals match "alle påmeldinger" view exactly
    $registrationQuery = "
        SELECT 
            CASE WHEN mm.membership_plan_id IS NOT NULL THEN 'Medlem' ELSE 'Ikke-medlem' END as member_type,
            COALESCE(emp.ticket_price, e.ticket_price) AS ticket_price
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id
        INNER JOIN tenant_customers c ON r.customer_id = c.id AND c.company_id = r.company_id
        LEFT JOIN membership_members mm ON mm.customer_id = c.id AND mm.company_id = r.company_id AND mm.status = 'active'
        LEFT JOIN event_membership_plans emp ON emp.event_id = r.event_id AND emp.membership_plan_id = mm.membership_plan_id
        WHERE r.event_id = ? AND r.company_id = ? AND r.approval_status = 'approved'
    ";
    $registrationStmt = $db->prepare($registrationQuery);
    $registrationStmt->execute([$event_id, $company_id]);
    $registrations = $registrationStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build income groups - use membership_members to determine member vs non-member (same as "alle påmeldinger")
    $checkinIncome = [];
    $totalCheckinIncome = 0;
    
    // Aggregate by member type
    $memberTotals = ['Medlem' => ['count' => 0, 'total' => 0], 'Ikke-medlem' => ['count' => 0, 'total' => 0]];
    
    foreach ($registrations as $reg) {
        $label = $reg['member_type'];
        $price = floatval($reg['ticket_price']);
        
        // Only include paid registrations (price > 0)
        if ($price > 0) {
            $totalCheckinIncome += $price;
            $memberTotals[$label]['count']++;
            $memberTotals[$label]['total'] += $price;
        }
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
