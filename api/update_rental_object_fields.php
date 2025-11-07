<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);

$objectId = $input['object_id'] ?? null;
$fields = $input['fields'] ?? [];

if (!$objectId) {
    echo json_encode(['success' => false, 'message' => 'Object ID is required']);
    exit;
}

try {
    $db = getDBConnection();
    
    $db->beginTransaction();
    
    $stmt = $db->prepare("DELETE FROM rental_object_fields WHERE object_id = :object_id");
    $stmt->execute([':object_id' => $objectId]);
    
    if (!empty($fields)) {
        $insertStmt = $db->prepare("
            INSERT INTO rental_object_fields (object_id, field_name, is_required)
            VALUES (:object_id, :field_name, :is_required)
        ");
        
        foreach ($fields as $field) {
            $insertStmt->execute([
                ':object_id' => $objectId,
                ':field_name' => $field['field_name'],
                ':is_required' => $field['is_required'] ? 1 : 0
            ]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Felter oppdatert'
    ]);
    
} catch (PDOException $e) {
    $db->rollBack();
    error_log("Database error in update_rental_object_fields.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
