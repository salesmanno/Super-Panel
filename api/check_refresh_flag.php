<?php
session_start();
header('Content-Type: application/json');
if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Ikke autentisert']); exit;
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/tenant_notifications.php';
if (empty($_SESSION['notification_csrf'])) $_SESSION['notification_csrf'] = bin2hex(random_bytes(32));
$token = $_SESSION['notification_csrf'];
$company = (string) $_SESSION['company_id'];
session_write_close();
try {
    $polls = tenantPendingNotifications(getDBConnection(), $company);
    echo json_encode(['success'=>true,'hasPending'=>(bool)$polls,
        'poll'=>$polls[0]??null,'polls'=>$polls,'csrf_token'=>$token]);
} catch (TenantAuditException $e) {
    http_response_code(403); echo json_encode(['success'=>false,'message'=>'Ingen tilgang']);
} catch (Throwable $e) {
    error_log('Notification read failed: '.$e->getMessage());
    http_response_code(500); echo json_encode(['success'=>false,'message'=>'Kunne ikke hente varsler']);
}
