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
    $db        = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $context   = $_GET['context'] ?? 'all';

    $where = "tc.company_id = ? AND tc.status = 'active'";
    $params = [$companyId];

    if ($context === 'new_member') {
        $where .= " AND NOT EXISTS (
            SELECT 1 FROM membership_members mm
            WHERE mm.customer_id = tc.id
              AND mm.company_id = ?
        )";
        $params[] = $companyId;
    }

    if ($context === 'renewal') {
        $stmt = $db->prepare("
            SELECT
                tc.id,
                TRIM(CONCAT(COALESCE(tc.first_name,''), ' ', COALESCE(tc.last_name,''))) AS name,
                tc.email,
                tc.customer_code,
                mm.id          AS mm_id,
                mm.member_code,
                mm.membership_plan_id AS plan_id,
                mp.name        AS plan_name,
                mp.price       AS plan_price,
                mp.currency,
                mp.billing_cycle,
                mm.expiration_date,
                mm.status      AS mm_status
            FROM tenant_customers tc
            INNER JOIN membership_members mm ON (
                mm.customer_id = tc.id
                AND mm.company_id = ?
                AND mm.id = (
                    SELECT mm2.id FROM membership_members mm2
                    WHERE mm2.customer_id = tc.id AND mm2.company_id = ?
                    ORDER BY mm2.expiration_date DESC, mm2.id DESC
                    LIMIT 1
                )
            )
            LEFT JOIN membership_plans mp ON mp.id = mm.membership_plan_id
            WHERE tc.company_id = ? AND tc.status = 'active'
            ORDER BY tc.first_name, tc.last_name
        ");
        $stmt->execute([$companyId, $companyId, $companyId]);
        $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'customers' => $customers]);
        exit;
    }

    $stmt = $db->prepare("
        SELECT tc.id,
               TRIM(CONCAT(COALESCE(tc.first_name, ''), ' ', COALESCE(tc.last_name, ''))) AS name,
               tc.email,
               tc.customer_code
        FROM tenant_customers tc
        WHERE $where
        ORDER BY tc.first_name, tc.last_name
    ");
    $stmt->execute($params);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'customers' => $customers]);

} catch (Exception $e) {
    error_log("get_customers_simple error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke hente kunder']);
}
