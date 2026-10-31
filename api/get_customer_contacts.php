<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $customerId = trim((string) ($_GET['customer_id'] ?? ''));
    if ($customerId === '') {
        http_response_code(400);
        throw new InvalidArgumentException('Kunde-ID mangler.');
    }
    $db = getDBConnection();
    $customerStmt = $db->prepare(
        'SELECT id, first_name, last_name
         FROM tenant_customers
         WHERE id = ? AND company_id = ?
         LIMIT 1'
    );
    $customerStmt->execute([$customerId, $_SESSION['company_id']]);
    $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer) {
        http_response_code(404);
        throw new InvalidArgumentException('Kunden ble ikke funnet i valgt bedrift.');
    }

    $stmt = $db->prepare(
        'SELECT id, contact_type, contact_value, is_primary, verified_at, created_at
         FROM tenant_customer_contacts
         WHERE customer_id = ?
         ORDER BY contact_type ASC, is_primary DESC, created_at ASC, id ASC'
    );
    $stmt->execute([$customerId]);
    echo json_encode([
        'success' => true,
        'customer' => $customer,
        'contacts' => $stmt->fetchAll(PDO::FETCH_ASSOC),
    ]);
} catch (Throwable $e) {
    if (!($e instanceof InvalidArgumentException)) {
        error_log('get_customer_contacts error: ' . $e->getMessage());
        http_response_code(500);
        $message = 'Kunne ikke hente kontaktopplysninger.';
    } else {
        $message = $e->getMessage();
    }
    echo json_encode(['success' => false, 'message' => $message]);
}
