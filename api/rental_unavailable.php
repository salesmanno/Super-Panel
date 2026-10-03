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
require_once __DIR__ . '/../includes/rental_unavailable_rules.php';

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

function rentalUnavailableId(): string {
    return sprintf(
        '%s-%s-%s-%s-%s',
        bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)),
        bin2hex(random_bytes(2)), bin2hex(random_bytes(6))
    );
}

function rentalUnavailableDates(array $data): array {
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
        $today = rentalUnavailableToday();
        $stmt = $db->prepare(
            "SELECT rs.id, rs.rental_object_id, rs.start_date, rs.end_date, rs.notes, ro.object_name
             FROM rental_schedule rs
             INNER JOIN rental_objects ro
               ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
             WHERE rs.company_id = ? AND rs.status = 'unavailable'
               AND rs.end_date >= ?
             ORDER BY rs.start_date, ro.object_name"
        );
        $stmt->execute([$companyId, $today]);
        echo json_encode(['success' => true, 'periods' => $stmt->fetchAll(PDO::FETCH_ASSOC), 'today' => $today]);
        exit;
    }

    if ($method === 'DELETE') {
        $id = (string) ($data['id'] ?? $_GET['id'] ?? '');
        if ($id === '') {
            throw new InvalidArgumentException('ID mangler.');
        }
        $stmt = $db->prepare(
            "DELETE FROM rental_schedule
             WHERE id = ? AND company_id = ? AND status = 'unavailable'"
        );
        $stmt->execute([$id, $companyId]);
        if ($stmt->rowCount() !== 1) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Perioden ble ikke funnet.']);
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

    [$start, $end] = rentalUnavailableDates($data);
    $hasNotes = array_key_exists('notes', $data);
    $notes = normalizeRentalUnavailableNotes($data['notes'] ?? null);
    $objectIds = $data['rental_object_ids']
        ?? $data['object_ids']
        ?? $data['rental_object_id']
        ?? [];
    if (!is_array($objectIds)) {
        $objectIds = [$objectIds];
    }
    $objectIds = array_values(array_unique(array_filter(array_map('strval', $objectIds))));
    if ($objectIds === []) {
        throw new InvalidArgumentException('Minst ett utleieobjekt må velges.');
    }
    if ($method === 'PUT' && count($objectIds) !== 1) {
        throw new InvalidArgumentException('Velg ett utleieobjekt når en periode redigeres.');
    }

    $db->beginTransaction();
    $placeholders = implode(',', array_fill(0, count($objectIds), '?'));
    $objectCheck = $db->prepare(
        "SELECT id, created_by
         FROM rental_objects
         WHERE company_id = ? AND id IN ($placeholders)
         FOR UPDATE"
    );
    $objectCheck->execute(array_merge([$companyId], $objectIds));
    $validObjects = [];
    foreach ($objectCheck->fetchAll(PDO::FETCH_ASSOC) as $object) {
        $validObjects[(string) $object['id']] = (string) $object['created_by'];
    }
    if (count($validObjects) !== count($objectIds)) {
        throw new InvalidArgumentException('Ugyldig utleieobjekt.');
    }

    $editingId = $method === 'PUT' ? (string) ($data['id'] ?? '') : '';
    if ($method === 'PUT') {
        if ($editingId === '') {
            throw new InvalidArgumentException('ID mangler.');
        }
        $existing = $db->prepare(
            "SELECT id, notes FROM rental_schedule
             WHERE id = ? AND company_id = ? AND status = 'unavailable'
             FOR UPDATE"
        );
        $existing->execute([$editingId, $companyId]);
        $existingPeriod = $existing->fetch(PDO::FETCH_ASSOC);
        if ($existingPeriod === false) {
            throw new InvalidArgumentException('Perioden ble ikke funnet.');
        }
        // Older clients which only edit dates must not silently erase the label.
        if (!$hasNotes) {
            $notes = $existingPeriod['notes'];
        }
    }

    $conflict = $db->prepare(
        "SELECT id FROM rental_schedule
         WHERE company_id = ? AND rental_object_id = ? AND id <> ?
           AND (
             status = 'unavailable'
             OR (approved = 1 AND status IN ('approved', 'active'))
           )
           AND start_date <= ? AND end_date >= ?
         LIMIT 1 FOR UPDATE"
    );
    foreach ($objectIds as $objectId) {
        $conflict->execute([$companyId, $objectId, $editingId, $end, $start]);
        if ($conflict->fetchColumn() !== false) {
            throw new InvalidArgumentException(
                'Perioden overlapper en godkjent utleie eller en annen utilgjengelig periode.'
            );
        }
    }

    if ($method === 'PUT') {
        $stmt = $db->prepare(
            "UPDATE rental_schedule
             SET rental_object_id = ?, start_date = ?, start_time = '00:00:00',
                 end_date = ?, end_time = '23:59:59', notes = ?, updated_at = CURRENT_TIMESTAMP
             WHERE id = ? AND company_id = ? AND status = 'unavailable'"
        );
        $stmt->execute([$objectIds[0], $start, $end, $notes, $editingId, $companyId]);
    } else {
        $stmt = $db->prepare(
            "INSERT INTO rental_schedule
             (id, company_id, rental_object_id, customer_id, customer_name, customer_email,
              customer_phone, start_date, start_time, end_date, end_time, rental_fee,
              status, notes, created_by, approved, pickup_time)
             VALUES (?, ?, ?, NULL, NULL, NULL, NULL, ?, '00:00:00', ?, '23:59:59',
                     0.00, 'unavailable', ?, ?, 0, '08:00:00')"
        );
        foreach ($objectIds as $objectId) {
            $createdBy = (string) $_SESSION['tenant_admin_id'];
            if ($createdBy === '') {
                $createdBy = $validObjects[$objectId];
            }
            $stmt->execute([
                rentalUnavailableId(), $companyId, $objectId, $start, $end, $notes, $createdBy
            ]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('rental_unavailable error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Perioden kunne ikke lagres.']);
}