<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

$body      = json_decode(file_get_contents('php://input'), true);
$invoiceId = $body['invoice_id'] ?? null;

if (!$invoiceId) {
    echo json_encode(['success' => false, 'error' => 'Mangler faktura-ID']);
    exit;
}

function billingCycleToMonths(string $cycle): int {
    return match($cycle) {
        'monthly'   => 1,
        'quarterly' => 3,
        'yearly'    => 12,
        default     => 12
    };
}

try {
    $db        = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $invStmt = $db->prepare("
        SELECT id, invoice_number, invoice_type, membership_plan_id,
               membership_member_id, customer_id, total_amount, currency, status
        FROM economy_invoices
        WHERE id = ? AND company_id = ?
    ");
    $invStmt->execute([$invoiceId, $companyId]);
    $invoice = $invStmt->fetch(PDO::FETCH_ASSOC);

    if (!$invoice) {
        echo json_encode(['success' => false, 'error' => 'Faktura ikke funnet']);
        exit;
    }
    if ($invoice['status'] === 'paid') {
        echo json_encode(['success' => false, 'error' => 'Faktura er allerede betalt']);
        exit;
    }

    $db->beginTransaction();

    $db->prepare("
        UPDATE economy_invoices SET status = 'paid', paid_at = NOW()
        WHERE id = ? AND company_id = ?
    ")->execute([$invoiceId, $companyId]);

    $ledger      = new UnifiedLedgerService($db, $companyId);
    $invoiceType = $invoice['invoice_type'] ?? 'general';
    $result      = ['success' => true, 'action' => 'marked_paid'];

    if ($invoiceType === 'new_member') {
        $planStmt = $db->prepare("
            SELECT id, name, price, currency, billing_cycle
            FROM membership_plans
            WHERE id = ? AND company_id = ? AND is_active = 1
        ");
        $planStmt->execute([$invoice['membership_plan_id'], $companyId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'Plan ikke funnet — kan ikke aktivere membre']);
            exit;
        }

        $months         = billingCycleToMonths($plan['billing_cycle']);
        $expirationDate = date('Y-m-d', strtotime("+{$months} months"));
        $memberCode     = 'MEM-' . strtoupper(substr(md5(uniqid('', true)), 0, 8));

        $db->prepare("
            INSERT INTO membership_members
                (company_id, customer_id, membership_plan_id, member_code,
                 status, join_date, expiration_date, payment_status, paymentMethod)
            VALUES
                (?, ?, ?, ?, 'active', CURDATE(), ?, 'paid', 'faktura')
        ")->execute([
            $companyId,
            $invoice['customer_id'],
            $plan['id'],
            $memberCode,
            $expirationDate
        ]);

        $ledger->logTransaction([
            'order_id'         => $invoice['invoice_number'],
            'module_source'    => 'membership',
            'item_type'        => 'membership_new',
            'source_id'        => $invoiceId,
            'item_name'        => $plan['name'] . ' — Nytt medlemskap (faktura)',
            'quantity'         => 1,
            'selling_price'    => $invoice['total_amount'],
            'cost_price'       => null,
            'customer_id'      => $invoice['customer_id'],
            'payment_method'   => 'faktura',
            'payment_status'   => 'completed',
            'status'           => 'active',
            'transaction_date' => date('Y-m-d'),
            'metadata'         => [
                'member_code'     => $memberCode,
                'expiration_date' => $expirationDate,
                'invoice_id'      => $invoiceId
            ]
        ]);

        $result = [
            'success'         => true,
            'action'          => 'new_member_activated',
            'member_code'     => $memberCode,
            'expiration_date' => $expirationDate
        ];

    } elseif ($invoiceType === 'renewal') {
        $memStmt = $db->prepare("
            SELECT mm.id, mm.member_code, mm.membership_plan_id,
                   mm.expiration_date, mm.customer_id,
                   mp.name AS plan_name, mp.billing_cycle, mp.price, mp.currency
            FROM membership_members mm
            LEFT JOIN membership_plans mp ON mp.id = mm.membership_plan_id
            WHERE mm.id = ? AND mm.company_id = ?
        ");
        $memStmt->execute([$invoice['membership_member_id'], $companyId]);
        $member = $memStmt->fetch(PDO::FETCH_ASSOC);

        if (!$member) {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'Membre ikke funnet — kan ikke fornye']);
            exit;
        }

        $months      = billingCycleToMonths($member['billing_cycle'] ?? 'yearly');
        $baseDate    = ($member['expiration_date'] && $member['expiration_date'] > date('Y-m-d'))
                       ? $member['expiration_date']
                       : date('Y-m-d');
        $newExpiry   = date('Y-m-d', strtotime($baseDate . " +{$months} months"));

        $db->prepare("
            UPDATE membership_members
            SET expiration_date = ?, status = 'active', payment_status = 'paid'
            WHERE id = ? AND company_id = ?
        ")->execute([$newExpiry, $member['id'], $companyId]);

        $orderId = 'INV-PAY-' . $invoice['invoice_number'];
        $db->prepare("
            INSERT INTO membership_members_renew
                (company_id, member_code, membership_member_id, membership_plan_id,
                 order_id, amount, payment_method, status, completed_at, expires_at)
            VALUES
                (?, ?, ?, ?, ?, ?, 'faktura', 'completed', NOW(), ?)
        ")->execute([
            $companyId,
            $member['member_code'],
            $member['id'],
            $member['membership_plan_id'],
            $orderId,
            $invoice['total_amount'],
            $newExpiry
        ]);

        $ledger->logTransaction([
            'order_id'         => $orderId,
            'module_source'    => 'membership',
            'item_type'        => 'membership_renewal',
            'source_id'        => $member['id'],
            'item_name'        => ($member['plan_name'] ?? 'Medlemskap') . ' — Fornyelse (faktura)',
            'quantity'         => 1,
            'selling_price'    => $invoice['total_amount'],
            'cost_price'       => null,
            'customer_id'      => $member['customer_id'],
            'payment_method'   => 'faktura',
            'payment_status'   => 'completed',
            'status'           => 'active',
            'transaction_date' => date('Y-m-d'),
            'metadata'         => [
                'member_code'         => $member['member_code'],
                'new_expiration_date' => $newExpiry,
                'invoice_id'          => $invoiceId
            ]
        ]);

        $result = [
            'success'             => true,
            'action'              => 'renewal_completed',
            'new_expiration_date' => $newExpiry
        ];
    }

    $db->commit();
    echo json_encode($result);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("mark_invoice_paid error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke behandle betaling: ' . $e->getMessage()]);
}
