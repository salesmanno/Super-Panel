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
$field = $data['field'] ?? null;
$value = $data['value'] ?? 0;

if (!$event_id || !$field) {
    echo json_encode(['success' => false, 'message' => 'Event ID eller felt mangler']);
    exit;
}

// Whitelist of allowed fields to update
$allowedFields = [
    'food_sales', 'drink_sales', 'other_sales',
    'purchase_cost', 'food_drink_cost',
    'food_units_sold', 'drink_units_sold',
    'event_cancelled'
];

if (!in_array($field, $allowedFields)) {
    echo json_encode(['success' => false, 'message' => 'Ugyldig felt']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Update the specific field using parameterized column name via CASE statement
    $fieldMapping = [
        'food_sales' => 'food_sales',
        'drink_sales' => 'drink_sales',
        'other_sales' => 'other_sales',
        'purchase_cost' => 'purchase_cost',
        'food_drink_cost' => 'food_drink_cost',
        'food_units_sold' => 'food_units_sold',
        'drink_units_sold' => 'drink_units_sold',
        'event_cancelled' => 'event_cancelled'
    ];
    
    $safeField = $fieldMapping[$field];
    
    // Build UPDATE query with safe column name
    $updateQuery = "
        UPDATE events_results 
        SET $safeField = ?,
            updated_at = NOW()
        WHERE event_id = ? AND company_id = ?
    ";
    
    $stmt = $db->prepare($updateQuery);
    $stmt->execute([$value, $event_id, $company_id]);
    
    // Recalculate totals
    $recalcStmt = $db->prepare("
        UPDATE events_results
        SET 
            total_ticket_sales = member_amount + guest_amount,
            total_food_drink_sales = food_sales + drink_sales + other_sales,
            total_revenue = (member_amount + guest_amount) + (food_sales + drink_sales + other_sales),
            total_costs = purchase_cost + food_drink_cost,
            profit_loss = ((member_amount + guest_amount) + (food_sales + drink_sales + other_sales)) - (purchase_cost + food_drink_cost),
            updated_at = CURRENT_TIMESTAMP
        WHERE event_id = ? AND company_id = ?
    ");
    $recalcStmt->execute([$event_id, $company_id]);
    
    // Fetch updated results
    $fetchStmt = $db->prepare("SELECT * FROM events_results WHERE event_id = ? AND company_id = ?");
    $fetchStmt->execute([$event_id, $company_id]);
    $results = $fetchStmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Oppdatert',
        'results' => $results
    ]);
    
} catch (Exception $e) {
    error_log("Update event results error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
