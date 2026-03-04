<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$body = json_decode(file_get_contents('php://input'), true);
$customerId = $body['customer_id'] ?? null;
$planId = $body['plan_id'] ?? null;
$quantity = max(1, (int)($body['quantity'] ?? 1));
$dueDate = $body['due_date'] ?? null;
$notes = $body['notes'] ?? null;

if (!$customerId || !$planId) {
    echo json_encode(['success' => false, 'error' => 'Mangler kunde eller vare']);
    exit;
}

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $configStmt = $db->prepare("
        SELECT invoice_prefix, invoice_start_number, invoice_due_days, invoice_terms
        FROM tenant_companies WHERE id = ?
    ");
    $configStmt->execute([$companyId]);
    $config = $configStmt->fetch(PDO::FETCH_ASSOC);

    $prefix = $config['invoice_prefix'] ?? 'INV';
    $startNum = (int)($config['invoice_start_number'] ?? 1000);
    $dueDays = (int)($config['invoice_due_days'] ?? 30);
    $terms = $config['invoice_terms'] ?? null;

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
        'prefix' => $prefix,
        'start' => $startNum,
        'company_id' => $companyId,
        'prefix2' => $prefix
    ]);
    $nextNum = (int)$numStmt->fetchColumn();
    $invoiceNumber = $prefix . '-' . $nextNum;

    $subtotal = round($plan['price'] * $quantity, 2);
    $taxAmount = 0.00;
    $totalAmount = $subtotal;
    $invoiceDate = date('Y-m-d');
    $calculatedDue = $dueDate ?: date('Y-m-d', strtotime("+{$dueDays} days"));
    $paymentTerms = $notes ?: $terms;

    $insertStmt = $db->prepare("
        INSERT INTO economy_invoices
            (company_id, customer_id, invoice_number, invoice_date, due_date,
             subtotal, tax_rate, tax_amount, total_amount, currency,
             status, payment_terms, notes, sent_at)
        VALUES
            (:company_id, :customer_id, :invoice_number, :invoice_date, :due_date,
             :subtotal, 0, :tax_amount, :total_amount, :currency,
             'sent', :payment_terms, :notes, NOW())
    ");
    $insertStmt->execute([
        'company_id' => $companyId,
        'customer_id' => $customerId,
        'invoice_number' => $invoiceNumber,
        'invoice_date' => $invoiceDate,
        'due_date' => $calculatedDue,
        'subtotal' => $subtotal,
        'tax_amount' => $taxAmount,
        'total_amount' => $totalAmount,
        'currency' => $plan['currency'] ?? 'NOK',
        'payment_terms' => $paymentTerms,
        'notes' => null
    ]);

    $invoiceId = $db->lastInsertId();
    $db->commit();

    echo json_encode([
        'success' => true,
        'invoice_id' => $invoiceId,
        'invoice_number' => $invoiceNumber,
        'total_amount' => $totalAmount
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log("create_invoice error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke opprette faktura']);
}
