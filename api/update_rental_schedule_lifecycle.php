<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$tenantAdminId = (string) $_SESSION['tenant_admin_id'];
$companyId = (string) $_SESSION['company_id'];
$sessionCsrf = (string) ($_SESSION['rental_lifecycle_csrf'] ?? '');
session_write_close();

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

$data = json_decode(file_get_contents('php://input'), true);
$scheduleId = is_array($data) ? trim((string) ($data['id'] ?? '')) : '';
$action = is_array($data) ? trim((string) ($data['action'] ?? '')) : '';
$requestCsrf = is_array($data) ? (string) ($data['csrf_token'] ?? '') : '';

if ($sessionCsrf === '' || $requestCsrf === '' || !hash_equals($sessionCsrf, $requestCsrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Sikkerhetskontrollen feilet. Last siden på nytt.']);
    exit;
}

if ($scheduleId === '' || !in_array($action, ['mark_paid', 'cancel'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig handling']);
    exit;
}

function rentalScheduleOrderId(): string {
    return 'RNS-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

try {
    $db = getDBConnection();

    $access = $db->prepare("
        SELECT 1
        FROM tenant_admin_modules tam
        INNER JOIN system_modules sm ON sm.id = tam.module_id
        WHERE tam.tenant_admin_id = ? AND sm.module_key = 'rental'
        LIMIT 1
    ");
    $access->execute([$tenantAdminId]);
    if (!$access->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Du har ikke tilgang til Rental-modulen']);
        exit;
    }

    $db->beginTransaction();

    $stmt = $db->prepare("
        SELECT rs.*, ro.object_name
        FROM rental_schedule rs
        INNER JOIN rental_objects ro
          ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
        WHERE rs.id = ? AND rs.company_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$scheduleId, $companyId]);
    $rental = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$rental) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Utleien ble ikke funnet']);
        exit;
    }

    if ($action === 'cancel') {
        if ($rental['status'] === 'cancelled') {
            $db->commit();
            echo json_encode(['success' => true, 'message' => 'Utleien er allerede avlyst']);
            exit;
        }
        if ($rental['status'] !== 'approved') {
            $db->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Bare godkjente kommende leier kan avlyses']);
            exit;
        }

        $db->prepare("
            UPDATE rental_schedule
            SET status = 'cancelled', cancelled_at = NOW(), updated_at = NOW()
            WHERE id = ? AND company_id = ?
        ")->execute([$scheduleId, $companyId]);

        if (($rental['payment_status'] ?? 'unpaid') === 'paid') {
            $db->prepare("
                UPDATE unified_ledger
                SET status = 'cancelled'
                WHERE company_id = ? AND rental_schedule_id = ?
            ")->execute([$companyId, $scheduleId]);
        }

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Utleien er avlyst']);
        exit;
    }

    if ($rental['status'] !== 'approved') {
        $db->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Bare godkjente kommende leier kan registreres som betalt']);
        exit;
    }

    $isAlreadyPaid = ($rental['payment_status'] ?? 'unpaid') === 'paid';
    $allowedMethods = [
        'vipps' => 'Vipps',
        'card' => 'Kort',
        'cash' => 'Kontant',
        'invoice' => 'Faktura',
        'bank_transfer' => 'Bankoverføring',
        'other' => 'Annet',
    ];
    $methodKey = trim((string) ($data['payment_method'] ?? ''));
    if (!$isAlreadyPaid && !isset($allowedMethods[$methodKey])) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Velg betalingsmåte']);
        exit;
    }

    $amount = (float) ($rental['rental_fee'] ?? 0);
    if ($amount <= 0) {
        $db->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Leiebeløpet må være større enn 0 før betaling kan registreres']);
        exit;
    }

    $existing = $db->prepare("
        SELECT id, module_source, item_type, source_id, selling_price,
               payment_method, transaction_type, payment_status, status
        FROM unified_ledger
        WHERE company_id = ? AND rental_schedule_id = ?
        LIMIT 1
        FOR UPDATE
    ");
    $existing->execute([$companyId, $scheduleId]);
    $existingLedger = $existing->fetch(PDO::FETCH_ASSOC);
    $existingLedgerId = $existingLedger['id'] ?? null;

    if ($existingLedger && (
        $existingLedger['module_source'] !== 'rental'
        || $existingLedger['item_type'] !== 'rental'
        || $existingLedger['source_id'] !== $scheduleId
        || abs((float) $existingLedger['selling_price'] - $amount) > 0.005
        || $existingLedger['transaction_type'] !== 'income'
        || $existingLedger['payment_status'] !== 'completed'
        || $existingLedger['status'] !== 'active'
    )) {
        $db->rollBack();
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Den eksisterende regnskapsføringen må korrigeres før betaling kan registreres']);
        exit;
    }

    if (!$existingLedgerId) {
        $legacy = $db->prepare("
            SELECT id, selling_price, payment_method
            FROM unified_ledger
            WHERE company_id = ?
              AND module_source = 'rental'
              AND source_id = ?
              AND transaction_type = 'income'
              AND payment_status = 'completed'
              AND status = 'active'
            FOR UPDATE
        ");
        $legacy->execute([$companyId, $scheduleId]);
        $legacyLedgers = $legacy->fetchAll(PDO::FETCH_ASSOC);
        if (count($legacyLedgers) > 1) {
            $db->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Flere regnskapsposter finnes for denne leien. De må korrigeres før betalingen kan registreres.']);
            exit;
        }
        if (count($legacyLedgers) === 1) {
            $legacyLedger = $legacyLedgers[0];
            if (abs((float) $legacyLedger['selling_price'] - $amount) > 0.005) {
                $db->rollBack();
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Regnskapsbeløpet stemmer ikke med leiebeløpet']);
                exit;
            }
            $existingLedgerId = $legacyLedger['id'];
            $existingLedger = $legacyLedger;
            $db->prepare("
                UPDATE unified_ledger
                SET rental_schedule_id = ?
                WHERE id = ? AND company_id = ?
            ")->execute([$scheduleId, $existingLedgerId, $companyId]);
        }
    }

    if ($isAlreadyPaid) {
        if (!$existingLedgerId) {
            $db->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Leien er markert betalt, men mangler en gyldig regnskapspost']);
            exit;
        }
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Betalingen er allerede registrert']);
        exit;
    }

    if (!$existingLedgerId) {
        $ledger = new UnifiedLedgerService($db, $companyId);
        $ledgerResult = $ledger->logTransaction([
            'order_id' => rentalScheduleOrderId(),
            'module_source' => 'rental',
            'item_type' => 'rental',
            'source_id' => $scheduleId,
            'item_name' => 'Utleie: ' . ($rental['object_name'] ?: 'Utleieobjekt'),
            'selling_price' => $amount,
            'customer_id' => $rental['customer_id'] ?: null,
            'payment_method' => $allowedMethods[$methodKey],
            'payment_status' => 'completed',
            'status' => 'active',
            'transaction_type' => 'income',
            'category' => 'Utleie',
            'metadata' => [
                'source_table' => 'rental_schedule',
                'customer_name' => $rental['customer_name'] ?? null,
                'customer_email' => $rental['customer_email'] ?? null,
                'start_date' => $rental['start_date'],
                'end_date' => $rental['end_date'],
            ],
        ]);
        if (empty($ledgerResult['success'])) {
            throw new RuntimeException('Regnskapsføring feilet: ' . ($ledgerResult['message'] ?? 'ukjent feil'));
        }
        $existingLedgerId = $ledgerResult['ledger_id'];
        $db->prepare("
            UPDATE unified_ledger
            SET rental_schedule_id = ?
            WHERE id = ? AND company_id = ?
        ")->execute([$scheduleId, $existingLedgerId, $companyId]);
    }

    $resolvedPaymentMethod = $existingLedger['payment_method'] ?? $allowedMethods[$methodKey];
    $db->prepare("
        UPDATE rental_schedule
        SET payment_status = 'paid', payment_method = ?, paid_at = NOW(), updated_at = NOW()
        WHERE id = ? AND company_id = ?
    ")->execute([$resolvedPaymentMethod, $scheduleId, $companyId]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Betalingen er registrert og ført i regnskapet']);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('update_rental_schedule_lifecycle error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kunne ikke oppdatere utleien. Prøv igjen.']);
}
