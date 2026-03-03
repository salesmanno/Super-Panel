<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db        = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $year      = (int)($_GET['year'] ?? date('Y'));

    if ($year < 2000 || $year > 2100) {
        echo json_encode(['success' => false, 'error' => 'Ugyldig år']);
        exit;
    }

    $newStmt = $db->prepare("
        SELECT
            DATE(mm.created_at)   AS d,
            c.first_name,
            c.last_name,
            p.name                AS plan
        FROM membership_members mm
        JOIN tenant_customers c ON c.id = mm.customer_id
        JOIN membership_plans p ON p.id = mm.membership_plan_id
        WHERE mm.company_id = ?
          AND YEAR(mm.created_at) = ?
          AND (mm.cancellation_active IS NULL OR mm.cancellation_active = 0)
        ORDER BY d
    ");
    $newStmt->execute([$companyId, $year]);
    $newRows = $newStmt->fetchAll(PDO::FETCH_ASSOC);

    $renewStmt = $db->prepare("
        SELECT
            DATE(mmr.completed_at) AS d,
            c.first_name,
            c.last_name,
            p.name                 AS plan
        FROM membership_members_renew mmr
        JOIN membership_members mm ON mm.id = mmr.membership_member_id
        JOIN tenant_customers c    ON c.id  = mm.customer_id
        JOIN membership_plans p    ON p.id  = mm.membership_plan_id
        WHERE mm.company_id = ?
          AND mmr.status = 'completed'
          AND YEAR(mmr.completed_at) = ?
        ORDER BY d
    ");
    $renewStmt->execute([$companyId, $year]);
    $renewRows = $renewStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($newRows as &$r) {
        $r['name'] = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        unset($r['first_name'], $r['last_name']);
    }
    unset($r);

    foreach ($renewRows as &$r) {
        $r['name'] = trim(($r['first_name'] ?? '') . ' ' . ($r['last_name'] ?? ''));
        unset($r['first_name'], $r['last_name']);
    }
    unset($r);

    echo json_encode([
        'success'  => true,
        'year'     => $year,
        'new'      => $newRows,
        'renewals' => $renewRows,
    ]);

} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
