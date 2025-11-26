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
            id, 
            title, 
            description,
            ticket_price, 
            member_ticket_price,
            is_member_exclusive,
            start_date,
            start_time,
            end_date,
            end_time,
            age_limit,
            venue_name,
            event_city,
            event_postal,
            event_address,
            min_attendees,
            max_attendees,
            registration_type,
            registration_deadline,
            payment_timing,
            payment_methods_json,
            event_image_url,
            cost_price,
            created_at
        FROM events_eventdata 
        WHERE id = ? AND company_id = ?
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke hente arrangement: ' . $e->getMessage()
    ]);
}
