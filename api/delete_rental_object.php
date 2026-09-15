<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$admin = [
    'tenant_admin_id' => $_SESSION['tenant_admin_id'],
    'company_id' => $_SESSION['company_id']
];

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
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$object_id, $admin['company_id']]);
    $object = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$object) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Objekt ikke funnet']);
        exit;
    }
    
    // Check if object has active bookings
    $checkStmt = $db->prepare("
        SELECT COUNT(*) as count FROM rental_schedule 
        WHERE rental_object_id = ? 
        AND status NOT IN ('completed', 'cancelled')
    ");
    $checkStmt->execute([$object_id]);
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
    $deleteStmt = $db->prepare("DELETE FROM rental_objects WHERE id = ? AND company_id = ?");
    $deleteStmt->execute([$object_id, $admin['company_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => "'{$object['object_name']}' er slettet"
    ]);
    
} catch (\Throwable $e) {
    error_log('delete_rental_object error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting av utleieobjekt'
    ]);
}
