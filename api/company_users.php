<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/tenant_supervisor.php';
if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Logg inn på nytt.']);
    exit;
}
try {
    $db = getDBConnection();
    if (!empty($_SESSION['company_auth_version'])) {
        require_once __DIR__ . '/../includes/company_login.php';
        companyLoginRequire($db);
    }
    $company = (string) $_SESSION['company_id'];
    tenantRequireSupervisor($db, $company);
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $token = (string) ($_SESSION['company_users_csrf'] ?? '');
        if (!is_array($data) || $token === '' || !is_string($data['csrf_token'] ?? null)
            || !hash_equals($token, $data['csrf_token'])) {
            throw new DomainException('Sikkerhetskontrollen feilet. Åpne brukeradministrasjonen på nytt.');
        }
        tenantChangeManagedUser($db, $company, $data);
        echo json_encode(['success' => true, 'message' => 'Endringen er lagret.']);
    } elseif ($method === 'GET') {
        $_SESSION['company_users_csrf'] ??= bin2hex(random_bytes(32));
        echo json_encode(['success' => true, 'csrf_token' => $_SESSION['company_users_csrf']]
            + tenantManagedUsers($db, $company), JSON_THROW_ON_ERROR);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metoden støttes ikke.']);
    }
} catch (DomainException $e) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('Company users: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Brukeradministrasjonen kunne ikke oppdateres. Prøv igjen senere.']);
}
