<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$body      = json_decode(file_get_contents('php://input'), true);
$invoiceId = $body['invoice_id'] ?? null;

if (!$invoiceId) {
    echo json_encode(['success' => false, 'error' => 'Mangler faktura-ID']);
    exit;
}

try {
    $db        = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $stmt = $db->prepare("
        UPDATE economy_invoices
        SET status = 'dismantled', status_updated_at = NOW()
        WHERE id = ? AND company_id = ? AND status IN ('pending', 'sent')
    ");
    $stmt->execute([$invoiceId, $companyId]);

    if ($stmt->rowCount() === 0) {
        echo json_encode(['success' => false, 'error' => 'Faktura ikke funnet eller kan ikke ugyldiggjøres (allerede betalt/ugyldiggjort)']);
        exit;
    }

    echo json_encode(['success' => true, 'invoice_id' => $invoiceId]);

} catch (Exception $e) {
    error_log("void_invoice error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Feil ved ugyldiggjøring']);
}
