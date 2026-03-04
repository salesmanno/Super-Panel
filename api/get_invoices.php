<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db        = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $migrations = [
        "ALTER TABLE economy_invoices ADD COLUMN IF NOT EXISTS invoice_type VARCHAR(20) NOT NULL DEFAULT 'general'",
        "ALTER TABLE economy_invoices ADD COLUMN IF NOT EXISTS membership_plan_id CHAR(36) DEFAULT NULL",
        "ALTER TABLE economy_invoices ADD COLUMN IF NOT EXISTS membership_member_id CHAR(36) DEFAULT NULL",
        "ALTER TABLE economy_invoices ADD COLUMN IF NOT EXISTS created_by_admin_id CHAR(36) DEFAULT NULL",
        "ALTER TABLE economy_invoices ADD COLUMN IF NOT EXISTS status_updated_at DATETIME DEFAULT NULL",
    ];
    foreach ($migrations as $sql) {
        try { $db->exec($sql); } catch (Exception $e) { /* kolonne eksisterer allerede */ }
    }

    $stmt = $db->prepare("
        SELECT
            i.id AS invoice_id,
            i.invoice_number,
            i.invoice_date,
            i.due_date,
            i.status,
            i.subtotal,
            i.tax_rate,
            i.tax_amount,
            i.total_amount,
            i.currency,
            i.customer_id,
            i.notes,
            i.payment_terms,
            i.sent_at,
            i.paid_at,
            i.created_at,
            i.invoice_type,
            i.membership_plan_id,
            i.membership_member_id,
            TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, ''))) AS customer_name,
            c.email AS customer_email
        FROM economy_invoices i
        LEFT JOIN tenant_customers c ON c.id = i.customer_id
        WHERE i.company_id = ?
          AND i.status != 'dismantled'
        ORDER BY i.created_at DESC
    ");
    $stmt->execute([$companyId]);
    $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $today = date('Y-m-d');
    foreach ($invoices as &$inv) {
        $inv['computed_overdue'] = (
            in_array($inv['status'], ['pending', 'sent']) &&
            $inv['due_date'] < $today
        );
        $inv['customer_name'] = trim($inv['customer_name']);
    }
    unset($inv);

    $configStmt = $db->prepare("
        SELECT invoice_prefix, invoice_start_number, invoice_due_days, invoice_terms
        FROM tenant_companies WHERE id = ?
    ");
    $configStmt->execute([$companyId]);
    $config = $configStmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'        => true,
        'invoices'       => $invoices,
        'invoice_config' => $config ?: [
            'invoice_prefix'       => 'INV',
            'invoice_start_number' => 1000,
            'invoice_due_days'     => 30,
            'invoice_terms'        => ''
        ]
    ]);

} catch (Exception $e) {
    error_log("get_invoices error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke hente fakturaer: ' . $e->getMessage()]);
}
