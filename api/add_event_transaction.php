<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Validate input
    if (!isset($input['event_id']) || !isset($input['transaction_type']) || 
        !isset($input['description']) || !isset($input['amount']) || !isset($input['date'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }
    
    $eventId = $input['event_id'];
    $transactionType = $input['transaction_type']; // 'income' or 'expense'
    $description = $input['description'];
    $amount = floatval($input['amount']);
    $date = $input['date'];
    $category = $input['category'] ?? null;
    $notes = $input['notes'] ?? null;
    
    // Validate transaction type
    if (!in_array($transactionType, ['income', 'expense'])) {
        echo json_encode(['success' => false, 'message' => 'Ugyldig transaksjonstype']);
        exit;
    }
    
    // Validate amount
    if ($amount <= 0) {
        echo json_encode(['success' => false, 'message' => 'Beløp må være større enn 0']);
        exit;
    }
    
    $db = getDBConnection();
    
    // Verify event exists and belongs to user's company
    $stmt = $db->prepare("SELECT id, title FROM events_eventdata WHERE id = ? AND company_id = ? AND is_active = 1");
    $stmt->execute([$eventId, $_SESSION['company_id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    // Use UnifiedLedgerService to log transaction
    $ledgerService = new UnifiedLedgerService($db, $_SESSION['company_id']);
    
    // For expenses, use negative amount
    $ledgerAmount = ($transactionType === 'expense') ? -$amount : $amount;
    
    // Generate unique order ID
    $orderId = 'EVT-' . strtoupper($transactionType) . '-' . $eventId . '-' . time();
    
    // Prepare metadata
    $metadata = [
        'event_id' => $eventId,
        'transaction_type' => $transactionType,
        'category' => $category,
        'notes' => $notes,
        'manual_entry' => true
    ];
    
    // Log to unified ledger
    $result = $ledgerService->logTransaction([
        'order_id' => $orderId,
        'module_source' => 'events',
        'item_type' => 'event_' . $transactionType,
        'source_id' => $eventId,
        'item_name' => $event['title'] . ' - ' . $description,
        'quantity' => 1,
        'selling_price' => $ledgerAmount,
        'cost_price' => null,
        'payment_method' => 'manual',
        'payment_status' => 'completed',
        'status' => 'active',
        'transaction_date' => $date . ' 00:00:00',
        'metadata' => $metadata
    ]);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Transaksjon lagt til',
            'ledger_id' => $result['ledger_id']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kunne ikke lagre transaksjon: ' . $result['message']
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error adding event transaction: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Serverfeil: ' . $e->getMessage()
    ]);
}
