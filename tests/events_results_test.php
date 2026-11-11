<?php

require_once __DIR__ . '/../includes/event_results_helpers.php';
require_once __DIR__ . '/../includes/event_results_data.php';

$failures = [];

$check = static function (bool $condition, string $message) use (&$failures): void {
    if (!$condition) {
        $failures[] = $message;
    }
};

$base = [
    'module_source' => 'events',
    'status' => 'active',
    'payment_status' => 'completed',
    'item_type' => 'event',
    'transaction_type' => 'income',
    'selling_price' => 125,
    'cost_price' => 50,
];

$amounts = event_results_ledger_amounts($base);
$check($amounts === ['income' => 125.0, 'expense' => 0.0], 'Billettinntekt må telles uten å gjøre kostpris til utgift');

$pending = $base;
$pending['payment_status'] = 'pending';
$check(event_results_ledger_amounts($pending) === ['income' => 0.0, 'expense' => 0.0], 'Ventende betaling må utelates');

$failed = $base;
$failed['payment_status'] = 'failed';
$check(event_results_ledger_amounts($failed) === ['income' => 0.0, 'expense' => 0.0], 'Feilet betaling må utelates');

$refunded = $base;
$refunded['status'] = 'refunded';
$check(event_results_ledger_amounts($refunded) === ['income' => 0.0, 'expense' => 0.0], 'Refundert post må utelates');

$otherModule = $base;
$otherModule['module_source'] = 'membership';
$check(event_results_ledger_amounts($otherModule) === ['income' => 0.0, 'expense' => 0.0], 'Andre moduler må utelates');

$expense = $base;
$expense['item_type'] = 'expense';
$expense['transaction_type'] = 'expense';
$expense['selling_price'] = -40;
$expense['cost_price'] = 0;
$check(event_results_ledger_amounts($expense) === ['income' => 0.0, 'expense' => 40.0], 'Signert utgift må summeres positivt');

$legacyExpense = $base;
$legacyExpense['item_type'] = 'event_expense';
$legacyExpense['transaction_type'] = null;
$legacyExpense['selling_price'] = 0;
$legacyExpense['cost_price'] = 30;
$check(event_results_ledger_amounts($legacyExpense) === ['income' => 0.0, 'expense' => 30.0], 'Eldre utgift i cost_price må fortsatt telles');

$events = [
    [
        'title' => 'Sommerfest',
        'income' => 125,
        'projected_income' => 25,
        'expense' => 40,
        'net_result' => 85,
        'projected_net_result' => 110,
        'attendee_count' => 4,
        'unpaid_attendee_count' => 1,
        'ticket_price' => 100,
        'status' => 'Avsluttet',
        'is_active' => 1,
    ],
    [
        'title' => 'Vinterfest',
        'income' => 75,
        'projected_income' => 100,
        'expense' => 100,
        'net_result' => -25,
        'projected_net_result' => 75,
        'attendee_count' => 8,
        'unpaid_attendee_count' => 2,
        'ticket_price' => 200,
        'status' => 'Kommende',
        'is_active' => 0,
    ],
];
$summary = event_results_summary($events);
$check($summary['total_events'] === 2, 'Alle lagrede arrangementer må telles');
$check($summary['total_revenue'] === 200.0, 'Inntekt må summeres fra arrangementsradene');
$check($summary['total_projected_income'] === 125.0, 'Forventede ubetalte inntekter må summeres separat');
$check($summary['total_income_including_projection'] === 325.0, 'Betalt og forventet inntekt skal kunne summeres');
$check($summary['total_expense'] === 140.0, 'Utgift må summeres fra arrangementsradene');
$check($summary['net_result'] === 60.0, 'Netto må stemme med inntekt minus utgift');
$check($summary['projected_net_result'] === 185.0, 'Prognosenetto må inkludere betalt inntekt, ubetalt inntekt og utgifter');
$check($summary['total_attendees'] === 12, 'Deltakere må summeres likt');
$check($summary['total_unpaid_attendees'] === 3, 'Ubetalte påmeldte må vises separat');
$check($summary['active_events'] === 1 && $summary['inactive_events'] === 1, 'Skjulte arrangementer telles og skilles fra aktive');
$check($summary['upcoming_events'] === 1, 'Kommende arrangementer må telles');
$check($summary['most_profitable']['title'] === 'Sommerfest', 'Mest lønnsomme arrangement må bruke beregnet netto');
$check($summary['most_expensive']['title'] === 'Vinterfest', 'Dyreste arrangement må bruke billettpris');
$check($summary['most_attended']['title'] === 'Vinterfest', 'Mest besøkte arrangement må bruke deltakertall');

$resultsApi = file_get_contents(__DIR__ . '/../api/get_events_results.php');
$dataHelper = file_get_contents(__DIR__ . '/../includes/event_results_data.php');
$financesApi = file_get_contents(__DIR__ . '/../api/get_event_finances.php');
$transactionsApi = file_get_contents(__DIR__ . '/../api/get_event_transactions.php');
$incomeApi = file_get_contents(__DIR__ . '/../api/add_event_income.php');
$expenseApi = file_get_contents(__DIR__ . '/../api/add_event_expense.php');

