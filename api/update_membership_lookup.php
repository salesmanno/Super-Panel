<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$company_id = $_SESSION['company_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig forespørsel']);
    exit;
}

$member_id = trim((string) ($data['membership_member_id'] ?? ''));
$renewal_id = trim((string) ($data['renewal_id'] ?? ''));
$payment_status = trim((string) ($data['payment_status'] ?? ''));
$expiration_date = trim((string) ($data['expiration_date'] ?? ''));
$renewal_status = trim((string) ($data['renewal_status'] ?? ''));

$allowedPaymentStatuses = ['paid', 'unpaid', 'pending', 'refunded'];
$allowedRenewalStatuses = ['pending', 'payment_pending', 'completed', 'cancelled', 'expired'];

if ($member_id === '' || !in_array($payment_status, $allowedPaymentStatuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig medlem eller betalingsstatus']);
    exit;
}

if ($expiration_date !== '') {
    $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $expiration_date);
    $dateErrors = DateTimeImmutable::getLastErrors();
    if (
        !$parsedDate
        || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0))
        || $parsedDate->format('Y-m-d') !== $expiration_date
    ) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ugyldig utløpsdato']);
        exit;
    }
}

if ($renewal_id !== '' && !in_array($renewal_status, $allowedRenewalStatuses, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig fornyelsesstatus']);
    exit;
}

try {
    $db = getDBConnection();
    $db->beginTransaction();

    $memberStmt = $db->prepare("
        SELECT id
        FROM membership_members
        WHERE id = ? AND company_id = ?
        FOR UPDATE
    ");
    $memberStmt->execute([$member_id, $company_id]);
    if (!$memberStmt->fetchColumn()) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Medlem ikke funnet']);
        exit;
    }

    if ($renewal_id !== '') {
        $renewalStmt = $db->prepare("
            SELECT id
            FROM membership_members_renew
            WHERE id = ?
              AND membership_member_id = ?
              AND company_id = ?
            FOR UPDATE
        ");
        $renewalStmt->execute([$renewal_id, $member_id, $company_id]);
        if (!$renewalStmt->fetchColumn()) {
            $db->rollBack();
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Fornyelse ikke funnet for medlemmet']);
            exit;
        }
    }

    $memberUpdateStmt = $db->prepare("
        UPDATE membership_members
        SET payment_status = ?,
            expiration_date = ?
        WHERE id = ? AND company_id = ?
    ");
    $memberUpdateStmt->execute([
        $payment_status,
        $expiration_date !== '' ? $expiration_date : null,
        $member_id,
        $company_id
    ]);

    if ($renewal_id !== '') {
        $completed_at = $renewal_status === 'completed' ? 'NOW()' : 'NULL';
        $renewalUpdateStmt = $db->prepare("
            UPDATE membership_members_renew
            SET status = ?,
                completed_at = {$completed_at}
            WHERE id = ?
              AND membership_member_id = ?
              AND company_id = ?
        ");
        $renewalUpdateStmt->execute([$renewal_status, $renewal_id, $member_id, $company_id]);
    }

    $db->commit();
    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('update_membership_lookup error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kunne ikke lagre medlemsopplysninger']);
}