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

$event_id = $data['event_id'] ?? null;
$description = trim($data['description'] ?? '');
$amount = floatval($data['amount'] ?? 0);
$date = $data['date'] ?? date('Y-m-d');
$notes = $data['notes'] ?? '';
$payment_status = $data['payment_status'] ?? 'behandlet';
$category = $data['category'] ?? null;

if (!$event_id || !$description || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Manglende eller ugyldig data']);
    exit;
}

if (!in_array($payment_status, ['behandlet', 'prosjektert'])) {
    echo json_encode(['success' => false, 'message' => 'Ugyldig betalingsstatus']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];

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

    $expenseId = bin2hex(random_bytes(16));
    $unified_ledger_id = null;

    $db->beginTransaction();

    if ($payment_status === 'behandlet') {
        $ledgerService = new UnifiedLedgerService($db, $company_id);
        $orderId = 'EVENT-INC-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $result = $ledgerService->logTransaction([
            'order_id' => $orderId,
            'module_source' => 'events',
            'item_type' => 'event_income',
            'source_id' => $event_id,
            'item_name' => $description,
            'quantity' => 1,
            'selling_price' => $amount,
            'payment_method' => 'manual',
            'payment_status' => 'completed',
            'status' => 'active',
            'transaction_type' => 'income',
            'category' => 'Arrangement',
            'transaction_date' => $date,
            'metadata' => [
                'event_id' => $event_id,
                'event_title' => $event['title'],
                'expense_id' => $expenseId,
                'manual_entry' => true,
                'notes' => $notes
            ]
        ]);

        if (!$result['success']) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Feil ved opprettelse av hovedbok-oppføring: ' . $result['message']]);
            exit;
        }

        $unified_ledger_id = $result['ledger_id'];
    }

    $stmt = $db->prepare("
        INSERT INTO events_expenses (
            id, event_id, description, amount, transaction_type, 
            payment_status, unified_ledger_id, category, expense_date, 
            created_by, created_at, updated_at
        ) VALUES (
            ?, ?, ?, ?, 'income',
            ?, ?, ?, ?,
            ?, NOW(), NOW()
        )
    ");

    $stmt->execute([
        $expenseId, $event_id, $description, $amount,
        $payment_status, $unified_ledger_id, $category, $date,
        $_SESSION['tenant_admin_id']
    ]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Inntekt lagt til',
        'expense_id' => $expenseId
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error adding event income: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved lagring av inntekt: ' . $e->getMessage()
    ]);
}
