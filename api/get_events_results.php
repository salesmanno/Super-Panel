<?php
session_start();
session_write_close();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Check if events_results table exists (MySQL production data)
    $hasEventsResults = false;
    try {
        $stmt = $db->query("SHOW TABLES LIKE 'events_results'");
        $hasEventsResults = $stmt->rowCount() > 0;
    } catch (Exception $e) {
        // Table doesn't exist
    }
    
    if ($hasEventsResults) {
        // USE EXISTING events_results TABLE (MySQL production data)
        
        // Get summary data
        $stmt = $db->prepare("
            SELECT 
                COUNT(*) as total_events,
                COALESCE(SUM(total_revenue), 0) as total_revenue,
                COALESCE(SUM(total_checkins), 0) as total_attendees,
                COALESCE(SUM(CASE WHEN date_start > NOW() THEN 1 ELSE 0 END), 0) as upcoming_events
            FROM events_results
            WHERE company_id = ?
        ");
        $stmt->execute([$company_id]);
        $summary = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get detailed event list with results
        $stmt = $db->prepare("
            SELECT 
                er.event_id as id,
                e.title,
                er.date_start as start_date,
                TIME(er.date_start) as start_time,
                er.total_checkins as attendee_count,
                er.total_revenue as income,
                er.total_costs as expense,
                er.profit_loss as net_result,
                CASE 
                    WHEN er.date_start > NOW() THEN 'Kommende'
                    WHEN er.date_start <= NOW() AND (er.date_end IS NULL OR er.date_end >= NOW()) THEN 'Pågår'
                    ELSE 'Avsluttet'
                END as status
            FROM events_results er
            LEFT JOIN events_eventdata e ON er.event_id = e.id
            WHERE er.company_id = ?
            ORDER BY er.date_start DESC
        ");
        $stmt->execute([$company_id]);
        $eventsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } else {
        // Fallback: Build from events_eventdata and unified_ledger
        
        // Get total number of events (only active events - deleted_at column doesn't exist)
        $stmt = $db->prepare("
            SELECT COUNT(*) as total 
            FROM events_eventdata e
            WHERE e.company_id = ? AND e.is_active = 1
        ");
        $stmt->execute([$company_id]);
        $totalEvents = $stmt->fetchColumn();
        
        // Build JSON query for MySQL
        $jsonEventCheck = "JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.event_id'))";
        
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
        
        // Get total attendees
        $totalAttendees = 0;
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) as total
                FROM events_checkin ec
                INNER JOIN events_eventdata e ON ec.event_id = e.id
                WHERE e.company_id = ? AND e.is_active = 1
            ");
            $stmt->execute([$company_id]);
            $totalAttendees = $stmt->fetchColumn();
        } catch (Exception $e) {
            // events_checkin might not exist
        }
        
        // Get upcoming events
        $stmt = $db->prepare("
            SELECT COUNT(*) as upcoming
            FROM events_eventdata e
            WHERE e.company_id = ? AND e.is_active = 1
            AND CONCAT(e.start_date, ' ', e.start_time) > NOW()
        ");
        $stmt->execute([$company_id]);
        $upcomingEvents = $stmt->fetchColumn();
        
        // Get most profitable event (from events_results profit_loss)
        $mostProfitable = ['title' => 'N/A', 'profit' => 0];
        $stmt = $db->prepare("
            SELECT e.id, e.title, COALESCE(er.profit_loss, 0) as profit
            FROM events_eventdata e
            LEFT JOIN events_results er ON e.id = er.event_id AND e.company_id = er.company_id
            WHERE e.company_id = ? AND e.is_active = 1
            ORDER BY profit DESC
            LIMIT 1
        ");
        $stmt->execute([$company_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $mostProfitable = ['title' => $row['title'], 'profit' => round($row['profit'], 2)];
        }
        
        // Get most expensive event (highest ticket price)
        $mostExpensive = ['title' => 'N/A', 'price' => 0];
        $stmt = $db->prepare("
            SELECT id, title, ticket_price
            FROM events_eventdata
            WHERE company_id = ? AND is_active = 1
            ORDER BY ticket_price DESC
            LIMIT 1
        ");
        $stmt->execute([$company_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $mostExpensive = ['title' => $row['title'], 'price' => $row['ticket_price']];
        }
        
        // Get most attended event
        $mostAttended = ['title' => 'N/A', 'attendees' => 0];
        $stmt = $db->prepare("
            SELECT e.id, e.title, COUNT(ec.id) as attendee_count
            FROM events_eventdata e
            LEFT JOIN events_checkin ec ON e.id = ec.event_id
            WHERE e.company_id = ? AND e.is_active = 1
            GROUP BY e.id
            ORDER BY attendee_count DESC
            LIMIT 1
        ");
        $stmt->execute([$company_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $mostAttended = ['title' => $row['title'], 'attendees' => $row['attendee_count']];
        }
        
        $summary = [
            'total_events' => $totalEvents,
            'total_revenue' => $totalRevenue,
            'total_attendees' => $totalAttendees,
            'upcoming_events' => $upcomingEvents,
            'most_profitable' => $mostProfitable,
            'most_expensive' => $mostExpensive,
            'most_attended' => $mostAttended
        ];
        
        // Get detailed list of events
        $statusCase = "CASE 
            WHEN CONCAT(e.start_date, ' ', e.start_time) > NOW() THEN 'Kommende'
            WHEN CONCAT(e.start_date, ' ', e.start_time) <= NOW() AND 
                 (e.end_date IS NULL OR CONCAT(e.end_date, ' ', COALESCE(e.end_time, '23:59:59')) >= NOW()) THEN 'Pågår'
            ELSE 'Avsluttet'
        END";
        
        $stmt = $db->prepare("
            SELECT 
                e.id,
                e.title,
                e.start_date,
                e.start_time,
                " . $statusCase . " as status
            FROM events_eventdata e
            WHERE e.company_id = ? AND e.is_active = 1
            ORDER BY e.start_date DESC, e.start_time DESC
        ");
        $stmt->execute([$company_id]);
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // For each event, get financial data
        $eventsList = [];
        foreach ($events as $event) {
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
            
            $expenseQuery = "
                SELECT COALESCE(SUM(ABS(selling_price)), 0) as expense
                FROM unified_ledger
                WHERE company_id = ?
                AND status = 'active'
                AND selling_price < 0
                AND (
                    $jsonEventCheck = ?
                    OR (module_source = 'events' AND source_id = ?)
                )
            ";
            $expenseStmt = $db->prepare($expenseQuery);
            $expenseStmt->execute([$company_id, $event['id'], $event['id']]);
            $expense = $expenseStmt->fetchColumn();
            
            $attendeeCount = 0;
            try {
                $attendeeStmt = $db->prepare("SELECT COUNT(*) FROM events_checkin WHERE event_id = ?");
                $attendeeStmt->execute([$event['id']]);
                $attendeeCount = $attendeeStmt->fetchColumn();
            } catch (Exception $e) {
                // events_checkin might not exist
            }
            
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
    }
    
    echo json_encode([
        'success' => true,
        'summary' => [
            'total_events' => $summary['total_events'],
            'total_revenue' => number_format($summary['total_revenue'], 2, '.', ''),
            'total_attendees' => $summary['total_attendees'],
            'upcoming_events' => $summary['upcoming_events'],
            'most_profitable' => $summary['most_profitable'] ?? ['title' => 'N/A', 'profit' => 0],
            'most_expensive' => $summary['most_expensive'] ?? ['title' => 'N/A', 'price' => 0],
            'most_attended' => $summary['most_attended'] ?? ['title' => 'N/A', 'attendees' => 0]
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
