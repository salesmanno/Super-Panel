<?php
session_start();
session_write_close();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$company_id = $_SESSION['company_id'];

try {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT 
            id,
            product_id,
            product_name,
            supplier,
            quantity,
            unit_cost,
            total_cost,
            purchase_date,
            created_at
        FROM sales_purchase_history
        WHERE company_id = ?
        ORDER BY purchase_date DESC, created_at DESC
        LIMIT 100
    ");
    $stmt->execute([$company_id]);
    $purchases = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'purchases' => $purchases
    ]);
    
} catch (Exception $e) {
    error_log("Get purchase history error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Serverfeil ved henting av innkjøpshistorikk']);
}
