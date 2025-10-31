<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['member_id'])) {
        echo json_encode(['success' => false, 'message' => 'Medlem-ID mangler']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    
    // Build dynamic update query based on provided fields
    $updates = [];
    $params = [];
    
    $allowedFields = ['first_name', 'last_name', 'email', 'phone', 'address', 'poststed', 'dateofbirth', 'notes'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updates[] = "$field = ?";
            $params[] = $data[$field] ?: null;
        }
    }
    
    if (empty($updates)) {
        echo json_encode(['success' => false, 'message' => 'Ingen felt å oppdatere']);
        exit;
    }
    
    $params[] = $data['member_id'];
    $params[] = $_SESSION['company_id'];
    
    $sql = "UPDATE membership_members SET " . implode(', ', $updates) . " WHERE member_id = ? AND company_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Medlem oppdatert'
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error updating member: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
