<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$objectId = $_GET['object_id'] ?? null;

if (!$objectId) {
    echo json_encode(['success' => false, 'message' => 'Object ID is required']);
    exit;
}

try {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT 
            field_name,
            is_required
        FROM rental_object_fields
        WHERE object_id = :object_id
        ORDER BY field_name
    ");
    
    $stmt->execute([':object_id' => $objectId]);
    $fields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'fields' => $fields
    ]);
    
} catch (PDOException $e) {
    error_log("Database error in get_rental_object_fields.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
