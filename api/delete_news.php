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
    
    if (empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler nyhet ID']);
        exit;
    }
    
    $db = getDBConnection();
    
    // Delete news item
    $stmt = $db->prepare("DELETE FROM core_sys_news WHERE id = ?");
    $stmt->execute([$data['id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Nyhet slettet'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Nyhet ikke funnet'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error deleting news: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting av nyhet: ' . $e->getMessage()
    ]);
}
