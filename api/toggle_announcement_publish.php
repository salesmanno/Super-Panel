<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth_check.php';
require_once '../includes/announcement_editor.php';

header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Kunngjørings-ID mangler']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $tableName = 'tenant_announcements';
    
    $db->beginTransaction();
    $stmt = $db->prepare("SELECT is_published FROM {$tableName} WHERE id = ? AND company_id = ? FOR UPDATE");
    $stmt->execute([$data['id'], $companyId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$existing) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success'=>false,'message'=>'Nyheten finnes ikke']);
        exit;
    }
    $publish = !(bool)$existing['is_published'];
    if ($publish && !announcement_editor_author($db, (string)$_SESSION['tenant_admin_id'], (string)$companyId)) {
        throw new InvalidArgumentException('Brukeren må være knyttet til en ansatt i bedriften for å publisere.');
    }
    // Explicit values avoid MySQL's left-to-right SET assignment semantics.
    $stmt = $db->prepare("UPDATE {$tableName} SET is_published = ?,
        publish_at = CASE WHEN ? = 1 THEN NOW() ELSE publish_at END,
        updated_at = NOW() WHERE id = ? AND company_id = ?");
    $stmt->execute([(int)$publish, (int)$publish, $data['id'], $companyId]);
    $db->commit();
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Publiseringsstatus endret'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kunngjøring ikke funnet'
        ]);
    }
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Toggle announcement publish error: ' . $e->getMessage());
    http_response_code($e instanceof InvalidArgumentException ? 422 : 500);
    echo json_encode([
        'success' => false,
        'message' => $e instanceof InvalidArgumentException ? $e->getMessage() : 'Kunne ikke endre publiseringsstatus'
    ]);
}
