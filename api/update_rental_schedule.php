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
require_once __DIR__ . '/../includes/rental_schedule_editing.php';
require_once __DIR__ . '/../includes/rental_request.php';

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

    $concurrencyTestToken = getenv('RENTAL_CONCURRENCY_TEST_TOKEN') ?: '';
    $isConcurrencyTestRequest = getenv('RUN_RENTAL_CONCURRENCY_TEST') === '1'
        && $concurrencyTestToken !== ''
        && hash_equals(
            $concurrencyTestToken,
            (string) ($_SERVER['HTTP_X_RENTAL_CONCURRENCY_TOKEN'] ?? '')
        );
    $concurrencyTestRole = $isConcurrencyTestRequest
        ? (string) ($_SERVER['HTTP_X_RENTAL_CONCURRENCY_ROLE'] ?? '')
        : '';
    if ($concurrencyTestRole === 'contender') {
        $contenderPath = getenv('RENTAL_CONCURRENCY_CONTENDER_PATH') ?: '';
        if ($contenderPath !== '') {
            file_put_contents($contenderPath, (string) getmypid(), LOCK_EX);
        }
    }

    $stmt = $db->prepare("
        SELECT rs.id, rs.company_id, rs.rental_object_id, rs.approved, rs.status,
               rs.start_date, rs.end_date, rs.rental_fee, rs.customer_id,
               rs.pickup_time, rs.start_time, rs.end_time, rs.logistics, rs.notes,
               rs.key_box_code,
               COALESCE(rs.cleaning, 0) AS cleaning,
               rs.price_adjustment_type, COALESCE(rs.price_adjustment_cents, 0) AS price_adjustment_cents,
               rs.price_adjustment_explanation,
               rs.customer_name AS request_customer_name,
               rs.customer_email AS request_customer_email,
               ro.object_name, ro.rental_text, ro.rental_price, ro.weekend_price,
               ro.saturday_price, ro.sunday_price, rv.access_code,
               COALESCE(rv.cleaning_fee, 0) AS cleaning_fee,
               tc.email AS profile_customer_email,
               tc.first_name AS profile_first_name,
               tc.last_name AS profile_last_name,
               company.name AS company_name,
               COALESCE(NULLIF(company.logo_url, ''), NULLIF(twg.logo_url, ''), '') AS logo_url,
               COALESCE(NULLIF(twg.header_bg_color, ''), '#ffffff') AS header_bg_color,
               company.contact_phone, company.address_street, company.address_zip,
               company.address_city, company.org_number,
               (
                   SELECT eba.account_number
                   FROM economy_bank_accounts eba
                   WHERE eba.company_id = rs.company_id
                     AND eba.is_active = 1
                     AND eba.account_number IS NOT NULL
                     AND eba.account_number <> ''
                   ORDER BY eba.bank_name ASC, eba.account_number ASC
                   LIMIT 1
               ) AS account_number
        FROM rental_schedule rs
        LEFT JOIN rental_objects ro
          ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
        LEFT JOIN rental_venues rv ON rv.object_id = ro.id
        LEFT JOIN tenant_customers tc ON tc.id = rs.customer_id AND tc.company_id = rs.company_id
        LEFT JOIN tenant_companies company ON company.id = rs.company_id
        LEFT JOIN tenant_website_general twg ON twg.company_id = rs.company_id
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

    if ($concurrencyTestRole === 'holder') {
        $holderPath = getenv('RENTAL_CONCURRENCY_HOLDER_PATH') ?: '';
        $contenderPath = getenv('RENTAL_CONCURRENCY_CONTENDER_PATH') ?: '';
        if ($holderPath !== '' && $contenderPath !== '') {
            file_put_contents($holderPath, (string) getmypid(), LOCK_EX);
            $contenderDeadline = microtime(true) + 5;
            while (!is_file($contenderPath) && microtime(true) < $contenderDeadline) {
                usleep(10000);
            }
            if (!is_file($contenderPath)) {
                throw new RuntimeException(
                    'Samtidighetstesten mottok ikke en andre worker før tidsfristen.'
                );
            }
        }
    }

    if ((int) ($row['approved'] ?? 0) !== 0 || (string) ($row['status'] ?? 'pending') !== 'pending') {
        $db->rollBack();
        http_response_code(409);
        $alreadyApproved = (string) ($row['status'] ?? '') === 'approved'
            && ($data['status'] ?? null) === 'approved';
        echo json_encode([
            'success' => false,
            'already_approved' => $alreadyApproved,
            'stale_request' => true,
            'message' => $alreadyApproved
                ? 'Forespørselen er allerede godkjent.'
                : 'Forespørselen er allerede behandlet. Last inn listen på nytt.'
        ]);
        exit;
    }

    if (array_key_exists('start_date', $data)) {
        $data['start_date'] = normalizeRequestDate($data['start_date'], 'Startdato', false);
    }
    if (array_key_exists('end_date', $data)) {
        $data['end_date'] = normalizeRequestDate($data['end_date'], 'Sluttdato', false);
    }
    // Ignore legacy clients attempting to customize the fixed pickup time.
    $data['pickup_time'] = '08:00:00';
    if (array_key_exists('end_time', $data)) {
        $data['end_time'] = normalizeRequestTime($data['end_time'], 'Sluttid', false);
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
    $finalizingNow = false;

    $newApproved = null;
    $newStatus = (string) ($row['status'] ?? 'pending');
    $allowedStatuses = ['pending', 'approved', 'rejected', 'completed', 'cancelled'];
    if (array_key_exists('status', $data)
        && (!is_string($data['status']) || !in_array($data['status'], $allowedStatuses, true))) {
        throw new InvalidArgumentException('Ugyldig forespørselsstatus');
    }

    if (array_key_exists('approved', $data)) {
        $approvedValue = $data['approved'];
        $validApproved = (is_int($approvedValue) && in_array($approvedValue, [0, 1], true))
            || (is_string($approvedValue) && in_array($approvedValue, ['0', '1'], true));
        if (!$validApproved) {
            throw new InvalidArgumentException('Ugyldig godkjenningsstatus');
        }
        $newApproved = (int) $approvedValue;
        $newStatus = $data['status'] ?? ($newApproved === 1 ? 'approved' : 'pending');
        $validStatusForApproved = $newApproved === 1
            ? in_array($newStatus, ['approved', 'rejected'], true)
            : $newStatus === 'pending';
        if (!$validStatusForApproved) {
            throw new InvalidArgumentException('Godkjenningsstatus og forespørselsstatus må samsvare');
        }
        $updateFields[] = 'approved = ?';
        $updateParams[] = $newApproved;
        $finalizingNow = $newApproved === 1;
        // Approval and status are intentionally written in the same UPDATE.
        $updateFields[] = 'status = ?';
        $updateParams[] = $newStatus;
    } elseif (array_key_exists('status', $data)) {
        $newStatus = $data['status'];
        $updateFields[] = 'status = ?';
        $updateParams[] = $newStatus;
        if ($newStatus === 'approved' || $newStatus === 'rejected' || $newStatus === 'pending') {
            $newApproved = $newStatus === 'pending' ? 0 : 1;
            $updateFields[] = 'approved = ?';
            $updateParams[] = $newApproved;
            $finalizingNow = $newApproved === 1;
        }
    }
    $row['status'] = $newStatus;
    if ($newApproved !== null) {
        $row['approved'] = $newApproved;
    }
    if (array_key_exists('key_box_code', $data)) {
        $keyBoxCode = trim((string) $data['key_box_code']);
        if (strlen($keyBoxCode) > 6) {
            throw new InvalidArgumentException('Nøkkelbokskoden er for lang.');
        }
        $updateFields[] = 'key_box_code = ?';
        $updateParams[] = $keyBoxCode !== '' ? $keyBoxCode : null;
        $row['key_box_code'] = $keyBoxCode !== '' ? $keyBoxCode : null;
    }
    if ($finalizingNow && $newStatus === 'approved'
        && trim((string) ($row['key_box_code'] ?? '')) === '') {
        $updateFields[] = 'key_box_code = ?';
        $updateParams[] = $row['access_code'] !== null && $row['access_code'] !== ''
            ? $row['access_code']
            : null;
        $row['key_box_code'] = $updateParams[array_key_last($updateParams)];
    }

    $effectiveStartDate = $data['start_date'] ?? $row['start_date'];
    $effectivePickupTime = '08:00:00';
    $effectiveEndDate = $data['end_date'] ?? $row['end_date'];
    $effectiveEndTime = $data['end_time'] ?? $row['end_time'];
    validateRentalSchedulePeriod(
        $effectiveStartDate,
        $effectivePickupTime,
        $effectiveEndDate,
        $effectiveEndTime
    );

    if (array_key_exists('start_date', $data)) {
        $updateFields[] = 'start_date = ?';
        $updateParams[] = $data['start_date'];
        $row['start_date'] = $data['start_date'];
    }
    $updateFields[] = 'pickup_time = ?';
    $updateParams[] = '08:00:00';
    $row['pickup_time'] = '08:00:00';
    if (array_key_exists('end_date', $data)) {
        $updateFields[] = 'end_date = ?';
        $updateParams[] = $data['end_date'];
        $row['end_date'] = $data['end_date'];
    }
    if (array_key_exists('end_time', $data)) {
        $et = $data['end_time'];
        $updateFields[] = 'end_time = ?';
        $updateParams[] = $et;
        $row['end_time'] = $et;
    }

    $adjustmentType = array_key_exists('price_adjustment_type', $data)
        ? (string) $data['price_adjustment_type']
        : (string) ($row['price_adjustment_type'] ?? '');
    $adjustmentAmount = array_key_exists('price_adjustment_amount', $data)
        ? $data['price_adjustment_amount']
        : ((int) ($row['price_adjustment_cents'] ?? 0) / 100);
    if ($adjustmentType !== '' && !in_array($adjustmentType, ['discount', 'surcharge'], true)) {
        throw new InvalidArgumentException('Ugyldig type prisjustering.');
    }
    $adjustmentCents = $adjustmentType === '' ? 0 : rentalAdjustmentCents($adjustmentType, $adjustmentAmount);
    $adjustmentExplanation = array_key_exists('price_adjustment_explanation', $data)
        ? trim((string) $data['price_adjustment_explanation'])
        : (string) ($row['price_adjustment_explanation'] ?? '');
    if (strlen($adjustmentExplanation) > 1000) {
        throw new InvalidArgumentException('Forklaringen på prisjusteringen er for lang.');
    }
    if ($adjustmentType !== '') {
        $normalizedAdjustmentAmount = str_replace(',', '.', trim((string) $adjustmentAmount));
        if (!preg_match('/^\d+(?:\.\d{1,2})?$/', $normalizedAdjustmentAmount)
            || rentalMoneyToCents($normalizedAdjustmentAmount) <= 0) {
            throw new InvalidArgumentException('Prisjusteringen må ha et gyldig beløp større enn null.');
        }
        if ($adjustmentExplanation === '') {
            throw new InvalidArgumentException('Prisjusteringen må ha en forklaring.');
        }
    } elseif (rentalMoneyToCents($adjustmentAmount) !== 0 || $adjustmentExplanation !== '') {
        throw new InvalidArgumentException('Velg rabatt eller tillegg for prisjusteringen.');
    }
    if (array_key_exists('price_adjustment_type', $data)
        || array_key_exists('price_adjustment_amount', $data)
        || array_key_exists('price_adjustment_explanation', $data)) {
        $updateFields[] = 'price_adjustment_type = ?';
        $updateParams[] = $adjustmentType !== '' && $adjustmentCents !== 0 ? $adjustmentType : null;
        $updateFields[] = 'price_adjustment_cents = ?';
        $updateParams[] = abs($adjustmentCents);
        $updateFields[] = 'price_adjustment_explanation = ?';
        $updateParams[] = $adjustmentExplanation !== '' ? $adjustmentExplanation : null;
        $row['price_adjustment_type'] = $adjustmentType !== '' && $adjustmentCents !== 0
            ? $adjustmentType
            : null;
        $row['price_adjustment_cents'] = abs($adjustmentCents);
        $row['price_adjustment_explanation'] = $adjustmentExplanation !== ''
            ? $adjustmentExplanation
            : null;
    }

    $newCleaning = (int) ($row['cleaning'] ?? 0);
    if (array_key_exists('cleaning', $data)) {
        $cleaningValue = $data['cleaning'];
        $validCleaning = (is_int($cleaningValue) && in_array($cleaningValue, [0, 1], true))
            || (is_string($cleaningValue) && in_array($cleaningValue, ['0', '1'], true));
        if (!$validCleaning) {
            throw new InvalidArgumentException('Ugyldig valg for renhold');
        }
        $newCleaning = (int) $cleaningValue;
        $updateFields[] = 'cleaning = ?';
        $updateParams[] = $newCleaning;
        $row['cleaning'] = $newCleaning;
    }

    $pricingChanged = array_key_exists('start_date', $data)
        || array_key_exists('end_date', $data)
        || array_key_exists('cleaning', $data)
        || array_key_exists('price_adjustment_type', $data)
        || array_key_exists('price_adjustment_amount', $data)
        || array_key_exists('price_adjustment_explanation', $data);
    if ($pricingChanged) {
        $baseCents = rentalPeriodPriceCents($row, $effectiveStartDate, $effectiveEndDate);
        $cleaningCents = $newCleaning === 1
            ? max(0, rentalMoneyToCents($row['cleaning_fee'] ?? 0))
            : 0;
        $newRentalFee = max(0, $baseCents + $cleaningCents + $adjustmentCents) / 100;
        $updateFields[] = 'rental_fee = ?';
        $updateParams[] = number_format($newRentalFee, 2, '.', '');
        $row['rental_fee'] = $newRentalFee;
    }

    $datesChanged = array_key_exists('start_date', $data) || array_key_exists('end_date', $data);
    if ($datesChanged || $newStatus === 'approved') {
        $scheduleConflict = $db->prepare(
            "SELECT id FROM rental_schedule
             WHERE company_id = ? AND rental_object_id = ? AND id <> ?
               AND (
                 status = 'unavailable'
                 OR (approved = 1 AND status IN ('approved', 'active'))
               )
               AND start_date <= ? AND end_date >= ?
             LIMIT 1 FOR UPDATE"
        );
        $scheduleConflict->execute([
            $companyId,
            $row['rental_object_id'],
            $data['id'],
            $effectiveEndDate,
            $effectiveStartDate,
        ]);
        if ($scheduleConflict->fetchColumn() !== false) {
            throw new RentalRequestConflictException(
                'Perioden overlapper en godkjent utleie eller en utilgjengelig periode for dette objektet.'
            );
        }
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

    $updateStmt = $db->prepare(
        "UPDATE rental_schedule SET " . implode(', ', $updateFields)
        . " WHERE id = ? AND company_id = ?"
        . " AND (approved = 0 OR approved IS NULL) AND status = 'pending'"
    );
    $updateStmt->execute($updateParams);
    if ($updateStmt->rowCount() !== 1) {
        throw new RuntimeException('Forespørselen ble endret av en annen bruker. Last inn listen på nytt.');
    }

    // Auto-email on either final rental decision.
    $emailQueued = false;
    if ($finalizingNow) {
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
                    'Forespørselen kan ikke behandles fordi kunden mangler en gyldig e-postadresse.'
                );
            }
            if (!filter_var($cfg['email'] ?? null, FILTER_VALIDATE_EMAIL)) {
                throw new RentalConfirmationEmailException(
                    'Forespørselen kan ikke behandles fordi avsenderadressen for e-post ikke er gyldig.'
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
                    'Forespørselen kan ikke behandles fordi e-postmalen for utleiebeslutning mangler.'
                );
            }

            $decisionTemplates = resolveRentalDecisionTemplates(
                $tpl['subject_template'],
                $tpl['body_template']
            );
            $vars = buildRentalConfirmationVariables($row);
            $subject = renderRentalConfirmationTemplate($decisionTemplates['subject'], $vars);
            $body = renderRentalConfirmationTemplate($decisionTemplates['body'], $vars);

            $scheduledAtSql = $isConcurrencyTestRequest
                ? 'DATE_ADD(NOW(), INTERVAL 1 DAY)'
                : 'NOW()';
            $db->prepare("
                INSERT INTO email_queue
                    (id, company_id, recipient_email, from_email, subject, body,
                     template_id, module_source, source_id, status, priority,
                     scheduled_at, created_at, updated_at)
                VALUES
                    (UUID(), ?, ?, ?, ?, ?, ?, 'rental', ?, 'queued', 5,
                     {$scheduledAtSql}, NOW(), NOW())
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

} catch (RentalRequestConflictException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('update_rental_schedule validation error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
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
