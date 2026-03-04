<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$body = json_decode(file_get_contents('php://input'), true);
$invoiceType        = $body['invoice_type'] ?? 'general';
$customerId         = $body['customer_id'] ?? null;
$planId             = $body['plan_id'] ?? null;
$quantity           = max(1, (int)($body['quantity'] ?? 1));
$dueDate            = $body['due_date'] ?? null;
$notes              = $body['notes'] ?? null;
$membershipMemberId = $body['membership_member_id'] ?? null;
$manualAmount       = isset($body['manual_amount']) ? (float)$body['manual_amount'] : null;
$manualCustomer     = $body['manual_customer'] ?? null;

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $configStmt = $db->prepare("
        SELECT invoice_prefix, invoice_start_number, invoice_due_days, invoice_terms
        FROM tenant_companies WHERE id = ?
    ");
    $configStmt->execute([$companyId]);
    $config = $configStmt->fetch(PDO::FETCH_ASSOC);

    $prefix   = $config['invoice_prefix'] ?? 'INV';
    $startNum = (int)($config['invoice_start_number'] ?? 1000);
    $dueDays  = (int)($config['invoice_due_days'] ?? 30);
    $terms    = $config['invoice_terms'] ?? null;

    $plan = null;
    $membershipPlanId = null;

    if ($invoiceType === 'renewal') {
        if (!$membershipMemberId) {
            echo json_encode(['success' => false, 'error' => 'Mangler membre-ID for fornyelse']);
            exit;
        }
        $memberStmt = $db->prepare("
            SELECT mm.customer_id, mm.membership_plan_id,
                   mp.name, mp.price, mp.currency
            FROM membership_members mm
            LEFT JOIN membership_plans mp ON mp.id = mm.membership_plan_id
            WHERE mm.id = ? AND mm.company_id = ?
        ");
        $memberStmt->execute([$membershipMemberId, $companyId]);
        $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
        if (!$member) {
            echo json_encode(['success' => false, 'error' => 'Membre ikke funnet']);
            exit;
        }
        $customerId       = $member['customer_id'];
        $membershipPlanId = $member['membership_plan_id'];
        $plan = [
            'name'     => $member['name'],
            'price'    => $member['price'],
            'currency' => $member['currency'] ?? 'NOK'
        ];

    } elseif ($invoiceType === 'new_member') {
        if (!$customerId || !$planId) {
            echo json_encode(['success' => false, 'error' => 'Mangler kunde eller plan']);
            exit;
        }
        $planStmt = $db->prepare("
            SELECT name, price, currency FROM membership_plans
            WHERE id = ? AND company_id = ? AND is_active = 1
        ");
        $planStmt->execute([$planId, $companyId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        if (!$plan) {
            echo json_encode(['success' => false, 'error' => 'Plan ikke funnet']);
            exit;
        }
        $membershipPlanId = $planId;

    } else {
        if ($planId) {
            $planStmt = $db->prepare("
                SELECT name, price, currency FROM membership_plans
                WHERE id = ? AND company_id = ? AND is_active = 1
            ");
            $planStmt->execute([$planId, $companyId]);
            $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
            $membershipPlanId = $planId;
        }
        if (!$customerId) {
            echo json_encode(['success' => false, 'error' => 'Mangler kunde']);
            exit;
        }
    }

    if ($customerId === '__manual__' || $manualCustomer) {
        if (!$manualCustomer || empty(trim($manualCustomer['first_name'] ?? ''))) {
            echo json_encode(['success' => false, 'error' => 'Fornavn er påkrevd ved manuell utfylling']);
            exit;
        }
        $firstName = trim($manualCustomer['first_name']);
        $lastName  = trim($manualCustomer['last_name'] ?? '');
        $email     = trim($manualCustomer['email'] ?? '');
        $existingStmt = $db->prepare("
            SELECT id FROM tenant_customers
            WHERE company_id = ? AND email = ? AND email != ''
            LIMIT 1
        ");
        $existingStmt->execute([$companyId, $email]);
        $existing = $existingStmt->fetchColumn();
        if ($existing) {
            $customerId = $existing;
        } else {
            $db->prepare("
                INSERT INTO tenant_customers (company_id, first_name, last_name, email, source, created_at)
                VALUES (?, ?, ?, ?, 'invoice_manual', NOW())
            ")->execute([$companyId, $firstName, $lastName, $email ?: null]);
            $customerId = $db->lastInsertId();
        }
    }

    $db->beginTransaction();

    $numStmt = $db->prepare("
        SELECT COALESCE(
            MAX(CAST(SUBSTRING(invoice_number, LENGTH(:prefix) + 2) AS UNSIGNED)),
            :start - 1
        ) + 1 AS next_num
        FROM economy_invoices
        WHERE company_id = :company_id
          AND invoice_number LIKE CONCAT(:prefix2, '-%')
    ");
    $numStmt->execute([
        'prefix'     => $prefix,
        'start'      => $startNum,
        'company_id' => $companyId,
        'prefix2'    => $prefix
    ]);
    $nextNum       = (int)$numStmt->fetchColumn();
    $invoiceNumber = $prefix . '-' . $nextNum;

    if ($plan) {
        $subtotal    = round($plan['price'] * $quantity, 2);
        $currency    = $plan['currency'] ?? 'NOK';
    } elseif ($manualAmount !== null) {
        $subtotal    = round($manualAmount, 2);
        $currency    = 'NOK';
    } else {
        $subtotal    = 0.00;
        $currency    = 'NOK';
    }

    $totalAmount   = $subtotal;
    $invoiceDate   = date('Y-m-d');
    $calculatedDue = $dueDate ?: date('Y-m-d', strtotime("+{$dueDays} days"));
    $paymentTerms  = $notes ?: $terms;

    $insertStmt = $db->prepare("
        INSERT INTO economy_invoices
            (company_id, customer_id, invoice_number, invoice_date, due_date,
             subtotal, tax_rate, tax_amount, total_amount, currency,
             status, payment_terms, notes, sent_at,
             invoice_type, membership_plan_id, membership_member_id, created_by_admin_id)
        VALUES
            (:company_id, :customer_id, :invoice_number, :invoice_date, :due_date,
             :subtotal, 0, 0, :total_amount, :currency,
             'pending', :payment_terms, NULL, NOW(),
             :invoice_type, :membership_plan_id, :membership_member_id, :created_by_admin_id)
    ");
    $insertStmt->execute([
        'company_id'           => $companyId,
        'customer_id'          => $customerId,
        'invoice_number'       => $invoiceNumber,
        'invoice_date'         => $invoiceDate,
        'due_date'             => $calculatedDue,
        'subtotal'             => $subtotal,
        'total_amount'         => $totalAmount,
        'currency'             => $currency,
        'payment_terms'        => $paymentTerms,
        'invoice_type'         => $invoiceType,
        'membership_plan_id'   => $membershipPlanId,
        'membership_member_id' => ($invoiceType === 'renewal') ? $membershipMemberId : null,
        'created_by_admin_id'  => $_SESSION['tenant_admin_id']
    ]);

    $invoiceId = $db->lastInsertId();
    $db->commit();

    echo json_encode([
        'success'        => true,
        'invoice_id'     => $invoiceId,
        'invoice_number' => $invoiceNumber,
        'total_amount'   => $totalAmount
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("create_invoice error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke opprette faktura']);
}
