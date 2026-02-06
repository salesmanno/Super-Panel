<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $eventId = $_GET['event_id'] ?? null;

    if (!$eventId) {
        echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
        exit;
    }

    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];

    $ticketStmt = $db->prepare("
        SELECT 
            id,
            order_id,
            item_type,
            item_name,
            quantity,
            selling_price as amount,
            payment_method,
            payment_status,
            status,
            transaction_date,
            metadata,
            created_at
        FROM unified_ledger
        WHERE company_id = ? 
          AND module_source = 'events'
          AND item_type = 'event_ticket'
          AND (source_id = ? OR JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.event_id')) = ?)
          AND status = 'active'
        ORDER BY transaction_date DESC, created_at DESC
    ");
    $ticketStmt->execute([$company_id, $eventId, $eventId]);
    $ticketTransactions = $ticketStmt->fetchAll(PDO::FETCH_ASSOC);

    $manualStmt = $db->prepare("
        SELECT id, description, amount, transaction_type, payment_status,
               category, expense_date, unified_ledger_id, created_at
        FROM events_expenses
        WHERE event_id = ?
        ORDER BY expense_date DESC, created_at DESC
    ");
    $manualStmt->execute([$eventId]);
    $manualEntries = $manualStmt->fetchAll(PDO::FETCH_ASSOC);

    $income = 0;
    $expense = 0;
    $processedTransactions = [];

    foreach ($ticketTransactions as $transaction) {
        $metadata = json_decode($transaction['metadata'], true);
        $amt = floatval($transaction['amount']);
        $income += $amt;

        $processedTransactions[] = [
            'id' => $transaction['id'],
            'order_id' => $transaction['order_id'],
            'type' => 'income',
            'description' => $transaction['item_name'],
            'amount' => abs($amt),
            'date' => $transaction['transaction_date'],
            'category' => $metadata['category'] ?? null,
            'notes' => $metadata['notes'] ?? null,
            'payment_method' => $transaction['payment_method'],
            'payment_status' => 'behandlet',
            'is_manual' => false,
            'source' => 'ticket_sale'
        ];
    }

    foreach ($manualEntries as $entry) {
        $amt = floatval($entry['amount']);

        if ($entry['transaction_type'] === 'income') {
            $income += $amt;
        } else {
            $expense += $amt;
        }

        $processedTransactions[] = [
            'id' => $entry['id'],
            'order_id' => null,
            'type' => $entry['transaction_type'],
            'description' => $entry['description'],
            'amount' => $amt,
            'date' => $entry['expense_date'],
            'category' => $entry['category'],
            'notes' => null,
            'payment_method' => 'manual',
            'payment_status' => $entry['payment_status'],
            'is_manual' => true,
            'source' => 'events_expenses',
            'unified_ledger_id' => $entry['unified_ledger_id']
        ];
    }

    usort($processedTransactions, function($a, $b) {
        return strcmp($b['date'] ?? '', $a['date'] ?? '');
    });

    echo json_encode([
        'success' => true,
        'transactions' => $processedTransactions,
        'summary' => [
            'total_income' => $income,
            'total_expense' => $expense,
            'net' => $income - $expense
        ]
    ]);

} catch (Exception $e) {
    error_log("Error fetching event transactions: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av transaksjoner: ' . $e->getMessage()
    ]);
}
