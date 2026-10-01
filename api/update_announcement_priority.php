<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert'], JSON_UNESCAPED_UNICODE);
    exit;
}
session_write_close();

require_once '../config/database.php';
require_once '../includes/announcement_helpers.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = is_array($data) && (is_string($data['id'] ?? null) || is_int($data['id'] ?? null))
        ? trim((string)$data['id'])
        : '';
    if ($id === '' || strlen($id) > 64) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Nyhet-ID mangler'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $priority = announcement_priority_for_write($data['priority'] ?? null);
    if ($priority === null) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Prioritet må være et positivt heltall'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db = getDBConnection();
    $companyId = (string)$_SESSION['company_id'];
    $db->beginTransaction();
    announcement_apply_priority_assignment($db, $companyId, $id, $priority);
    $db->commit();
    echo json_encode([
        'success' => true,
        'priority' => $priority,
        'priorities' => (object)announcement_priority_map($db, $companyId),
        'message' => 'Prioritet lagret'
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Update announcement priority error: ' . $e->getMessage());
    if ($e instanceof OutOfBoundsException) http_response_code(404);
    elseif ($e instanceof InvalidArgumentException) http_response_code(422);
    else http_response_code(500);
    $message = $e instanceof InvalidArgumentException || $e instanceof OutOfBoundsException
        ? $e->getMessage()
        : 'Kunne ikke lagre prioritet';
    echo json_encode(['success' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
}