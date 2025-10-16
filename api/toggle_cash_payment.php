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
    
    $enabled = $data['enabled'] ? 'TRUE' : 'FALSE';
    $companyId = $_SESSION['company_id'];
    
    $db = getDBConnection();
    
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
