<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $stmt = $db->prepare("
        SELECT 
            id,
            config_name,
            gateway_type,
            is_active,
            CASE 
                WHEN gateway_type = 'vipps' AND client_id IS NOT NULL AND client_secret_encrypted IS NOT NULL 
                     AND merchant_serial_number IS NOT NULL AND subscription_key IS NOT NULL THEN 1
                WHEN gateway_type = 'stripe' AND public_key IS NOT NULL AND private_key_encrypted IS NOT NULL THEN 1
                WHEN gateway_type = 'paypal' AND client_id IS NOT NULL AND client_secret_encrypted IS NOT NULL THEN 1
                WHEN gateway_type = 'bank' THEN 1
                WHEN gateway_type = 'cash' THEN 1
                ELSE 0
            END as is_configured
        FROM economy_configuration_data
        WHERE company_id = ?
        ORDER BY config_name ASC
    ");
    
    $stmt->execute([$companyId]);
    $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $configuredMethods = [];
    foreach ($gateways as $gateway) {
        if ($gateway['is_configured'] == 1 || $gateway['is_active'] == 1) {
            $configuredMethods[] = [
                'id' => $gateway['id'],
                'name' => $gateway['config_name'],
                'type' => $gateway['gateway_type'],
                'is_active' => (bool)$gateway['is_active'],
                'is_configured' => (bool)$gateway['is_configured']
            ];
        }
    }
    
    $cashStmt = $db->prepare("SELECT cash_payment FROM tenant_companies WHERE id = ?");
    $cashStmt->execute([$companyId]);
    $company = $cashStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($company && $company['cash_payment']) {
        array_unshift($configuredMethods, [
            'id' => 'cash',
            'name' => 'Kontant',
            'type' => 'cash',
            'is_active' => true,
            'is_configured' => true
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'payment_methods' => $configuredMethods
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching payment methods: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av betalingsmetoder'
    ]);
}
