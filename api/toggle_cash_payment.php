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

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['enabled'])) {
        echo json_encode(['success' => false, 'error' => 'Mangler enabled parameter']);
        exit;
    }
    
    $companyId = $_SESSION['company_id'];
    $db = getDBConnection();
    
    // If trying to disable cash payment, check if other payment methods are active
    if (!$data['enabled']) {
        // Fetch current payment status
        $stmt = $db->prepare("SELECT cash_payment FROM tenant_companies WHERE id = ?");
        $stmt->execute([$companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Future: Check other payment gateways here (Stripe, Vipps, Klarna, PayPal)
        // For now, only cash_payment exists, so we cannot allow disabling it
        $hasOtherActivePayment = false; // TODO: Check other gateway columns when implemented
        
        if (!$hasOtherActivePayment) {
            echo json_encode([
                'success' => false, 
                'error' => 'Kan ikke deaktivere kontantbetaling uten annen aktiv betalingsløsning.'
            ]);
            exit;
        }
    }
    
    $enabled = $data['enabled'] ? 'TRUE' : 'FALSE';
    
    $stmt = $db->prepare("UPDATE tenant_companies SET cash_payment = $enabled WHERE id = ?");
    $stmt->execute([$companyId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Kontantbetaling oppdatert',
        'enabled' => $data['enabled']
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database feil: ' . $e->getMessage()]);
}
