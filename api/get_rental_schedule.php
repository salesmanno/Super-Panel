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

$companyId = $_SESSION['company_id'];

try {
    $db = getDBConnection();

    $approvedFilter = $_GET['approved'] ?? null;

    $where  = ['rs.company_id = ?'];
    $params = [$companyId];

    if ($approvedFilter !== null && in_array($approvedFilter, ['0', '1'], true)) {
        if ($approvedFilter === '0') {
            $where[] = '(rs.approved = 0 OR rs.approved IS NULL)';
            $where[] = "rs.status != 'declined'";
        } else {
            $where[]  = 'rs.approved = ?';
            $params[] = 1;
        }
    }

    $whereClause = implode(' AND ', $where);

    $stmt = $db->prepare("
        SELECT
            rs.*,
            ro.object_name,
            ro.category      AS object_category,
            ro.rental_price  AS object_daily_price,
            COALESCE(
                NULLIF(TRIM(rs.customer_name), ''),
                NULLIF(TRIM(CONCAT(COALESCE(tc.first_name,''), ' ', COALESCE(tc.last_name,''))), ''),
                'Ukjent kunde'
            )                AS display_customer_name,
            COALESCE(NULLIF(rs.customer_email,''), tc.email)  AS display_email,
            COALESCE(NULLIF(rs.customer_phone,''), tc.phone)  AS display_phone,
            DATEDIFF(rs.end_date, CURDATE()) AS days_remaining
        FROM rental_schedule rs
        INNER JOIN rental_objects ro ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
        LEFT  JOIN tenant_customers tc ON tc.id = rs.customer_id AND tc.company_id = rs.company_id
        WHERE $whereClause
        ORDER BY rs.start_date ASC, rs.created_at DESC
    ");
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'requests' => $rows]);

} catch (\Throwable $e) {
    error_log('get_rental_schedule error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Feil ved henting av utleieforespørsler.']);
}
