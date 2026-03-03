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
    $eventId = $_GET['event_id'] ?? null;
    
    if (!$eventId) {
        echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
        exit;
    }
    
    $db = getDBConnection();
    
    // Verify event belongs to admin's company
    $stmt = $db->prepare("
        SELECT id FROM events_eventdata 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$eventId, $_SESSION['company_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    // Get registration fields for this event
    $stmt = $db->prepare("
        SELECT 
            field_name,
            field_label,
            field_type,
            is_required,
            is_visible,
            display_order
        FROM event_registration_fields
        WHERE event_id = ?
        ORDER BY display_order ASC
    ");
    $stmt->execute([$eventId]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching event fields: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av feltconfig'
    ]);
}
