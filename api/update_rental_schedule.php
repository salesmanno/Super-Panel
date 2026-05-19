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
$data = json_decode(file_get_contents('php://input'), true);

if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $db->beginTransaction();

    $stmt = $db->prepare("
        SELECT rs.id, rs.company_id, rs.approved, rs.status,
               rs.start_date, rs.end_date, rs.rental_fee, rs.customer_id,
               rs.pickup_time, rs.end_time, rs.logistics, rs.notes,
               ro.object_name,
               tc.email AS customer_email, tc.first_name, tc.last_name
        FROM rental_schedule rs
        LEFT JOIN rental_objects ro  ON ro.id = rs.rental_object_id
        LEFT JOIN tenant_customers tc ON tc.id = rs.customer_id AND tc.company_id = rs.company_id
        WHERE rs.id = ? AND rs.company_id = ?
    ");
    $stmt->execute([$data['id'], $companyId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Forespørsel ikke funnet']);
        exit;
    }

    $updateFields = [];
    $updateParams = [];
    $approvingNow = false;

    if (isset($data['approved']) && in_array((int)$data['approved'], [0, 1, 2], true)) {
        $newApproved = (int) $data['approved'];
        $updateFields[] = 'approved = ?';
        $updateParams[] = $newApproved;
        if ($newApproved === 1 && (int)$row['approved'] !== 1) {
            $approvingNow = true;
        }
    }

    $allowedStatuses = ['pending', 'approved', 'declined', 'completed', 'cancelled'];
    if (!empty($data['status']) && in_array($data['status'], $allowedStatuses)) {
        $updateFields[] = 'status = ?';
        $updateParams[] = $data['status'];
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
        echo json_encode(['success' => true, 'message' => 'Ingen endringer', 'email_queued' => false]);
        exit;
    }

    $updateParams[] = $data['id'];
    $updateParams[] = $companyId;

    $db->prepare("UPDATE rental_schedule SET " . implode(', ', $updateFields) . " WHERE id = ? AND company_id = ?")
       ->execute($updateParams);

    $db->commit();

    // Auto-email on approval
    $emailQueued = false;
    if ($approvingNow && !empty($row['customer_email'])) {
        try {
            $cfgStmt = $db->prepare("
                SELECT email, display_name, automail_rental_confirm
                FROM email_configs
                WHERE company_id = ? AND is_active = 1
                LIMIT 1
            ");
            $cfgStmt->execute([$companyId]);
            $cfg = $cfgStmt->fetch(PDO::FETCH_ASSOC);

            if ($cfg && !empty($cfg['automail_rental_confirm'])) {
                $tplStmt = $db->prepare("
                    SELECT subject_template, body_template
                    FROM email_templates
                    WHERE company_id = ? AND template_key = 'rentalinvoice' AND is_active = 1
                    LIMIT 1
                ");
                $tplStmt->execute([$companyId]);
                $tpl = $tplStmt->fetch(PDO::FETCH_ASSOC);

                if ($tpl) {
                    $vars = [
                        '{{cus_first_name}}' => $row['first_name'] ?? '',
                        '{{cus_last_name}}'  => $row['last_name']  ?? '',
                        '{{rental_start}}'   => $row['start_date'] ?? '',
                        '{{rental_end}}'     => $row['end_date']   ?? '',
                        '{{rental_fee}}'     => number_format((float)($row['rental_fee'] ?? 0), 2, ',', ' '),
                        '{{object_name}}'    => $row['object_name'] ?? '',
                    ];
                    $subject = str_replace(array_keys($vars), array_values($vars), $tpl['subject_template']);
                    $body    = str_replace(array_keys($vars), array_values($vars), $tpl['body_template']);

                    $db->prepare("
                        INSERT INTO email_queue
                            (id, company_id, recipient_email, from_email, subject, body,
                             module_source, source_id, status, priority, scheduled_at, created_at, updated_at)
                        VALUES
                            (UUID(), ?, ?, ?, ?, ?, 'rental', ?, 'queued', 5, NOW(), NOW(), NOW())
                    ")->execute([
                        $companyId,
                        $row['customer_email'],
                        $cfg['email'],
                        $subject,
                        $body,
                        $data['id'],
                    ]);

                    $emailQueued = true;
                }
            }
        } catch (\Throwable $emailErr) {
            error_log('rental automail error: ' . $emailErr->getMessage());
        }
    }

    echo json_encode([
        'success'      => true,
        'message'      => 'Forespørsel oppdatert',
        'email_queued' => $emailQueued,
    ]);

} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('update_rental_schedule error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Feil ved oppdatering. Prøv igjen.']);
}
