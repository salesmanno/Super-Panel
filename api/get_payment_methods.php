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
    
    // Get cash payment status from tenant_companies
    $stmt = $db->prepare("SELECT cash_payment FROM tenant_companies WHERE id = ?");
    $stmt->execute([$company_id]);
    $company = $stmt->fetch();
    $cashEnabled = $company ? ($company['cash_payment'] ?? true) : true;
    
    // Get configured payment methods from payment_methods table
    $stmt = $db->prepare("
        SELECT 
            id,
            method_name,
            method_type,
            is_demo,
            is_active,
            config_data
        FROM payment_methods 
        WHERE company_id = ? AND is_active = ?
        ORDER BY created_at ASC
    ");
    $stmt->execute([$company_id, true]);
    $configured = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Build payment methods array
    $methods = [];
    
    // Always include cash (based on company setting)
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
    
    // Add configured payment methods
    foreach ($configured as $method) {
        $config = json_decode($method['config_data'], true) ?? [];
        
        $methods[] = [
            'method_type' => $method['method_type'],
            'method_name' => $method['method_name'],
            'is_demo' => (bool)$method['is_demo'],
            'is_active' => (bool)$method['is_active'],
            'config' => $config
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
