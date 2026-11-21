<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/portal_service_payments.php';
$db = null;
try {
    if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
        http_response_code(401);
        throw new DomainException('Du må logge inn på nytt.');
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if (!in_array($method, ['GET','POST'], true)) {
        http_response_code(405);
        header('Allow: GET, POST');
        throw new InvalidArgumentException('Kun GET og POST er tillatt.');
    }
    $input = $method === 'POST' ? json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR) : $_GET;
    if (!is_array($input)) throw new InvalidArgumentException('Ugyldig forespørsel.');
    if ($method === 'POST') entityFormRequireCsrf($input);
    if (!is_string($input['module'] ?? null) || !is_string($input['id'] ?? null) || $input['id'] === '') {
        throw new InvalidArgumentException('Velg en tjeneste først.');
    }
    $db = getDBConnection();
    if ($method === 'POST') $db->beginTransaction();
    $result = portalServicePayments($db, (string)$_SESSION['company_id'], $input['module'], $input['id'],
        $method === 'POST' ? $input : null);
    if ($method === 'POST') $db->commit();
    echo json_encode(['success'=>true]+$result, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    if ($db instanceof PDO && $db->inTransaction()) $db->rollBack();
    $expected = $error instanceof InvalidArgumentException || $error instanceof DomainException
        || $error instanceof OutOfBoundsException || $error instanceof JsonException || $error instanceof TenantAuditException;
    if (http_response_code() < 400) http_response_code($expected ? 400 : 500);
    if (!$expected) error_log('Portal service payment settings: '.get_class($error));
    echo json_encode(['success'=>false, 'message'=>$expected ? $error->getMessage()
        : 'Betalingsmetodene kunne ikke lastes eller lagres. Ingen endring er bekreftet.'], JSON_UNESCAPED_UNICODE);
}
