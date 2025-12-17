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
    
    if (empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Tjeneste-ID mangler']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $db->beginTransaction();
    
    $stmt = $db->prepare("DELETE FROM staff_services WHERE service_id = ? AND company_id = ?");
    $stmt->execute([$data['id'], $companyId]);
    
    $stmt = $db->prepare("DELETE FROM reservation_services WHERE id = ? AND company_id = ?");
    $stmt->execute([$data['id'], $companyId]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tjeneste slettet'
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error deleting service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting: ' . $e->getMessage()
    ]);
}
