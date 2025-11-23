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
    
    if (empty($data['plan_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler plan_id']);
        exit;
    }

    $db = getDBConnection();
    
    // Soft delete: mark as inactive instead of deleting
    // This preserves output_products and historical data
    $stmt = $db->prepare("
        UPDATE membership_plans 
        SET is_active = ?
        WHERE id = ? AND company_id = ?
    ");
    
    $stmt->execute([
        0,
        $data['plan_id'],
        $_SESSION['company_id']
    ]);
    
    if ($stmt->rowCount() > 0) {
        // Also hide the product from output_products catalog
        $hideStmt = $db->prepare("
            UPDATE output_products 
            SET is_visible = ?, is_public = ?
            WHERE product_id = ? 
            AND product_type = 'membership'
            AND company_id = ?
        ");
        $hideStmt->execute([
            0,
            0,
            $data['plan_id'],
            $_SESSION['company_id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Medlemskapsplan slettet'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Kunne ikke slette medlemskapsplan'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error deleting membership plan: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting'
    ]);
}
