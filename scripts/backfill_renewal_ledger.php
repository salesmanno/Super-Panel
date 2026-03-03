#!/usr/bin/env php
<?php
/**
 * Backfill ledger entries for completed membership renewals
 *
 * Usage:
 *   php scripts/backfill_renewal_ledger.php [--dry-run] [--company-id=UUID]
 *
 * Options:
 *   --dry-run         Show what would be inserted without writing to DB
 *   --company-id=UUID Run only for a specific company (omit to run for all companies)
 */

define('RUNNING_FROM_CLI', true);

$isDryRun  = in_array('--dry-run', $argv);
$companyId = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--company-id=')) {
        $companyId = trim(substr($arg, strlen('--company-id=')));
    }
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

$startTime = date('Y-m-d H:i:s');
echo "[{$startTime}] Ledger backfill started" . ($isDryRun ? ' [DRY RUN]' : '') . "\n";

try {
    $db = getDBConnection();

    $hasNewExpiry = (bool) $db->query(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
         AND TABLE_NAME = 'membership_members_renew'
         AND COLUMN_NAME = 'new_expiry_date'"
    )->fetchColumn();

    $expirySelect = $hasNewExpiry ? 'mmr.new_expiry_date,' : '';

    $companyWhere  = '';
    $companyParams = [];
    if ($companyId) {
        $companyWhere  = 'AND mm.company_id = ?';
        $companyParams = [$companyId];
        echo "[" . date('Y-m-d H:i:s') . "] Begrenset til company_id: {$companyId}\n";
    }

    $stmt = $db->prepare("
        SELECT
            mmr.id             AS renewal_id,
            mmr.membership_member_id,
            mmr.amount,
            mmr.payment_method,
            mmr.completed_at,
            mmr.created_at     AS renew_created_at,
            {$expirySelect}
            mm.customer_id,
            mm.company_id,
            p.name             AS plan_name,
            p.price            AS plan_price,
            c.first_name,
            c.last_name,
            c.email
        FROM membership_members_renew mmr
        JOIN membership_members mm ON mm.id   = mmr.membership_member_id
        JOIN membership_plans   p  ON p.id    = mm.membership_plan_id
        JOIN tenant_customers   c  ON c.id    = mm.customer_id
        LEFT JOIN unified_ledger ul
            ON ul.module_source = 'membership'
           AND ul.source_id     = mmr.id
        WHERE mmr.status = 'completed'
          AND ul.id IS NULL
          {$companyWhere}
        ORDER BY mmr.completed_at DESC
    ");
    $stmt->execute($companyParams);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $found = count($rows);
    echo "[" . date('Y-m-d H:i:s') . "] Fant {$found} fornyelse(r) uten ledger-oppføring.\n";

    if ($found === 0) {
        echo "[" . date('Y-m-d H:i:s') . "] Ingenting å gjøre. Avslutter.\n";
        exit(0);
    }

    $inserted = 0;
    $skipped  = 0;
    $errors   = 0;

    foreach ($rows as $r) {
        $customerName = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));

        if (empty($r['renewal_id']) || empty($r['customer_id']) || empty($r['company_id'])) {
            $skipped++;
            echo "[" . date('Y-m-d H:i:s') . "] HOPP: Manglende nøkkeldata for renewal_id=" . ($r['renewal_id'] ?? 'N/A') . "\n";
            continue;
        }

        $sellingPrice    = (float)($r['amount']     ?? $r['plan_price'] ?? 0);
        $paymentMethod   = $r['payment_method']     ?? 'renewal';
        $rawDate         = $r['completed_at'] ?? $r['renew_created_at'] ?? null;
        $transactionDate = $rawDate ?? date('Y-m-d H:i:s');
        $shortId         = substr($r['renewal_id'], 0, 8);
        $dateTag         = date('Ymd', strtotime($transactionDate));
        $orderId         = "MMR-{$shortId}-{$dateTag}";

        echo "[" . date('Y-m-d H:i:s') . "] " . ($isDryRun ? '[DRY] ' : '') .
             "Insert: {$customerName} | {$r['plan_name']} | {$sellingPrice} NOK | {$transactionDate}\n";

        if ($isDryRun) {
            $inserted++;
            continue;
        }

        try {
            $ledger = new UnifiedLedgerService($db, $r['company_id']);
            $result = $ledger->logTransaction([
                'order_id'         => $orderId,
                'module_source'    => 'membership',
                'item_type'        => 'membership_renewal',
                'source_id'        => $r['renewal_id'],
                'item_name'        => ($r['plan_name'] ?? 'Medlemskap') . ' - Fornyelse',
                'quantity'         => 1,
                'selling_price'    => $sellingPrice,
                'cost_price'       => null,
                'customer_id'      => $r['customer_id'],
                'payment_method'   => $paymentMethod,
                'payment_status'   => 'completed',
                'status'           => 'active',
                'transaction_type' => 'income',
                'category'         => 'Medlemskap',
                'transaction_date' => $transactionDate,
                'metadata'         => [
                    'member_id'       => $r['membership_member_id'],
                    'renewal_id'      => $r['renewal_id'],
                    'new_expiry_date' => $r['new_expiry_date'] ?? null,
                    'customer_name'   => $customerName,
                    'customer_email'  => $r['email'] ?? null,
                ],
            ]);

            if ($result['success']) {
                $inserted++;
                echo "[" . date('Y-m-d H:i:s') . "] OK: {$customerName}\n";
            } else {
                $errors++;
                echo "[" . date('Y-m-d H:i:s') . "] FEIL: {$customerName} — " . ($result['message'] ?? 'Ukjent feil') . "\n";
            }
        } catch (\Throwable $e) {
            $errors++;
            echo "[" . date('Y-m-d H:i:s') . "] EXCEPTION: " . $e->getMessage() . "\n";
        }
    }

    echo "\n[" . date('Y-m-d H:i:s') . "] === FERDIG" . ($isDryRun ? ' (DRY RUN)' : '') . " ===\n";
    echo "  Insertet    : {$inserted}\n";
    echo "  Hoppet over : {$skipped}\n";
    echo "  Feil        : {$errors}\n";

} catch (\Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] KRITISK FEIL: " . $e->getMessage() . "\n";
    exit(1);
}
