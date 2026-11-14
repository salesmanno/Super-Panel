<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405); header('Allow: POST'); echo json_encode(['success'=>false,'message'=>'Kun POST tillatt.']); exit;
}
if (!isset($_SESSION['tenant_admin_id'],$_SESSION['company_id'])) {
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'Ikke autentisert.']); exit;
}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/entity_form_fields.php';
require_once __DIR__.'/../includes/membership_benefits_preview.php';
require_once __DIR__.'/../includes/membership_product_benefits.php';
$db = null;
try {
    $input = json_decode(file_get_contents('php://input'),true,32,JSON_THROW_ON_ERROR);
    if (!is_array($input)) throw new InvalidArgumentException('Ugyldige data.');
    try { entityFormRequireCsrf($input); } catch (Throwable $e) {
        http_response_code(403); echo json_encode(['success'=>false,'message'=>'Sikkerhetskontrollen feilet. Last siden på nytt.']); exit;
    }
    $company = (string)$_SESSION['company_id'];
    $db = getDBConnection();
    $db->beginTransaction();
    if (!membershipBenefitsPreviewCanAccess($db,(string)$_SESSION['tenant_admin_id'],$company)) {
        $db->rollBack(); http_response_code(403); echo json_encode(['success'=>false,'message'=>'Du har ikke tilgang til medlemsfordeler i valgt bedrift.']); exit;
    }
    $operation = function() use($db,$company,$input) {
        $result = membershipProductBenefitSave($db,$company,$input);
        if ($result['after']) tenantAuditChange($db,$company,'membership','update','membership_product_modifications',
            (string)$result['after']['id'],$result['resource_name'],$result['before'],$result['after']);
        return $result;
    };
    $result = $db instanceof AuditedPDO ? $db->withExplicitAudit($operation) : $operation();
    $db->commit();
    echo json_encode(['success'=>true,'benefit'=>$result['benefit']],JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    if ($db instanceof PDO && $db->inTransaction()) $db->rollBack();
    $expected = $e instanceof InvalidArgumentException || $e instanceof JsonException;
    http_response_code($expected ? 400 : 500);
    if (!$expected) error_log('Membership benefit save failed: '.get_class($e));
    echo json_encode(['success'=>false,'message'=>$expected ? $e->getMessage() : 'Fordelen kunne ikke lagres. Ingen endring er bekreftet.'],JSON_UNESCAPED_UNICODE);
}
