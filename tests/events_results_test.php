<?php

require_once __DIR__ . '/../includes/event_results_helpers.php';

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
        'expense' => 40,
        'net_result' => 85,
        'attendee_count' => 4,
        'ticket_price' => 100,
        'status' => 'Avsluttet',
    ],
    [
        'title' => 'Vinterfest',
        'income' => 75,
        'expense' => 100,
        'net_result' => -25,
        'attendee_count' => 8,
        'ticket_price' => 200,
        'status' => 'Kommende',
    ],
];
$summary = event_results_summary($events);
$check($summary['total_events'] === 2, 'Alle aktive arrangementer må telles');
$check($summary['total_revenue'] === 200.0, 'Inntekt må summeres fra arrangementsradene');
$check($summary['total_expense'] === 140.0, 'Utgift må summeres fra arrangementsradene');
$check($summary['net_result'] === 60.0, 'Netto må stemme med inntekt minus utgift');
$check($summary['total_attendees'] === 12, 'Deltakere må summeres likt');
$check($summary['upcoming_events'] === 1, 'Kommende arrangementer må telles');
$check($summary['most_profitable']['title'] === 'Sommerfest', 'Mest lønnsomme arrangement må bruke beregnet netto');
$check($summary['most_expensive']['title'] === 'Vinterfest', 'Dyreste arrangement må bruke billettpris');
$check($summary['most_attended']['title'] === 'Vinterfest', 'Mest besøkte arrangement må bruke deltakertall');

$resultsApi = file_get_contents(__DIR__ . '/../api/get_events_results.php');
$financesApi = file_get_contents(__DIR__ . '/../api/get_event_finances.php');
$transactionsApi = file_get_contents(__DIR__ . '/../api/get_event_transactions.php');
$incomeApi = file_get_contents(__DIR__ . '/../api/add_event_income.php');
$expenseApi = file_get_contents(__DIR__ . '/../api/add_event_expense.php');

$check(!str_contains($resultsApi, 'FROM events_results'), 'Resultat-API-et må ikke velge den utdaterte resultattabellen');
$check(!str_contains($resultsApi, "SHOW TABLES LIKE 'events_results'"), 'Tabell-eksistens må ikke styre beregningsmodellen');
$check(str_contains($resultsApi, 'FROM events_eventdata e'), 'Arrangementstabellen må drive resultatlisten');
$check(str_contains($resultsApi, 'FROM events_checkin ec'), 'Deltakere må komme fra innsjekkingstabellen');
$check(str_contains($resultsApi, 'event_results_ledger_valid_sql'), 'Oversikten må bruke de delte hovedbokfiltrene');
$check(str_contains($financesApi, 'event_results_ledger_valid_sql'), 'Økonomidetaljene må bruke samme hovedbokfiltre');
$check(str_contains($transactionsApi, 'event_results_ledger_valid_sql'), 'Transaksjonsdetaljene må bruke samme hovedbokfiltre');
$check(str_contains($transactionsApi, "'net_result' => \$netResult"), 'Detaljmodalen må få forventet nettofelt');
$check(!str_contains($incomeApi, 'customer_name'), 'Inntektsføring må bruke gjeldende hovedbokskjema');
$check(!str_contains($expenseApi, 'customer_name'), 'Utgiftsføring må bruke gjeldende hovedbokskjema');

if ($failures) {
    fwrite(STDERR, implode(PHP_EOL, $failures) . PHP_EOL);
    exit(1);
}

echo "events results tests passed\n";