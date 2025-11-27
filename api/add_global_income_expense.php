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
    
    if (!$data || !isset($data['transaction_type'], $data['amount'], $data['module_source'], $data['source_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    $db = Database::connect();
    $tenant_id = $_SESSION['tenant_id'];
    
    $id = bin2hex(random_bytes(18));
    
    $stmt = $db->prepare("
        INSERT INTO global_income_expenses 
        (id, tenant_id, transaction_type, amount, description, category, unified_ledger_id, 
         source_id, module_source, notes, created_by, created_at, updated_at)
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $id,
        $tenant_id,
        $data['transaction_type'],
        $data['amount'],
        $data['description'] ?? null,
        $data['category'] ?? null,
        $data['unified_ledger_id'] ?? null,
        $data['source_id'],
        $data['module_source'],
        $data['notes'] ?? null,
        $_SESSION['staff_id'] ?? null
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Inntekt/utgift lagt til',
        'id' => $id
    ]);
    
} catch (Exception $e) {
    error_log("Add global income/expense error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>
