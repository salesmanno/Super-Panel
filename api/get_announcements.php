<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once '../config/database.php';

try {
    $db = getDBConnection();
    $dbType = getDBType();
    $companyId = $_SESSION['company_id'];
    
    $tableName = $dbType === 'mysql' ? 'tenant_announcements' : 'x_tenant_announcements';
    
    // Get filter parameters
    $showUnpublished = isset($_GET['show_unpublished']) && $_GET['show_unpublished'] === 'true';
    
    $sql = "SELECT 
                a.id,
                a.company_id,
                a.author_id,
                a.title,
                a.content,
                a.image_url,
                a.is_published,
                a.publish_at,
                a.expire_at,
                a.is_pinned,
                a.created_at,
                a.updated_at
            FROM {$tableName} a
            WHERE a.company_id = :company_id";
    
    // Apply filters
    if (!$showUnpublished) {
        $sql .= " AND a.is_published = 1 
                  AND COALESCE(a.publish_at, " . ($dbType === 'mysql' ? "NOW()" : "CURRENT_TIMESTAMP") . ") <= " . ($dbType === 'mysql' ? "NOW()" : "CURRENT_TIMESTAMP") . "
                  AND (a.expire_at IS NULL OR a.expire_at > " . ($dbType === 'mysql' ? "NOW()" : "CURRENT_TIMESTAMP") . ")";
    }
    
    $sql .= " ORDER BY a.is_pinned DESC, a.publish_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':company_id', $companyId);
    
    $stmt->execute();
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cast boolean fields
    foreach ($announcements as &$announcement) {
        $announcement['is_published'] = (bool)$announcement['is_published'];
        $announcement['is_pinned'] = (bool)$announcement['is_pinned'];
    }
    
    echo json_encode([
        'success' => true,
        'announcements' => $announcements
    ]);
    
} catch (Exception $e) {
    error_log('Get announcements error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke hente kunngjøringer'
    ]);
}
