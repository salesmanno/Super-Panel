<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    echo json_encode(['success' => false, 'message' => 'Event ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Get or create event results
    $stmt = $db->prepare("
        SELECT * FROM events_results 
        WHERE event_id = ? AND company_id = ?
    ");
    $stmt->execute([$event_id, $company_id]);
    $results = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$results) {
        // Auto-create results entry for this event using ON DUPLICATE KEY to prevent race conditions
        $eventStmt = $db->prepare("
            SELECT 
                start_date, 
                end_date,
                ticket_price
            FROM events_eventdata 
            WHERE id = ? AND company_id = ?
        ");
        $eventStmt->execute([$event_id, $company_id]);
        $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($event) {
            $createStmt = $db->prepare("
                INSERT INTO events_results (
                    event_id, company_id, date_start, date_end,
                    ticketprice_member, ticketprice_normal, status, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'active', NOW(), NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()
            ");
            $createStmt->execute([
                $event_id,
                $company_id,
                $event['start_date'],
                $event['end_date'],
                $event['ticket_price'] ?? 0,
                $event['ticket_price'] ?? 0
            ]);
            
            // Fetch the results (newly created or existing)
            $stmt->execute([$event_id, $company_id]);
            $results = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
    
} catch (Exception $e) {
    error_log("Get event results error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av arrangementsresultater: ' . $e->getMessage()
    ]);
}
