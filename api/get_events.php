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
            id,
            title,
            description,
            max_attendees,
            min_attendees,
            age_limit,
            is_member_exclusive,
            event_address,
            event_city,
            event_postal,
            venue_name,
            start_time,
            start_date,
            end_time,
            end_date,
            event_image_url,
            ticket_price,
            member_ticket_price
        FROM tenant_events
        WHERE company_id = ?
        ORDER BY start_date DESC, start_time DESC
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
        'message' => 'Feil ved henting av arrangementer'
    ]);
}
