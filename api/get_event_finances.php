<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$event_id = $_GET['event_id'] ?? null;

if (!$event_id) {
    echo json_encode(['success' => false, 'message' => 'Event ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Get all income from unified_ledger for this event
    // Income = positive selling_price values where metadata contains event_id
    $incomeStmt = $db->prepare("
        SELECT 
            id,
            item_name,
            selling_price,
            payment_method,
            transaction_date,
            metadata
        FROM unified_ledger
        WHERE company_id = ? 
        AND status = 'active'
        AND selling_price > 0
        AND (
            JSON_EXTRACT(metadata, '$.event_id') = ?
            OR module_source = 'events' AND source_id = ?
        )
        ORDER BY transaction_date DESC
    ");
    $incomeStmt->execute([$company_id, $event_id, $event_id]);
    $income = $incomeStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all expenses for this event
    // Expenses = negative selling_price values where metadata contains event_id
    $expenseStmt = $db->prepare("
        SELECT 
            id,
            item_name,
            selling_price,
            payment_method,
            transaction_date,
            metadata
        FROM unified_ledger
        WHERE company_id = ? 
        AND status = 'active'
        AND selling_price < 0
        AND JSON_EXTRACT(metadata, '$.event_id') = ?
        ORDER BY transaction_date DESC
    ");
    $expenseStmt->execute([$company_id, $event_id]);
    $expenses = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $totalIncome = 0;
    foreach ($income as $item) {
        $totalIncome += $item['selling_price'];
    }
    
    $totalExpense = 0;
    foreach ($expenses as $item) {
        $totalExpense += abs($item['selling_price']);
    }
    
    echo json_encode([
        'success' => true,
        'income' => $income,
        'expenses' => $expenses,
        'summary' => [
            'total_income' => $totalIncome,
            'total_expense' => $totalExpense,
            'net_result' => $totalIncome - $totalExpense
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get event finances error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av arrangementsøkonomi: ' . $e->getMessage()
    ]);
}
