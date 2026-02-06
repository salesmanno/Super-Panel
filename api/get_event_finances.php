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

    $eventStmt = $db->prepare("
        SELECT title, ticket_price 
        FROM events_eventdata 
        WHERE id = ? AND company_id = ?
    ");
    $eventStmt->execute([$event_id, $company_id]);
    $eventInfo = $eventStmt->fetch(PDO::FETCH_ASSOC);

    if (!$eventInfo) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }

    $eventTitle = $eventInfo['title'];
    $standardPrice = floatval($eventInfo['ticket_price']);

    $checkinQuery = "
        SELECT 
            ec.ticket_price,
            ec.payment_status,
            ec.checked_in,
            CASE WHEN ec.membership_plan_id IS NOT NULL THEN 'Medlem' ELSE 'Ikke-medlem' END as member_type
        FROM events_checkin ec
        WHERE ec.event_id = ?
    ";
    $checkinStmt = $db->prepare($checkinQuery);
    $checkinStmt->execute([$event_id]);
    $checkins = $checkinStmt->fetchAll(PDO::FETCH_ASSOC);

    $checkinIncome = [];
    $totalCheckinIncome = 0;
    $totalCheckinBehandlet = 0;
    $totalCheckinProsjektert = 0;

    $groupedTickets = [];

    foreach ($checkins as $ci) {
        $price = floatval($ci['ticket_price']);
        if ($price <= 0) continue;

        $isPaid = ($ci['payment_status'] === 'paid' || $ci['payment_status'] === 'completed');
        $isCheckedIn = intval($ci['checked_in']) === 1;
        $status = ($isPaid || $isCheckedIn) ? 'behandlet' : 'prosjektert';

        $totalCheckinIncome += $price;
        if ($isPaid || $isCheckedIn) {
            $totalCheckinBehandlet += $price;
        } else {
            $totalCheckinProsjektert += $price;
        }

        $key = $ci['member_type'] . '_' . $status . '_' . number_format($price, 2);
        if (!isset($groupedTickets[$key])) {
            $groupedTickets[$key] = [
                'label' => $ci['member_type'],
                'payment_status' => $status,
                'count' => 0,
                'price' => $price,
                'total' => 0
            ];
        }
        $groupedTickets[$key]['count']++;
        $groupedTickets[$key]['total'] += $price;
    }

    $checkinIncome = array_values($groupedTickets);

    $expenseStmt = $db->prepare("
        SELECT id, description, amount, transaction_type, payment_status, 
               category, expense_date, unified_ledger_id
        FROM events_expenses
        WHERE event_id = ?
        ORDER BY expense_date DESC, created_at DESC
    ");
    $expenseStmt->execute([$event_id]);
    $expenseItems = $expenseStmt->fetchAll(PDO::FETCH_ASSOC);

    $expenses = [];
    $totalManualIncome = 0;
    $totalExpense = 0;
    $totalExpenseBehandlet = 0;
    $totalExpenseProsjektert = 0;

    foreach ($expenseItems as $item) {
        $amt = floatval($item['amount']);

        if ($item['transaction_type'] === 'income') {
            $totalManualIncome += $amt;
        } elseif ($item['transaction_type'] === 'expense') {
            $totalExpense += $amt;
            if ($item['payment_status'] === 'behandlet') {
                $totalExpenseBehandlet += $amt;
            } else {
                $totalExpenseProsjektert += $amt;
            }
        }

        $expenses[] = [
            'id' => $item['id'],
            'description' => $item['description'],
            'amount' => $amt,
            'transaction_type' => $item['transaction_type'],
            'payment_status' => $item['payment_status'],
            'category' => $item['category'],
            'expense_date' => $item['expense_date'],
            'unified_ledger_id' => $item['unified_ledger_id']
        ];
    }

    $totalIncome = $totalCheckinIncome + $totalManualIncome;

    echo json_encode([
        'success' => true,
        'event_title' => $eventTitle,
        'standard_price' => $standardPrice,
        'checkin_income' => $checkinIncome,
        'expenses' => $expenses,
        'summary' => [
            'total_ticket_income' => $totalCheckinIncome,
            'total_ticket_behandlet' => $totalCheckinBehandlet,
            'total_ticket_prosjektert' => $totalCheckinProsjektert,
            'total_manual_income' => $totalManualIncome,
            'total_expense' => $totalExpense,
            'total_income' => $totalIncome,
            'total_expense_behandlet' => $totalExpenseBehandlet,
            'total_expense_prosjektert' => $totalExpenseProsjektert,
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
