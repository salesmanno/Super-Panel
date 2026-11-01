<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/SalesService.php';
try {
    if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
        http_response_code(401);
        throw new DomainException('Ikke autentisert');
    }
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        throw new DomainException('Kun POST er tillatt.');
    }
    $input = json_decode(file_get_contents('php://input'),true,512,JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new InvalidArgumentException('Ugyldig forespørsel.');
    entityFormRequireCsrf($input);
    $id = $input['id'] ?? null;
    $mode = $input['mode'] ?? null;
    if (!is_string($id) || $id === '' || strlen($id)>36 || !in_array($mode,['preview','apply'],true)
        || !is_string($input['revision'] ?? '')) throw new InvalidArgumentException('Ugyldig reversering.');
    $company = (string)$_SESSION['company_id'];
    $db = getDBConnection();
    if (!tenantIsSupervisor($db,$company)) throw new TenantAuditException('Du har ikke tilgang til reversering.');
    $service = new SalesService($db,$company,(string)$_SESSION['tenant_admin_id'],session_id());
    $result = $service->reverseStockAdjustment($id,$mode==='apply',$input['revision'] ?? '');
    echo json_encode(['success'=>true,'result'=>$result],JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    if (http_response_code() < 400) http_response_code(match (true) {
        $error instanceof TenantAuditException => 403,
        $error instanceof OutOfBoundsException => 404,
        $error instanceof SalesConflict => 409,
        $error instanceof DomainException, $error instanceof InvalidArgumentException, $error instanceof JsonException => 400,
        default => 500,
    });
    $public = $error instanceof DomainException || $error instanceof InvalidArgumentException
        || $error instanceof TenantAuditException || $error instanceof SalesConflict || $error instanceof OutOfBoundsException;
    if (!$public) error_log('Audit reversal failed: '.get_class($error));
    echo json_encode(['success'=>false,'message'=>$public ? $error->getMessage() : 'Reversering feilet. Ingen endringer er bekreftet.'],JSON_UNESCAPED_UNICODE);
}
