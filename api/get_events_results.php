<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    $dbType = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    
    // Build JSON query based on database type
    if ($dbType === 'pgsql') {
        $jsonEventCheck = "metadata::jsonb->>'event_id'";
    } else {
        $jsonEventCheck = "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.event_id'))";
    }
    
    // Check if deleted_at column exists
    $hasDeletedAt = false;
    try {
        if ($dbType === 'mysql') {
            $stmt = $db->query("SHOW COLUMNS FROM events_eventdata LIKE 'deleted_at'");
            $hasDeletedAt = $stmt->rowCount() > 0;
        } else {
            $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'events_eventdata' AND column_name = 'deleted_at'");
            $hasDeletedAt = $stmt->rowCount() > 0;
        }
    } catch (Exception $e) {
        // Ignore error, assume no deleted_at
    }
    
    $deletedFilter = $hasDeletedAt ? " AND e.deleted_at IS NULL" : "";
    
    // Get total number of events
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM events_eventdata e
        WHERE e.company_id = ?" . $deletedFilter . "
    ");
    $stmt->execute([$company_id]);
    $totalEvents = $stmt->fetchColumn();
    
    // Get total revenue from unified_ledger
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(selling_price), 0) as revenue
        FROM unified_ledger
        WHERE company_id = ?
        AND status = 'active'
        AND selling_price > 0
        AND (module_source = 'events' OR $jsonEventCheck IS NOT NULL)
    ");
    $stmt->execute([$company_id]);
    $totalRevenue = $stmt->fetchColumn();
    
    // Get total attendees from events_checkin (if exists)
    $totalAttendees = 0;
    try {
        $stmt = $db->prepare("
            SELECT COUNT(*) as total
            FROM events_checkin ec
            INNER JOIN events_eventdata e ON ec.event_id = e.id
            WHERE e.company_id = ?" . $deletedFilter . "
        ");
        $stmt->execute([$company_id]);
        $totalAttendees = $stmt->fetchColumn();
    } catch (Exception $e) {
        // events_checkin might not exist
    }
    
    // Get upcoming events
    $nowComparison = $dbType === 'pgsql' 
        ? "(e.start_date || ' ' || e.start_time)::timestamp > NOW()"
        : "CONCAT(e.start_date, ' ', e.start_time) > NOW()";
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as upcoming
        FROM events_eventdata e
        WHERE e.company_id = ?" . $deletedFilter . "
        AND " . $nowComparison . "
    ");
    $stmt->execute([$company_id]);
    $upcomingEvents = $stmt->fetchColumn();
    
    // Get detailed list of events with financial data from unified_ledger
    $statusCase = $dbType === 'pgsql' 
        ? "CASE 
            WHEN (e.start_date || ' ' || e.start_time)::timestamp > NOW() THEN 'Kommende'
            WHEN (e.start_date || ' ' || e.start_time)::timestamp <= NOW() AND 
                 (e.end_date IS NULL OR (e.end_date || ' ' || COALESCE(e.end_time, '23:59:59'))::timestamp >= NOW()) THEN 'Pågår'
            ELSE 'Avsluttet'
        END"
        : "CASE 
            WHEN CONCAT(e.start_date, ' ', e.start_time) > NOW() THEN 'Kommende'
            WHEN CONCAT(e.start_date, ' ', e.start_time) <= NOW() AND 
                 (e.end_date IS NULL OR CONCAT(e.end_date, ' ', COALESCE(e.end_time, '23:59:59')) >= NOW()) THEN 'Pågår'
            ELSE 'Avsluttet'
        END";
    
    // First get all events
    $stmt = $db->prepare("
        SELECT 
            e.id,
            e.title,
            e.start_date,
            e.start_time,
            " . $statusCase . " as status
        FROM events_eventdata e
        WHERE e.company_id = ?" . $deletedFilter . "
        ORDER BY e.start_date DESC, e.start_time DESC
    ");
    $stmt->execute([$company_id]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // For each event, get financial data from unified_ledger
    $eventsList = [];
    foreach ($events as $event) {
        // Get income for this event
        $incomeQuery = "
            SELECT COALESCE(SUM(selling_price), 0) as income
            FROM unified_ledger
            WHERE company_id = ?
            AND status = 'active'
            AND selling_price > 0
            AND (
                $jsonEventCheck = ?
                OR (module_source = 'events' AND source_id = ?)
            )
        ";
        $incomeStmt = $db->prepare($incomeQuery);
        $incomeStmt->execute([$company_id, $event['id'], $event['id']]);
        $income = $incomeStmt->fetchColumn();
        
        // Get expenses for this event
        $expenseQuery = "
            SELECT COALESCE(SUM(ABS(selling_price)), 0) as expense
            FROM unified_ledger
            WHERE company_id = ?
            AND status = 'active'
            AND selling_price < 0
            AND $jsonEventCheck = ?
        ";
        $expenseStmt = $db->prepare($expenseQuery);
        $expenseStmt->execute([$company_id, $event['id']]);
        $expense = $expenseStmt->fetchColumn();
        
        // Get attendee count (if events_checkin exists)
        $attendeeCount = 0;
        try {
            $attendeeStmt = $db->prepare("
                SELECT COUNT(*) FROM events_checkin WHERE event_id = ?
            ");
            $attendeeStmt->execute([$event['id']]);
            $attendeeCount = $attendeeStmt->fetchColumn();
        } catch (Exception $e) {
            // events_checkin might not exist
        }
        
        // Calculate net result
        $netResult = $income - $expense;
        
        $eventsList[] = [
            'id' => $event['id'],
            'title' => $event['title'],
            'start_date' => $event['start_date'],
            'start_time' => $event['start_time'],
            'attendee_count' => $attendeeCount,
            'income' => $income,
            'expense' => $expense,
            'net_result' => $netResult,
            'status' => $event['status']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'summary' => [
            'total_events' => $totalEvents,
            'total_revenue' => number_format($totalRevenue, 2, '.', ''),
            'total_attendees' => $totalAttendees,
            'upcoming_events' => $upcomingEvents
        ],
        'events' => $eventsList
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching events results: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke hente arrangement resultat: ' . $e->getMessage()
    ]);
}
