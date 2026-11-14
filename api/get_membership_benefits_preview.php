<?php
declare(strict_types=1);
session_start();
session_write_close();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['success'=>false, 'message'=>'Kun GET tillatt']);
    exit;
}
if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false, 'message'=>'Ikke autentisert']);
    exit;
}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/membership_benefits_preview.php';

try {
    $db = getDBConnection();
    // Both a delegated user right and an active company module are required.
    if (!membershipBenefitsPreviewCanAccess($db, (string)$_SESSION['tenant_admin_id'], (string)$_SESSION['company_id'])) {
        http_response_code(403);
        echo json_encode(['success'=>false,'message'=>'Du har ikke tilgang til medlemsmodulen i valgt bedrift.']);
        exit;
    }
    echo json_encode(membershipBenefitsPreviewLoad($db, (string)$_SESSION['company_id']), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    $ref = bin2hex(random_bytes(4));
    error_log("Benefits preview [$ref]: ".$e->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false,'message'=>"Kunne ikke hente tjenesteforhåndsvisningen. Feilreferanse: $ref"]);
}
