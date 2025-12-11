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
$data = json_decode(file_get_contents('php://input'), true);

$gatewayName = $data['gateway_name'] ?? null;
$isActive = isset($data['is_active']) ? (bool)$data['is_active'] : null;

if (!$gatewayName || $isActive === null) {
    echo json_encode(['success' => false, 'message' => 'Mangler gateway_name eller is_active']);
    exit;
}

try {
    $db = getDBConnection();
    
    if ($gatewayName === 'cash') {
        if (!$isActive) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as active_count 
                FROM economy_payment_gateways 
                WHERE company_id = ? AND is_active = 1
            ");
            $stmt->execute([$company_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (($result['active_count'] ?? 0) == 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Kan ikke deaktivere kontant uten andre aktive betalingsløsninger'
                ]);
                exit;
            }
        }
        
        $stmt = $db->prepare("UPDATE tenant_companies SET cash_payment = ? WHERE id = ?");
        $stmt->execute([$isActive ? 1 : 0, $company_id]);
        
        echo json_encode([
            'success' => true,
            'message' => $isActive ? 'Kontant aktivert' : 'Kontant deaktivert',
            'is_active' => $isActive
        ]);
        exit;
    }
    
    $stmt = $db->prepare("
        SELECT id, is_active,
               vipps_client_id, vipps_client_secret_encrypted, vipps_merchant_serial_number, vipps_subscription_key,
               stripe_public_key, stripe_secret_key_encrypted,
               paypal_client_id, paypal_client_secret_encrypted
        FROM economy_payment_gateways 
        WHERE company_id = ? AND LOWER(gateway_name) = LOWER(?)
    ");
    $stmt->execute([$company_id, $gatewayName]);
    $gateway = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gateway) {
        echo json_encode(['success' => false, 'message' => 'Gateway ikke funnet']);
        exit;
    }
    
    $requiredFields = [
        'vipps' => ['vipps_client_id', 'vipps_client_secret_encrypted', 'vipps_merchant_serial_number', 'vipps_subscription_key'],
        'stripe' => ['stripe_public_key', 'stripe_secret_key_encrypted'],
        'paypal' => ['paypal_client_id', 'paypal_client_secret_encrypted']
    ];
    
    $fields = $requiredFields[strtolower($gatewayName)] ?? [];
    $missingFields = [];
    foreach ($fields as $field) {
        if (empty($gateway[$field])) {
            $missingFields[] = $field;
        }
    }
    
    if ($isActive && !empty($missingFields)) {
        echo json_encode([
            'success' => false,
            'message' => 'Kan ikke aktivere - mangler konfigurasjon: ' . implode(', ', $missingFields)
        ]);
        exit;
    }
    
    $stmt = $db->prepare("
        UPDATE economy_payment_gateways 
        SET is_active = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$isActive ? 1 : 0, $gateway['id']]);
    
    $displayName = ucfirst($gatewayName);
    echo json_encode([
        'success' => true,
        'message' => $isActive ? "$displayName aktivert" : "$displayName deaktivert",
        'is_active' => $isActive
    ]);
    
} catch (Exception $e) {
    error_log("Toggle payment gateway error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}
