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

$event_id = $data['event_id'] ?? null;
$type = $data['type'] ?? null; // 'income' or 'expense'
$item_name = $data['item_name'] ?? null;
$amount = $data['amount'] ?? null;
$payment_method = $data['payment_method'] ?? 'cash';

if (!$event_id || !$type || !$item_name || !$amount) {
    echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Beløp må være større enn 0']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Verify event exists and belongs to this company
    $eventStmt = $db->prepare("
        SELECT id, title FROM events_eventdata 
        WHERE id = ? AND company_id = ?
    ");
    $eventStmt->execute([$event_id, $company_id]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    // Initialize UnifiedLedgerService
    $ledgerService = new UnifiedLedgerService($db, $company_id);
    
    // Generate unique order ID
    $orderId = 'EVENT-' . strtoupper(substr(md5(uniqid()), 0, 8));
    
    // For expenses, make amount negative
    $selling_price = $type === 'expense' ? -abs($amount) : abs($amount);
    
    // Log to unified ledger
    $result = $ledgerService->logTransaction([
        'order_id' => $orderId,
        'module_source' => 'events',
        'item_type' => $type === 'expense' ? 'expense' : 'income',
        'source_id' => $event_id,
        'item_name' => $item_name,
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
        // Update events_results
        try {
            $updateResultsStmt = $db->prepare("
                INSERT INTO events_results (event_id, company_id, status, created_at, updated_at)
                VALUES (?, ?, 'active', NOW(), NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()
            ");
            $updateResultsStmt->execute([$event_id, $company_id]);
        } catch (Exception $resultsErr) {
            error_log("Warning: Could not create events_results record: " . $resultsErr->getMessage());
        }
        
        echo json_encode([
            'success' => true,
            'message' => $type === 'expense' ? 'Utgift lagt til' : 'Inntekt lagt til',
            'ledger_id' => $result['ledger_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Add event finance error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved lagring: ' . $e->getMessage()
    ]);
}
