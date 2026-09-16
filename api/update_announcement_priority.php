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
        echo json_encode(['success' => false, 'message' => 'Prioritet må være et heltall mellom 1 og 10'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $db = getDBConnection();
    $stmt = $db->prepare('UPDATE tenant_announcements SET priority = ?, updated_at = NOW() WHERE id = ? AND company_id = ?');
    $stmt->execute([$priority, $id, $_SESSION['company_id']]);
    if ($stmt->rowCount() === 0) {
        $check = $db->prepare('SELECT id FROM tenant_announcements WHERE id = ? AND company_id = ?');
        $check->execute([$id, $_SESSION['company_id']]);
        if (!$check->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Nyhet ikke funnet'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }
    echo json_encode(['success' => true, 'priority' => $priority, 'message' => 'Prioritet lagret'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('Update announcement priority error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kunne ikke lagre prioritet'], JSON_UNESCAPED_UNICODE);
}