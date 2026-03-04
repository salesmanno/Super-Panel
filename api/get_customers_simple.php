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
              AND mm.status = 'active'
              AND (mm.cancellation_active IS NULL OR mm.cancellation_active = 0)
        )";
        $params[] = $companyId;
    } elseif ($context === 'has_membership') {
        $where .= " AND EXISTS (
            SELECT 1 FROM membership_members mm
            WHERE mm.customer_id = tc.id
              AND mm.company_id = ?
              AND mm.status = 'active'
              AND (mm.cancellation_active IS NULL OR mm.cancellation_active = 0)
        )";
        $params[] = $companyId;
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
