<?php
declare(strict_types=1);
session_start();
session_write_close();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo json_encode(['success' => false, 'message' => 'Kun POST tillatt.']);
    exit;
}
if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert.']);
    exit;
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/entity_form_fields.php';
require_once __DIR__ . '/../includes/membership_benefits_preview.php';
require_once __DIR__ . '/../includes/member_page_activation.php';
$db = null;
try {
    $input = json_decode(file_get_contents('php://input'), true, 32, JSON_THROW_ON_ERROR);
    if (!is_array($input) || !is_string($input['member_id'] ?? null) || trim($input['member_id']) === '') {
        throw new InvalidArgumentException('Ugyldig medlem.');
    }
    try {
        entityFormRequireCsrf($input);
    } catch (Throwable $e) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Sikkerhetskontrollen feilet. Last siden på nytt.']);
        exit;
    }
    $db = getDBConnection();
    $companyId = (string) $_SESSION['company_id'];
    if (!membershipBenefitsPreviewCanAccess($db, (string) $_SESSION['tenant_admin_id'], $companyId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Du har ikke tilgang til medlemskap i valgt bedrift.']);
        exit;
    }
    $db->beginTransaction();
    activateMemberPage($db, $companyId, trim($input['member_id']));
    $db->commit();
    echo json_encode(['success' => true, 'my_membership_page_active' => 1]);
} catch (Throwable $e) {
    if ($db instanceof PDO && $db->inTransaction()) $db->rollBack();
    $expected = $e instanceof InvalidArgumentException || $e instanceof JsonException || $e instanceof OutOfBoundsException;
    http_response_code($e instanceof OutOfBoundsException ? 404 : ($expected ? 400 : 500));
    if (!$expected) error_log('Member page activation failed: ' . get_class($e));
    echo json_encode(['success' => false, 'message' => $expected ? $e->getMessage() : 'Kunne ikke aktivere medlemssiden. Prøv igjen.']);
}
