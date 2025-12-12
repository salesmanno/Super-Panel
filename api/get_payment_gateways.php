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
    'paypal' => ['paypal_client_id', 'paypal_client_secret_encrypted', 'paypal_webhook_id'],
    'klarna' => []
];

$gatewayDisplayNames = [
    'vipps' => 'Vipps',
    'stripe' => 'Stripe',
    'paypal' => 'PayPal',
    'klarna' => 'Klarna'
];

$gatewayIcons = [
    'vipps' => 'phone',
    'stripe' => 'credit-card',
    'paypal' => 'paypal',
    'klarna' => 'bag-check'
];

try {
    $db = getDBConnection();
    
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
        WHERE company_id = ?
    ");
    $stmt->execute([$company_id]);
    $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT cash_payment FROM tenant_companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch(PDO::FETCH_ASSOC);
    $cashEnabled = $company ? (bool)($company['cash_payment'] ?? true) : true;
    
    $result = [];
    
    $result[] = [
        'gateway_name' => 'cash',
        'display_name' => 'Kontant',
        'icon' => 'cash-stack',
        'is_configured' => true,
        'is_active' => $cashEnabled,
        'test_mode' => false,
        'missing_fields' => []
    ];
    
    $configuredGateways = [];
    foreach ($gateways as $gw) {
        $gatewayName = strtolower($gw['gateway_name']);
        $configuredGateways[$gatewayName] = $gw;
    }
    
    foreach ($requiredFields as $gatewayName => $fields) {
        $gw = $configuredGateways[$gatewayName] ?? null;
        
        $missingFields = [];
        $isConfigured = false;
        $isActive = false;
        $testMode = true;
        
        if ($gw) {
            foreach ($fields as $field) {
                if (empty($gw[$field])) {
                    $missingFields[] = $field;
                }
            }
            $isConfigured = empty($missingFields) && count($fields) > 0;
            $isActive = (bool)($gw['is_active'] ?? false);
            $testMode = (bool)($gw['test_mode'] ?? true);
        } else if (count($fields) === 0) {
            $isConfigured = false;
        }
        
        $result[] = [
            'gateway_name' => $gatewayName,
            'display_name' => $gw['display_name'] ?? $gatewayDisplayNames[$gatewayName] ?? ucfirst($gatewayName),
            'icon' => $gatewayIcons[$gatewayName] ?? 'wallet2',
            'is_configured' => $isConfigured,
            'is_active' => $isActive,
            'test_mode' => $testMode,
            'missing_fields' => $missingFields
        ];
    }
    
    echo json_encode([
        'success' => true,
        'gateways' => $result
    ]);
    
} catch (Exception $e) {
    error_log("Get payment gateways error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}
