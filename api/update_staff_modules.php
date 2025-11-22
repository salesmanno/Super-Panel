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
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$admin_id = $_SESSION['tenant_admin_id'];
$company_id = $_SESSION['company_id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $target_admin_id = intval($data['admin_id'] ?? 0);
    $modules = $data['modules'] ?? []; // Array of module_ids that should be active
    
    if (!$target_admin_id) {
        echo json_encode(['success' => false, 'message' => 'Admin-ID mangler']);
        exit;
    }
    
    $db = getDBConnection();
    $db->beginTransaction();
    
    // Verify that target admin belongs to this company
    $stmt = $db->prepare("SELECT id FROM tenant_admins WHERE id = ? AND company_id = ?");
    $stmt->execute([$target_admin_id, $company_id]);
    if (!$stmt->fetch()) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Ugyldig admin']);
        exit;
    }
    
    // Get all available modules
    $stmt = $db->query("SELECT id FROM system_modules WHERE is_active = 1");
    $availableModules = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Delete existing module assignments for this admin
    $stmt = $db->prepare("DELETE FROM tenant_admin_modules WHERE tenant_admin_id = ?");
    $stmt->execute([$target_admin_id]);
    
    // Insert new module assignments
    $insertStmt = $db->prepare("
        INSERT INTO tenant_admin_modules 
        (tenant_admin_id, module_id, is_active, granted_by, granted_at)
        VALUES (?, ?, 1, ?, NOW())
    ");
    
    foreach ($modules as $module_id) {
        $module_id = intval($module_id);
        
        // Verify module exists
        if (!in_array($module_id, $availableModules)) {
            continue;
        }
        
        $insertStmt->execute([$target_admin_id, $module_id, $admin_id]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Modulrettigheter oppdatert',
        'modules_granted' => count($modules)
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error updating staff modules: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
