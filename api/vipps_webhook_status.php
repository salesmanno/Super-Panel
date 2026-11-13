<?php
declare(strict_types=1);
session_start();
session_write_close();
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/economy_vipps_access.php';
require_once __DIR__.'/../includes/EconomyVippsWebhookSetup.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
try {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') throw new RuntimeException('Kun GET tillatt.', 405);
    if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) throw new RuntimeException('Ikke autentisert.', 401);
    $id = $_GET['configuration_id'] ?? '';
    if (!is_string($id) || $id === '' || strlen($id) > 64) throw new RuntimeException('Ugyldig betalingsoppsett.', 400);
    $db = getDBConnection();
    $company = economyVippsAccess($db, $_SESSION);
    $state = (new EconomyVippsWebhookSetup($db))->status($company, $id);
    echo json_encode(['success'=>true, 'webhook'=>$state], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    // Never serialize DB/provider exceptions: these may contain credentials.
    $code = in_array($e->getCode(), [400,401,403,405], true) ? $e->getCode() : 409;
    http_response_code($code);
    $safe = $e instanceof PDOException || $e instanceof JsonException
        ? 'Webhook-oppsettet kunne ikke leses. Kontroller databaseoppsettet.'
        : ($e instanceof SalesVippsFailure ? 'Vipps kunne ikke nås. Prøv å oppdatere status senere.' : $e->getMessage());
    echo json_encode(['success'=>false, 'message'=>$safe]);
}
