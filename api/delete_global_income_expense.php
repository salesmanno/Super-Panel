<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/GlobalIncomeExpenseService.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
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
    
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    $globalService = new GlobalIncomeExpenseService($db, $company_id);
    $result = $globalService->delete($data['id']);
    
    if ($result['success']) {
        echo json_encode($result);
    } else {
        http_response_code(404);
        echo json_encode($result);
    }
    
} catch (Exception $e) {
    error_log("Delete global income/expense error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
