<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

// Get event_id from query parameter
$eventId = $_GET['event_id'] ?? null;

if (!$eventId) {
    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Fetch registration fields for the event
    $stmt = $db->prepare("
        SELECT 
            field_name,
            is_required
        FROM event_registration_fields
        WHERE event_id = :event_id
        ORDER BY field_name
    ");
    
    $stmt->execute([':event_id' => $eventId]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_event_registration_fields.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
