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
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        http_response_code(405);
        throw new InvalidArgumentException('Kun POST støttes.');
    }
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        http_response_code(400);
        throw new InvalidArgumentException('Ugyldig forespørsel.');
    }

    $customerId = trim((string) ($data['customer_id'] ?? ''));
    $action = (string) ($data['action'] ?? '');
    $contactId = trim((string) ($data['contact_id'] ?? ''));
    $companyId = (string) $_SESSION['company_id'];
    if ($customerId === '') {
        http_response_code(400);
        throw new InvalidArgumentException('Kunde-ID mangler.');
    }

    $db = getDBConnection();
    $db->beginTransaction();
    $lock = $db->prepare(
        'SELECT id FROM tenant_customers WHERE id = ? AND company_id = ? FOR UPDATE'
    );
    $lock->execute([$customerId, $companyId]);
    if (!$lock->fetchColumn()) {
        throw new InvalidArgumentException('Kunden ble ikke funnet i valgt bedrift.');
    }

    if ($action === 'add') {
        $type = (string) ($data['contact_type'] ?? '');
        $value = trim((string) ($data['contact_value'] ?? ''));
        if (!in_array($type, ['email', 'phone'], true) || $value === '') {
            http_response_code(400);
            throw new InvalidArgumentException('Velg e-post eller telefon, og fyll inn verdien.');
        }
        if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            throw new InvalidArgumentException('E-postadressen er ugyldig.');
        }
        $normalized = tenantCustomerNormalizeContact($type, $value);
        $existingStmt = $db->prepare(
            'SELECT id FROM tenant_customer_contacts
             WHERE customer_id = ? AND contact_type = ? AND normalized_value = ?
             LIMIT 1'
        );
        $existingStmt->execute([$customerId, $type, $normalized]);
        if ($existingStmt->fetchColumn()) {
            http_response_code(409);
            throw new InvalidArgumentException('Denne kontaktverdien finnes allerede på kunden.');
        }
        $primary = !empty($data['is_primary']);
        $hasPrimaryStmt = $db->prepare(
            'SELECT id FROM tenant_customer_contacts
             WHERE customer_id = ? AND contact_type = ? AND is_primary = 1
             LIMIT 1'
        );
        $hasPrimaryStmt->execute([$customerId, $type]);
        $primary = $primary || !$hasPrimaryStmt->fetchColumn();

        if ($primary) {
            tenantCustomerSetPrimaryContact($db, $companyId, $customerId, $type, $value);
        } else {
            $db->prepare(
                'INSERT INTO tenant_customer_contacts
                    (id, customer_id, contact_type, contact_value, normalized_value, is_primary, created_at)
                 VALUES (?, ?, ?, ?, ?, 0, NOW())'
            )->execute([
                tenantCustomerContactUuid(),
                $customerId,
                $type,
                $value,
                $normalized,
            ]);
        }
    } elseif ($action === 'primary') {
        $contactStmt = $db->prepare(
            'SELECT contact_type, contact_value
             FROM tenant_customer_contacts
             WHERE id = ? AND customer_id = ?
             LIMIT 1'
        );
        $contactStmt->execute([$contactId, $customerId]);
        $contact = $contactStmt->fetch(PDO::FETCH_ASSOC);
        if (!$contact) {
            http_response_code(404);
            throw new InvalidArgumentException('Kontaktpunktet ble ikke funnet.');
        }
        tenantCustomerSetPrimaryContact(
            $db,
            $companyId,
            $customerId,
            (string) $contact['contact_type'],
            (string) $contact['contact_value']
        );
    } elseif ($action === 'delete') {
        $contactStmt = $db->prepare(
            'SELECT contact_type, contact_value, is_primary
             FROM tenant_customer_contacts
             WHERE id = ? AND customer_id = ?
             LIMIT 1'
        );
        $contactStmt->execute([$contactId, $customerId]);
        $contact = $contactStmt->fetch(PDO::FETCH_ASSOC);
        if (!$contact) {
            http_response_code(404);
            throw new InvalidArgumentException('Kontaktpunktet ble ikke funnet.');
        }
        $db->prepare(
            'DELETE FROM tenant_customer_contacts WHERE id = ? AND customer_id = ?'
        )->execute([$contactId, $customerId]);

        if ((int) $contact['is_primary'] === 1) {
            $nextStmt = $db->prepare(
                'SELECT contact_value
                 FROM tenant_customer_contacts
                 WHERE customer_id = ? AND contact_type = ?
                 ORDER BY created_at ASC, id ASC
                 LIMIT 1
                 FOR UPDATE'
            );
            $nextStmt->execute([$customerId, $contact['contact_type']]);
            $nextValue = $nextStmt->fetchColumn();
            tenantCustomerSetPrimaryContact(
                $db,
                $companyId,
                $customerId,
                (string) $contact['contact_type'],
                $nextValue === false ? null : (string) $nextValue
            );
        }
    } else {
        http_response_code(400);
        throw new InvalidArgumentException('Ukjent kontaktoperasjon.');
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Kontaktopplysningene er oppdatert.']);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    if (!($e instanceof InvalidArgumentException)) {
        error_log('manage_customer_contact error: ' . $e->getMessage());
        http_response_code(500);
        $message = 'Kunne ikke oppdatere kontaktopplysningene.';
    } else {
        $message = $e->getMessage();
    }
    echo json_encode(['success' => false, 'message' => $message]);
}
