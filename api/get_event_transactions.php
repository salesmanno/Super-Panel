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
    $dbType = getDBType();
    
    // Query unified_ledger for all transactions related to this event
    // Including both ticket sales and manual income/expense entries
    if ($dbType === 'mysql') {
        $stmt = $db->prepare("
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
              AND (source_id = ? OR JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.event_id')) = ?)
              AND status = 'active'
            ORDER BY transaction_date DESC, created_at DESC
        ");
        $stmt->execute([$_SESSION['company_id'], $eventId, $eventId]);
    } else {
        $stmt = $db->prepare("
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
              AND (source_id::text = ? OR metadata::jsonb->>'event_id' = ?)
              AND status = 'active'
            ORDER BY transaction_date DESC, created_at DESC
        ");
        $stmt->execute([$_SESSION['company_id'], $eventId, $eventId]);
    }
    
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Parse metadata and categorize transactions
    $income = 0;
    $expense = 0;
    $processedTransactions = [];
    
    foreach ($transactions as $transaction) {
        $metadata = json_decode($transaction['metadata'], true);
        $type = 'income'; // default to income
        
        // Determine transaction type
        if (str_contains($transaction['item_type'], 'expense')) {
            $type = 'expense';
            $expense += abs($transaction['amount']);
        } else {
            $income += $transaction['amount'];
        }
        
        $processedTransactions[] = [
            'id' => $transaction['id'],
            'order_id' => $transaction['order_id'],
            'type' => $type,
            'description' => $transaction['item_name'],
            'amount' => abs($transaction['amount']),
            'date' => $transaction['transaction_date'],
            'category' => $metadata['category'] ?? null,
            'notes' => $metadata['notes'] ?? null,
            'payment_method' => $transaction['payment_method'],
            'is_manual' => $metadata['manual_entry'] ?? false
        ];
    }
    
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
