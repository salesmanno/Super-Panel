<?php
session_start();
require_once __DIR__ . '/../includes/Database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['tenant_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing ID']);
        exit;
    }
    
    $db = Database::connect();
    $tenant_id = $_SESSION['tenant_id'];
    
    $stmt = $db->prepare("
        DELETE FROM global_income_expenses
        WHERE id = ? AND tenant_id = ?
    ");
    
    $stmt->execute([$data['id'], $tenant_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Inntekt/utgift slettet']);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Record not found']);
    }
    
} catch (Exception $e) {
    error_log("Delete global income/expense error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
