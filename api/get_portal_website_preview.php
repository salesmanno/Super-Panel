<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/session_manager.php';
require_once __DIR__.'/../includes/portal_website_preview_data.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(405);
    header('Allow: GET');
    echo json_encode(['success'=>false, 'message'=>'Kun GET tillatt.']);
    exit;
}
try {
    if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
        http_response_code(401);
        echo json_encode(['success'=>false, 'message'=>'Logg inn på nytt.']);
        exit;
    }
    if (portalViewMode($db, (string)$_SESSION['tenant_admin_id']) !== 'wpreview') {
        http_response_code(403);
        echo json_encode(['success'=>false, 'message'=>'Denne visningen er ikke aktivert for brukeren.']);
        exit;
    }
    $data = portalWebsitePreviewData(
        $db, (string)$_SESSION['company_id'],
        getTenantAdminModules($_SESSION['tenant_admin_id']),
        getCompanyModules($_SESSION['company_id'])
    );
    echo json_encode(['success'=>true] + $data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    error_log('Refresh portal preview: '.$error->getMessage());
    http_response_code(500);
    echo json_encode(['success'=>false, 'message'=>'Kunne ikke oppdatere forhåndsvisningen. Last siden på nytt.']);
}
