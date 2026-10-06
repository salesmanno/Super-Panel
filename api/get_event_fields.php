<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/entity_form_fields.php';

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
    
    tenantAuditActor($db, (string)$_SESSION['company_id']);
    $fields = entityFormLoad($db, (string)$_SESSION['company_id'], 'event', (string)$eventId)['fields'];
    $fields = array_map(static fn($f) => $f + ['display_order'=>$f['sort_order']], $fields);
    
    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);
    
} catch (Throwable $e) {
    error_log("Error fetching event fields: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av feltconfig'
    ]);
}
