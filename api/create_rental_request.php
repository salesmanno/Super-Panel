<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/rental_customer_fields.php';
require_once __DIR__ . '/../includes/rental_request.php';
require_once __DIR__ . '/../includes/rental_request_fields.php';
require_once __DIR__ . '/../includes/rental_schedule_editing.php';
require_once __DIR__ . '/../includes/tenant_notifications.php';

function rentalRequestUuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metoden støttes ikke']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    $data = $_POST;
    if (isset($data['fields']) && is_string($data['fields'])) {
        $decodedFields = json_decode($data['fields'], true);
        $data['fields'] = is_array($decodedFields) ? $decodedFields : [];
    }
}

$csrf = (string) ($_POST['csrf_token'] ?? $data['csrf_token'] ?? '');
$requestToken = (string) ($_SESSION['rental_request_token'] ?? '');
if ($requestToken === '' || $csrf === '' || !hash_equals($requestToken, $csrf)
    || !rentalRequestSameOrigin($_SERVER)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Forespørselen kunne ikke verifiseres']);
    exit;
}

if (!empty($data['_website'] ?? $data['website'] ?? '')) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Skjemaet kunne ikke sendes']);
    exit;
}

$objectId = (string) ($data['object_id'] ?? $_GET['object_id'] ?? '');
if ($objectId === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Utleieobjekt mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $db->beginTransaction();

    $weekdaySelect = rentalWeekdayPriceSelect($db);
    $weekdayEnabledSelect = rentalWeekdayPricingEnabledSelect($db);
    $objectStmt = $db->prepare(
        'SELECT ' . $weekdaySelect . ' AS weekday_prices, '
                . $weekdayEnabledSelect . ' AS weekday_prices_enabled, id, company_id, object_name, rental_price, weekend_price,
                saturday_price, sunday_price, created_by
         FROM rental_objects
         WHERE id = ? AND ' . rentalPublicEligibilitySql() . '
         FOR UPDATE'
    );
    $objectStmt->execute([$objectId]);
    $object = $objectStmt->fetch(PDO::FETCH_ASSOC);
    if (!$object) {
        throw new InvalidArgumentException('Utleieobjektet er ikke tilgjengelig');
    }
    if (trim((string) ($object['created_by'] ?? '')) === '') {
        throw new RuntimeException('Utleieobjektet mangler ansvarlig tenant-administrator');
    }

    $submission = $data['submission_id'] ?? hash('sha256', json_encode($data, JSON_THROW_ON_ERROR));
    if (!is_string($submission) || strlen($submission)>128) {
        throw new InvalidArgumentException('Ugyldig innsending');
    }
    $key = hash('sha256', $requestToken . "\0" . $objectId . "\0" . $submission);
    $requestId = substr($key,0,8).'-'.substr($key,8,4).'-'.substr($key,12,4).'-'.substr($key,16,4).'-'.substr($key,20,12);
    $retry = $db->prepare('SELECT id, status FROM rental_schedule WHERE id = ? AND company_id = ? AND rental_object_id = ?');
    $retry->execute([$requestId, $object['company_id'], $objectId]);
    if ($existingRequest = $retry->fetch(PDO::FETCH_ASSOC)) {
        $db->commit();
        echo json_encode(['success'=>true,'message'=>'Forespørselen er allerede sendt',
            'request_id'=>$existingRequest['id'],'status'=>$existingRequest['status']]);
        exit;
    }
    $settings = getRentalCustomerFieldSettings($db, $object['company_id'], $objectId);
    $validated = validateRentalRequestInput($data, $settings);
    $startDateTime = $validated['start_date'] . ' ' . $validated['start_time'];
    $endDateTime = $validated['end_date'] . ' ' . $validated['end_time'];

    // Approved/active rentals and unavailable periods reserve the object.
    $scheduleConflict = $db->prepare(
        "SELECT rs.id
         FROM rental_schedule rs
         WHERE rs.company_id = ? AND rs.rental_object_id = ?
            AND (
              rs.status = 'unavailable'
              OR (rs.approved = 1 AND rs.status IN ('approved', 'active'))
            )
           AND CONCAT(rs.start_date, ' ', rs.start_time) < ?
           AND CONCAT(rs.end_date, ' ', rs.end_time) > ?
         LIMIT 1
         FOR UPDATE"
    );
    $scheduleConflict->execute([
        $object['company_id'],
        $objectId,
        $endDateTime,
        $startDateTime,
    ]);
    if ($scheduleConflict->fetchColumn() !== false) {
        throw new RentalRequestConflictException('Tidsrommet er allerede reservert eller utilgjengelig');
    }

    $contractConflict = $db->prepare(
        "SELECT id
         FROM rental_contracts
         WHERE company_id = ? AND rental_object_id = ? AND status = 'active'
           AND start_date <= ? AND end_date >= ?
         LIMIT 1
         FOR UPDATE"
    );
    $contractConflict->execute([
        $object['company_id'],
        $objectId,
        $validated['end_date'],
        $validated['start_date'],
    ]);
    if ($contractConflict->fetchColumn() !== false) {
        throw new RentalRequestConflictException('Tidsrommet er allerede reservert');
    }

    $fields = $validated['fields'];
    $customerId = null;
    $customer = null;
    $lookup = null;
    if (!empty($fields['email'])) {
        $lookup = ['email', $fields['email']];
    } elseif (!empty($fields['phone'])) {
        $lookup = ['phone', $fields['phone']];
    }
    if ($lookup) {
        $customerStmt = $db->prepare(
            "SELECT id, first_name, last_name, email, phone
             FROM tenant_customers
             WHERE company_id = ? AND {$lookup[0]} = ?
             ORDER BY created_at ASC
             LIMIT 1
             FOR UPDATE"
        );
        $customerStmt->execute([$object['company_id'], $lookup[1]]);
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    $customerColumnMap = [
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'email' => 'email',
        'phone' => 'phone',
        'street_address' => 'address_street',
        'postal_code' => 'address_zip',
        'city' => 'address_city',
    ];
    if ($customer) {
        // Public requests may link an existing tenant customer, but must
        // never mutate that customer. Submitted values live in the request
        // snapshot below.
        $customerId = $customer['id'];
    } else {
        $hasCustomerValues = false;
        foreach ($customerColumnMap as $field => $column) {
            if (array_key_exists($field, $fields) && $fields[$field] !== '') {
                $hasCustomerValues = true;
                break;
            }
        }
        if ($hasCustomerValues) {
            $customerId = rentalRequestUuid();
            $columns = ['id', 'company_id', 'customer_code', 'customer_type', 'status', 'source'];
            $values = [$customerId, $object['company_id'], 'RNT-' . strtoupper(substr(str_replace('-', '', $customerId), 0, 12)), 'individual', 'active', 'rental_request'];
            foreach ($customerColumnMap as $field => $column) {
                if (array_key_exists($field, $fields)) {
                    $columns[] = $column;
                    $values[] = $fields[$field] !== '' ? $fields[$field] : null;
                }
            }
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $db->prepare(
                'INSERT INTO tenant_customers (' . implode(', ', $columns) . ') VALUES (' . $placeholders . ')'
            )->execute($values);
            $customer = array_merge(
                ['id' => $customerId, 'first_name' => null, 'last_name' => null, 'email' => null, 'phone' => null],
                $fields
            );
        }
    }

    $customerDisplay = rentalRequestCustomerDisplay($fields, $customer);
    $customerName = $customerDisplay['name'];
    $customerEmail = $customerDisplay['email'];
    $customerPhone = $customerDisplay['phone'];
    $rentalFee = rentalPeriodPriceCents(
        $object, $validated['start_date'], $validated['end_date']
    ) / 100;

    $insert = $db->prepare(
        'INSERT INTO rental_schedule
         (id, company_id, rental_object_id, customer_id, customer_name,
          customer_email, customer_phone, start_date, start_time, end_date,
           end_time, return_time, logistics, rental_fee, pickup_time, status, approved, notes, created_by)
          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, \'pending\', 0, ?, ?)'
    );
    $insert->execute([
        $requestId,
        $object['company_id'],
        $objectId,
        $customerId,
        $customerName,
        $customerEmail ?: null,
        $customerPhone ?: null,
        $validated['start_date'],
        $validated['start_time'],
        $validated['end_date'],
        $validated['end_time'],
        $validated['end_time'],
        $validated['logistics'],
        $rentalFee,
        $validated['start_time'],
        $validated['notes'] ?: null,
        $object['created_by'],
    ]);
    saveRentalRequestFieldValues(
        $db,
        $object['company_id'],
        $objectId,
        $requestId,
        $fields
    );

    tenantNotifyRentalRequest($db, (string)$object['company_id'], $requestId);
    $db->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Forespørselen er sendt',
        'request_id' => $requestId,
        'status' => 'pending',
    ]);
} catch (RentalRequestConflictException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('create_rental_request conflict: ' . $e->getMessage());
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Det valgte tidsrommet er allerede reservert']);
} catch (InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('create_rental_request validation error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Skjemaet kunne ikke valideres']);
} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('create_rental_request error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Forespørselen kunne ikke lagres']);
}