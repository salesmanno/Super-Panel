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
    
    if (empty($data['plan_name'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler plan navn']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    
    try {
        // 1. Create membership plan
        $stmt = $db->prepare("
            INSERT INTO x_membership_plan (
                company_id,
                plan_name,
                description,
                price,
                duration_months,
                features,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
            RETURNING plan_id
        ");
        
        $stmt->execute([
            $_SESSION['company_id'],
            $data['plan_name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $data['duration_months'] ?? 12,
            $data['features'] ?? null,
            $data['is_active'] ?? true
        ]);
        
        $planId = $stmt->fetchColumn();
        
        // 2. Auto-generate output_products entry
        $catalogService = new ProductCatalogService($db);
        $outputProductId = $catalogService->createProductEntry(
            $_SESSION['company_id'],
            'membership',
            $planId,
            [
                'is_visible' => true,
                'is_public' => $data['is_public'] ?? true,
                'is_marketplace_featured' => $data['is_marketplace_featured'] ?? false
            ],
            $_SESSION['tenant_admin_id']
        );
        
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
