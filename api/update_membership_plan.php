<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ProductCatalogService.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['plan_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler plan ID']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    try {
        // Update membership plan (MYSQL ONLY)
        $updateFields = [];
        $params = [];
        
        if (isset($data['name'])) {
            $updateFields[] = "name = ?";
            $params[] = $data['name'];
        }
        
        if (isset($data['description'])) {
            $updateFields[] = "description = ?";
            $params[] = $data['description'];
        }
        
        if (isset($data['price'])) {
            $updateFields[] = "price = ?";
            $params[] = $data['price'];
        }
        
        if (isset($data['billing_cycle'])) {
            $updateFields[] = "billing_cycle = ?";
            $params[] = $data['billing_cycle'];
        }
        
        if (isset($data['benefits'])) {
            $updateFields[] = "benefits = ?";
            $params[] = $data['benefits'];
        }
        
        if (isset($data['is_active'])) {
            $updateFields[] = "is_active = ?";
            $params[] = $data['is_active'];
        }
        
        if (isset($data['for_sale'])) {
            $updateFields[] = "for_sale = ?";
            $params[] = $data['for_sale'];
        }
        
        if (array_key_exists('future_price_change_date', $data)) {
            $updateFields[] = "future_price_change_date = ?";
            $params[] = $data['future_price_change_date'] ?: null;
        }
        
        if (array_key_exists('future_price_new', $data)) {
            $updateFields[] = "future_price_new = ?";
            $params[] = $data['future_price_new'] ?: null;
        }
        
        if (empty($updateFields)) {
            echo json_encode(['success' => false, 'message' => 'Ingen felter å oppdatere']);
            exit;
        }
        
        $params[] = $data['plan_id'];
        $params[] = $_SESSION['company_id'];
        
        $stmt = $db->prepare("
            UPDATE membership_plans 
            SET " . implode(', ', $updateFields) . "
            WHERE id = ? AND company_id = ?
        ");
        
        $stmt->execute($params);
        
        // Handle output_products sync based on for_sale status
        $catalogService = new ProductCatalogService($db);
        
        if (isset($data['for_sale'])) {
            if ($data['for_sale']) {
                // Ensure product entry exists if for_sale is now true
                $checkStmt = $db->prepare("
                    SELECT id FROM output_products 
                    WHERE company_id = ? AND product_type = 'membership' AND product_id = ?
                ");
                $checkStmt->execute([$_SESSION['company_id'], $data['plan_id']]);
                
                if (!$checkStmt->fetch()) {
                    // Create product entry if it doesn't exist
                    $catalogService->createProductEntry(
                        $_SESSION['company_id'],
                        'membership',
                        $data['plan_id'],
                        ['is_visible' => true, 'is_public' => true],
                        $_SESSION['tenant_admin_id']
                    );
                }
            } else {
                // Delete product entry if for_sale is now false
                $deleteStmt = $db->prepare("
                    DELETE FROM output_products 
                    WHERE company_id = ? AND product_type = 'membership' AND product_id = ?
                ");
                $deleteStmt->execute([$_SESSION['company_id'], $data['plan_id']]);
            }
        }
        
        // Sync output_products after update
        $catalogService->syncProductData($_SESSION['company_id'], 'membership');
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Medlemskapsplan oppdatert'
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error updating membership plan: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
