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
    $adminId = $_SESSION['tenant_admin_id'];
    
    $stmt = $db->prepare("
        SELECT 
            automail_events_confirm,
            automail_events_reminder_3d,
            automail_events_thankyou,
            automail_sales_order,
            automail_membership_welcome,
            automail_membership_renewal,
            automail_membership_expired,
            automail_reservation_confirm,
            automail_reservation_reminder,
            automail_rental_confirm,
            automail_rental_return
        FROM email_configs 
        WHERE company_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$settings) {
        $settings = [];
    }
    
    $stmt = $db->prepare("
        SELECT sm.module_key 
        FROM tenant_admin_modules tam
        JOIN system_modules sm ON tam.module_id = sm.id
        WHERE tam.tenant_admin_id = ?
    ");
    $stmt->execute([$adminId]);
    $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'settings' => $settings,
        'modules' => $modules
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
