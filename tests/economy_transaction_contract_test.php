<?php
// Focused source contract regression test for the economy transaction browser.
function economy_check(bool $ok, string $message): void {
    if (!$ok) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
}
$api = file_get_contents(__DIR__ . '/../api/get_economy_data.php');
$module = file_get_contents(__DIR__ . '/../modules/economy_content.php');
foreach ([
    "FILTER_VALIDATE_INT", "max(1, min(100000", "max(1, min(100",
    "COUNT(*) FROM unified_ledger", "ul.company_id = ?", "tc.company_id = ul.company_id",
    "ORDER BY ul.transaction_date DESC, ul.id DESC", "LIMIT {\$perPage} OFFSET {\$offset}",
    "CASE WHEN ul.cost_price IS NULL THEN NULL", "ul.metadata",
    "ul.order_id", "ul.source_id", "ul.customer_id", "ul.item_type",
    "ul.payment_status", "ul.status AS ledger_status"
] as $fragment) economy_check(str_contains($api, $fragment), "API contains {$fragment}");
economy_check(str_contains($module, "['Transaksjons-ID', t.ledger_id]"), 'modal displays ledger transaction ID');
foreach ([
    "economyEsc", "data-transaction-index", "type=\"button\"", "economy-transaction-detail",
    "viser \${first}–\${last} av \${economyTransactionTotal}",
    "Laster transaksjoner", "economy-transaction-prev", "economy-transaction-next",
    "focusIndex < 0 ? economyTransactionRows.length - 1", "economyModalNavigationLoading"
] as $fragment) economy_check(str_contains($module, $fragment), "module contains {$fragment}");
echo "Economy transaction contract checks passed\n";