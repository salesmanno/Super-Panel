<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once '../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['title']) || !isset($data['content'])) {
        echo json_encode(['success' => false, 'message' => 'Tittel og innhold er påkrevd']);
        exit;
    }
    
    $db = getDBConnection();
    $dbType = getDBType();
    $companyId = $_SESSION['company_id'];
    $authorId = $_SESSION['tenant_admin_id'];
    
    $tableName = $dbType === 'mysql' ? 'tenant_announcements' : 'x_tenant_announcements';
    
    $db->beginTransaction();
    
    // Generate UUID for MySQL
    if ($dbType === 'mysql') {
        $stmt = $db->query("SELECT UUID() as id");
        $announcementId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    }
    
    $sql = "INSERT INTO {$tableName} (
                " . ($dbType === 'mysql' ? "id," : "") . "
                company_id,
                author_id,
                title,
                content,
                announcement_type,
                priority,
                target_audience,
                is_published,
                publish_at,
                expire_at,
                is_pinned,
                related_event_id,
                related_module,
                metadata
            ) VALUES (
                " . ($dbType === 'mysql' ? ":id," : "") . "
                :company_id,
                :author_id,
                :title,
                :content,
                :announcement_type,
                :priority,
                :target_audience,
                :is_published,
                :publish_at,
                :expire_at,
                :is_pinned,
                :related_event_id,
                :related_module,
                " . ($dbType === 'mysql' ? ":metadata" : "CAST(:metadata AS JSONB)") . "
            )" . ($dbType === 'pgsql' ? " RETURNING id" : "");
    
    $stmt = $db->prepare($sql);
    
    if ($dbType === 'mysql') {
        $stmt->bindValue(':id', $announcementId);
    }
    
    $stmt->bindValue(':company_id', $companyId);
    $stmt->bindValue(':author_id', $authorId);
    $stmt->bindValue(':title', $data['title']);
    $stmt->bindValue(':content', $data['content']);
    $stmt->bindValue(':announcement_type', $data['announcement_type'] ?? 'info');
    $stmt->bindValue(':priority', $data['priority'] ?? 'normal');
    $stmt->bindValue(':target_audience', $data['target_audience'] ?? 'all');
    $stmt->bindValue(':is_published', isset($data['is_published']) ? (int)$data['is_published'] : 0);
    $stmt->bindValue(':publish_at', $data['publish_at'] ?? null);
    $stmt->bindValue(':expire_at', $data['expire_at'] ?? null);
    $stmt->bindValue(':is_pinned', isset($data['is_pinned']) ? (int)$data['is_pinned'] : 0);
    $stmt->bindValue(':related_event_id', $data['related_event_id'] ?? null);
    $stmt->bindValue(':related_module', $data['related_module'] ?? null);
    $stmt->bindValue(':metadata', isset($data['metadata']) ? json_encode($data['metadata']) : null);
    
    $stmt->execute();
    
    if ($dbType === 'pgsql') {
        $announcementId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Kunngjøring opprettet',
        'announcement_id' => $announcementId
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Create announcement error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke opprette kunngjøring'
    ]);
}
