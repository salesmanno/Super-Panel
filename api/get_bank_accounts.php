<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db        = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $stmt = $db->prepare("
        SELECT id, account_number, bank_name
        FROM economy_bank_accounts
        WHERE company_id = ? AND is_active = 1
        ORDER BY bank_name ASC, account_number ASC
    ");
    $stmt->execute([$companyId]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'accounts' => $accounts]);

} catch (Exception $e) {
    error_log("get_bank_accounts error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke hente bankkontoer']);
}
