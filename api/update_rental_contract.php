<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';
require_once __DIR__ . '/../includes/rental_payment.php';

$companyId = $_SESSION['company_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Kontrakt-ID mangler']);
    exit;
}

function generateOrderId() {
    return 'RNT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
}

try {
    $db = getDBConnection();
    $db->beginTransaction();

    $stmt = $db->prepare("
        SELECT rc.*, ro.object_name, ro.company_id AS obj_company_id
        FROM rental_contracts rc
        INNER JOIN rental_objects ro
          ON ro.id = rc.rental_object_id AND ro.company_id = rc.company_id
        WHERE rc.id = ? AND rc.company_id = ?
    ");
    $stmt->execute([$data['id'], $companyId]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contract) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Kontrakt ikke funnet']);
        exit;
    }

    $activationRequested = ($data['status'] ?? null) === 'active'
        && $contract['status'] !== 'active';
    if ($activationRequested) {
        // Serialize activation attempts for this object before checking the
        // date interval. This closes the race between two administrators.
        $objectLock = $db->prepare(
            'SELECT id FROM rental_objects WHERE id = ? AND company_id = ? FOR UPDATE'
        );
        $objectLock->execute([$contract['rental_object_id'], $companyId]);
        if (!$objectLock->fetchColumn()) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Utleieobjekt ikke funnet']);
            exit;
        }

        // FOR UPDATE is a locking/current read in InnoDB, rather than a
        // repeatable-read snapshot. It sees contracts committed after the
        // initial contract lookup while the object lock is held.
        $activeConflict = $db->prepare("
            SELECT id FROM rental_contracts
            WHERE rental_object_id = ? AND company_id = ? AND status = 'active' AND id != ?
              AND start_date <= ? AND end_date >= ?
            LIMIT 1
            FOR UPDATE
        ");
        $activeConflict->execute([
            $contract['rental_object_id'],
            $companyId,
            $data['id'],
            $contract['end_date'],
            $contract['start_date']
        ]);
        if ($activeConflict->fetchColumn() !== false) {
            $db->rollBack();
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Objektet er allerede utleid (aktiv kontrakt eksisterer). Avslutt eller kanseller eksisterende kontrakt først.']);
            exit;
        }
    }

    $updateFields = [];
    $updateParams = [];

    $allowedFields = ['payment_method', 'total_amount', 'deposit_amount', 'deposit_paid', 'notes', 'customer_name'];
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $updateFields[] = "$field = ?";
            $updateParams[] = $data[$field];
        }
    }

    $newStatus        = null;
    $newPaymentStatus = null;

    if (!empty($data['status'])) {
        $allowedStatuses = ['draft', 'active', 'completed', 'cancelled'];
        if (in_array($data['status'], $allowedStatuses)) {
            $newStatus = $data['status'];
            $updateFields[] = 'status = ?';
            $updateParams[] = $newStatus;
        }
    }

    if (!empty($data['payment_status'])) {
        $allowedPS = ['unpaid', 'partial', 'paid'];
        if (in_array($data['payment_status'], $allowedPS)) {
            $newPaymentStatus = $data['payment_status'];
            $updateFields[] = 'payment_status = ?';
            $updateParams[] = $newPaymentStatus;
            $paidAtTransition = rentalPaidAtTransition($contract['payment_status'], $newPaymentStatus);
            if ($paidAtTransition === 'set_now') {
                // Set only on the first paid transition; repeating the same
                // request must not move the historical payment timestamp.
                $updateFields[] = 'paid_at = NOW()';
            } elseif ($paidAtTransition === 'clear') {
                // An explicit unpaid/partial update is a payment reversal.
                $updateFields[] = 'paid_at = NULL';
            }
        }
    }

    if (!empty($updateFields)) {
        $updateParams[] = $data['id'];
        $updateParams[] = $companyId;
        $db->prepare("UPDATE rental_contracts SET " . implode(', ', $updateFields) . " WHERE id = ? AND company_id = ?")
           ->execute($updateParams);
    }

    if ($newStatus === 'active' && $contract['status'] !== 'active') {
        $db->prepare("UPDATE rental_objects SET is_available = 0 WHERE id = ? AND company_id = ?")
           ->execute([$contract['rental_object_id'], $companyId]);
    }

    if (in_array($newStatus, ['completed', 'cancelled']) && $contract['status'] === 'active') {
        $remainingStmt = $db->prepare("
            SELECT COUNT(*) FROM rental_contracts
            WHERE rental_object_id = ? AND company_id = ? AND status = 'active' AND id != ?
        ");
        $remainingStmt->execute([$contract['rental_object_id'], $companyId, $data['id']]);
        if ($remainingStmt->fetchColumn() == 0) {
            $db->prepare("UPDATE rental_objects SET is_available = 1 WHERE id = ? AND company_id = ?")
               ->execute([$contract['rental_object_id'], $companyId]);
        }
    }

    if ($newPaymentStatus === 'paid' && $contract['payment_status'] !== 'paid') {
        $amount = floatval($data['total_amount'] ?? $contract['total_amount']);
        $existingLedger = $db->prepare(
            "SELECT COUNT(*) FROM unified_ledger WHERE module_source = 'rental' AND source_id = ? AND company_id = ?"
        );
        $existingLedger->execute([$contract['id'], $companyId]);
        if ($amount > 0 && $existingLedger->fetchColumn() == 0) {
            $ledger = new UnifiedLedgerService($db, $companyId);
            $ledgerResult = $ledger->logTransaction([
                'order_id'         => generateOrderId(),
                'module_source'    => 'rental',
                'item_type'        => 'rental',
                'source_id'        => $contract['id'],
                'item_name'        => 'Utleie: ' . $contract['object_name'],
                'selling_price'    => $amount,
                'customer_id'      => $contract['customer_id'] ?: null,
                'payment_method'   => $data['payment_method'] ?? $contract['payment_method'],
                'payment_status'   => 'completed',
                'status'           => 'active',
                'transaction_type' => 'income',
                'category'         => 'Utleie',
                'metadata'         => [
                    'customer_name' => $contract['customer_name'],
                    'start_date'    => $contract['start_date'],
                    'end_date'      => $contract['end_date'],
                    'rental_days'   => $contract['rental_days']
                ]
            ]);
            if (empty($ledgerResult['success'])) {
                throw new \RuntimeException('Regnskapsføring feilet: ' . ($ledgerResult['message'] ?? 'ukjent feil'));
            }
        }
    }

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Kontrakt oppdatert']);

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('update_rental_contract error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering av kontrakt. Prøv igjen.'
    ]);
}
