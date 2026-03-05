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

    $sumStmt = $db->prepare("
        SELECT COALESCE(SUM(mmr.amount), 0)
        FROM membership_members_renew mmr
        JOIN membership_members mm ON mm.id = mmr.membership_member_id
        WHERE mmr.company_id = ?
          AND mmr.status      = 'completed'
          AND mm.payment_status = 'paid'
    ");
    $sumStmt->execute([$company_id]);
    $sum_completed = (float) $sumStmt->fetchColumn();

    $renewedStmt = $db->prepare("
        SELECT COUNT(*)
        FROM membership_members_renew mmr
        JOIN membership_members mm ON mm.id = mmr.membership_member_id
        WHERE mmr.company_id  = ?
          AND mmr.status       = 'completed'
          AND mm.payment_status = 'paid'
    ");
    $renewedStmt->execute([$company_id]);
    $count_renewed = (int) $renewedStmt->fetchColumn();

    $validStmt = $db->prepare("
        SELECT COUNT(*)
        FROM membership_members_renew mmr
        JOIN membership_members mm ON mm.id = mmr.membership_member_id
        WHERE mmr.company_id   = ?
          AND mmr.status        = 'completed'
          AND mm.payment_status  = 'paid'
          AND mm.expiration_date > CURDATE()
    ");
    $validStmt->execute([$company_id]);
    $count_valid = (int) $validStmt->fetchColumn();

    echo json_encode([
        'success'       => true,
        'sum_completed' => $sum_completed,
        'count_renewed' => $count_renewed,
        'count_valid'   => $count_valid,
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
