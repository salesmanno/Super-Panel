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
require_once __DIR__ . '/../includes/rental_confirmation_email.php';

$companyId = $_SESSION['company_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID mangler']);
    exit;
}

try {
    if (array_key_exists('pickup_time', $data)) {
        $data['pickup_time'] = normalizeRequestTime($data['pickup_time'], 'Hentetid');
    }
    if (array_key_exists('end_time', $data)) {
        $data['end_time'] = normalizeRequestTime($data['end_time'], 'Sluttid');
    }
} catch (InvalidArgumentException $e) {
    error_log('update_rental_schedule validation error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig tidspunkt']);
    exit;
}

try {
    $db = getDBConnection();
    $db->beginTransaction();

    $stmt = $db->prepare("
        SELECT rs.id, rs.company_id, rs.approved, rs.status,
               rs.start_date, rs.end_date, rs.rental_fee, rs.customer_id,
               rs.pickup_time, rs.end_time, rs.logistics, rs.notes,
               rs.customer_name AS request_customer_name,
               rs.customer_email AS request_customer_email,
               ro.object_name,
               tc.email AS profile_customer_email,
               tc.first_name AS profile_first_name,
               tc.last_name AS profile_last_name,
               company.name AS company_name
        FROM rental_schedule rs
        LEFT JOIN rental_objects ro
          ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
        LEFT JOIN tenant_customers tc ON tc.id = rs.customer_id AND tc.company_id = rs.company_id
        LEFT JOIN tenant_companies company ON company.id = rs.company_id
        WHERE rs.id = ? AND rs.company_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$data['id'], $companyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Forespørsel ikke funnet']);
        exit;
    }

    $requestCustomerName = trim((string) ($row['request_customer_name'] ?? ''));
    $profileCustomerName = trim(
        (string) ($row['profile_first_name'] ?? '') . ' ' . (string) ($row['profile_last_name'] ?? '')
    );
    $row['customer_name'] = $requestCustomerName !== '' ? $requestCustomerName : $profileCustomerName;
    $requestCustomerEmail = trim((string) ($row['request_customer_email'] ?? ''));
    $row['customer_email'] = $requestCustomerEmail !== ''
        ? $requestCustomerEmail
        : trim((string) ($row['profile_customer_email'] ?? ''));

    $updateFields = [];
    $updateParams = [];
    $approvingNow = false;

    $newApproved = null;
    $newStatus = (string) ($row['status'] ?? 'pending');
    $allowedStatuses = ['pending', 'approved', 'rejected', 'completed', 'cancelled'];
    if (array_key_exists('status', $data)
        && (!is_string($data['status']) || !in_array($data['status'], $allowedStatuses, true))) {
        throw new InvalidArgumentException('Ugyldig forespørselsstatus');
    }

    if (array_key_exists('approved', $data)) {
        $approvedValue = $data['approved'];
        $validApproved = (is_int($approvedValue) && in_array($approvedValue, [0, 1, 2], true))
            || (is_string($approvedValue) && in_array($approvedValue, ['0', '1', '2'], true));
        if (!$validApproved) {
            throw new InvalidArgumentException('Ugyldig godkjenningsstatus');
        }
        $newApproved = (int) $approvedValue;
        $newStatus = [0 => 'pending', 1 => 'approved', 2 => 'rejected'][$newApproved];
        if (array_key_exists('status', $data) && $data['status'] !== $newStatus) {
            throw new InvalidArgumentException('Godkjenningsstatus og forespørselsstatus må samsvare');
        }
        $updateFields[] = 'approved = ?';
        $updateParams[] = $newApproved;
        if ($newApproved === 1 && (int)$row['approved'] !== 1) {
            $approvingNow = true;
        }
        // Approval and status are intentionally written in the same UPDATE.
        $updateFields[] = 'status = ?';
        $updateParams[] = $newStatus;
    } elseif (array_key_exists('status', $data)) {
        $newStatus = $data['status'];
        $updateFields[] = 'status = ?';
        $updateParams[] = $newStatus;
        if ($newStatus === 'approved' || $newStatus === 'rejected' || $newStatus === 'pending') {
            $newApproved = ['pending' => 0, 'approved' => 1, 'rejected' => 2][$newStatus];
            $updateFields[] = 'approved = ?';
            $updateParams[] = $newApproved;
            if ($newApproved === 1 && (int) $row['approved'] !== 1) {
                $approvingNow = true;
            }
        }
    }

    if (array_key_exists('pickup_time', $data)) {
        $pt = $data['pickup_time'];
        $updateFields[] = 'pickup_time = ?';
        $updateParams[] = ($pt !== null && $pt !== '') ? $pt : null;
    }

    if (array_key_exists('end_time', $data)) {
        $et = $data['end_time'];
        $updateFields[] = 'end_time = ?';
        $updateParams[] = ($et !== null && $et !== '') ? $et : null;
    }

    if (empty($updateFields)) {
        $db->rollBack();
        echo json_encode([
            'success' => true,
            'message' => 'Ingen endringer',
            'status' => (string) ($row['status'] ?? 'pending'),
            'approved' => (int) ($row['approved'] ?? 0),
            'email_queued' => false
        ]);
        exit;
    }

    $updateParams[] = $data['id'];
    $updateParams[] = $companyId;

    $db->prepare("UPDATE rental_schedule SET " . implode(', ', $updateFields) . " WHERE id = ? AND company_id = ?")
       ->execute($updateParams);

    // Auto-email on approval
    $emailQueued = false;
    if ($approvingNow) {
        $cfgStmt = $db->prepare("
            SELECT email, display_name, automail_rental_confirm
            FROM email_configs
            WHERE company_id = ? AND is_active = 1
            LIMIT 1
        ");
        $cfgStmt->execute([$companyId]);
        $cfg = $cfgStmt->fetch(PDO::FETCH_ASSOC);

        if ($cfg && (int) $cfg['automail_rental_confirm'] === 1) {
            if (!filter_var($row['customer_email'], FILTER_VALIDATE_EMAIL)) {
                throw new RentalConfirmationEmailException(
                    'Forespørselen kan ikke godkjennes fordi kunden mangler en gyldig e-postadresse.'
                );
            }
            if (!filter_var($cfg['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
                throw new RentalConfirmationEmailException(
                    'Forespørselen kan ikke godkjennes fordi avsenderadressen for e-post ikke er gyldig.'
                );
            }

            $tplStmt = $db->prepare("
                SELECT id, subject_template, body_template
                FROM email_templates
                WHERE company_id = ? AND template_key = 'rental_confirmation' AND is_active = 1
                LIMIT 1
            ");
            $tplStmt->execute([$companyId]);
            $tpl = $tplStmt->fetch(PDO::FETCH_ASSOC);
            if (!$tpl) {
                throw new RentalConfirmationEmailException(
                    'Forespørselen kan ikke godkjennes fordi e-postmalen for utleiebekreftelse mangler.'
                );
            }

            $vars = buildRentalConfirmationVariables($row);
            $subject = renderRentalConfirmationTemplate($tpl['subject_template'], $vars);
            $body = renderRentalConfirmationTemplate($tpl['body_template'], $vars);

            $db->prepare("
                INSERT INTO email_queue
                    (id, company_id, recipient_email, from_email, subject, body,
                     template_id, module_source, source_id, status, priority,
                     scheduled_at, created_at, updated_at)
                VALUES
                    (UUID(), ?, ?, ?, ?, ?, ?, 'rental', ?, 'queued', 5, NOW(), NOW(), NOW())
            ")->execute([
                $companyId,
                $row['customer_email'],
                $cfg['email'],
                $subject,
                $body,
                $tpl['id'],
                $data['id'],
            ]);

            $emailQueued = true;
        }
    }

    $db->commit();

    echo json_encode([
        'success'      => true,
        'message'      => 'Forespørsel oppdatert',
        'status'       => $newStatus,
        'approved'     => $newApproved === null ? (int) ($row['approved'] ?? 0) : $newApproved,
        'email_queued' => $emailQueued,
    ]);

} catch (InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('update_rental_schedule validation error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig forespørselsdata']);
} catch (RentalConfirmationEmailException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('rental automail validation error: ' . $e->getMessage());
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('update_rental_schedule error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Feil ved oppdatering. Prøv igjen.']);
}
