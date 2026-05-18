<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$companyId = $_SESSION['company_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $db->beginTransaction();

    $stmt = $db->prepare("SELECT id, company_id, approved, status FROM rental_schedule WHERE id = ? AND company_id = ?");
    $stmt->execute([$data['id'], $companyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Forespørsel ikke funnet']);
        exit;
    }

    $updateFields = [];
    $updateParams = [];

    if (isset($data['approved']) && in_array((int)$data['approved'], [0, 1, 2], true)) {
        $updateFields[] = 'approved = ?';
        $updateParams[] = (int) $data['approved'];
    }

    $allowedStatuses = ['pending', 'approved', 'declined', 'completed', 'cancelled'];
    if (!empty($data['status']) && in_array($data['status'], $allowedStatuses)) {
        $updateFields[] = 'status = ?';
        $updateParams[] = $data['status'];
    }

    if (array_key_exists('pickup_time', $data)) {
        $pt = $data['pickup_time'];
        $updateFields[] = 'pickup_time = ?';
        $updateParams[] = ($pt !== null && $pt !== '') ? $pt : null;
    }

    if (array_key_exists('end_time', $data)) {
        $et = $data['end_time'];
        $updateFields[] = 'end_time = ?';
        $updateParams[] = ($et !== null && $et !== '') ? $et : null;
    }

    if (empty($updateFields)) {
        $db->rollBack();
        echo json_encode(['success' => true, 'message' => 'Ingen endringer']);
        exit;
    }

    $updateParams[] = $data['id'];
    $updateParams[] = $companyId;

    $db->prepare("UPDATE rental_schedule SET " . implode(', ', $updateFields) . " WHERE id = ? AND company_id = ?")
       ->execute($updateParams);

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Forespørsel oppdatert']);

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('update_rental_schedule error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Feil ved oppdatering. Prøv igjen.']);
}