$check(!str_contains($resultsApi, 'FROM events_results'), 'Resultat-API-et må ikke velge den utdaterte resultattabellen');
$check(!str_contains($resultsApi, "SHOW TABLES LIKE 'events_results'"), 'Tabell-eksistens må ikke styre beregningsmodellen');
$check(str_contains($resultsApi, 'FROM events_eventdata e'), 'Arrangementstabellen må drive resultatlisten');
$check(str_contains($dataHelper, 'FROM events_checkin ec'), 'Deltakere må komme fra innsjekkingstabellen');
$check(str_contains($dataHelper, 'projected_income'), 'Resultat-API-et må returnere ubetalte forventede inntekter');
$check(!str_contains($resultsApi, 'attendee_event.is_active = 1'), 'Skjulte arrangementer må beholde deltakerantallet');
$check(!str_contains($resultsApi, 'ledger_event.is_active = 1'), 'Skjulte arrangementer må beholde økonomidata');
$check(!str_contains($resultsApi, 'AND e.is_active = 1'), 'Resultatlisten må ikke filtrere bort skjulte arrangementer');
$check(str_contains($resultsApi, 'event_results_load') && str_contains($dataHelper, 'event_results_ledger_valid_sql'), 'Oversikten må bruke de delte hovedbokfiltrene');
$check(str_contains($financesApi, 'event_results_load'), 'Økonomidetaljene må bruke samme hovedbokfiltre');
$check(!str_contains($financesApi, 'AND e.is_active = 1'), 'Økonomidetaljer må også åpnes for skjulte arrangementer');
$check(str_contains($transactionsApi, 'event_results_load'), 'Transaksjonsdetaljene må bruke samme hovedbokfiltre');
$check(!str_contains($transactionsApi, 'AND is_active = 1'), 'Transaksjoner må være lesbare for skjulte arrangementer');
$check(str_contains($transactionsApi, "'net_result' => \$netResult"), 'Detaljmodalen må få forventet nettofelt');
$check(str_contains(file_get_contents(__DIR__ . '/../modules/events_content.php'), 'summary-projected-income'), 'Resultatsammendraget må vise forventet inntekt');
$check(!str_contains($incomeApi, 'customer_name'), 'Inntektsføring må bruke gjeldende hovedbokskjema');
$check(!str_contains($expenseApi, 'customer_name'), 'Utgiftsføring må bruke gjeldende hovedbokskjema');

$ticket = static fn($id, $price, $status, $reg=null) => [
    'id'=>$id,'event_id'=>'e','ticket_price'=>$price,'payment_status'=>$status,'registration_id'=>$reg,
];
$entry = static fn($id, $source, $amount, $metadata='{}') => array_replace($base, [
    'id'=>$id,'source_id'=>$source,'order_id'=>$id,'selling_price'=>$amount,'metadata'=>$metadata,
]);
$result = event_results_calculate([['id'=>'e','is_active'=>0]], [
    $ticket('group-a',100,'pending','reg'), $ticket('group-b',150,'pending','reg'),
    $ticket('manual',75,'unpaid'), $ticket('paid-missing',50,'paid'),
    $ticket('refunded',100,'refunded'), $ticket('noshow',100,'no_show'),
    $ticket('unapproved',100,'pending','waiting'), $ticket('free',0,'pending'),
    $ticket('direct',30,'pending'),
], [
    ['id'=>'reg','event_id'=>'e','approval_status'=>'approved'],
    ['id'=>'waiting','event_id'=>'e','approval_status'=>'pending'],
], [
    $entry('web','reg',250), $entry('onsite','direct',30),
    $entry('manual-income','e',20), $entry('cost','e',-40),
    $entry('conflict','manual',999,'{"event_id":"foreign"}'),
]);
$row = $result['events'][0];
$check($row['income'] === 300.0 && $row['expense'] === 40.0, 'Registration-ID, checkin-ID and event-ID payments resolve once');
$check($row['projected_income'] === 75.0 && $row['unpaid_attendee_count'] === 1, 'Settled group/direct tickets cannot also inflate forecast');
$check($row['attendee_count'] === 9 && $row['is_active'] === 0, 'All existing participants and hidden events retained');
$check($row['unreconciled_paid_count'] === 1, 'Paid tickets without ledger remain paid but flagged, never projected');
$check($row['projected_net_result'] === 335.0, 'Projected net combines paid, outstanding and expenses');
$partial = event_results_calculate([['id'=>'e']], [$ticket('a',100,'paid','reg'),$ticket('b',100,'pending','reg')],
    [['id'=>'reg','event_id'=>'e','approval_status'=>'approved']], [$entry('partial','reg',125)])['events'][0];
$check($partial['projected_income'] === 75.0, 'Partial group coverage is consumed once after paid sibling');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "events results tests passed\n";