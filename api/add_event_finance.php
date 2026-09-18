<?php
session_start();
require_once '../config/database.php';
require_once '../includes/UnifiedLedgerService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig forespørsel']);
    exit;
}

$event_id = $data['event_id'] ?? null;
$type = $data['type'] ?? null; // 'income' or 'expense'
$item_name = $data['item_name'] ?? null;
$amount = $data['amount'] ?? null;
$payment_method = $data['payment_method'] ?? 'cash';

if (
    !$event_id
    || !in_array($type, ['income', 'expense'], true)
    || !is_string($item_name)
    || trim($item_name) === ''
    || !is_numeric($amount)
) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
    exit;
}

if ((float) $amount <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Beløp må være større enn 0']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Verify event exists and belongs to this company
    $eventStmt = $db->prepare("
        SELECT id, title FROM events_eventdata
        WHERE id = ? AND company_id = ? AND is_active = 1
    ");
    $eventStmt->execute([$event_id, $company_id]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    // Initialize UnifiedLedgerService
    $ledgerService = new UnifiedLedgerService($db, $company_id);
    
    // Generate unique order ID
    $orderId = 'EVENT-' . strtoupper(bin2hex(random_bytes(6)));
    
    // For expenses, make amount negative
    $selling_price = $type === 'expense' ? -abs((float) $amount) : abs((float) $amount);
    
    // Log to unified ledger
    $result = $ledgerService->logTransaction([
        'order_id' => $orderId,
        'module_source' => 'events',
        'item_type' => $type === 'expense' ? 'expense' : 'income',
        'source_id' => $event_id,
        'item_name' => trim($item_name),
        'quantity' => 1,
        'selling_price' => $selling_price,
        'payment_method' => $payment_method,
        'payment_status' => 'completed',
        'status' => 'active',
        'transaction_type' => $type,
        'category' => 'Arrangement',
        'metadata' => [
            'event_id' => $event_id,
            'event_title' => $event['title'],
            'manual_entry' => true,
            'added_by' => $_SESSION['tenant_admin_id']
        ]
    ]);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $type === 'expense' ? 'Utgift lagt til' : 'Inntekt lagt til',
            'ledger_id' => $result['ledger_id']
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Kunne ikke lagre hovedboksposten'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Add event finance error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke lagre arrangementsøkonomien'
    ]);
}
