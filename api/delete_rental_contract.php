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
    echo json_encode(['success' => false, 'message' => 'Kontrakt-ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $db->beginTransaction();

    $stmt = $db->prepare("
        SELECT rc.*, ro.is_available
        FROM rental_contracts rc
        INNER JOIN rental_objects ro ON ro.id = rc.rental_object_id
        WHERE rc.id = ? AND rc.company_id = ?
    ");
    $stmt->execute([$data['id'], $companyId]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contract) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Kontrakt ikke funnet']);
        exit;
    }

    if ($contract['status'] !== 'draft') {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Kun utkast-kontrakter kan slettes. Kanseller kontrakten først.'
        ]);
        exit;
    }

    $db->prepare("DELETE FROM rental_contracts WHERE id = ? AND company_id = ?")
       ->execute([$data['id'], $companyId]);

    $remainingStmt = $db->prepare("
        SELECT COUNT(*) FROM rental_contracts
        WHERE rental_object_id = ? AND company_id = ? AND status = 'active'
    ");
    $remainingStmt->execute([$contract['rental_object_id'], $companyId]);
    if ($remainingStmt->fetchColumn() == 0) {
        $db->prepare("UPDATE rental_objects SET is_available = 1 WHERE id = ? AND company_id = ?")
           ->execute([$contract['rental_object_id'], $companyId]);
    }

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Kontrakt slettet']);

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting av kontrakt',
        'error'   => $e->getMessage()
    ]);
}
