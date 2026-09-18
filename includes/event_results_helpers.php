<?php

function event_results_sql_alias(string $alias): string {
    if (!preg_match('/^[a-z][a-z0-9_]*$/i', $alias)) {
        throw new InvalidArgumentException('Ugyldig SQL-alias');
    }
    return $alias;
}

function event_results_ledger_event_id_sql(string $ledgerAlias): string {
    $ledgerAlias = event_results_sql_alias($ledgerAlias);
    return "COALESCE(
        NULLIF(JSON_UNQUOTE(JSON_EXTRACT({$ledgerAlias}.metadata, '$.event_id')), ''),
        {$ledgerAlias}.source_id
    )";
}

function event_results_ledger_valid_sql(string $ledgerAlias): string {
    $ledgerAlias = event_results_sql_alias($ledgerAlias);
    return "{$ledgerAlias}.module_source = 'events'
        AND {$ledgerAlias}.status = 'active'
        AND {$ledgerAlias}.payment_status = 'completed'";
}

function event_results_ledger_is_expense_sql(string $ledgerAlias): string {
    $ledgerAlias = event_results_sql_alias($ledgerAlias);
    return "(
        {$ledgerAlias}.transaction_type = 'expense'
        OR {$ledgerAlias}.item_type LIKE '%expense%'
        OR {$ledgerAlias}.selling_price < 0
    )";
}

function event_results_ledger_income_sql(string $ledgerAlias): string {
    $ledgerAlias = event_results_sql_alias($ledgerAlias);
    $isExpense = event_results_ledger_is_expense_sql($ledgerAlias);
    return "CASE
        WHEN {$isExpense} THEN 0
        WHEN {$ledgerAlias}.selling_price > 0 THEN {$ledgerAlias}.selling_price
        ELSE 0
    END";
}

function event_results_ledger_expense_sql(string $ledgerAlias): string {
    $ledgerAlias = event_results_sql_alias($ledgerAlias);
    $isExpense = event_results_ledger_is_expense_sql($ledgerAlias);
    return "CASE
        WHEN {$isExpense} THEN
            CASE
                WHEN {$ledgerAlias}.selling_price < 0 THEN ABS({$ledgerAlias}.selling_price)
                WHEN COALESCE({$ledgerAlias}.cost_price, 0) > 0 THEN {$ledgerAlias}.cost_price
                ELSE ABS({$ledgerAlias}.selling_price)
            END
        ELSE 0
    END";
}

function event_results_ledger_amounts(array $row): array {
    if (
        strtolower((string) ($row['module_source'] ?? '')) !== 'events'
        || strtolower((string) ($row['status'] ?? '')) !== 'active'
        || strtolower((string) ($row['payment_status'] ?? '')) !== 'completed'
    ) {
        return ['income' => 0.0, 'expense' => 0.0];
    }

    $sellingPrice = (float) ($row['selling_price'] ?? 0);
    $costPrice = (float) ($row['cost_price'] ?? 0);
    $transactionType = strtolower((string) ($row['transaction_type'] ?? ''));
    $itemType = strtolower((string) ($row['item_type'] ?? ''));
    $isExpense = $transactionType === 'expense'
        || str_contains($itemType, 'expense')
        || $sellingPrice < 0;

    if (!$isExpense) {
        return ['income' => max(0.0, $sellingPrice), 'expense' => 0.0];
    }

    $expense = $sellingPrice < 0
        ? abs($sellingPrice)
        : ($costPrice > 0 ? $costPrice : abs($sellingPrice));

    return ['income' => 0.0, 'expense' => $expense];
}

function event_results_summary(array $events): array {
    $summary = [
        'total_events' => count($events),
        'total_revenue' => 0.0,
        'total_expense' => 0.0,
        'net_result' => 0.0,
        'total_attendees' => 0,
        'upcoming_events' => 0,
        'most_profitable' => ['title' => 'N/A', 'profit' => 0.0],
        'most_expensive' => ['title' => 'N/A', 'price' => 0.0],
        'most_attended' => ['title' => 'N/A', 'attendees' => 0],
    ];

    $mostProfitable = null;
    $mostExpensive = null;
    $mostAttended = null;

    foreach ($events as $event) {
        $income = (float) ($event['income'] ?? 0);
        $expense = (float) ($event['expense'] ?? 0);
        $net = (float) ($event['net_result'] ?? ($income - $expense));
        $attendees = (int) ($event['attendee_count'] ?? 0);
        $ticketPrice = (float) ($event['ticket_price'] ?? 0);

        $summary['total_revenue'] += $income;
        $summary['total_expense'] += $expense;
        $summary['net_result'] += $net;
        $summary['total_attendees'] += $attendees;
        if (($event['status'] ?? '') === 'Kommende') {
            $summary['upcoming_events']++;
        }

        if ($mostProfitable === null || $net > $mostProfitable['value']) {
            $mostProfitable = ['title' => (string) ($event['title'] ?? ''), 'value' => $net];
        }
        if ($mostExpensive === null || $ticketPrice > $mostExpensive['value']) {
            $mostExpensive = ['title' => (string) ($event['title'] ?? ''), 'value' => $ticketPrice];
        }
        if ($mostAttended === null || $attendees > $mostAttended['value']) {
            $mostAttended = ['title' => (string) ($event['title'] ?? ''), 'value' => $attendees];
        }
    }

    if ($mostProfitable !== null) {
        $summary['most_profitable'] = [
            'title' => $mostProfitable['title'],
            'profit' => round($mostProfitable['value'], 2),
        ];
    }
    if ($mostExpensive !== null) {
        $summary['most_expensive'] = [
            'title' => $mostExpensive['title'],
            'price' => round($mostExpensive['value'], 2),
        ];
    }
    if ($mostAttended !== null) {
        $summary['most_attended'] = [
            'title' => $mostAttended['title'],
            'attendees' => $mostAttended['value'],
        ];
    }

    $summary['total_revenue'] = round($summary['total_revenue'], 2);
    $summary['total_expense'] = round($summary['total_expense'], 2);
    $summary['net_result'] = round($summary['net_result'], 2);

    return $summary;
}