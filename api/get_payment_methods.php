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
    
    $stmt = $db->prepare("SELECT cash_payment FROM tenant_companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    $cashEnabled = $company ? (bool)($company['cash_payment'] ?? true) : true;
    
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
            id,
            gateway_name,
            display_name,
            is_active,
            test_mode,
            vipps_client_id,
            vipps_client_secret_encrypted,
            vipps_merchant_serial_number,
            vipps_subscription_key,
            stripe_public_key,
            stripe_secret_key_encrypted,
            stripe_webhook_secret,
            paypal_client_id,
            paypal_client_secret_encrypted,
            paypal_webhook_id
        FROM economy_payment_gateways
        WHERE company_id = ? AND is_active = 1
    ");
    $stmt->execute([$company_id]);
    $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($gateways as $gw) {
        $gatewayName = strtolower($gw['gateway_name']);
        
        $fields = $requiredFields[$gatewayName] ?? [];
        $isConfigured = true;
        foreach ($fields as $field) {
            if (empty($gw[$field])) {
                $isConfigured = false;
                break;
            }
        }
        
        if (!$isConfigured) {
            continue;
        }
        
        $methods[] = [
            'method_type' => $gatewayName,
            'method_name' => $gw['display_name'] ?? $gatewayDisplayNames[$gatewayName] ?? ucfirst($gatewayName),
            'is_demo' => (bool)($gw['test_mode'] ?? false),
            'is_active' => true,
            'config' => [
                'display_name' => $gw['display_name'] ?? $gatewayDisplayNames[$gatewayName] ?? ucfirst($gatewayName),
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
