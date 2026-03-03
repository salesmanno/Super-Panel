<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    // First: Get unique events with creator info
    $stmt = $db->prepare("
        SELECT 
            e.id,
            e.title,
            e.description,
            e.max_attendees,
            e.min_attendees,
            e.age_limit,
            e.is_member_exclusive,
            e.event_address,
            e.event_city,
            e.event_postal,
            e.venue_name,
            e.start_time,
            e.start_date,
            e.end_time,
            e.end_date,
            e.event_image_url,
            e.ticket_price,
            e.registration_type,
            e.output_product_id,
            e.created_by,
            e.created_at,
            e.cost_price,
            e.payment_methods_json,
            e.payment_timing,
            e.default_payment_method,
            COALESCE(ts.first_name, 'Ukjent') as first_name,
            COALESCE(ts.last_name, 'bruker') as last_name
        FROM events_eventdata e
        LEFT JOIN tenant_admins ta ON e.created_by = ta.id
        LEFT JOIN tenant_staff ts ON ta.tenant_staff_id = ts.id
        WHERE e.company_id = ? AND e.is_active = 1
        ORDER BY e.start_date DESC, e.start_time DESC
    ");
    
    $stmt->execute([$_SESSION['company_id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Then: Count attendees for each event separately (avoids LEFT JOIN duplicates)
    foreach ($events as &$event) {
        try {
            // Count approved attendees from events_checkin (these are ready for check-in)
            $countStmt = $db->prepare("
                SELECT COUNT(DISTINCT id) as attendee_count
                FROM events_checkin
                WHERE event_id = ?
            ");
            $countStmt->execute([$event['id']]);
            $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
            $event['current_attendees'] = intval($countResult['attendee_count'] ?? 0);
            
            // Count checked-in attendees
            $checkedInStmt = $db->prepare("
                SELECT COUNT(DISTINCT id) as checked_in_count
                FROM events_checkin
                WHERE event_id = ? AND checked_in = 1
            ");
            $checkedInStmt->execute([$event['id']]);
            $checkedInResult = $checkedInStmt->fetch(PDO::FETCH_ASSOC);
            $event['checked_in_count'] = intval($checkedInResult['checked_in_count'] ?? 0);
            
            // Count pending registrations (not yet approved)
            $pendingStmt = $db->prepare("
                SELECT COUNT(DISTINCT id) as pending_count
                FROM event_registrations
                WHERE event_id = ? AND approval_status = 'pending'
            ");
            $pendingStmt->execute([$event['id']]);
            $pendingResult = $pendingStmt->fetch(PDO::FETCH_ASSOC);
            $event['pending_registrations'] = intval($pendingResult['pending_count'] ?? 0);
        } catch (Exception $e) {
            error_log("Warning: Failed to count attendees for event {$event['id']}: " . $e->getMessage());
            $event['current_attendees'] = 0;
            $event['checked_in_count'] = 0;
            $event['pending_registrations'] = 0;
        }
    }
    
    // Fetch ALL membership prices for events (including private/non-public ones)
    foreach ($events as &$event) {
        $event['membership_prices'] = [];
        try {
            $priceStmt = $db->prepare("
                SELECT emp.ticket_price as price, mp.name as plan_name, mp.id as plan_id, emp.public_display
                FROM event_membership_plans emp
                JOIN membership_plans mp ON emp.membership_plan_id = mp.id
                WHERE emp.event_id = ?
                ORDER BY emp.ticket_price ASC
            ");
            $priceStmt->execute([$event['id']]);
            $results = $priceStmt->fetchAll(PDO::FETCH_ASSOC);
            $event['membership_prices'] = $results ?: [];
        } catch (Exception $e) {
            error_log("Warning: Failed to fetch membership prices for event {$event['id']}: " . $e->getMessage());
            $event['membership_prices'] = [];
        }
    }
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching events: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av arrangementer'
    ]);
}
