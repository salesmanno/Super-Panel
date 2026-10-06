<?php
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Ikke autentisert']); exit;
}
if (($_SERVER['REQUEST_METHOD']??'') !== 'POST') {
    http_response_code(405); echo json_encode(['success'=>false,'message'=>'Metoden støttes ikke']); exit;
}
$data = json_decode(file_get_contents('php://input'), true);
$token = $_SESSION['notification_csrf']??'';
if (!is_array($data) || !is_string($data['csrf_token']??null)
    || $token === '' || !hash_equals($token, $data['csrf_token'])) {
    http_response_code(403); echo json_encode(['success'=>false,'message'=>'Sikkerhetskontrollen feilet']); exit;
}
$id = $data['poll_id']??'';
if (!is_string($id) || $id === '' || strlen($id)>36) {
    http_response_code(400); echo json_encode(['success'=>false,'message'=>'Ugyldig varsel']); exit;
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/tenant_notifications.php';
$company = (string) $_SESSION['company_id'];
session_write_close();
try {
    tenantAcknowledgeNotification(getDBConnection(), $company, $id, ($data['dismissed']??false) === true);
    echo json_encode(['success'=>true]);
} catch (TenantAuditException $e) {
    http_response_code(403); echo json_encode(['success'=>false,'message'=>'Ingen tilgang']);
} catch (OutOfBoundsException $e) {
    http_response_code(404); echo json_encode(['success'=>false,'message'=>'Varsel ikke funnet']);
} catch (Throwable $e) {
    error_log('Notification acknowledgement failed: '.$e->getMessage());
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Kunne ikke kvittere varselet']);
}
