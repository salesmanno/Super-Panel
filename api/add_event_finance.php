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
$payment_status = $data['payment_status'] ?? 'pending'; // 'pending' or 'paid'
$category = $data['category'] ?? 'other';
$expense_date = $data['date'] ?? date('Y-m-d');

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
    
    // Generate UUID for events_expenses entry
    $expenseId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    
    $unified_ledger_id = null;
    
    // Only write to unified_ledger if payment_status is 'paid'
    $ledger_sync_failed = false;
    if ($payment_status === 'paid') {
        $ledgerService = new UnifiedLedgerService($db, $company_id);
        
        $orderId = 'EVENT-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $selling_price = $type === 'expense' ? -abs($amount) : abs($amount);
        
        $ledgerResult = $ledgerService->logTransaction([
            'order_id' => $orderId,
            'module_source' => 'events',
            'item_type' => $type === 'expense' ? 'expense' : 'income',
            'source_id' => $event_id,
            'item_name' => $item_name,
            'quantity' => 1,
            'selling_price' => $selling_price,
            'payment_method' => 'manual',
            'payment_status' => 'completed',
            'status' => 'active',
            'transaction_type' => $type,
            'category' => 'Arrangement',
            'metadata' => [
                'event_id' => $event_id,
                'event_title' => $event['title'],
                'events_expense_id' => $expenseId,
                'manual_entry' => true,
                'added_by' => $_SESSION['tenant_admin_id']
            ]
        ]);
        
        if ($ledgerResult['success']) {
            $unified_ledger_id = $ledgerResult['ledger_id'];
        } else {
            // Ledger sync failed - downgrade to pending status
            $ledger_sync_failed = true;
            $payment_status = 'pending';
            error_log("Ledger sync failed for event expense: " . ($ledgerResult['message'] ?? 'Unknown error'));
        }
    }
    
    // Insert into events_expenses
    $insertStmt = $db->prepare("
        INSERT INTO events_expenses 
        (id, event_id, description, amount, transaction_type, payment_status, unified_ledger_id, category, expense_date, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertStmt->execute([
        $expenseId,
        $event_id,
        $item_name,
        abs($amount),
        $type, // 'income' or 'expense'
        $payment_status,
        $unified_ledger_id,
        $category,
        $expense_date,
        $_SESSION['tenant_admin_id']
    ]);
    
    $message = $type === 'expense' ? 'Utgift lagt til' : 'Inntekt lagt til';
    if ($ledger_sync_failed) {
        $message .= ' (økonomisync feilet - lagret som ventende)';
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'expense_id' => $expenseId,
        'unified_ledger_id' => $unified_ledger_id,
        'synced_to_ledger' => !$ledger_sync_failed && $payment_status === 'paid',
        'ledger_sync_failed' => $ledger_sync_failed
    ]);
    
} catch (Exception $e) {
    error_log("Add event finance error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved lagring: ' . $e->getMessage()
    ]);
}
