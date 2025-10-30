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
            ticket_price, 
            member_ticket_price,
            start_date,
            start_time
        FROM events_eventdata 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$event_id, $company_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
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
