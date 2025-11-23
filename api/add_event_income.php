<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$event_id = $data['event_id'] ?? null;
$description = $data['description'] ?? '';
$amount = $data['amount'] ?? 0;
$date = $data['date'] ?? date('Y-m-d');
$notes = $data['notes'] ?? '';

if (!$event_id || !$description || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Manglende eller ugyldig data']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    $dbType = getDBType();
    
    $stmt = $db->prepare("
        SELECT id, title FROM events_eventdata 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$event_id, $company_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    $entryId = bin2hex(random_bytes(16));
    $orderId = 'EVENT_INCOME_' . time() . '_' . substr(md5(random_bytes(16)), 0, 8);
    
    $stmt = $db->prepare("
        INSERT INTO unified_ledger (
            id, order_id, company_id, module_source, item_type, source_id,
            item_name, quantity, selling_price, cost_price, 
            customer_name, customer_email, payment_method, payment_status, transaction_date
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?
        )
    ");
    
    $stmt->execute([
        $entryId, $orderId, $company_id, 'events', 'event_income', $event_id,
        $description, 1, $amount, 0,
        'Manuel registrering', $notes, 'manual', 'completed', $date
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Inntekt lagt til'
    ]);
    
} catch (Exception $e) {
    error_log("Error adding event income: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved lagring av inntekt: ' . $e->getMessage()
    ]);
}
