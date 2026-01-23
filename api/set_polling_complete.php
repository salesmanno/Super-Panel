<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $input = json_decode(file_get_contents('php://input'), true);
    $pollId = $input['poll_id'] ?? null;
    $dismissed = $input['dismissed'] ?? false;
    
    if (!$pollId) {
        echo json_encode(['success' => false, 'message' => 'Mangler poll_id']);
        exit;
    }
    
    $status = $dismissed ? 'dismissed' : 'complete';
    
    $stmt = $db->prepare("
        UPDATE tenant_polling_tracker 
        SET polling_status = ?,
            last_successful_poll = NOW()
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$status, $pollId, $companyId]);
    
    if ($stmt->rowCount() > 0) {
        $message = $dismissed ? 'Polling avvist' : 'Polling markert som fullført';
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Polling ikke funnet eller allerede fullført']);
    }
    
} catch (Exception $e) {
    error_log("Error setting polling complete: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering av polling-status'
    ]);
}
?>
