<?php
session_start();
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

// Autentiser bruker
$admin = authenticateTenantAdmin();
if (!$admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

// Hent object ID
$data = json_decode(file_get_contents('php://input'), true);
$object_id = $data['id'] ?? null;

if (!$object_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Objekt-ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Verify object exists and belongs to company
    $stmt = $db->prepare("
        SELECT id, object_name FROM rental_objects 
        WHERE id = :id AND company_id = :company_id
    ");
    $stmt->execute([':id' => $object_id, ':company_id' => $admin['company_id']]);
    $object = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$object) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Objekt ikke funnet']);
        exit;
    }
    
    // Check if object has active bookings
    $checkStmt = $db->prepare("
        SELECT COUNT(*) as count FROM rental_schedule 
        WHERE rental_object_id = :object_id 
        AND status NOT IN ('completed', 'cancelled')
    ");
    $checkStmt->execute([':object_id' => $object_id]);
    $result = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Kan ikke slette objekt med aktive bookinger. Avbryt eller fullfør bookingene først.'
        ]);
        exit;
    }
    
    // Delete object (CASCADE will delete from type-specific table and rental_schedule)
    $deleteStmt = $db->prepare("DELETE FROM rental_objects WHERE id = :id AND company_id = :company_id");
    $deleteStmt->execute([':id' => $object_id, ':company_id' => $admin['company_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => "'{$object['object_name']}' er slettet"
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting av utleieobjekt',
        'error' => $e->getMessage()
    ]);
}
