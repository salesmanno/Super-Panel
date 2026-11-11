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
        SELECT ba.id, ba.account_number, ba.bank_name
        FROM economy_bank_accounts ba
        JOIN economy_configuration_data c ON c.company_id=ba.company_id
             AND LOWER(c.gateway_type)='faktura' AND c.is_active=1
        WHERE ba.company_id = ? AND ba.is_active = 1
          AND ba.account_number IS NOT NULL AND ba.account_number <> ''
          AND (SELECT COUNT(*) FROM economy_bank_accounts x
               WHERE x.company_id=ba.company_id AND x.is_active=1
                 AND x.account_number IS NOT NULL AND x.account_number<>'')=1
        ORDER BY ba.bank_name ASC
    ");
    $stmt->execute([$companyId]);
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'accounts' => $accounts]);

} catch (Exception $e) {
    error_log("get_bank_accounts error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke hente bankkontoer']);
}
