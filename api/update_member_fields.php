<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$company_id = $_SESSION['company_id'];
$data       = json_decode(file_get_contents('php://input'), true);

$membership_member_id = trim($data['membership_member_id'] ?? '');
$payment_status       = trim($data['payment_status']       ?? '');
$expiration_date      = trim($data['expiration_date']      ?? '');

$allowed_payment = ['paid', 'unpaid', 'pending', 'refunded'];

if ($membership_member_id === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'membership_member_id mangler']);
    exit;
}
if (!in_array($payment_status, $allowed_payment, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig payment_status']);
    exit;
}
if ($expiration_date !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $expiration_date)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig datoformat (forventet YYYY-MM-DD)']);
    exit;
}

try {
    $db = getDBConnection();

    $exp_value = ($expiration_date !== '') ? $expiration_date : null;

    $stmt = $db->prepare("
        UPDATE membership_members
        SET payment_status  = ?,
            expiration_date = ?
        WHERE id         = ?
          AND company_id = ?
    ");
    $stmt->execute([$payment_status, $exp_value, $membership_member_id, $company_id]);

    if ($stmt->rowCount() === 0) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Medlem ikke funnet']);
        exit;
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
