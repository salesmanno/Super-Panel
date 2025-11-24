<?php
/**
 * Get Event Results Summary
 * Retrieve complete event statistics, income, and expenses
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
    $event_id = $_GET['event_id'] ?? null;
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
    $summary = $resultsService->getSummary($event_id);
    
    if ($summary) {
        // Format summary data
        $formatted = [
            'success' => true,
            'summary' => [
                'revenue' => [
                    'tickets_cash' => floatval($summary['revenue_tickets_cash'] ?? 0),
                    'tickets_card' => floatval($summary['revenue_tickets_card'] ?? 0),
                    'tickets_vipps' => floatval($summary['revenue_tickets_vipps'] ?? 0),
                    'tickets_invoice' => floatval($summary['revenue_tickets_invoice'] ?? 0),
                    'merchandise' => floatval($summary['revenue_merchandise'] ?? 0),
                    'donations' => floatval($summary['revenue_donations'] ?? 0),
                    'total' => floatval($summary['total_revenue'] ?? 0)
                ],
                'expenses' => [
                    'staff' => floatval($summary['expense_staff'] ?? 0),
                    'venue' => floatval($summary['expense_venue'] ?? 0),
                    'equipment' => floatval($summary['expense_equipment'] ?? 0),
                    'materials' => floatval($summary['expense_materials'] ?? 0),
                    'other' => floatval($summary['expense_other'] ?? 0),
                    'total' => floatval($summary['total_costs'] ?? 0)
                ],
                'attendance' => [
                    'total_registered' => intval($summary['total_checkins'] ?? 0) + intval($summary['no_shows'] ?? 0),
                    'checked_in' => intval($summary['total_checkins'] ?? 0),
                    'no_shows' => intval($summary['no_shows'] ?? 0),
                    'attendance_rate' => floatval($summary['attendance_rate'] ?? 0),
                    'average_age' => intval($summary['average_age'] ?? 0)
                ],
                'financial' => [
                    'profit_loss' => floatval($summary['profit_loss'] ?? 0)
                ],
                'status' => $summary['status'] ?? 'planning',
                'finalized_at' => $summary['finalized_at'] ?? null
            ]
        ];
        
        echo json_encode($formatted);
    } else {
        echo json_encode(['success' => false, 'message' => 'Arrangementet har ingen resultater ennå']);
    }
    
} catch (Exception $e) {
    error_log("Error getting event results: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil: ' . $e->getMessage()
    ]);
}
?>
