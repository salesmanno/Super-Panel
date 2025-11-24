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
    $companyId = $_SESSION['company_id'];
    $authorId = $_SESSION['tenant_admin_id'];
    
    $tableName = 'tenant_announcements';
    
    $db->beginTransaction();
    
    // Generate UUID for MySQL
    $stmt = $db->query("SELECT UUID() as id");
    $announcementId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    
    // MySQL INSERT with explicit ID
    $sql = "INSERT INTO {$tableName} (
                id,
                company_id,
                author_id,
                title,
                content,
                image_url,
                is_published,
                publish_at,
                expire_at,
                is_pinned,
                created_at,
                updated_at
            ) VALUES (
                ?,?,?,?,?,?,?,?,?,?,NOW(),NOW()
            )";
    
    $stmt = $db->prepare($sql);
    
    $params = [
        $announcementId,
        $companyId,
        $authorId,
        $data['title'],
        $data['content'],
        $data['image_url'] ?? null,
        isset($data['is_published']) ? (int)$data['is_published'] : 0,
        ($data['publish_at'] ?? null) ?: date('Y-m-d H:i:s'),
        $data['expire_at'] ?? null,
        isset($data['is_pinned']) ? (int)$data['is_pinned'] : 0
    ];
    
    $stmt->execute($params);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Nyhet opprettet',
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
        'message' => 'Kunne ikke opprette nyhet'
    ]);
}
