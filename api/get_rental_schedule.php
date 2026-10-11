<?php
session_start();
session_write_close();
header('Content-Type: application/json');
header('Cache-Control: no-store');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/rental_request_fields.php';
require_once __DIR__ . '/../includes/tenant_notifications.php';
require_once __DIR__ . '/../includes/rental_schedule_editing.php';
require_once __DIR__ . '/../includes/rental_reservation_codes.php';

$companyId = $_SESSION['company_id'];

try {
    $db = getDBConnection();
    rentalRequireAccess($db, (string)$companyId);

    $approvedFilter = $_GET['approved'] ?? null;

    $where  = ["rs.company_id = ?", "rs.status <> 'unavailable'"];
    $params = [$companyId];
    if (isset($_GET['request_id'])) {
        if (!is_string($_GET['request_id']) || $_GET['request_id'] === '' || strlen($_GET['request_id'])>36) {
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'Ugyldig forespørselsreferanse']); exit;
        }
        $where[] = 'rs.id = ?';
        $params[] = $_GET['request_id'];
    }

    if ($approvedFilter !== null && in_array($approvedFilter, ['0', '1', '2'], true)) {
        if ($approvedFilter === '0') {
            $where[] = '(rs.approved = 0 OR rs.approved IS NULL) AND rs.status = \'pending\'';
        } elseif ($approvedFilter === '1') {
            $where[] = 'rs.approved = 1 AND rs.status = \'approved\'';
        } else {
            // Compatibility for historical rows that stored rejected as approved = 2.
            $where[] = 'rs.status = \'rejected\'';
        }
    }

    $whereClause = implode(' AND ', $where);

    $weekdaySelect = rentalWeekdayPriceSelect($db, 'ro');
    $stmt = $db->prepare("
        SELECT
            rs.*,
            ro.object_name,
            ro.category      AS object_category,
            ro.rental_price  AS object_daily_price,
            ro.weekend_price AS object_weekend_price,
            ro.saturday_price AS object_saturday_price,
            ro.sunday_price AS object_sunday_price,
            $weekdaySelect AS object_weekday_prices,
            COALESCE(rv.cleaning_fee, 0) AS cleaning_fee,
            COALESCE(
                NULLIF(TRIM(rs.customer_name), ''),
                NULLIF(TRIM(CONCAT(COALESCE(tc.first_name,''), ' ', COALESCE(tc.last_name,''))), ''),
                'Ukjent kunde'
            )                AS display_customer_name,
            COALESCE(NULLIF(TRIM(rs.customer_email), ''), NULLIF(TRIM(tc.email), '')) AS display_email,
            COALESCE(NULLIF(TRIM(rs.customer_phone), ''), NULLIF(TRIM(tc.phone), '')) AS display_phone,
            tc.date_of_birth AS date_of_birth,
            (SELECT eq.status FROM email_queue eq
             WHERE eq.company_id = rs.company_id AND eq.source_id = rs.id
               AND eq.module_source = 'rental_checkin'
             ORDER BY eq.created_at DESC, eq.id DESC LIMIT 1) AS checkin_queue_status,
            DATEDIFF(rs.end_date, CURDATE()) AS days_remaining
        FROM rental_schedule rs
        LEFT JOIN rental_objects ro
          ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
        LEFT JOIN rental_venues rv ON rv.object_id = ro.id
        LEFT JOIN tenant_customers tc
          ON tc.id = rs.customer_id AND tc.company_id = rs.company_id
        WHERE $whereClause
        ORDER BY rs.start_date ASC, rs.created_at DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $today = rentalCodeToday();
    $nextCodes = rentalNextCodeReservations($db, (string)$companyId, $today);
    foreach ($rows as &$row) {
        $row['access_code'] = $row['key_box_code'] ?? null;
        $row['can_prepare_code'] = $row['object_category'] === 'venue'
            && ($nextCodes[$row['rental_object_id']] ?? null) === (string)$row['id'];
        $row['checkin_code_missing'] = !rentalValidAssignedCode($row['key_box_code'] ?? null)
            && empty($row['checkin_email_queued_at']) && empty($row['checkin_email_sent_at']);
        $row['decision_version'] = rentalDecisionVersion($row);
        $row['request_fields'] = getRentalRequestFieldValues(
            $db,
            (string) $companyId,
            (string) $row['rental_object_id'],
            (string) $row['id']
        );
    }
    unset($row);

    $cfg = $db->prepare('SELECT automail_rental_confirm FROM email_configs WHERE company_id = ? AND is_active = 1 LIMIT 1');
    $cfg->execute([$companyId]);
    echo json_encode(['success' => true, 'today'=>$today, 'requests' => $rows, 'decision_email_enabled'=>(int)$cfg->fetchColumn()===1]);

} catch (TenantAuditException $e) {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'Du har ikke tilgang til utleiemodulen']);
} catch (\Throwable $e) {
    error_log('get_rental_schedule error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Feil ved henting av utleieforespørsler.']);
}
