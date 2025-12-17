<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['service_id']) || empty($data['staff_id'])) {
        echo json_encode(['success' => false, 'message' => 'Tjeneste-ID og ansatt-ID er påkrevd']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $verifyStaff = $db->prepare("SELECT id FROM tenant_staff WHERE id = ? AND company_id = ?");
    $verifyStaff->execute([$data['staff_id'], $companyId]);
    if (!$verifyStaff->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Ansatt ikke funnet']);
        exit;
    }
    
    $stmt = $db->prepare("
        DELETE FROM staff_services 
        WHERE company_id = ? AND service_id = ? AND staff_id = ?
    ");
    $stmt->execute([$companyId, $data['service_id'], $data['staff_id']]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Ansatt fjernet fra tjeneste'
    ]);
    
} catch (Exception $e) {
    error_log("Error removing staff: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved fjerning: ' . $e->getMessage()
    ]);
}
