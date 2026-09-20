<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/request_date_time.php';

$companyId = (string) $_SESSION['company_id'];
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    $data = $_POST;
}
if ($method !== 'GET') {
    $sessionCsrf = (string) ($_SESSION['rental_lifecycle_csrf'] ?? '');
    $requestCsrf = (string) (
        $_SERVER['HTTP_X_CSRF_TOKEN']
        ?? $data['csrf_token']
        ?? ''
    );
    if ($sessionCsrf === '' || $requestCsrf === '' || !hash_equals($sessionCsrf, $requestCsrf)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Ugyldig sikkerhetstoken. Last siden på nytt.']);
        exit;
    }
}

function rentalBlackoutId(): string {
    return sprintf(
        '%s-%s-%s-%s-%s',
        bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)), bin2hex(random_bytes(6))
    );
}

function rentalBlackoutDates(array $data): array {
    $start = normalizeRequestDate($data['start_date'] ?? null, 'Startdato', false);
    $end = normalizeRequestDate($data['end_date'] ?? null, 'Sluttdato', false);
    if ($end < $start) {
        throw new InvalidArgumentException('Sluttdato kan ikke være før startdato.');
    }
    return [$start, $end];
}

try {
    $db = getDBConnection();
    if ($method === 'GET') {
        $stmt = $db->prepare(
            'SELECT rb.*, ro.object_name
             FROM rental_blackouts rb
             INNER JOIN rental_objects ro ON ro.id = rb.rental_object_id AND ro.company_id = rb.company_id
             WHERE rb.company_id = ? ORDER BY rb.start_date, ro.object_name'
        );
        $stmt->execute([$companyId]);
        echo json_encode(['success' => true, 'blackouts' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($method === 'DELETE') {
        $id = (string) ($data['id'] ?? $_GET['id'] ?? '');
        if ($id === '') throw new InvalidArgumentException('ID mangler.');
        $stmt = $db->prepare('DELETE FROM rental_blackouts WHERE id = ? AND company_id = ?');
        $stmt->execute([$id, $companyId]);
        if ($stmt->rowCount() !== 1) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Sperringen ble ikke funnet.']);
            exit;
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if (!in_array($method, ['POST', 'PUT'], true)) {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Metoden støttes ikke']);
        exit;
    }
    [$start, $end] = rentalBlackoutDates($data);
    $objectIds = $data['rental_object_ids']
        ?? $data['object_ids']
        ?? $data['rental_object_id']
        ?? [];
    if (!is_array($objectIds)) $objectIds = [$objectIds];
    $objectIds = array_values(array_unique(array_filter(array_map('strval', $objectIds))));
    if ($objectIds === []) throw new InvalidArgumentException('Minst ett utleieobjekt må velges.');
    if ($method === 'PUT' && count($objectIds) !== 1) {
        throw new InvalidArgumentException('Velg ett utleieobjekt når en sperring redigeres.');
    }
    $db->beginTransaction();
    $placeholders = implode(',', array_fill(0, count($objectIds), '?'));
    $check = $db->prepare(
        "SELECT id FROM rental_objects WHERE company_id = ? AND id IN ($placeholders) FOR UPDATE"
    );
    $check->execute(array_merge([$companyId], $objectIds));
    $validIds = $check->fetchAll(PDO::FETCH_COLUMN);
    if (count($validIds) !== count($objectIds)) throw new InvalidArgumentException('Ugyldig utleieobjekt.');

    $approvedConflict = $db->prepare(
        "SELECT id FROM rental_schedule
         WHERE company_id = ? AND rental_object_id = ?
           AND approved = 1 AND status IN ('approved', 'active')
           AND start_date <= ? AND end_date >= ?
         LIMIT 1 FOR UPDATE"
    );
    foreach ($objectIds as $objectId) {
        $approvedConflict->execute([$companyId, $objectId, $end, $start]);
        if ($approvedConflict->fetchColumn() !== false) {
            throw new InvalidArgumentException(
                'Perioden overlapper en allerede godkjent utleie for ett av objektene.'
            );
        }
    }

    if ($method === 'PUT') {
        $id = (string) ($data['id'] ?? '');
        if ($id === '') throw new InvalidArgumentException('ID mangler.');
        $existing = $db->prepare(
            'SELECT id FROM rental_blackouts WHERE id = ? AND company_id = ? FOR UPDATE'
        );
        $existing->execute([$id, $companyId]);
        if ($existing->fetchColumn() === false) {
            throw new InvalidArgumentException('Sperringen ble ikke funnet.');
        }
        $stmt = $db->prepare(
            'UPDATE rental_blackouts SET rental_object_id = ?, start_date = ?, end_date = ?
             WHERE id = ? AND company_id = ?'
        );
        $stmt->execute([$objectIds[0], $start, $end, $id, $companyId]);
    } else {
        $stmt = $db->prepare(
            'INSERT INTO rental_blackouts
             (id, company_id, rental_object_id, start_date, end_date, created_by)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        foreach ($objectIds as $objectId) {
            $stmt->execute([rentalBlackoutId(), $companyId, $objectId, $start, $end, $_SESSION['tenant_admin_id']]);
        }
    }
    $db->commit();
    echo json_encode(['success' => true]);
} catch (InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('rental_blackouts error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Sperringen kunne ikke lagres.']);
}