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

    $registrationQuery = "
        SELECT 
            r.id as registration_id,
            r.customer_id,
            ec.ticket_price as checkin_ticket_price,
            COALESCE(emp.ticket_price, e.ticket_price) AS fallback_ticket_price,
            CASE WHEN ec.membership_plan_id IS NOT NULL THEN 'Medlem' ELSE 'Ikke-medlem' END as member_type,
            ec.payment_status as checkin_payment_status,
            ec.checked_in
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id
        INNER JOIN tenant_customers c ON r.customer_id = c.id AND c.company_id = r.company_id
        LEFT JOIN membership_members mm ON mm.customer_id = c.id AND mm.company_id = r.company_id AND mm.status = 'active'
        LEFT JOIN event_membership_plans emp ON emp.event_id = r.event_id AND emp.membership_plan_id = mm.membership_plan_id
        LEFT JOIN events_checkin ec ON ec.registration_id = r.id
        WHERE r.event_id = ? AND r.company_id = ? AND r.approval_status = 'approved'
    ";
    $registrationStmt = $db->prepare($registrationQuery);
    $registrationStmt->execute([$event_id, $company_id]);
    $registrations = $registrationStmt->fetchAll(PDO::FETCH_ASSOC);

    $checkinIncome = [];
    $totalCheckinIncome = 0;
    $totalCheckinBehandlet = 0;
    $totalCheckinProsjektert = 0;

    $groupedTickets = [];

    foreach ($registrations as $reg) {
        $price = ($reg['checkin_ticket_price'] !== null) ? floatval($reg['checkin_ticket_price']) : floatval($reg['fallback_ticket_price']);
        if ($price <= 0) continue;

        $isPaid = ($reg['checkin_payment_status'] === 'paid' || $reg['checkin_payment_status'] === 'completed');
        $isCheckedIn = intval($reg['checked_in'] ?? 0) === 1;
        $status = ($isPaid || $isCheckedIn) ? 'behandlet' : 'prosjektert';

        $totalCheckinIncome += $price;
        if ($isPaid || $isCheckedIn) {
            $totalCheckinBehandlet += $price;
        } else {
            $totalCheckinProsjektert += $price;
        }

        $key = $reg['member_type'] . '_' . $status . '_' . number_format($price, 2);
        if (!isset($groupedTickets[$key])) {
            $groupedTickets[$key] = [
                'label' => $reg['member_type'],
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
