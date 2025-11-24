<?php
/**
 * Add Event Expense
 * Register expenses for an event and update events_results
 * Categories: staff, venue, equipment, materials, other
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
    $expense_type = $data['expense_type'] ?? 'other'; // staff, venue, equipment, materials, other
    $amount = floatval($data['amount'] ?? 0);
    $description = $data['description'] ?? '';
    
    if (!$event_id || $amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Manglende event_id eller amount']);
        exit;
    }
    
    // Validate expense type
    $validTypes = ['staff', 'venue', 'equipment', 'materials', 'other'];
    if (!in_array($expense_type, $validTypes)) {
        echo json_encode(['success' => false, 'message' => 'Ugyldig expense_type']);
        exit;
    }
    
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Verify event exists
    $stmt = $db->prepare("SELECT id, title FROM events_eventdata WHERE id = ? AND company_id = ?");
    $stmt->execute([$event_id, $company_id]);
    if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    // Add expense to events_results
    $resultsService = new EventResultsService($db, $company_id);
    $success = $resultsService->addExpense($event_id, $expense_type, $amount, $description);
    
    if ($success) {
        // Get updated summary
        $summary = $resultsService->getSummary($event_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'Utgift registrert',
            'summary' => $summary
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Feil ved registrering av utgift']);
    }
    
} catch (Exception $e) {
    error_log("Error adding event expense: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil: ' . $e->getMessage()
    ]);
}
