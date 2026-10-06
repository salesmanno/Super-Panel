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

// Get event_id from query parameter
$eventId = $_GET['event_id'] ?? null;

if (!$eventId) {
    echo json_encode(['success' => false, 'message' => 'Event ID is required']);
    exit;
}

try {
    $db = getDBConnection();
    
    tenantAuditActor($db, (string)($_SESSION['company_id']??''));
    $fields = entityFormLoad($db, (string)$_SESSION['company_id'], 'event', (string)$eventId)['fields'];
    $fields = array_values(array_filter($fields, static fn($f) => $f['is_visible']));
    
    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);
    
} catch (Throwable $e) {
    error_log("Database error in get_event_registration_fields.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
