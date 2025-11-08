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
    
    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM {$tableName} WHERE id = :id AND company_id = :company_id");
    $stmt->execute([':id' => $data['id'], ':company_id' => $companyId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Kunngjøring ikke funnet']);
        exit;
    }
    
    $db->beginTransaction();
    
    $sql = "UPDATE {$tableName} SET
                title = :title,
                content = :content,
                image_url = :image_url,
                is_published = :is_published,
                publish_at = :publish_at,
                expire_at = :expire_at,
                is_pinned = :is_pinned,
                updated_at = " . ($dbType === 'mysql' ? "NOW()" : "CURRENT_TIMESTAMP") . "
            WHERE id = :id AND company_id = :company_id";
    
    $stmt = $db->prepare($sql);
    
    $stmt->bindValue(':id', $data['id']);
    $stmt->bindValue(':company_id', $companyId);
    $stmt->bindValue(':title', $data['title']);
    $stmt->bindValue(':content', $data['content']);
    $stmt->bindValue(':image_url', $data['image_url'] ?? null);
    $stmt->bindValue(':is_published', isset($data['is_published']) ? (int)$data['is_published'] : 0);
    $stmt->bindValue(':publish_at', $data['publish_at'] ?? null);
    $stmt->bindValue(':expire_at', $data['expire_at'] ?? null);
    $stmt->bindValue(':is_pinned', isset($data['is_pinned']) ? (int)$data['is_pinned'] : 0);
    
    $stmt->execute();
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Kunngjøring oppdatert'
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Update announcement error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke oppdatere kunngjøring'
    ]);
}
