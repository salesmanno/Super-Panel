<?php
ob_start();
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Server-feil: ' . $error['message']]);
        exit;
    }
});

session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/tenant_customer_contacts.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $source = $_GET['source'] ?? 'customers';
    $customerEmailSql = tenantCustomerContactSql('tc', 'email');

    if ($source === 'members') {
        $stmt = $db->prepare("
            SELECT DISTINCT
                tc.id,
                tc.first_name,
                tc.last_name,
                {$customerEmailSql} AS email
            FROM membership_members mm
            JOIN tenant_customers tc
              ON tc.id = mm.customer_id
             AND tc.company_id = mm.company_id
            WHERE mm.company_id = ?
              AND mm.status = 'active'
              AND (mm.cancellation_active IS NULL OR mm.cancellation_active = 0)
              AND (mm.expiration_date IS NULL OR mm.expiration_date >= CURDATE())
              AND {$customerEmailSql} IS NOT NULL
              AND {$customerEmailSql} != ''
            ORDER BY tc.last_name, tc.first_name
        ");
        $stmt->execute([$companyId]);
    } else {
        $stmt = $db->prepare("
            SELECT tc.id, tc.first_name, tc.last_name, {$customerEmailSql} AS email
            FROM tenant_customers tc
            WHERE tc.company_id = ?
              AND {$customerEmailSql} IS NOT NULL
              AND {$customerEmailSql} != ''
              AND tc.status = 'active'
            ORDER BY tc.last_name, tc.first_name
        ");
        $stmt->execute([$companyId]);
    }

    $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_end_clean();
    echo json_encode([
        'success'    => true,
        'recipients' => $recipients,
        'source'     => $source
    ]);

} catch (\Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
