<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $stmt = $db->prepare("
        SELECT
            mm.id,
            mm.member_code,
            mm.customer_id,
            TRIM(CONCAT(COALESCE(c.first_name, ''), ' ', COALESCE(c.last_name, ''))) AS customer_name,
            c.email,
            mm.membership_plan_id AS plan_id,
            mp.name AS plan_name,
            mp.price AS plan_price,
            mp.billing_cycle AS plan_billing_cycle,
            mp.currency,
            mm.expiration_date,
            mm.status
        FROM membership_members mm
        LEFT JOIN tenant_customers c ON c.id = mm.customer_id
        LEFT JOIN membership_plans mp ON mp.id = mm.membership_plan_id
        WHERE mm.company_id = ?
          AND (mm.cancellation_active IS NULL OR mm.cancellation_active = 0)
        ORDER BY
          FIELD(mm.status, 'active', 'pending', 'expired', 'inactive'),
          c.first_name, c.last_name
    ");
    $stmt->execute([$companyId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'members' => $members]);

} catch (Exception $e) {
    error_log("get_members_simple error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke hente membres']);
}
