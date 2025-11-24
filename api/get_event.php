<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Mangler arrangement ID']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    $event_id = $_GET['id'];
    
    $stmt = $db->prepare("
        SELECT 
            e.id, 
            e.title, 
            e.description,
            e.ticket_price, 
            e.member_ticket_price,
            e.is_member_exclusive,
            e.start_date,
            e.start_time,
            e.end_date,
            e.end_time,
            e.age_limit,
            e.venue_name,
            e.event_city,
            e.event_postal,
            e.event_address,
            e.min_attendees,
            e.max_attendees,
            e.registration_type,
            e.registration_deadline,
            e.default_payment_method,
            e.payment_timing,
            e.payment_methods_json,
            e.event_image_url,
            e.cost_price,
            e.created_at,
            e.created_by,
            COALESCE(ta.name, 'Ukjent bruker') as created_by_name
        FROM events_eventdata e
        LEFT JOIN tenant_admins ta ON e.created_by = ta.id
        WHERE e.id = ? AND e.company_id = ?
        LIMIT 1
    ");
    $stmt->execute([$event_id, $company_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Also fetch membership prices if it's a member-exclusive event
    if ($event && $event['is_member_exclusive']) {
        try {
            $planStmt = $db->prepare("
                SELECT emp.id, mp.name as plan_name, emp.ticket_price, emp.public_display
                FROM event_membership_plans emp
                JOIN membership_plans mp ON emp.membership_plan_id = mp.id
                WHERE emp.event_id = ?
                ORDER BY mp.name
            ");
            $planStmt->execute([$event_id]);
            $event['membership_prices'] = $planStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Exception $e) {
            error_log("Warning: Failed to fetch membership prices for event {$event_id}: " . $e->getMessage());
            $event['membership_prices'] = [];
        }
    } else {
        $event['membership_prices'] = [];
    }
    
    if ($event) {
        echo json_encode([
            'success' => true,
            'event' => $event
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Arrangement ikke funnet'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error fetching event: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke hente arrangement'
    ]);
}
