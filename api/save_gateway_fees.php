<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Kun POST tillatt']);
    exit;
}

if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$body       = json_decode(file_get_contents('php://input'), true);
$gatewayId  = $body['gateway_id']  ?? null;
$feeFixed   = isset($body['fee_fixed'])   ? (float)$body['fee_fixed']   : null;
$feePercent = isset($body['fee_percent']) ? (float)$body['fee_percent'] : null;

if (!$gatewayId || $feeFixed === null || $feePercent === null) {
    echo json_encode(['success' => false, 'error' => 'Mangler gateway_id, fee_fixed eller fee_percent']);
    exit;
}

if ($feeFixed < 0 || $feePercent < 0 || $feePercent > 100) {
    echo json_encode(['success' => false, 'error' => 'Ugyldige gebyr-verdier']);
    exit;
}

try {
    $db        = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $checkStmt = $db->prepare("
        SELECT id FROM economy_configuration_data
        WHERE id = ? AND company_id = ?
    ");
    $checkStmt->execute([$gatewayId, $companyId]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Gateway ikke funnet']);
        exit;
    }

    $db->prepare("
        UPDATE economy_configuration_data
        SET fee_fixed = ?, fee_percent = ?, updated_at = NOW()
        WHERE id = ? AND company_id = ?
    ")->execute([$feeFixed, $feePercent, $gatewayId, $companyId]);

    echo json_encode([
        'success'     => true,
        'fee_fixed'   => $feeFixed,
        'fee_percent' => $feePercent
    ]);

} catch (Exception $e) {
    error_log("save_gateway_fees error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke lagre gebyr']);
}
