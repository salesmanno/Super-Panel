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
        // Update membership plan - support both MySQL and PostgreSQL
        $updateFields = [];
        $params = [];
        
        // MySQL: membership_plans table
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
            
            if (isset($data['is_internal'])) {
                $updateFields[] = "is_internal = ?";
                $params[] = $data['is_internal'];
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
            
        $data['name'])) {
                $updateFields[] = "plan_name = ?";
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
                // Convert billing_cycle to duration_months for PostgreSQL
                $durationMonths = match($data['billing_cycle']) {
                    'monthly' => 1,
                    'quarterly' => 3,
                    'yearly' => 12,
                    default => 12
                };
                $updateFields[] = "duration_months = ?";
                $params[] = $durationMonths;
            }
            
            if (isset($data['benefits'])) {
                $updateFields[] = "features = ?";
                $params[] = $data['benefits'];
            }
            
            if (isset($data['is_active'])) {
                $updateFields[] = "is_active = ?";
                $params[] = $data['is_active'];
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
                WHERE plan_id = ? AND company_id = ?
            ");
            
            $stmt->execute($params);
        }
        
        // Automatically sync output_products after update
        $catalogService = new ProductCatalogService($db);
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
