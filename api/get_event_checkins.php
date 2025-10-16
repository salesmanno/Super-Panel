<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $eventId = $_GET['event_id'] ?? null;
    
    if (empty($eventId)) {
        echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
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
            c.paid_at,
            c.checked_in,
            c.check_in_time,
            c.notes,
            tc.first_name,
            tc.last_name
        FROM events_checkin c
        LEFT JOIN tenant_customers tc ON c.customer_id = tc.id
        WHERE c.event_id = ?
        ORDER BY c.check_in_time DESC
    ");
    
    $stmt->execute([$eventId]);
    $checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate summary stats
    $totalAttendees = count($checkins);
    $totalRevenue = array_sum(array_column($checkins, 'ticket_price'));
    $paidCount = count(array_filter($checkins, fn($c) => $c['payment_status'] === 'paid'));
    
    echo json_encode([
        'success' => true,
        'checkins' => $checkins,
        'summary' => [
            'total_attendees' => $totalAttendees,
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
