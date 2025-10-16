<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT 
            id,
            item_name,
            sku,
            description,
            category,
            price,
            stock_quantity,
            is_active
        FROM sales_items
        WHERE company_id = ? AND is_active = " . dbBool(true) . "
        ORDER BY item_name ASC
    ");
    
    $stmt->execute([$_SESSION['company_id']]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'items' => $items
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching sales items: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av produkter'
    ]);
}
