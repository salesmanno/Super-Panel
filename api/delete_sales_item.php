<?php
require __DIR__.'/sales_legacy_disabled.php';
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['item_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler item_id']);
        exit;
    }

    $db = getDBConnection();
    
    // Soft delete: mark as inactive instead of deleting
    // This preserves output_products and historical data
    $dbType = getDBType();
    if ($dbType === 'mysql') {
        $stmt = $db->prepare("
            UPDATE sales_products 
            SET is_active = " . 0 . "
            WHERE id = ? AND company_id = ?
        ");
    } else {
        $stmt = $db->prepare("
            UPDATE sales_items 
            SET is_active = " . 0 . "
            WHERE id = ? AND company_id = ?
        ");
    }
    
    $stmt->execute([
        $data['item_id'],
        $_SESSION['company_id']
    ]);
    
    if ($stmt->rowCount() > 0) {
        // Also hide the product from output_products catalog
        $hideStmt = $db->prepare("
            UPDATE output_products 
            SET is_visible = " . 0 . ", is_public = " . 0 . "
            WHERE product_id = ? 
            AND product_type IN ('product', 'sales_item')
            AND company_id = ?
        ");
        $hideStmt->execute([
            $data['item_id'],
            $_SESSION['company_id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Produkt slettet'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kunne ikke slette produkt'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error deleting sales item: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting'
    ]);
}
