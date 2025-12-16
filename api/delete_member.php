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
    $companyId = $_SESSION['company_id'];
    $memberId = $data['member_id'];
    
    $stmt = $db->prepare("DELETE FROM membership_members WHERE id = ? AND company_id = ?");
    $stmt->execute([$memberId, $companyId]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Medlem slettet'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Medlem ikke funnet eller allerede slettet'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error deleting member: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting: ' . $e->getMessage()
    ]);
}
