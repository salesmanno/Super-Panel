<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/request_date_time.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';
require_once __DIR__ . '/../includes/active_payment_methods.php';

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

try {
    $date = normalizeRequestDate($date, 'Inntektsdato', false);
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
    
    $db->beginTransaction();
    activePaymentMethodsLock($db,(string)$company_id);
    $paymentMethod=requireActivePaymentMethod($db,(string)$company_id,$data['payment_method']??null);
    $ledger = new UnifiedLedgerService($db, $company_id);
    $result = $ledger->logTransaction([
        'order_id' => 'EVENT-INCOME-' . strtoupper(bin2hex(random_bytes(6))),
        'module_source' => 'events',
        'item_type' => 'event_income',
        'source_id' => $event_id,
        'item_name' => $description,
        'quantity' => 1,
        'selling_price' => abs((float) $amount),
        'cost_price' => null,
        'payment_method' => $paymentMethod,
        'payment_status' => 'completed',
        'status' => 'active',
        'transaction_type' => 'income',
        'category' => 'Arrangement',
        'transaction_date' => $date . ' 00:00:00',
        'metadata' => [
            'event_id' => $event_id,
            'event_title' => $event['title'],
            'manual_entry' => true,
            'notes' => $notes,
        ],
    ]);

    if (!$result['success']) {
        throw new RuntimeException('Hovedbokføringen mislyktes');
    }
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Inntekt lagt til'
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("Error adding event income: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke lagre inntekten'
    ]);
}
