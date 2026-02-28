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
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $source = $_GET['source'] ?? 'customers';

    if ($source === 'members') {
        $stmt = $db->prepare("
            SELECT DISTINCT
                tc.id,
                tc.first_name,
                tc.last_name,
                tc.email
            FROM membership_members mm
            JOIN tenant_customers tc ON mm.customer_id = tc.id
            WHERE mm.company_id = ?
              AND mm.status = 'active'
              AND (mm.cancellation_active IS NULL OR mm.cancellation_active = 0)
              AND tc.email IS NOT NULL
              AND tc.email != ''
            ORDER BY tc.last_name, tc.first_name
        ");
        $stmt->execute([$companyId]);
    } else {
        $stmt = $db->prepare("
            SELECT id, first_name, last_name, email
            FROM tenant_customers
            WHERE company_id = ?
              AND email IS NOT NULL
              AND email != ''
              AND status = 'active'
            ORDER BY last_name, first_name
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
