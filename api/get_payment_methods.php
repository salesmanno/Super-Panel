<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Kun GET tillatt']);
    exit;
}

if (!isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];

$requiredFields = [
    'vipps' => ['vipps_client_id', 'vipps_client_secret_encrypted', 'vipps_merchant_serial_number', 'vipps_subscription_key'],
    'stripe' => ['stripe_public_key', 'stripe_secret_key_encrypted', 'stripe_webhook_secret'],
    'paypal' => ['paypal_client_id', 'paypal_client_secret_encrypted', 'paypal_webhook_id']
];

$gatewayDisplayNames = [
    'cash' => 'Kontant',
    'vipps' => 'Vipps',
    'stripe' => 'Kort',
    'paypal' => 'PayPal'
];

$gatewayIcons = [
    'cash' => 'cash-stack',
    'vipps' => 'phone',
    'stripe' => 'credit-card',
    'paypal' => 'paypal'
];

try {
    $db = getDBConnection();
    
    $methods = [];
    
    $stmt = $db->prepare("SELECT Cash_payment FROM tenant_companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    $cashEnabled = $company ? (bool)($company['Cash_payment'] ?? true) : true;
    
    if ($cashEnabled) {
        $methods[] = [
            'method_type' => 'cash',
            'method_name' => 'Kontant',
            'is_demo' => false,
            'is_active' => true,
            'config' => [
                'display_name' => 'Kontant',
                'icon' => 'cash-stack',
                'description' => 'Kontant betaling'
            ]
        ];
    }
    
    $stmt = $db->prepare("
        SELECT 
            epg.id,
            epg.gateway_name,
            epg.is_active,
            epg.test_mode,
            ecd.config_name,
            ecd.client_id,
            ecd.client_secret_encrypted,
            ecd.subscription_key,
            ecd.merchant_serial_number,
            ecd.public_key,
            ecd.private_key_encrypted,
            ecd.webhook_secret,
            ecd.webhook_id
        FROM economy_payment_gateways epg
        LEFT JOIN economy_configuration_data ecd ON epg.configuration_id = ecd.id
        WHERE epg.company_id = ? AND epg.is_active = 1
    ");
    $stmt->execute([$company_id]);
    $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($gateways as $gw) {
        $gatewayName = strtolower($gw['gateway_name']);
        
        $isConfigured = false;
        switch ($gatewayName) {
            case 'vipps':
                $isConfigured = !empty($gw['client_id']) && !empty($gw['client_secret_encrypted']) && !empty($gw['merchant_serial_number']);
                break;
            case 'stripe':
                $isConfigured = !empty($gw['public_key']) && !empty($gw['private_key_encrypted']);
                break;
            case 'paypal':
                $isConfigured = !empty($gw['client_id']) && !empty($gw['client_secret_encrypted']);
                break;
            default:
                $isConfigured = true;
        }
        
        if (!$isConfigured) {
            continue;
        }
        
        $displayName = $gw['config_name'] ?? $gatewayDisplayNames[$gatewayName] ?? ucfirst($gatewayName);
        
        $methods[] = [
            'method_type' => $gatewayName,
            'method_name' => $displayName,
            'is_demo' => (bool)($gw['test_mode'] ?? false),
            'is_active' => true,
            'config' => [
                'display_name' => $displayName,
                'icon' => $gatewayIcons[$gatewayName] ?? 'wallet2',
                'description' => ''
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'payment_methods' => $methods,
        'total' => count($methods)
    ]);
    
} catch (Exception $e) {
    error_log("Get payment methods error: " . $e->getMessage());
    $isConnectionError = strpos($e->getMessage(), 'Network is unreachable') !== false || 
                         strpos($e->getMessage(), 'Connection refused') !== false ||
                         strpos($e->getMessage(), 'Kan ikke koble til') !== false;
    echo json_encode([
        'success' => false, 
        'message' => 'Serverfeil: ' . $e->getMessage(),
        'error_type' => $isConnectionError ? 'connection_error' : 'general_error'
    ]);
}
