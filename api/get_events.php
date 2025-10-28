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
            e.member_ticket_price,
            e.output_product_id,
            e.created_by,
            e.created_at,
            op.is_visible,
            op.is_public,
            ts.first_name,
            ts.last_name,
            COUNT(ec.id) as current_attendees
        FROM events_eventdata e
        LEFT JOIN output_products op ON e.output_product_id = op.id
        LEFT JOIN tenant_admins ta ON e.created_by = ta.id
        LEFT JOIN tenant_staff ts ON ta.tenant_staff_id = ts.id
        LEFT JOIN events_checkin ec ON e.id = ec.event_id
        WHERE e.company_id = ? AND e.is_active = TRUE
        GROUP BY e.id, e.title, e.description, e.max_attendees, e.min_attendees, e.age_limit, 
                 e.is_member_exclusive, e.event_address, e.event_city, e.event_postal, e.venue_name,
                 e.start_time, e.start_date, e.end_time, e.end_date, e.event_image_url, 
                 e.ticket_price, e.member_ticket_price, e.output_product_id, e.created_by, e.created_at,
                 op.is_visible, op.is_public, ts.first_name, ts.last_name
        ORDER BY e.start_date DESC, e.start_time DESC
    ");
    
    $stmt->execute([$_SESSION['company_id']]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'events' => $events
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching events: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database utilgjengelig',
        'events' => []
    ]);
}
