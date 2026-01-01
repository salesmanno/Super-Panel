<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Nyhet-ID mangler']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $tableName = 'tenant_announcements';
    
    // Verify ownership
    $stmt = $db->prepare("SELECT id FROM {$tableName} WHERE id = ? AND company_id = ?");
    $stmt->execute([$data['id'], $companyId]);
    
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Nyhet ikke funnet']);
        exit;
    }
    
    $db->beginTransaction();
    
    $sql = "UPDATE {$tableName} SET
                title = ?,
                content = ?,
                image_url = ?,
                is_published = ?,
                publish_at = ?,
                expire_at = ?,
                is_pinned = ?,
                updated_at = NOW()
            WHERE id = ? AND company_id = ?";
    
    $stmt = $db->prepare($sql);
    
    $stmt->execute([
        $data['title'],
        $data['content'],
        $data['image_url'] ?? null,
        isset($data['is_published']) ? (int)$data['is_published'] : 0,
        $data['publish_at'] ?? null,
        $data['expire_at'] ?? null,
        isset($data['is_pinned']) ? (int)$data['is_pinned'] : 0,
        $data['id'],
        $companyId
    ]);
    
    // Handle product association - delete existing and add new if provided
    $deleteProductSql = "DELETE FROM announcement_products WHERE announcement_id = ? AND company_id = ?";
    $deleteStmt = $db->prepare($deleteProductSql);
    $deleteStmt->execute([$data['id'], $companyId]);
    
    if (isset($data['product']) && !empty($data['product']['output_product_id'])) {
        $product = $data['product'];
        $productSql = "INSERT INTO announcement_products (
            company_id,
            announcement_id,
            output_product_id,
            discount_percent,
            max_quantity,
            expiry_date
        ) VALUES (?, ?, ?, ?, ?, ?)";
        
        $productStmt = $db->prepare($productSql);
        $productStmt->execute([
            $companyId,
            $data['id'],
            $product['output_product_id'],
            $product['discount_percent'] ?? 0,
            $product['max_quantity'] ?? null,
            $product['expiry_date'] ?? null
        ]);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Nyhet oppdatert'
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Update announcement error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke oppdatere nyhet'
    ]);
}
