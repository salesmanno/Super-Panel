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
    
    if (empty($data['object_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler object_id']);
        exit;
    }

    $db = getDBConnection();
    
    // Soft delete: mark as unavailable instead of deleting
    // This preserves output_products and historical data
    $dbType = getDBType();
    if ($dbType === 'mysql') {
        $stmt = $db->prepare("
            UPDATE rental_objects 
            SET is_available = " . dbBool(false) . "
            WHERE id = ? AND company_id = ?
        ");
    } else {
        $stmt = $db->prepare("
            UPDATE rental_objects 
            SET is_available = " . dbBool(false) . "
            WHERE object_id = ? AND company_id = ?
        ");
    }
    
    $stmt->execute([
        $data['object_id'],
        $_SESSION['company_id']
    ]);
    
    if ($stmt->rowCount() > 0) {
        // Also hide the product from output_products catalog
        $hideStmt = $db->prepare("
            UPDATE output_products 
            SET is_visible = " . dbBool(false) . ", is_public = " . dbBool(false) . "
            WHERE product_id = ? 
            AND product_type = 'rental'
            AND company_id = ?
        ");
        $hideStmt->execute([
            $data['object_id'],
            $_SESSION['company_id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Utleieobjekt slettet'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kunne ikke slette utleieobjekt'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error deleting rental object: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting'
    ]);
}
