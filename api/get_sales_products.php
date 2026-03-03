<?php
session_start();
session_write_close();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Try with stock columns first (for databases that have them)
    try {
        $stmt = $db->prepare("
            SELECT 
                id,
                name,
                sku,
                description,
                cost_price,
                selling_price,
                (selling_price - COALESCE(cost_price, 0)) as profit_margin,
                img_product,
                item_taxable,
                category_id,
                is_active,
                stock_quantity,
                low_stock_threshold,
                created_at
            FROM sales_products 
            WHERE company_id = ?
            ORDER BY created_at DESC
        ");
        
        $stmt->execute([$company_id]);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        // If stock columns don't exist, try without them (backward compatibility)
        if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), 'Column not found') !== false) {
            $stmt = $db->prepare("
                SELECT 
                    id,
                    name,
                    sku,
                    description,
                    cost_price,
                    selling_price,
                    (selling_price - COALESCE(cost_price, 0)) as profit_margin,
                    img_product,
                    item_taxable,
                    category_id,
                    is_active,
                    0 as stock_quantity,
                    5 as low_stock_threshold,
                    created_at
                FROM sales_products 
                WHERE company_id = ?
                ORDER BY created_at DESC
            ");
            
            $stmt->execute([$company_id]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            throw $e;
        }
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products
    ]);
    
} catch (Exception $e) {
    error_log("Get sales products error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av produkter: ' . $e->getMessage()
    ]);
}
