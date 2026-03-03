<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

try {
    $db        = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $stmt = $db->prepare("
        SELECT
            mmr.id             AS renewal_id,
            mmr.membership_member_id,
            mmr.amount,
            mmr.payment_method,
            mmr.completed_at,
            mmr.created_at     AS renew_created_at,
            mmr.new_expiry_date,
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
        WHERE mmr.status    = 'completed'
          AND mm.company_id = ?
          AND ul.id IS NULL
        ORDER BY mmr.completed_at DESC
    ");
    $stmt->execute([$companyId]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo json_encode([
            'success'  => true,
            'inserted' => 0,
            'skipped'  => 0,
            'errors'   => 0,
            'details'  => [],
            'message'  => 'Ingen fornyelser mangler ledger-oppføring.',
        ]);
        exit;
    }

    $ledger   = new UnifiedLedgerService($db, $companyId);
    $inserted = 0;
    $skipped  = 0;
    $errors   = 0;
    $details  = [];

    foreach ($rows as $r) {
        if (empty($r['renewal_id']) || empty($r['customer_id']) || empty($r['company_id'])) {
            $skipped++;
            $details[] = ['status' => 'skipped', 'reason' => 'Manglende nøkkeldata', 'renewal_id' => $r['renewal_id'] ?? null];
            continue;
        }

        try {
            $sellingPrice    = (float)($r['amount']    ?? $r['plan_price'] ?? 0);
            $paymentMethod   = $r['payment_method']    ?? 'renewal';
            $transactionDate = $r['completed_at']      ?? $r['renew_created_at'] ?? date('Y-m-d H:i:s');
            $customerName    = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
            $shortId         = substr($r['renewal_id'], 0, 8);
            $dateTag         = date('Ymd', strtotime($transactionDate));

            $result = $ledger->logTransaction([
                'order_id'         => "MMR-{$shortId}-{$dateTag}",
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
                    'member_id'      => $r['membership_member_id'],
                    'renewal_id'     => $r['renewal_id'],
                    'new_expiry_date'=> $r['new_expiry_date'] ?? null,
                    'customer_name'  => $customerName,
                    'customer_email' => $r['email'] ?? null,
                ],
            ]);

            if ($result['success']) {
                $inserted++;
                $details[] = ['status' => 'inserted', 'name' => $customerName, 'plan' => $r['plan_name'], 'amount' => $sellingPrice, 'date' => $transactionDate];
            } else {
                $errors++;
                $details[] = ['status' => 'error', 'reason' => $result['message'] ?? 'Ukjent feil', 'name' => $customerName];
            }
        } catch (\Throwable $e) {
            $errors++;
            $details[] = ['status' => 'error', 'reason' => $e->getMessage(), 'renewal_id' => $r['renewal_id']];
        }
    }

    echo json_encode([
        'success'  => true,
        'inserted' => $inserted,
        'skipped'  => $skipped,
        'errors'   => $errors,
        'details'  => $details,
        'message'  => "{$inserted} oppføring(er) lagt til, {$skipped} hoppet over, {$errors} feil.",
    ]);

} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
