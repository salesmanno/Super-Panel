<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['event_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
        exit;
    }

    $db = getDBConnection();
    
    // Delete only if the event belongs to the user's company
    $stmt = $db->prepare("
        DELETE FROM tenant_events 
        WHERE id = ? AND company_id = ?
    ");
    
    $stmt->execute([
        $data['event_id'],
        $_SESSION['company_id']
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Arrangement slettet'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kunne ikke slette arrangement'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error deleting event: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting'
    ]);
}
