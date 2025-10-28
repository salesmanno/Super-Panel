<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autorisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$adminId = $_GET['admin_id'] ?? null;

if (!$adminId) {
    echo json_encode(['success' => false, 'message' => 'Mangler admin_id']);
    exit;
}

try {
    $db = getDBConnection();
    
    // Verify the admin belongs to the same company
    $stmt = $db->prepare("
        SELECT ta.id 
        FROM tenant_admins ta
        WHERE ta.id = ? AND ta.company_id = ?
    ");
    $stmt->execute([$adminId, $_SESSION['company_id']]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Admin ikke funnet eller tilhører annet selskap']);
        exit;
    }
    
    // Get available modules for this company (from tenant_company_modules)
    $activeValue = dbBool(true);
    $stmt = $db->prepare("
        SELECT DISTINCT sm.id, sm.name, sm.description
        FROM system_modules sm
        INNER JOIN tenant_company_modules tcm ON sm.id = tcm.module_id
        WHERE tcm.company_id = ? 
        AND sm.is_active = $activeValue
        AND tcm.can_access = $activeValue
        ORDER BY sm.name
    ");
    $stmt->execute([$_SESSION['company_id']]);
    $availableModules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get current module access for this admin
    $stmt = $db->prepare("
        SELECT module_id 
        FROM tenant_admin_modules 
        WHERE tenant_admin_id = ?
    ");
    $stmt->execute([$adminId]);
    $currentAccess = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'success' => true,
        'available_modules' => $availableModules,
        'current_access' => $currentAccess
    ]);
    
} catch (Exception $e) {
    error_log("Get admin module access error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database feil']);
}
