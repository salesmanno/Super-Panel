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

    $objectId  = $_GET['object_id'] ?? null;
    $status    = $_GET['status'] ?? null;
    $upcoming  = isset($_GET['upcoming']) && $_GET['upcoming'] === '1';

    $where  = ['rc.company_id = ?'];
    $params = [$companyId];

    if ($objectId) {
        $where[]  = 'rc.rental_object_id = ?';
        $params[] = $objectId;
    }

    if ($status && in_array($status, ['draft', 'active', 'completed', 'cancelled'])) {
        $where[]  = 'rc.status = ?';
        $params[] = $status;
    }

    if ($upcoming) {
        $where[]  = "rc.status = 'active'";
        $where[]  = 'rc.end_date >= CURDATE()';
    }

    $whereClause = implode(' AND ', $where);

    try {
        $stmt = $db->prepare("
            SELECT
                rc.*,
                ro.object_name,
                ro.rental_price  AS object_daily_price,
                ro.category      AS object_category,
                COALESCE(rc.customer_name,
                    TRIM(CONCAT(COALESCE(tc.first_name,''), ' ', COALESCE(tc.last_name,''))),
                    'Ukjent kunde'
                ) AS display_customer_name,
                tc.email         AS customer_email,
                DATEDIFF(rc.end_date, CURDATE()) AS days_remaining
            FROM rental_contracts rc
            INNER JOIN rental_objects ro  ON ro.id = rc.rental_object_id AND ro.company_id = rc.company_id
            LEFT  JOIN tenant_customers tc ON tc.id = rc.customer_id AND tc.company_id = rc.company_id
            WHERE $whereClause
            ORDER BY rc.start_date ASC, rc.created_at DESC
        ");
        $stmt->execute($params);
        $contracts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\Throwable $qe) {
        // rental_contracts table may not exist yet — return empty list
        error_log('get_rental_contracts table missing: ' . $qe->getMessage());
        $contracts = [];
    }

    echo json_encode(['success' => true, 'contracts' => $contracts]);

} catch (\Throwable $e) {
    error_log('get_rental_contracts error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av kontrakter. Prøv igjen.'
    ]);
}
