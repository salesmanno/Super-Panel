<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/announcement_helpers.php';

header('Content-Type: application/json');
if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success'=>false,'message'=>'Ikke autentisert'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!is_array($data) || !isset($data['id']) || !is_scalar($data['id']) || trim((string)$data['id']) === '') {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Nyhet-ID mangler']);
        exit;
    }
    $validated = announcement_validate_payload($data);
    if (!$validated['ok']) { http_response_code(422); echo json_encode(['success'=>false,'message'=>$validated['message']], JSON_UNESCAPED_UNICODE); exit; }
    $clean = $validated['data'];
    
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
                is_pinned = ?, announcement_type = ?, priority = ?,
                updated_at = NOW()
            WHERE id = ? AND company_id = ?";
    
    $stmt = $db->prepare($sql);
    
    $stmt->execute([
        $clean['title'], $clean['content'], $clean['image_url'], (int)$clean['is_published'],
        $clean['publish_at'], $clean['expire_at'], (int)$clean['is_pinned'],
        $clean['announcement_type'], $clean['priority'],
        $data['id'],
        $companyId
    ]);
    
    // Handle product association - delete existing and add new if provided
    $deleteProductSql = "DELETE FROM announcement_products WHERE announcement_id = ? AND company_id = ?";
    $deleteStmt = $db->prepare($deleteProductSql);
    $deleteStmt->execute([$data['id'], $companyId]);
    
    if (isset($clean['product']) && $clean['product'] !== null) {
        $product = $clean['product'];
        $ownership = $db->prepare('SELECT id FROM output_products WHERE id = ? AND company_id = ?');
        $ownership->execute([$product['output_product_id'], $companyId]);
        if (!$ownership->fetchColumn()) throw new InvalidArgumentException('Produktet tilhører ikke dette firmaet');
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
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Update announcement error: ' . $e->getMessage());
    http_response_code($e instanceof InvalidArgumentException ? 422 : 500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke oppdatere nyhet'
    ], JSON_UNESCAPED_UNICODE);
}
