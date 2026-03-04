<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$body = json_decode(file_get_contents('php://input'), true);
$invoiceId = $body['invoice_id'] ?? null;

if (!$invoiceId) {
    echo json_encode(['success' => false, 'error' => 'Mangler faktura-ID']);
    exit;
}

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $stmt = $db->prepare("
        UPDATE economy_invoices
        SET status = 'paid', paid_at = NOW()
        WHERE id = ? AND company_id = ? AND status != 'paid'
    ");
    $stmt->execute([$invoiceId, $companyId]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Faktura ikke funnet eller allerede betalt']);
        exit;
    }

    $paidAt = date('Y-m-d H:i:s');
    echo json_encode(['success' => true, 'invoice_id' => $invoiceId, 'paid_at' => $paidAt]);

} catch (Exception $e) {
    error_log("mark_invoice_paid error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke oppdatere faktura']);
}
