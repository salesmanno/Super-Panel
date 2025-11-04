<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

$companyId = $_SESSION['company_id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $moduleName = $data['module_name'] ?? null;
    $isEnabled = isset($data['is_enabled']) ? (bool)$data['is_enabled'] : false;
    $settings = $data['settings'] ?? [];
    
    if (empty($moduleName)) {
        throw new Exception('Modulnavn mangler');
    }
    
    $db = getDBConnection();
    $db->beginTransaction();
    
    // Update or insert each setting
    foreach ($settings as $key => $value) {
        $stmt = $db->prepare("
            INSERT INTO automailer_settings (
                company_id, module_name, is_enabled, setting_key, setting_value, updated_at
            ) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
            ON CONFLICT (company_id, module_name, setting_key)
            DO UPDATE SET 
                is_enabled = EXCLUDED.is_enabled,
                setting_value = EXCLUDED.setting_value,
                updated_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([
            $companyId,
            $moduleName,
            $isEnabled,
            $key,
            (bool)$value
        ]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'AutoMailer-innstillinger lagret'
    ]);

} catch (Exception $e) {
    if ($db && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Save automailer settings error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Kunne ikke lagre automailer-innstillinger: ' . $e->getMessage()
    ]);
}
