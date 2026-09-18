<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$company_id = $_SESSION['company_id'];

try {
    $db = getDBConnection();

    // Lookup mode: exact renewal order_id first, then exact member_code.
    if (isset($_GET['lookup']) && $_GET['lookup'] !== '') {
        $lookup = trim($_GET['lookup']);
        $stmt = $db->prepare("
            SELECT
                mmr.id,
                mmr.order_id,
                mmr.amount,
                mmr.status        AS renewal_status,
                mmr.created_at,
                mmr.completed_at,
                mmr.payment_method,
                mm.id             AS membership_member_id,
                mm.payment_status,
                mm.expiration_date,
                mm.status         AS member_status,
                mm.member_code,
                c.first_name,
                c.last_name,
                mp.name           AS plan_name
            FROM membership_members_renew mmr
            JOIN membership_members mm
                ON mm.id = mmr.membership_member_id AND mm.company_id = mmr.company_id
            LEFT JOIN tenant_customers c
                ON c.id = mm.customer_id AND c.company_id = mm.company_id
            LEFT JOIN membership_plans mp
                ON mp.id = mmr.membership_plan_id AND mp.company_id = mmr.company_id
            WHERE mmr.company_id = ?
              AND mmr.order_id   = ?
            LIMIT 1
        ");
        $stmt->execute([$company_id, $lookup]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $row['lookup_type'] = 'renewal';
            $row['has_renewal'] = true;
            echo json_encode(['success' => true, 'row' => $row]);
            exit;
        }

        $memberStmt = $db->prepare("
            SELECT
                latest_renewal.id,
                latest_renewal.order_id,
                latest_renewal.amount,
                latest_renewal.status AS renewal_status,
                latest_renewal.created_at,
                latest_renewal.completed_at,
                latest_renewal.payment_method,
                mm.id AS membership_member_id,
                mm.payment_status,
                mm.expiration_date,
                mm.status AS member_status,
                mm.member_code,
                c.first_name,
                c.last_name,
                mp.name AS plan_name
            FROM membership_members mm
            LEFT JOIN tenant_customers c
                ON c.id = mm.customer_id AND c.company_id = mm.company_id
            LEFT JOIN membership_plans mp
                ON mp.id = mm.membership_plan_id AND mp.company_id = mm.company_id
            LEFT JOIN membership_members_renew latest_renewal
                ON latest_renewal.id = (
                    SELECT mmr_latest.id
                    FROM membership_members_renew mmr_latest
                    WHERE mmr_latest.membership_member_id = mm.id
                      AND mmr_latest.company_id = mm.company_id
                    ORDER BY mmr_latest.created_at DESC, mmr_latest.id DESC
                    LIMIT 1
                )
            WHERE mm.company_id = ?
              AND UPPER(mm.member_code) = UPPER(?)
            LIMIT 2
        ");
        $memberStmt->execute([$company_id, $lookup]);
        $memberRows = $memberStmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($memberRows) > 1) {
            http_response_code(409);
            echo json_encode([
                'success' => false,
                'message' => 'Flere medlemmer har denne medlemskoden. Kontroller medlemsdataene.'
            ]);
            exit;
        }
        if (!$memberRows) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Ingen fornyelse eller medlem funnet']);
            exit;
        }

        $row = $memberRows[0];
        $row['lookup_type'] = 'member';
        $row['has_renewal'] = !empty($row['id']);
        echo json_encode(['success' => true, 'row' => $row]);
        exit;
    }

    // List mode: paginated, optional search on order_id
    $page     = max(1, intval($_GET['page']     ?? 1));
    $per_page = max(1, intval($_GET['per_page'] ?? 10));
    $search   = trim($_GET['search'] ?? '');
    $offset   = ($page - 1) * $per_page;

    $where  = 'mmr.company_id = ?';
    $params = [$company_id];

    if ($search !== '') {
        $where   .= ' AND mmr.order_id LIKE ?';
        $params[] = '%' . $search . '%';
    }

    $countStmt = $db->prepare("
        SELECT COUNT(*)
        FROM membership_members_renew mmr
        WHERE {$where}
    ");
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $listStmt = $db->prepare("
        SELECT
            mmr.id,
            mmr.order_id,
            mmr.amount,
            mmr.status        AS renewal_status,
            mmr.created_at,
            mmr.payment_method,
            mm.id             AS membership_member_id,
            mm.payment_status,
            mm.expiration_date,
            mm.kontrollert,
            CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) AS member_name,
            mp.name           AS plan_name
        FROM membership_members_renew mmr
        JOIN membership_members mm  ON mm.id  = mmr.membership_member_id
        LEFT JOIN tenant_customers c  ON c.id   = mm.customer_id
        LEFT JOIN membership_plans mp ON mp.id  = mmr.membership_plan_id
        WHERE {$where}
        ORDER BY mmr.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $params[] = $per_page;
    $params[] = $offset;
    $listStmt->execute($params);
    $rows = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success'  => true,
        'total'    => $total,
        'page'     => $page,
        'per_page' => $per_page,
        'rows'     => $rows,
    ]);

} catch (Exception $e) {
    error_log('get_membership_renewals error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kunne ikke hente fornyelser']);
}
