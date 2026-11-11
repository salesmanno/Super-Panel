<?php
session_start();
session_write_close();

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/event_results_helpers.php';
require_once __DIR__ . '/../includes/event_results_data.php';

$eventId = trim((string) ($_GET['event_id'] ?? ''));
if ($eventId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Arrangement-ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $eventStmt = $db->prepare("
        SELECT id, company_id, title, ticket_price, is_active
        FROM events_eventdata
        WHERE id = ? AND company_id = ?
    ");
    $eventStmt->execute([$eventId, $companyId]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);
    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }

    $report = event_results_load($db, (string) $companyId, [$event]);
    $event = $report['events'][0];
    $rows = $report['transactions'][$eventId];

    $transactions = [];
    $totalIncome = 0.0;
    $totalExpense = 0.0;

    foreach ($rows as $row) {
        $amounts = event_results_ledger_amounts($row);
        $entryType = $amounts['expense'] > 0 ? 'expense' : 'income';
        $amount = $entryType === 'expense' ? $amounts['expense'] : $amounts['income'];
        if ($amount <= 0) {
            continue;
        }

        $metadata = json_decode((string) ($row['metadata'] ?? ''), true);
        if (!is_array($metadata)) {
            $metadata = [];
        }

        $transactions[] = [
            'id' => $row['id'],
            'order_id' => $row['order_id'],
            'entry_type' => $entryType,
            'type' => $entryType,
            'item_name' => $row['item_name'],
            'quantity' => max(1, (int) ($row['quantity'] ?? 1)),
            'total_amount' => round($amount, 2),
            'amount' => round($amount, 2),
            'date' => $row['transaction_date'],
            'category' => $metadata['category'] ?? null,
            'notes' => $metadata['notes'] ?? null,
            'payment_method' => $row['payment_method'],
            'is_manual' => (bool) ($metadata['manual_entry'] ?? false),
        ];

        if ($entryType === 'expense') {
            $totalExpense += $amount;
        } else {
            $totalIncome += $amount;
        }
    }

    $totalIncome = round($totalIncome, 2);
    $totalExpense = round($totalExpense, 2);
    $netResult = round($totalIncome - $totalExpense, 2);

    echo json_encode([
        'success' => true,
        'transactions' => $transactions,
        'summary' => [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_result' => $netResult,
            'net' => $netResult,
            'projected_income' => $event['projected_income'],
            'projected_net_result' => $event['projected_net_result'],
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_event_transactions error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke hente arrangementstransaksjoner',
    ], JSON_UNESCAPED_UNICODE);
}