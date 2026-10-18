<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
require_once __DIR__ . '/../includes/ImageUpload.php';

$temporary = null;
try {
    if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
        http_response_code(401);
        throw new DomainException('Logg inn på nytt før du laster opp bildet.');
    }
    $method = $_SERVER['REQUEST_METHOD'] ?? '';
    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        http_response_code(405);
        throw new DomainException('Ugyldig forespørsel.');
    }
    if ($method === 'POST' && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > ImageUpload::MAX_BYTES + 1048576) {
        http_response_code(413);
        throw new InvalidArgumentException('Bildet er for stort. Velg en fil på høyst 10 MB.');
    }
    $input = $method === 'POST' ? $_POST : $_GET;
    $module = $input['module'] ?? '';
    $purpose = $input['purpose'] ?? 'image';
    if (!is_string($module) || !is_string($purpose)) {
        throw new InvalidArgumentException('Ugyldig bildeforespørsel.');
    }
    ImageUpload::requireContext($module, $purpose);
    if ($method === 'POST') {
        ImageUpload::requirePost($input);
    }
    require_once __DIR__ . '/../config/database.php';
    $company = (string)$_SESSION['company_id'];
    ImageUpload::authorize(getDBConnection(), $company, $module);
    $key = getenv('IMGBB_API_KEY');
    $key = is_string($key) ? trim($key) : '';
    if ($method === 'GET') {
        echo json_encode(['success' => true, 'configured' => $key !== '', 'csrf_token' => ImageUpload::token(),
            'company_id' => $company, 'max_bytes' => ImageUpload::MAX_BYTES, 'formats' => ImageUpload::FORMATS]);
        exit;
    }
    if ($key === '') {
        http_response_code(503);
        throw new DomainException('Bildeopplasting er ikke konfigurert. Serveransvarlig må sette IMGBB_API_KEY.');
    }
    $image = $_FILES['image'] ?? null;
    if (!is_array($image) || !is_int($image['error'] ?? null) || $image['error'] !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Bildet kunne ikke mottas. Velg en fil på høyst 10 MB og prøv igjen.');
    }
    if (!is_string($image['tmp_name'] ?? null) || !is_uploaded_file($image['tmp_name'])) {
        throw new InvalidArgumentException('Ugyldig opplastet fil.');
    }
    ImageUpload::reserveAttempt();
    // Keep company/session validation above; release the session during network I/O.
    session_write_close();
    $prepared = ImageUpload::prepare($image['tmp_name'], $purpose);
    $temporary = $prepared['path'];
    $result = ImageUpload::upload($prepared, $key);
    echo json_encode(['success' => true] + $result, JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    if (http_response_code() < 400) {
        http_response_code(match (true) {
            $e instanceof OverflowException => 429,
            $e instanceof DomainException, $e instanceof TenantAuditException => 403,
            $e instanceof InvalidArgumentException => 422,
            default => 502,
        });
    }
    // Never log provider responses, request bodies, keys, paths or deletion URLs.
    $safe = $e instanceof DomainException || $e instanceof InvalidArgumentException || $e instanceof OverflowException
        || $e instanceof TenantAuditException || get_class($e) === RuntimeException;
    echo json_encode(['success' => false, 'message' => $safe ? $e->getMessage() : 'Bildeopplasting er utilgjengelig. Prøv igjen senere.'], JSON_UNESCAPED_UNICODE);
} finally {
    if (is_string($temporary) && is_file($temporary)) {
        unlink($temporary);
    }
}
