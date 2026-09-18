<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'], $_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once '../config/database.php';
require_once '../includes/announcement_helpers.php';

try {
    $raw = json_decode(file_get_contents('php://input'), true);
    $validated = announcement_validate_payload($raw);
    if (!$validated['ok']) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => $validated['message']], JSON_UNESCAPED_UNICODE);
        exit;
    }
    $data = $validated['data'];
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $authorId = $_SESSION['tenant_admin_id'];
    
    $tableName = 'tenant_announcements';
    
    $db->beginTransaction();
    
    // Generate UUID for MySQL
    $stmt = $db->query("SELECT UUID() as id");
    $announcementId = $stmt->fetch(PDO::FETCH_ASSOC)['id'];
    
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
                is_pinned, announcement_type, priority,
                created_at,
                updated_at
            ) VALUES (
                ?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW()
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
        isset($data['is_pinned']) ? (int)$data['is_pinned'] : 0,
        announcement_type($data['announcement_type'] ?? null),
        announcement_priority($data['priority'] ?? null)
    ];
    
    $stmt->execute($params);
    
    if (isset($data['product']) && !empty($data['product']['output_product_id'])) {
        $product = $data['product'];
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
            $announcementId,
            $product['output_product_id'],
            $product['discount_percent'] ?? 0,
            $product['max_quantity'] ?? null,
            $product['expiry_date'] ?? null
        ]);
    }
    
    $db->commit();

    $storedContentStmt = $db->prepare(
        'SELECT content FROM tenant_announcements WHERE id = ? AND company_id = ?'
    );
    $storedContentStmt->execute([$announcementId, $companyId]);
    $storedContent = (string) $storedContentStmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Nyhet opprettet',
        'announcement_id' => $announcementId,
        'content' => $storedContent,
        'content_html' => announcement_content_to_html($storedContent),
        'content_text' => announcement_content_to_plain_text($storedContent),
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('Create announcement error: ' . $e->getMessage());
    http_response_code($e instanceof InvalidArgumentException ? 422 : 500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke opprette nyhet'
    ], JSON_UNESCAPED_UNICODE);
}
