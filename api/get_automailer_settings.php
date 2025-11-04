<?php
session_start();
header('Content-Type: application/json');
require_once '../includes/db.php';

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

$companyId = $_SESSION['company_id'];

try {
    $db = getDBConnection();
    
    // Get all automailer settings for this company
    $stmt = $db->prepare("
        SELECT 
            module_name,
            is_enabled,
            setting_key,
            setting_value
        FROM automailer_settings
        WHERE company_id = ?
        ORDER BY module_name, setting_key
    ");
    $stmt->execute([$companyId]);
    $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize by module
    $organized = [];
    foreach ($settings as $setting) {
        $module = $setting['module_name'];
        if (!isset($organized[$module])) {
            $organized[$module] = [
                'enabled' => false,
                'settings' => []
            ];
        }
        $organized[$module]['enabled'] = (bool)$setting['is_enabled'];
        $organized[$module]['settings'][$setting['setting_key']] = (bool)$setting['setting_value'];
    }
    
    echo json_encode([
        'success' => true,
        'settings' => $organized
    ]);

} catch (Exception $e) {
    error_log("Get automailer settings error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Kunne ikke hente automailer-innstillinger: ' . $e->getMessage()
    ]);
}
