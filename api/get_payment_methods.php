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

try {
    $db = getDBConnection();
    
    // Build payment methods array
    $methods = [];
    
    // Always include cash/arrival payment
    $methods[] = [
        'method_type' => 'cash',
        'method_name' => 'Kontant / Oppmøtebetaling',
        'is_demo' => false,
        'is_active' => true,
        'config' => [
            'display_name' => 'Kontant / Oppmøtebetaling',
            'icon' => 'cash-stack',
            'description' => 'Kontant betaling ved oppmøte'
        ]
    ];
    
    // Try to get configured payment gateways from economy_payment_gateways
    try {
        $stmt = $db->prepare("
            SELECT 
                id,
                provider_name,
                gateway_type,
                is_demo,
                is_active
            FROM economy_payment_gateways 
            WHERE company_id = ? AND is_active = 1
            ORDER BY created_at ASC
        ");
        $stmt->execute([$company_id]);
        $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add configured gateways
        foreach ($gateways as $gateway) {
            $methodType = strtolower($gateway['gateway_type']);
            $methods[] = [
                'method_type' => $methodType,
                'method_name' => $gateway['provider_name'],
                'is_demo' => (bool)$gateway['is_demo'],
                'is_active' => (bool)$gateway['is_active'],
                'config' => [
                    'display_name' => $gateway['provider_name'],
                    'icon' => $methodType === 'vipps' ? 'phone' : 'credit-card',
                    'description' => 'Online betaling via ' . $gateway['provider_name']
                ]
            ];
        }
    } catch (Exception $gatewayError) {
        // If economy_payment_gateways table doesn't exist or query fails, add default online methods
        $methods[] = [
            'method_type' => 'vipps',
            'method_name' => 'Vipps',
            'is_demo' => false,
            'is_active' => true,
            'config' => [
                'display_name' => 'Vipps',
                'icon' => 'phone',
                'description' => 'Betal med Vipps'
            ]
        ];
        
        $methods[] = [
            'method_type' => 'card',
            'method_name' => 'Kort',
            'is_demo' => false,
            'is_active' => true,
            'config' => [
                'display_name' => 'Kort',
                'icon' => 'credit-card',
                'description' => 'Betal med kort'
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
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}
