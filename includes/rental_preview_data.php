<?php
require_once __DIR__ . '/rental_preview.php';
if (rentalPreviewIsBlocked()) { http_response_code(404); exit; }
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('salesman_rental_preview');
    session_set_cookie_params(['httponly'=>true,'samesite'=>'Strict','secure'=>rentalPreviewCookieSecure(),'path'=>'/']);
    session_start();
}
if (!rentalPreviewEndpointCanServe()) { http_response_code(401); echo json_encode(['success'=>false,'message'=>'Preview authentication required']); exit; }
header('Content-Type: application/json; charset=utf-8');
echo json_encode(rentalPreviewSyntheticData($_GET['kind'] ?? 'objects'), JSON_UNESCAPED_UNICODE);