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
    
    if (empty($data['name'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler plan navn']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    $dbType = getDBType();
    
    try {
        $billingCycle = $data['billing_cycle'] ?? 'yearly';
        
        // Dual database support: MySQL uses membership_plans, PostgreSQL uses x_membership_plan
        if ($dbType === 'mysql') {
            // MySQL: membership_plans table
            $planId = bin2hex(random_bytes(16));
            $planCode = 'PLAN-' . strtoupper(substr($planId, 0, 8));
            
            $stmt = $db->prepare("
                INSERT INTO membership_plans (
                    id,
                    company_id,
                    plan_code,
                    name,
                    description,
                    price,
                    billing_cycle,
                    benefits,
                    image_url,
                    is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ");
            
            $stmt->execute([
                $planId,
                $_SESSION['company_id'],
                $planCode,
                $data['name'],
                $data['description'] ?? null,
                $data['price'] ?? 0,
                $billingCycle,
                $data['benefits'] ?? null,
                $data['image_url'] ?? null
            ]);
        } else {
            // PostgreSQL: x_membership_plan table
            $durationMonths = match($billingCycle) {
                'monthly' => 1,
                'quarterly' => 3,
                'yearly' => 12,
                default => 12
            };
            
            $stmt = $db->prepare("
                INSERT INTO x_membership_plan (
                    company_id,
                    plan_name,
                    description,
                    price,
                    duration_months,
                    features,
                    image_url,
                    is_active
                ) VALUES (?, ?, ?, ?, ?, ?, ?, true)
                RETURNING plan_id
            ");
            
            $stmt->execute([
                $_SESSION['company_id'],
                $data['name'],
                $data['description'] ?? null,
                $data['price'] ?? 0,
                $durationMonths,
                $data['benefits'] ?? null,
                $data['image_url'] ?? null
            ]);
            
            $planId = $stmt->fetchColumn();
        }
        
        // Automatically create output_products entry
        $catalogService = new ProductCatalogService($db);
        $outputProductId = $catalogService->createProductEntry(
            $_SESSION['company_id'],
            'membership',
            $planId,
            ['is_visible' => true, 'is_public' => true],
            $_SESSION['tenant_admin_id']
        );
        
        // Sync the newly created product to ensure name and price are set
        $catalogService->syncProductData($_SESSION['company_id'], 'membership');
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Medlemskapsplan opprettet',
            'plan_id' => $planId,
            'output_product_id' => $outputProductId
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error creating membership plan: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
