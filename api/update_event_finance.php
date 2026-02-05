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

$action = $data['action'] ?? null; // 'update' or 'delete' or 'toggle_paid'
$expense_id = $data['expense_id'] ?? null;

if (!$expense_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Get the expense entry and verify ownership via event
    $entryStmt = $db->prepare("
        SELECT ee.*, e.company_id, e.title as event_title
        FROM events_expenses ee
        INNER JOIN events_eventdata e ON ee.event_id = e.id
        WHERE ee.id = ? AND e.company_id = ?
    ");
    $entryStmt->execute([$expense_id, $company_id]);
    $entry = $entryStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$entry) {
        echo json_encode(['success' => false, 'message' => 'Oppføring ikke funnet']);
        exit;
    }
    
    if ($action === 'delete') {
        // If synced to ledger, remove from ledger first
        if ($entry['unified_ledger_id']) {
            $deleteLedgerStmt = $db->prepare("DELETE FROM unified_ledger WHERE id = ? AND company_id = ?");
            $deleteLedgerStmt->execute([$entry['unified_ledger_id'], $company_id]);
        }
        
        // Delete the entry
        $deleteStmt = $db->prepare("DELETE FROM events_expenses WHERE id = ?");
        $deleteStmt->execute([$expense_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Oppføring slettet'
        ]);
        
    } elseif ($action === 'toggle_paid') {
        $new_status = $entry['payment_status'] === 'paid' ? 'pending' : 'paid';
        $unified_ledger_id = $entry['unified_ledger_id'];
        
        if ($new_status === 'paid' && !$unified_ledger_id) {
            // Create ledger entry
            $ledgerService = new UnifiedLedgerService($db, $company_id);
            
            $orderId = 'EVENT-' . strtoupper(substr(md5(uniqid()), 0, 8));
            $selling_price = $entry['transaction_type'] === 'expense' ? -abs($entry['amount']) : abs($entry['amount']);
            
            $ledgerResult = $ledgerService->logTransaction([
                'order_id' => $orderId,
                'module_source' => 'events',
                'item_type' => $entry['transaction_type'] === 'expense' ? 'expense' : 'income',
                'source_id' => $entry['event_id'],
                'item_name' => $entry['description'],
                'quantity' => 1,
                'selling_price' => $selling_price,
                'payment_method' => 'manual',
                'payment_status' => 'completed',
                'status' => 'active',
                'transaction_type' => $entry['transaction_type'],
                'category' => 'Arrangement',
                'metadata' => [
                    'event_id' => $entry['event_id'],
                    'event_title' => $entry['event_title'],
                    'events_expense_id' => $expense_id,
                    'manual_entry' => true,
                    'added_by' => $_SESSION['tenant_admin_id']
                ]
            ]);
            
            if ($ledgerResult['success']) {
                $unified_ledger_id = $ledgerResult['ledger_id'];
            }
            
        } elseif ($new_status === 'pending' && $unified_ledger_id) {
            // Remove from ledger
            $deleteLedgerStmt = $db->prepare("DELETE FROM unified_ledger WHERE id = ? AND company_id = ?");
            $deleteLedgerStmt->execute([$unified_ledger_id, $company_id]);
            $unified_ledger_id = null;
        }
        
        // Update payment status
        $updateStmt = $db->prepare("
            UPDATE events_expenses 
            SET payment_status = ?, unified_ledger_id = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$new_status, $unified_ledger_id, $expense_id]);
        
        echo json_encode([
            'success' => true,
            'message' => $new_status === 'paid' ? 'Merket som betalt' : 'Merket som ventende',
            'new_status' => $new_status,
            'synced_to_ledger' => $new_status === 'paid'
        ]);
        
    } elseif ($action === 'update') {
        // Update fields
        $description = $data['description'] ?? $entry['description'];
        $amount = $data['amount'] ?? $entry['amount'];
        $category = $data['category'] ?? $entry['category'];
        $expense_date = $data['date'] ?? $entry['expense_date'];
        
        $updateStmt = $db->prepare("
            UPDATE events_expenses 
            SET description = ?, amount = ?, category = ?, expense_date = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $updateStmt->execute([$description, abs($amount), $category, $expense_date, $expense_id]);
        
        // If synced to ledger, update ledger too
        if ($entry['unified_ledger_id']) {
            $selling_price = $entry['transaction_type'] === 'expense' ? -abs($amount) : abs($amount);
            $updateLedgerStmt = $db->prepare("
                UPDATE unified_ledger 
                SET item_name = ?, selling_price = ?, updated_at = NOW()
                WHERE id = ? AND company_id = ?
            ");
            $updateLedgerStmt->execute([$description, $selling_price, $entry['unified_ledger_id'], $company_id]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Oppføring oppdatert'
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Ugyldig handling']);
    }
    
} catch (Exception $e) {
    error_log("Update event finance error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
