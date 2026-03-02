<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $cancelled = isset($_GET['cancelled']) && $_GET['cancelled'] == '1';

    $cancelFilter = $cancelled
        ? "AND mm.cancellation_active = 1"
        : "AND (mm.cancellation_active IS NULL OR mm.cancellation_active = 0)";

    $stmt = $db->prepare("
        SELECT
            mm.id as member_id,
            mm.member_code,
            mm.membership_plan_id,
            mm.join_date,
            mm.expiration_date as expiry_date,
            mm.auto_renew,
            mm.status,
            mm.payment_status,
            mm.Automailer_Sent,
            mm.cancellation_active,
            mm.cancellation_reasoning,
            c.id as customer_id,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            c.address_street as address,
            c.address_city as poststed,
            c.date_of_birth as dateofbirth,
            c.notes,
            p.name as membership_type,
            p.share_code
        FROM membership_members mm
        LEFT JOIN tenant_customers c ON mm.customer_id = c.id
        LEFT JOIN membership_plans p ON mm.membership_plan_id = p.id
        WHERE mm.company_id = ?
          {$cancelFilter}
        ORDER BY mm.created_at DESC
    ");

    $stmt->execute([$companyId]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $subStmt = $db->prepare("SELECT subdomain FROM tenant_companies WHERE id = ? LIMIT 1");
    $subStmt->execute([$companyId]);
    $subdomain = $subStmt->fetchColumn() ?: '';

    echo json_encode(['success' => true, 'members' => $members, 'subdomain' => $subdomain]);

} catch (Exception $e) {
    error_log("Error fetching members: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved henting av medlemmer: ' . $e->getMessage()]);
}
