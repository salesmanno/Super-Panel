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
    // Note: attendee_name, attendee_email, attendee_phone columns were dropped - use ticket_type and customer data instead
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.customer_id,
            c.ticket_type,
            c.ticket_price,
            c.payment_status,
            c.payment_method,
            c.checked_in,
            c.check_in_time,
            c.checked_in_by,
            c.barcode,
            c.membership_plan_id,
            c.notes,
            c.created_at,
            tc.first_name,
            tc.last_name,
            ta.first_name as admin_first_name,
            ta.last_name as admin_last_name
        FROM events_checkin c
        LEFT JOIN tenant_customers tc ON c.customer_id = tc.id
        LEFT JOIN tenant_admins ta ON c.checked_in_by = ta.id
        WHERE c.event_id = ?
        ORDER BY c.checked_in DESC, c.check_in_time DESC, c.created_at DESC
    ");
    
    $stmt->execute([$eventId]);
    $allCheckins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Split into checked_in and not checked_in
    $checkedIn = array_filter($allCheckins, fn($c) => $c['checked_in'] == 1);
    $notCheckedIn = array_filter($allCheckins, fn($c) => $c['checked_in'] == 0);
    
    // Calculate summary stats
    $totalAttendees = count($allCheckins);
    $totalRevenue = array_sum(array_column($allCheckins, 'ticket_price'));
    $paidCount = count(array_filter($allCheckins, fn($c) => $c['payment_status'] === 'paid'));
    $checkedInCount = count($checkedIn);
    
    echo json_encode([
        'success' => true,
        'checked_in' => array_values($checkedIn),
        'not_checked_in' => array_values($notCheckedIn),
        'summary' => [
            'total_attendees' => $totalAttendees,
            'checked_in_count' => $checkedInCount,
            'not_checked_in_count' => count($notCheckedIn),
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
