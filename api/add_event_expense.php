<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/request_date_time.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

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
$category = $data['category'] ?? 'other';
$notes = $data['notes'] ?? '';

try {
    $date = normalizeRequestDate($date, 'Utgiftsdato', false);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}

if (!$event_id || !$description || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Manglende eller ugyldig data']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    $stmt = $db->prepare("
        SELECT id, title FROM events_eventdata 
        WHERE id = ? AND company_id = ? AND is_active = 1
    ");
    $stmt->execute([$event_id, $company_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    $categoryLabel = $category ? " ({$category})" : '';
    $itemName = $event['title'] . ' - ' . $description . $categoryLabel;

    $ledger = new UnifiedLedgerService($db, $company_id);
    $result = $ledger->logTransaction([
        'order_id' => 'EVENT-EXPENSE-' . strtoupper(bin2hex(random_bytes(6))),
        'module_source' => 'events',
        'item_type' => 'event_expense',
        'source_id' => $event_id,
        'item_name' => $itemName,
        'quantity' => 1,
        'selling_price' => -abs((float) $amount),
        'cost_price' => null,
        'payment_method' => 'manual',
        'payment_status' => 'completed',
        'status' => 'active',
        'transaction_type' => 'expense',
        'category' => 'Arrangement',
        'transaction_date' => $date . ' 00:00:00',
        'metadata' => [
            'event_id' => $event_id,
            'event_title' => $event['title'],
            'manual_entry' => true,
            'category' => $category,
            'notes' => $notes,
        ],
    ]);

    if (!$result['success']) {
        throw new RuntimeException('Hovedbokføringen mislyktes');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Utgift lagt til'
    ]);
    
} catch (Exception $e) {
    error_log("Error adding event expense: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke lagre utgiften'
    ]);
}
