<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/payment_gateways_helper.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['gateway_id']) || !isset($data['is_active'])) {
        echo json_encode(['success' => false, 'error' => 'Mangler gateway_id eller is_active']);
        exit;
    }
    
    $gatewayId = $data['gateway_id'];
    $isActive = $data['is_active'] ? 'TRUE' : 'FALSE';
    $companyId = $_SESSION['company_id'];
    
    $db = getDBConnection();
    
    // Sjekk om det er kontant som skal deaktiveres
    $stmt = $db->prepare("SELECT gateway_name, is_active FROM economy_payment_gateways WHERE id = ? AND company_id = ?");
    $stmt->execute([$gatewayId, $companyId]);
    $gateway = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$gateway) {
        echo json_encode(['success' => false, 'error' => 'Betalingsmetode ikke funnet']);
        exit;
    }
    
    // Hvis kontant skal deaktiveres, sjekk at minst én annen er aktivert
    if ($gateway['gateway_name'] === 'cash' && $gateway['is_active'] && !$data['is_active']) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM economy_payment_gateways WHERE company_id = ? AND gateway_name != 'cash' AND is_active = TRUE");
        $stmt->execute([$companyId]);
        $otherActiveCount = $stmt->fetchColumn();
        
        if ($otherActiveCount == 0) {
            echo json_encode(['success' => false, 'error' => 'Kontant kan kun deaktiveres når minst én annen betalingsmetode er aktivert']);
            exit;
        }
    }
    
    // Oppdater betalingsmetoden
    $stmt = $db->prepare("UPDATE economy_payment_gateways SET is_active = $isActive, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND company_id = ?");
    $stmt->execute([$gatewayId, $companyId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Betalingsmetode oppdatert',
        'gateway' => $gateway['gateway_name'],
        'is_active' => $data['is_active']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database feil: ' . $e->getMessage()]);
}
