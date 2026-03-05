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

    // Lookup mode: exact order_id match, returns full detail for edit panel
    if (isset($_GET['lookup']) && $_GET['lookup'] !== '') {
        $order_id = trim($_GET['lookup']);
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
                c.first_name,
                c.last_name,
                mp.name           AS plan_name
            FROM membership_members_renew mmr
            JOIN membership_members mm  ON mm.id  = mmr.membership_member_id
            LEFT JOIN tenant_customers c  ON c.id   = mm.customer_id
            LEFT JOIN membership_plans mp ON mp.id  = mmr.membership_plan_id
            WHERE mmr.company_id = ?
              AND mmr.order_id   = ?
            LIMIT 1
        ");
        $stmt->execute([$company_id, $order_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(['success' => false, 'message' => 'Ikke funnet']);
            exit;
        }
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
            mmr.status AS renewal_status,
            mmr.created_at,
            mmr.payment_method,
            CONCAT(COALESCE(c.first_name,''), ' ', COALESCE(c.last_name,'')) AS member_name,
            mp.name AS plan_name
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
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
