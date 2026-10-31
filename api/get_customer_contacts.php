<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/tenant_customer_contacts.php';

try {
    $customerId = trim((string) ($_GET['customer_id'] ?? ''));
    if ($customerId === '') {
        http_response_code(400);
        throw new InvalidArgumentException('Kunde-ID mangler.');
    }
    $db = getDBConnection();
    $customerStmt = $db->prepare(
        'SELECT id, first_name, last_name, email, phone
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
    $contacts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach (['email', 'phone'] as $type) {
        $legacy = trim((string) ($customer[$type] ?? ''));
        $typeContacts = array_filter($contacts, static fn($row) => $row['contact_type'] === $type);
        $hasPrimary = array_filter($typeContacts, static fn($row) => (int) $row['is_primary'] === 1);
        $alreadyStored = array_filter($typeContacts, static fn($row) =>
            tenantCustomerNormalizeContact($type, $row['contact_value']) === tenantCustomerNormalizeContact($type, $legacy));
        if ($legacy !== '' && !$hasPrimary && !$alreadyStored) {
            $contacts[] = [
                'id' => null, 'contact_type' => $type, 'contact_value' => $legacy,
                'is_primary' => 1, 'verified_at' => null, 'is_legacy' => true,
            ];
        }
        unset($customer[$type]);
    }
    echo json_encode([
        'success' => true,
        'customer' => $customer,
        'contacts' => $contacts,
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
