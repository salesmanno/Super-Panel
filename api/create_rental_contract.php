<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$companyId = $_SESSION['company_id'];
$adminId   = $_SESSION['tenant_admin_id'];

$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['rental_object_id']) || empty($data['start_date']) || empty($data['end_date'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Utleieobjekt, startdato og sluttdato er påkrevd']);
    exit;
}

$startDate = $data['start_date'];
$endDate   = $data['end_date'];

if ($endDate <= $startDate) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Sluttdato må være etter startdato']);
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

    $stmt = $db->prepare("SELECT id, object_name, deposit_amount FROM rental_objects WHERE id = ? AND company_id = ?");
    $stmt->execute([$data['rental_object_id'], $companyId]);
    $object = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$object) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Utleieobjekt ikke funnet']);
        exit;
    }

    $rentalDays = (int) ((strtotime($endDate) - strtotime($startDate)) / 86400);
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
        ");
        $activeCheck->execute([$data['rental_object_id'], $companyId]);
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
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse av kontrakt',
        'error'   => $e->getMessage()
    ]);
}
