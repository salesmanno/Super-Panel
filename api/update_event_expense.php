<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;
$description = isset($data['description']) ? trim($data['description']) : null;
$amount = isset($data['amount']) ? floatval($data['amount']) : null;
$payment_status = $data['payment_status'] ?? null;
$category = array_key_exists('category', $data) ? $data['category'] : null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Mangler ID']);
    exit;
}

if ($payment_status !== null && !in_array($payment_status, ['behandlet', 'prosjektert'])) {
    echo json_encode(['success' => false, 'message' => 'Ugyldig betalingsstatus']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];

    $stmt = $db->prepare("
        SELECT ee.*, e.title as event_title, e.company_id
        FROM events_expenses ee
        INNER JOIN events_eventdata e ON ee.event_id = e.id
        WHERE ee.id = ? AND e.company_id = ?
    ");
    $stmt->execute([$id, $company_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Oppføring ikke funnet']);
        exit;
    }

    $newDescription = $description !== null ? $description : $existing['description'];
    $newAmount = $amount !== null ? $amount : floatval($existing['amount']);
    $newPaymentStatus = $payment_status !== null ? $payment_status : $existing['payment_status'];
    $newCategory = $category !== null ? $category : $existing['category'];
    $oldPaymentStatus = $existing['payment_status'];
    $unified_ledger_id = $existing['unified_ledger_id'];

    $db->beginTransaction();

    if ($oldPaymentStatus === 'prosjektert' && $newPaymentStatus === 'behandlet') {
        $ledgerService = new UnifiedLedgerService($db, $company_id);
        $orderId = 'EVENT-' . strtoupper(substr($existing['transaction_type'], 0, 3)) . '-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $sellingPrice = $existing['transaction_type'] === 'expense' ? -abs($newAmount) : $newAmount;

        $result = $ledgerService->logTransaction([
            'order_id' => $orderId,
            'module_source' => 'events',
            'item_type' => 'event_' . $existing['transaction_type'],
            'source_id' => $existing['event_id'],
            'item_name' => $newDescription,
            'quantity' => 1,
            'selling_price' => $sellingPrice,
            'payment_method' => 'manual',
            'payment_status' => 'completed',
            'status' => 'active',
            'transaction_type' => $existing['transaction_type'],
            'category' => 'Arrangement',
            'transaction_date' => $existing['expense_date'],
            'metadata' => [
                'event_id' => $existing['event_id'],
                'event_title' => $existing['event_title'],
                'expense_id' => $id,
                'manual_entry' => true
            ]
        ]);

        if (!$result['success']) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Feil ved opprettelse av hovedbok-oppføring: ' . $result['message']]);
            exit;
        }

        $unified_ledger_id = $result['ledger_id'];

    } elseif ($oldPaymentStatus === 'behandlet' && $newPaymentStatus === 'prosjektert') {
        if ($existing['unified_ledger_id']) {
            $delStmt = $db->prepare("DELETE FROM unified_ledger WHERE id = ? AND company_id = ?");
            $delStmt->execute([$existing['unified_ledger_id'], $company_id]);
        }
        $unified_ledger_id = null;

    } elseif ($newPaymentStatus === 'behandlet' && $existing['unified_ledger_id']) {
        $descChanged = ($description !== null && $description !== $existing['description']);
        $amountChanged = ($amount !== null && $amount != floatval($existing['amount']));

        if ($descChanged || $amountChanged) {
            $sellingPrice = $existing['transaction_type'] === 'expense' ? -abs($newAmount) : $newAmount;

            $updateLedger = $db->prepare("
                UPDATE unified_ledger 
                SET item_name = ?, selling_price = ?, updated_at = NOW()
                WHERE id = ? AND company_id = ?
            ");
            $updateLedger->execute([$newDescription, $sellingPrice, $existing['unified_ledger_id'], $company_id]);
        }
    }

    $stmt = $db->prepare("
        UPDATE events_expenses 
        SET description = ?, amount = ?, payment_status = ?, 
            category = ?, unified_ledger_id = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$newDescription, $newAmount, $newPaymentStatus, $newCategory, $unified_ledger_id, $id]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Oppføring oppdatert'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error updating event expense: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
