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
        SELECT e.title, e.ticket_price,
               (SELECT COUNT(*) FROM events_checkin ec WHERE ec.event_id = e.id) AS attendee_count
        FROM events_eventdata e
        WHERE e.id = ?
          AND e.company_id = ?
          AND e.is_active = 1
    ");
    $eventStmt->execute([$eventId, $companyId]);
    $event = $eventStmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }

    $validLedgerSql = event_results_ledger_valid_sql('ul');
    $ledgerEventIdSql = event_results_ledger_event_id_sql('ul');
    $ledgerStmt = $db->prepare("
        SELECT
            ul.item_name,
            ul.item_type,
            ul.quantity,
            ul.selling_price,
            ul.cost_price,
            ul.module_source,
            ul.payment_status,
            ul.status,
            ul.transaction_type
        FROM unified_ledger ul
        WHERE ul.company_id = ?
          AND {$validLedgerSql}
          AND {$ledgerEventIdSql} = ?
        ORDER BY ul.transaction_date ASC, ul.created_at ASC, ul.id ASC
    ");
    $ledgerStmt->execute([$companyId, $eventId]);
    $transactions = $ledgerStmt->fetchAll(PDO::FETCH_ASSOC);

    $incomeGroups = [];
    $expenseGroups = [];
    $totalIncome = 0.0;
    $totalExpense = 0.0;

    foreach ($transactions as $transaction) {
        $amounts = event_results_ledger_amounts($transaction);
        $quantity = max(1, (int) ($transaction['quantity'] ?? 1));
        $name = trim((string) ($transaction['item_name'] ?? ''));

        if ($amounts['income'] > 0) {
            $key = $name !== '' ? $name : 'Inntekt';
            if (!isset($incomeGroups[$key])) {
                $incomeGroups[$key] = ['label' => $key, 'count' => 0, 'total' => 0.0];
            }
            $incomeGroups[$key]['count'] += $quantity;
            $incomeGroups[$key]['total'] += $amounts['income'];
            $totalIncome += $amounts['income'];
        }

        if ($amounts['expense'] > 0) {
            $key = $name !== '' ? $name : 'Utgift';
            if (!isset($expenseGroups[$key])) {
                $expenseGroups[$key] = ['count' => 0, 'total' => 0.0];
            }
            $expenseGroups[$key]['count'] += $quantity;
            $expenseGroups[$key]['total'] += $amounts['expense'];
            $totalExpense += $amounts['expense'];
        }
    }

    $incomeList = [];
    foreach ($incomeGroups as $group) {
        $group['total'] = round($group['total'], 2);
        $group['price'] = $group['count'] > 0
            ? round($group['total'] / $group['count'], 2)
            : 0.0;
        $incomeList[] = $group;
    }

    foreach ($expenseGroups as &$group) {
        $group['total'] = round($group['total'], 2);
    }
    unset($group);

    usort($incomeList, static fn(array $a, array $b): int => $b['total'] <=> $a['total']);
    uasort($expenseGroups, static fn(array $a, array $b): int => $b['total'] <=> $a['total']);

    $totalIncome = round($totalIncome, 2);
    $totalExpense = round($totalExpense, 2);

    echo json_encode([
        'success' => true,
        'event_title' => $event['title'],
        'standard_price' => round((float) ($event['ticket_price'] ?? 0), 2),
        'attendee_count' => (int) $event['attendee_count'],
        'income_groups' => $incomeList,
        // Kept during the UI transition for older callers.
        'checkin_income' => $incomeList,
        'expense_groups' => $expenseGroups,
        'summary' => [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_result' => round($totalIncome - $totalExpense, 2),
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_event_finances error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke hente arrangementsøkonomi',
    ], JSON_UNESCAPED_UNICODE);
}