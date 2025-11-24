<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Databaseforbindelse mislyktes',
            'events' => []
        ]);
        exit;
    }
    
    $eventId = $_GET['event_id'] ?? null;
    
    // If no event_id, return list of all events for this company for check-in selector
    if (empty($eventId)) {
        try {
            $stmt = $db->prepare("
                SELECT 
                    id,
                    title,
                    start_date,
                    start_time,
                    ticket_price,
                    venue_name
                FROM events_eventdata 
                WHERE company_id = ? AND is_active = 1
                ORDER BY start_date DESC
            ");
            $stmt->execute([$_SESSION['company_id']]);
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching events list: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            echo json_encode([
                'success' => false,
                'message' => 'Feil ved henting av arrangementer: ' . $e->getMessage(),
                'events' => []
            ]);
            exit;
        }
        
        // Get check-in stats for each event
        $eventsWithStats = [];
        foreach ($events as $event) {
            $statsStmt = $db->prepare("
                SELECT 
                    COUNT(*) as total_attendees,
                    SUM(CASE WHEN checked_in = 1 THEN 1 ELSE 0 END) as checked_in_count,
                    SUM(CASE WHEN ticket_type IN ('member', 'membership') THEN 1 ELSE 0 END) as member_count,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_count,
                    SUM(CASE WHEN payment_status = 'paid' THEN ticket_price ELSE 0 END) as total_revenue
                FROM events_checkin
                WHERE event_id = ?
            ");
            $statsStmt->execute([$event['id']]);
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
            
            $event['total_attendees'] = $stats['total_attendees'] ?? 0;
            $event['checked_in'] = $stats['checked_in_count'] ?? 0;
            $event['member_count'] = $stats['member_count'] ?? 0;
            $event['paid_count'] = $stats['paid_count'] ?? 0;
            $event['total_revenue'] = $stats['total_revenue'] ?? 0;
            
            $eventsWithStats[] = $event;
        }
        
        echo json_encode([
            'success' => true,
            'events' => $eventsWithStats
        ]);
        exit;
    }

    $db = getDBConnection();
    
    // Verify event belongs to user's company
    $stmt = $db->prepare("
        SELECT id FROM events_eventdata 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$eventId, $_SESSION['company_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Event ikke funnet']);
        exit;
    }
    
    // Get all check-ins for the event
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.customer_id,
            c.attendee_name,
            c.attendee_email,
            c.attendee_phone,
            c.ticket_type,
            c.ticket_price,
            c.payment_status,
            c.payment_method,
            c.checked_in,
            c.check_in_time,
            c.notes,
            c.created_at,
            tc.first_name,
            tc.last_name
        FROM events_checkin c
        LEFT JOIN tenant_customers tc ON c.customer_id = tc.id
        WHERE c.event_id = ?
        ORDER BY c.created_at DESC, c.check_in_time DESC
    ");
    
    $stmt->execute([$eventId]);
    $checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary stats from events_checkin
    $totalAttendees = count($checkins);
    $checkedIn = count(array_filter($checkins, fn($c) => $c['checked_in'] === 1 || $c['checked_in'] === '1'));
    $noShows = $totalAttendees - $checkedIn;
    $totalRevenue = array_sum(array_column($checkins, 'ticket_price'));
    $paidCount = count(array_filter($checkins, fn($c) => $c['payment_status'] === 'paid'));
    $memberCount = count(array_filter($checkins, fn($c) => in_array($c['ticket_type'], ['member', 'membership'])));
    
    // Calculate attendance rate
    $attendanceRate = $totalAttendees > 0 ? round(($checkedIn * 100) / $totalAttendees, 2) : 0;
    
    echo json_encode([
        'success' => true,
        'checkins' => $checkins,
        'summary' => [
            'total_attendees' => $totalAttendees,
            'checked_in' => $checkedIn,
            'no_shows' => $noShows,
            'attendance_rate' => $attendanceRate,
            'member_count' => $memberCount,
            'total_revenue' => $totalRevenue,
            'paid_count' => $paidCount,
            'pending_count' => $totalAttendees - $paidCount
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching check-ins: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av check-ins'
    ]);
}
