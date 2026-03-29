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
        INNER JOIN rental_objects ro ON ro.id = rc.rental_object_id
        WHERE rc.id = ? AND rc.company_id = ?
    ");
    $stmt->execute([$data['id'], $companyId]);
    $contract = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$contract) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Kontrakt ikke funnet']);
        exit;
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

    $ledgerWarning = null;
    if ($newPaymentStatus === 'paid' && $contract['payment_status'] !== 'paid') {
        $amount = floatval($data['total_amount'] ?? $contract['total_amount']);
        if ($amount > 0) {
            try {
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
            } catch (\Throwable $ledgerErr) {
                error_log('UnifiedLedger rental error: ' . $ledgerErr->getMessage());
                $ledgerWarning = 'Kontrakt oppdatert, men regnskapsføring feilet (exception): ' . $ledgerErr->getMessage();
            }
            if (!isset($ledgerWarning) && isset($ledgerResult) && empty($ledgerResult['success'])) {
                $ledgerWarning = 'Kontrakt oppdatert, men regnskapsføring ble ikke lagret: ' . ($ledgerResult['message'] ?? 'ukjent feil');
            }
        }
    }

    $db->commit();

    $response = ['success' => true, 'message' => 'Kontrakt oppdatert'];
    if ($ledgerWarning) {
        $response['warning'] = $ledgerWarning;
    }
    echo json_encode($response);

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering av kontrakt',
        'error'   => $e->getMessage()
    ]);
}
