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

$gatewayId = $data['id'] ?? null;
$gatewayName = $data['gateway_name'] ?? null;
$isActive = isset($data['is_active']) ? (bool)$data['is_active'] : null;

if ((!$gatewayId && !$gatewayName) || $isActive === null) {
    echo json_encode(['success' => false, 'message' => 'Mangler id/gateway_name eller is_active']);
    exit;
}

try {
    $db = getDBConnection();
    
    if ($gatewayName === 'cash') {
        if (!$isActive) {
            $stmt = $db->prepare("
                SELECT COUNT(*) as active_count 
                FROM economy_configuration_data 
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
    
    if ($gatewayId) {
        $stmt = $db->prepare("
            SELECT id, config_name, gateway_type, is_active,
                   client_id, client_secret_encrypted, subscription_key, 
                   merchant_serial_number, public_key, private_key_encrypted
            FROM economy_configuration_data 
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$gatewayId, $company_id]);
    } else {
        $stmt = $db->prepare("
            SELECT id, config_name, gateway_type, is_active,
                   client_id, client_secret_encrypted, subscription_key, 
                   merchant_serial_number, public_key, private_key_encrypted
            FROM economy_configuration_data 
            WHERE company_id = ? AND LOWER(gateway_type) = LOWER(?)
        ");
        $stmt->execute([$company_id, $gatewayName]);
    }
    
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config) {
        echo json_encode(['success' => false, 'message' => 'Betalingsløsning ikke funnet']);
        exit;
    }
    
    $gatewayType = strtolower($config['gateway_type']);
    $missingFields = [];
    
    if ($isActive) {
        switch ($gatewayType) {
            case 'vipps':
                if (empty($config['client_id'])) $missingFields[] = 'client_id';
                if (empty($config['client_secret_encrypted'])) $missingFields[] = 'client_secret';
                if (empty($config['merchant_serial_number'])) $missingFields[] = 'merchant_serial_number';
                if (empty($config['subscription_key'])) $missingFields[] = 'subscription_key';
                break;
            case 'stripe':
                if (empty($config['public_key'])) $missingFields[] = 'public_key';
                if (empty($config['private_key_encrypted'])) $missingFields[] = 'private_key';
                break;
            case 'paypal':
                if (empty($config['client_id'])) $missingFields[] = 'client_id';
                if (empty($config['client_secret_encrypted'])) $missingFields[] = 'client_secret';
                break;
        }
        
        if (!empty($missingFields)) {
            echo json_encode([
                'success' => false,
                'message' => 'Kan ikke aktivere - mangler konfigurasjon: ' . implode(', ', $missingFields)
            ]);
            exit;
        }
    }
    
    $stmt = $db->prepare("
        UPDATE economy_configuration_data 
        SET is_active = ?, updated_at = CURRENT_TIMESTAMP 
        WHERE id = ?
    ");
    $stmt->execute([$isActive ? 1 : 0, $config['id']]);
    
    $displayName = $config['config_name'] ?: ucfirst($gatewayType);
    echo json_encode([
        'success' => true,
        'message' => $isActive ? "$displayName aktivert" : "$displayName deaktivert",
        'is_active' => $isActive
    ]);
    
} catch (Exception $e) {
    error_log("Toggle payment gateway error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil: ' . $e->getMessage()]);
}
