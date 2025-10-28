<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autorisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$input = json_decode(file_get_contents('php://input'), true);
$adminId = $input['admin_id'] ?? null;
$moduleIds = $input['module_ids'] ?? [];

if (!$adminId) {
    echo json_encode(['success' => false, 'message' => 'Mangler admin_id']);
    exit;
}

// Prevent admin from editing their own access
if ($adminId == $_SESSION['tenant_admin_id']) {
    echo json_encode(['success' => false, 'message' => 'Du kan ikke endre din egen modultilgang']);
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
    
    // Verify all module IDs are valid company modules
    if (!empty($moduleIds)) {
        $placeholders = str_repeat('?,', count($moduleIds) - 1) . '?';
        $activeValue = dbBool(true);
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM tenant_company_modules tcm
            WHERE tcm.company_id = ? 
            AND tcm.module_id IN ($placeholders)
            AND tcm.can_access = $activeValue
        ");
        $stmt->execute(array_merge([$_SESSION['company_id']], $moduleIds));
        
        if ($stmt->fetchColumn() != count($moduleIds)) {
            echo json_encode(['success' => false, 'message' => 'En eller flere moduler er ikke tilgjengelige for bedriften']);
            exit;
        }
    }
    
    // Begin transaction
    $db->beginTransaction();
    
    // Remove all existing module access for this admin
    $stmt = $db->prepare("DELETE FROM tenant_admin_modules WHERE tenant_admin_id = ?");
    $stmt->execute([$adminId]);
    
    // Add new module access
    if (!empty($moduleIds)) {
        $stmt = $db->prepare("
            INSERT INTO tenant_admin_modules (tenant_admin_id, module_id, granted_by, granted_at)
            VALUES (?, ?, ?, NOW())
        ");
        
        foreach ($moduleIds as $moduleId) {
            $stmt->execute([$adminId, $moduleId, $_SESSION['tenant_admin_id']]);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Modultilgang oppdatert',
        'modules_granted' => count($moduleIds)
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Update admin module access error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database feil: ' . $e->getMessage()]);
}
