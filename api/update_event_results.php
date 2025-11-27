<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$event_id = $data['event_id'] ?? null;
$type = $data['type'] ?? null; // 'income' or 'expense'
$amount = $data['amount'] ?? 0;
$category = $data['category'] ?? null;

if (!$event_id || !$type) {
    echo json_encode(['success' => false, 'message' => 'Mangler event_id eller type']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Verify event exists
    $eventStmt = $db->prepare("
        SELECT id FROM events_eventdata 
        WHERE id = ? AND company_id = ? AND is_active = 1
    ");
    $eventStmt->execute([$event_id, $company_id]);
    if (!$eventStmt->fetch(PDO::FETCH_ASSOC)) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    // Get or create event results record
    $resultsStmt = $db->prepare("
        SELECT id FROM events_results 
        WHERE event_id = ? AND company_id = ?
    ");
    $resultsStmt->execute([$event_id, $company_id]);
    $results = $resultsStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$results) {
        // Create new results record
        $insertStmt = $db->prepare("
            INSERT INTO events_results (
                event_id, company_id, status, created_at, updated_at
            ) VALUES (?, ?, 'active', NOW(), NOW())
        ");
        $insertStmt->execute([$event_id, $company_id]);
    }
    
    // Map category to column name
    if ($type === 'income') {
        $columnMap = [
            'cash' => 'revenue_tickets_cash',
            'card' => 'revenue_tickets_card',
            'vipps' => 'revenue_tickets_vipps',
            'invoice' => 'revenue_tickets_invoice',
            'merchandise' => 'revenue_merchandise',
            'donation' => 'revenue_donations'
        ];
    } else {
        $columnMap = [
            'staff' => 'expense_staff',
            'venue' => 'expense_venue',
            'equipment' => 'expense_equipment',
            'other' => 'expense_other'
        ];
    }
    
    $column = $columnMap[$category] ?? ($type === 'income' ? 'total_revenue' : 'total_costs');
    
    // Update the specific column (add to existing value)
    $updateStmt = $db->prepare("
        UPDATE events_results 
        SET 
            {$column} = COALESCE({$column}, 0) + ?,
            updated_at = NOW()
        WHERE event_id = ? AND company_id = ?
    ");
    
    $updateStmt->execute([$amount, $event_id, $company_id]);
    
    // Recalculate totals and profit_loss
    $recalcStmt = $db->prepare("
        UPDATE events_results
        SET 
            total_revenue = COALESCE(total_ticket_sales, 0) 
                + COALESCE(revenue_tickets_cash, 0)
                + COALESCE(revenue_tickets_card, 0)
                + COALESCE(revenue_tickets_vipps, 0)
                + COALESCE(revenue_tickets_invoice, 0)
                + COALESCE(revenue_merchandise, 0)
                + COALESCE(revenue_donations, 0),
            total_costs = COALESCE(purchase_cost, 0) 
                + COALESCE(food_drink_cost, 0)
                + COALESCE(expense_staff, 0)
                + COALESCE(expense_venue, 0)
                + COALESCE(expense_equipment, 0)
                + COALESCE(expense_other, 0),
            profit_loss = (
                COALESCE(total_ticket_sales, 0) 
                + COALESCE(revenue_tickets_cash, 0)
                + COALESCE(revenue_tickets_card, 0)
                + COALESCE(revenue_tickets_vipps, 0)
                + COALESCE(revenue_tickets_invoice, 0)
                + COALESCE(revenue_merchandise, 0)
                + COALESCE(revenue_donations, 0)
            ) - (
                COALESCE(purchase_cost, 0) 
                + COALESCE(food_drink_cost, 0)
                + COALESCE(expense_staff, 0)
                + COALESCE(expense_venue, 0)
                + COALESCE(expense_equipment, 0)
                + COALESCE(expense_other, 0)
            ),
            updated_at = NOW()
        WHERE event_id = ? AND company_id = ?
    ");
    $recalcStmt->execute([$event_id, $company_id]);
    
    echo json_encode([
        'success' => true,
        'message' => $type === 'income' ? 'Inntekt lagt til' : 'Utgift lagt til'
    ]);
    
} catch (Exception $e) {
    error_log("Update event results error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
