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

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $validLedgerSql = event_results_ledger_valid_sql('ul');
    $ledgerEventIdSql = event_results_ledger_event_id_sql('ul');
    $incomeSql = event_results_ledger_income_sql('ul');
    $expenseSql = event_results_ledger_expense_sql('ul');

    $statusSql = "CASE
        WHEN TIMESTAMP(e.start_date, e.start_time) > NOW() THEN 'Kommende'
        WHEN TIMESTAMP(e.start_date, e.start_time) <= NOW()
             AND (
                 e.end_date IS NULL
                 OR TIMESTAMP(e.end_date, COALESCE(e.end_time, '23:59:59')) >= NOW()
             ) THEN 'Pågår'
        ELSE 'Avsluttet'
    END";

    $stmt = $db->prepare("
        SELECT
            e.id,
            e.title,
            e.start_date,
            e.start_time,
            e.ticket_price,
            e.is_active,
            {$statusSql} AS status,
            COALESCE(attendees.attendee_count, 0) AS attendee_count,
            COALESCE(attendees.unpaid_attendee_count, 0) AS unpaid_attendee_count,
            COALESCE(attendees.projected_income, 0) AS projected_income,
            COALESCE(finance.income, 0) AS income,
            COALESCE(finance.expense, 0) AS expense
        FROM events_eventdata e
        LEFT JOIN (
            SELECT
                ec.event_id,
                COUNT(*) AS attendee_count,
                SUM(CASE
                    WHEN LOWER(COALESCE(ec.payment_status, '')) IN ('pending', 'unpaid', 'payment_pending')
                         AND COALESCE(ec.ticket_price, 0) > 0
                    THEN 1 ELSE 0
                END) AS unpaid_attendee_count,
                SUM(CASE
                    WHEN LOWER(COALESCE(ec.payment_status, '')) IN ('pending', 'unpaid', 'payment_pending')
                         AND COALESCE(ec.ticket_price, 0) > 0
                    THEN ec.ticket_price ELSE 0
                END) AS projected_income
            FROM events_checkin ec
            INNER JOIN events_eventdata attendee_event
                ON attendee_event.id = ec.event_id
            WHERE attendee_event.company_id = ?
            GROUP BY ec.event_id
        ) attendees ON attendees.event_id = e.id
        LEFT JOIN (
            SELECT
                ledger_event.id AS event_id,
                SUM({$incomeSql}) AS income,
                SUM({$expenseSql}) AS expense
            FROM events_eventdata ledger_event
            INNER JOIN unified_ledger ul
                ON ul.company_id = ledger_event.company_id
               AND {$validLedgerSql}
               AND {$ledgerEventIdSql} = ledger_event.id
            WHERE ledger_event.company_id = ?
            GROUP BY ledger_event.id
        ) finance ON finance.event_id = e.id
        WHERE e.company_id = ?
        ORDER BY e.start_date DESC, e.start_time DESC, e.id ASC
    ");
    $stmt->execute([$companyId, $companyId, $companyId]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($events as &$event) {
        $event['is_active'] = (int) ($event['is_active'] ?? 0);
        $event['attendee_count'] = (int) $event['attendee_count'];
        $event['unpaid_attendee_count'] = (int) $event['unpaid_attendee_count'];
        $event['projected_income'] = round((float) $event['projected_income'], 2);
        $event['income'] = round((float) $event['income'], 2);
        $event['expense'] = round((float) $event['expense'], 2);
        $event['net_result'] = round($event['income'] - $event['expense'], 2);
        $event['projected_net_result'] = round($event['income'] + $event['projected_income'] - $event['expense'], 2);
        $event['ticket_price'] = round((float) ($event['ticket_price'] ?? 0), 2);
    }
    unset($event);

    echo json_encode([
        'success' => true,
        'summary' => event_results_summary($events),
        'events' => $events,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_events_results error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke hente arrangementsresultater',
    ], JSON_UNESCAPED_UNICODE);
}