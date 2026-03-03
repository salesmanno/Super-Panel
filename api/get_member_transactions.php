<?php
ob_start();
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Server-feil: ' . $error['message']]);
        exit;
    }
});

session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

$memberId = $_GET['member_id'] ?? '';
if (empty($memberId)) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'member_id mangler']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $stmt = $db->prepare("
        SELECT
            transaction_date,
            item_name,
            selling_price,
            payment_method,
            payment_status
        FROM unified_ledger
        WHERE source_id  = ?
          AND module_source = 'membership'
          AND company_id = ?
        ORDER BY transaction_date DESC
    ");
    $stmt->execute([$memberId, $companyId]);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode(['success' => true, 'transactions' => $transactions]);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
