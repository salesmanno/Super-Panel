<?php
require_once '../config/database.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Kunngjørings-ID mangler']);
        exit;
    }
    
    $db = getDBConnection();
    $dbType = getDBType();
    $companyId = $_SESSION['company_id'];
    
    $tableName = $dbType === 'mysql' ? 'tenant_announcements' : 'x_tenant_announcements';
    
    $stmt = $db->prepare("DELETE FROM {$tableName} WHERE id = :id AND company_id = :company_id");
    $stmt->execute([':id' => $data['id'], ':company_id' => $companyId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Kunngjøring slettet'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kunngjøring ikke funnet'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Delete announcement error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke slette kunngjøring'
    ]);
}
