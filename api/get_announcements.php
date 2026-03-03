<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once '../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $tableName = 'tenant_announcements';
    
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
            WHERE a.company_id = ?";
    
    // Apply filters
    $params = [$companyId];
    if (!$showUnpublished) {
        $sql .= " AND a.is_published = ? 
                  AND COALESCE(a.publish_at, NOW()) <= NOW()
                  AND (a.expire_at IS NULL OR a.expire_at > NOW())";
        $params[] = 1;
    }
    
    $sql .= " ORDER BY a.is_pinned DESC, a.publish_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get product associations for all announcements
    $announcementIds = array_column($announcements, 'id');
    $productMap = [];
    
    if (!empty($announcementIds)) {
        $placeholders = implode(',', array_fill(0, count($announcementIds), '?'));
        $productSql = "SELECT announcement_id, output_product_id, discount_percent, max_quantity, expiry_date 
                       FROM announcement_products 
                       WHERE announcement_id IN ($placeholders) AND company_id = ?";
        $productStmt = $db->prepare($productSql);
        $productStmt->execute(array_merge($announcementIds, [$companyId]));
        
        while ($row = $productStmt->fetch(PDO::FETCH_ASSOC)) {
            $productMap[$row['announcement_id']] = [
                'output_product_id' => $row['output_product_id'],
                'discount_percent' => (int)$row['discount_percent'],
                'max_quantity' => $row['max_quantity'] ? (int)$row['max_quantity'] : null,
                'expiry_date' => $row['expiry_date']
            ];
        }
    }
    
    // Cast boolean fields and add product data
    foreach ($announcements as &$announcement) {
        $announcement['is_published'] = (bool)$announcement['is_published'];
        $announcement['is_pinned'] = (bool)$announcement['is_pinned'];
        $announcement['product'] = $productMap[$announcement['id']] ?? null;
    }
    
    echo json_encode([
        'success' => true,
        'announcements' => $announcements
    ]);
    
} catch (Exception $e) {
    error_log('Get announcements error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke hente nyheter'
    ]);
}
