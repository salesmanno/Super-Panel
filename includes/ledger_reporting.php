<?php
declare(strict_types=1);

/** Read policy only: never mutate payment control, journals or historical rows. */
function ledger_reporting_prefix(string $alias): string {
    if ($alias === '') return '';
    if (!preg_match('/^[a-z][a-z0-9_]*$/iD', $alias)) throw new InvalidArgumentException('Ugyldig SQL-alias');
    return $alias . '.';
}
function ledger_reporting_paid_sql(string $alias = ''): string {
    $p = ledger_reporting_prefix($alias);
    return "({$p}status = 'active' AND {$p}payment_status = 'completed')";
}
function ledger_reporting_filter_sql(string $filter, string $alias = ''): string {
    $paid = ledger_reporting_paid_sql($alias);
    return match ($filter) {
        'completed' => $paid,
        'attempts' => "NOT COALESCE({$paid}, 0)",
        'all' => '1 = 1',
        default => throw new InvalidArgumentException('Ugyldig betalingsfilter'),
    };
}
function ledger_reporting_expense_test_sql(string $alias = ''): string {
    $p = ledger_reporting_prefix($alias);
    return "({$p}transaction_type = 'expense' OR {$p}item_type LIKE '%expense%' OR {$p}selling_price < 0)";
}
function ledger_reporting_income_sql(string $alias = ''): string {
    $p = ledger_reporting_prefix($alias);
    $expense = ledger_reporting_expense_test_sql($alias);
    // selling_price is a frozen LINE TOTAL, not a unit price.
    return "CASE WHEN {$expense} THEN 0 WHEN {$p}selling_price > 0 THEN {$p}selling_price ELSE 0 END";
}
function ledger_reporting_expense_sql(string $alias = ''): string {
    $p = ledger_reporting_prefix($alias);
    $expense = ledger_reporting_expense_test_sql($alias);
    return "CASE WHEN {$expense} THEN CASE WHEN {$p}selling_price < 0 THEN ABS({$p}selling_price)
        WHEN COALESCE({$p}cost_price, 0) > 0 THEN {$p}cost_price ELSE ABS({$p}selling_price) END ELSE 0 END";
}
function ledger_reporting_amounts(array $row): array {
    if (($row['status'] ?? null) !== 'active' || ($row['payment_status'] ?? null) !== 'completed') {
        return ['counts_as_paid' => false, 'income_amount' => 0.0, 'expense_amount' => 0.0];
    }
    $amount = (float)($row['selling_price'] ?? 0);
    $expense = ($row['transaction_type'] ?? '') === 'expense'
        || str_contains((string)($row['item_type'] ?? ''), 'expense') || $amount < 0;
    return ['counts_as_paid' => true, 'income_amount' => $expense ? 0.0 : max(0.0, $amount),
        'expense_amount' => !$expense ? 0.0 : ($amount < 0 ? abs($amount) : (max(0.0, (float)($row['cost_price'] ?? 0)) ?: abs($amount)))];
}
