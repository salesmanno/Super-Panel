<?php
session_start();
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
    $companyId = $_SESSION['company_id'];
    
    $tableName = 'tenant_announcements';
    
    // Toggle pin status
    $sql = "UPDATE {$tableName} 
            SET is_pinned = CASE WHEN is_pinned = 1 THEN 0 ELSE 1 END,
                updated_at = NOW()
            WHERE id = ? AND company_id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$data['id'], $companyId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Festestatus endret'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kunngjøring ikke funnet'
        ]);
    }
    
} catch (Exception $e) {
    error_log('Toggle announcement pin error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke endre festestatus'
    ]);
}
