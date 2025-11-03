<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Kun POST tillatt']);
    exit;
}

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];

try {
    $db = getDBConnection();
    $db->beginTransaction();
    
    // Check if Vipps demo already exists
    $stmt = $db->prepare("
        SELECT id, is_active 
        FROM payment_methods 
        WHERE company_id = ? AND method_type = 'vipps' AND is_demo = ?
    ");
    $stmt->execute([$company_id, true]);
    $existing = $stmt->fetch();
    
    if ($existing) {
        // Already exists - toggle active status
        $new_status = !$existing['is_active'];
        $stmt = $db->prepare("
            UPDATE payment_methods 
            SET is_active = ?, 
                updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $existing['id']]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $new_status ? 'Demo Vipps aktivert' : 'Demo Vipps deaktivert',
            'is_active' => $new_status
        ]);
        exit;
    }
    
    // Create new demo Vipps
    $payment_id = generateUUID();
    
    // Demo sandbox credentials (ikke reelle nøkler)
    $demo_credentials = json_encode([
        'client_id' => 'demo_vipps_client_id_' . substr(md5(time()), 0, 16),
        'client_secret' => 'demo_vipps_secret_' . substr(md5(time() . 'secret'), 0, 32),
        'merchant_serial_number' => 'DEMO' . rand(100000, 999999),
        'subscription_key' => 'demo_subscription_key_' . substr(md5(time() . 'sub'), 0, 32),
        'mode' => 'sandbox',
        'api_url' => 'https://apitest.vipps.no',
        'note' => 'Dette er demo/sandbox credentials for testing. Ikke reelle Vipps-nøkler.'
    ]);
    
    $config_data = json_encode([
        'description' => 'Vipps mobilbetaling (Demo/Sandbox)',
        'icon' => 'phone',
        'display_name' => 'Vipps (Demo)',
        'sandbox_mode' => true
    ]);
    
    $stmt = $db->prepare("
        INSERT INTO payment_methods 
        (id, company_id, method_name, method_type, is_demo, is_active, api_credentials, config_data)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $payment_id,
        $company_id,
        'Vipps Demo',
        'vipps',
        true,
        true,
        $demo_credentials,
        $config_data
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Demo Vipps aktivert med sandbox credentials',
        'payment_id' => $payment_id,
        'is_active' => true
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Setup demo Vipps error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
