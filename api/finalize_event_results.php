<?php
/**
 * Finalize Event Results
 * Mark event as completed and finalize statistics
 * Recalculates attendance metrics and profit/loss
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/EventResultsService.php';

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $event_id = $data['event_id'] ?? null;
    if (!$event_id) {
        echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
        exit;
    }
    
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Verify event exists
    $stmt = $db->prepare("SELECT id FROM events_eventdata WHERE id = ? AND company_id = ?");
    $stmt->execute([$event_id, $company_id]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    $resultsService = new EventResultsService($db, $company_id);
    
    // Update attendance statistics
    $resultsService->updateAttendanceStats($event_id);
    
    // Finalize event
    $notes = [
        'notes' => $data['notes'] ?? null,
        'organizer_notes' => $data['organizer_notes'] ?? null
    ];
    $resultsService->finalize($event_id, $notes);
    
    // Get final summary
    $summary = $resultsService->getSummary($event_id);
    
    echo json_encode([
        'success' => true,
        'message' => 'Arrangement avsluttet',
        'summary' => $summary
    ]);
    
} catch (Exception $e) {
    error_log("Error finalizing event: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil: ' . $e->getMessage()
    ]);
}
?>
