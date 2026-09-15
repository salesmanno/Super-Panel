<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/request_date_time.php';

$companyId = $_SESSION['company_id'];
$adminId   = $_SESSION['tenant_admin_id'];

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['rental_object_id']) || empty($data['start_date']) || empty($data['end_date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Utleieobjekt, startdato og sluttdato er påkrevd']);
    exit;
}

try {
    $startDate = normalizeRequestDate($data['start_date'], 'Startdato', false);
    $endDate = normalizeRequestDate($data['end_date'], 'Sluttdato', false);
} catch (InvalidArgumentException $e) {
    error_log('create_rental_contract validation error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig start- eller sluttdato']);
    exit;
}

if ($endDate < $startDate) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sluttdato kan ikke være før startdato']);
    exit;
}

function generateUUID() {
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

try {
    $db = getDBConnection();
    $db->beginTransaction();

    // Lock the object while checking active contracts, preventing two
    // concurrent requests from creating overlapping active rentals.
    $stmt = $db->prepare("SELECT id, object_name, deposit_amount FROM rental_objects WHERE id = ? AND company_id = ? FOR UPDATE");
    $stmt->execute([$data['rental_object_id'], $companyId]);
    $object = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$object) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Utleieobjekt ikke funnet']);
        exit;
    }

    $startDateValue = new DateTimeImmutable($startDate);
    $endDateValue = new DateTimeImmutable($endDate);
    // Both rental dates are occupied, therefore a same-day rental is one day.
    $rentalDays = (int) $startDateValue->diff($endDateValue)->days + 1;
    $totalAmount = floatval($data['total_amount'] ?? 0);
    $depositAmount = floatval($data['deposit_amount'] ?? $object['deposit_amount'] ?? 0);

    $status          = $data['status'] ?? 'draft';
    $allowedStatuses = ['draft', 'active'];
    if (!in_array($status, $allowedStatuses)) {
        $status = 'draft';
    }

    $customerId   = !empty($data['customer_id'])   ? $data['customer_id']   : null;
    $customerName = !empty($data['customer_name']) ? $data['customer_name'] : null;

    if ($customerId) {
        $custStmt = $db->prepare("SELECT id, TRIM(CONCAT(COALESCE(first_name,''),' ',COALESCE(last_name,''))) AS name FROM tenant_customers WHERE id = ? AND company_id = ?");
        $custStmt->execute([$customerId, $companyId]);
        $cust = $custStmt->fetch(PDO::FETCH_ASSOC);
        if (!$cust) {
            $customerId = null;
        } elseif (!$customerName) {
            $customerName = trim($cust['name']);
        }
    }

    if ($status === 'active') {
        $activeCheck = $db->prepare("
            SELECT COUNT(*) FROM rental_contracts
            WHERE rental_object_id = ? AND company_id = ? AND status = 'active'
              AND start_date <= ? AND end_date >= ?
        ");
        $activeCheck->execute([$data['rental_object_id'], $companyId, $endDate, $startDate]);
        if ($activeCheck->fetchColumn() > 0) {
            $db->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Objektet er allerede utleid (aktiv kontrakt eksisterer). Avslutt eller kanseller eksisterende kontrakt først.']);
            exit;
        }
    }

    $contractId = generateUUID();

    $insertStmt = $db->prepare("
        INSERT INTO rental_contracts
            (id, company_id, rental_object_id, customer_id, customer_name,
             start_date, end_date, rental_days, total_amount, deposit_amount,
             payment_status, payment_method, status, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertStmt->execute([
        $contractId,
        $companyId,
        $data['rental_object_id'],
        $customerId,
        $customerName,
        $startDate,
        $endDate,
        $rentalDays,
        $totalAmount,
        $depositAmount,
        'unpaid',
        $data['payment_method'] ?? null,
        $status,
        $data['notes'] ?? null
    ]);

    if ($status === 'active') {
        $db->prepare("UPDATE rental_objects SET is_available = 0 WHERE id = ? AND company_id = ?")
           ->execute([$data['rental_object_id'], $companyId]);
    }

    $db->commit();

    echo json_encode([
        'success'     => true,
        'message'     => 'Kontrakt opprettet',
        'contract_id' => $contractId,
        'status'      => $status
    ]);

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('create_rental_contract error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse av kontrakt. Prøv igjen.'
    ]);
}
