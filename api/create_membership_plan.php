<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ProductCatalogService.php';
require_once __DIR__ . '/../includes/ShareLinkService.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['name'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler plan navn']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    
    try {
        $billingCycle = $data['billing_cycle'] ?? 'yearly';
        
        // Validate image_url if provided
        $imageUrl = null;
        if (!empty($data['image_url'])) {
            $url = trim($data['image_url']);
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $imageUrl = $url;
            }
        }
        
        // MySQL only - membership_plans table
        $planId = bin2hex(random_bytes(16));
        $planCode = 'PLAN-' . strtoupper(substr($planId, 0, 8));
        
        // Generate unique share_code for this membership plan
        $shareLinkService = new ShareLinkService($db, $_SESSION['company_id']);
        $shareCode = $shareLinkService->generateCode();
        
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
                is_active,
                image_url,
                share_code
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)
        ");
        
        // Get for_sale value (default to 1/true if not provided)
        $forSale = isset($data['for_sale']) ? $data['for_sale'] : 1;
        
        $stmt->execute([
            $planId,
            $_SESSION['company_id'],
            $planCode,
            $data['name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $billingCycle,
            $data['benefits'] ?? null,
            $imageUrl,
            $shareCode
        ]);
        
        // Only create output_products entry if for_sale is true
        $outputProductId = null;
        if ($forSale) {
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
        }
        
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
