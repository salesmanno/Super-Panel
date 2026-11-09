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
    $db->beginTransaction();
    $elements = $db->prepare('DELETE FROM tenant_news_elements WHERE announcement_id = ? AND company_id = ?');
    $elements->execute([$data['id'],$companyId]);
    $stmt = $db->prepare("DELETE FROM tenant_announcements WHERE id = ? AND company_id = ?");
    $stmt->execute([$data['id'], $companyId]);
    
    if ($stmt->rowCount() > 0) {
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Nyhet slettet'
        ]);
    } else {
        $db->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Nyhet ikke funnet'
        ]);
    }
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Delete announcement error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke slette nyhet'
    ]);
}
