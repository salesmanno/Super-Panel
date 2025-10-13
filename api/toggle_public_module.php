<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['module_id']) || !isset($data['is_public'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing parameters']);
    exit;
}

$moduleId = (int)$data['module_id'];
$isPublic = (bool)$data['is_public'];
$companyId = $_SESSION['company_id'];

try {
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }
    
    // Check if module belongs to company
    $stmt = $db->prepare("SELECT id FROM tenant_company_modules WHERE company_id = ? AND module_id = ?");
    $stmt->execute([$companyId, $moduleId]);
    $exists = $stmt->fetch();
    
    if (!$exists) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Module not found for this company']);
        exit;
    }
    
    // Update is_public status
    $publicValue = $isPublic ? dbBool(true) : dbBool(false);
    $stmt = $db->prepare("UPDATE tenant_company_modules SET is_public = {$publicValue} WHERE company_id = ? AND module_id = ?");
    $stmt->execute([$companyId, $moduleId]);
    
    echo json_encode([
        'success' => true,
        'module_id' => $moduleId,
        'is_public' => $isPublic
    ]);
    
} catch (Exception $e) {
    error_log("Error toggling public module: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
