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
    
    if (empty($data['item_name'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler produktnavn']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    
    try {
        // Generate sales item ID
        $salesItemId = bin2hex(random_bytes(16));
        
        // 1. Create sales item
        $stmt = $db->prepare("
            INSERT INTO sales_items (
                id,
                company_id,
                item_name,
                sku,
                description,
                category,
                price,
                cost_price,
                stock_quantity,
                low_stock_threshold,
                is_active,
                tax_rate
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $salesItemId,
            $_SESSION['company_id'],
            $data['item_name'],
            $data['sku'] ?? null,
            $data['description'] ?? null,
            $data['category'] ?? null,
            $data['price'] ?? 0,
            $data['cost_price'] ?? null,
            $data['stock_quantity'] ?? 0,
            $data['low_stock_threshold'] ?? 5,
            $data['is_active'] ?? true,
            $data['tax_rate'] ?? 0
        ]);
        
        // 2. Auto-generate output_products entry
        $catalogService = new ProductCatalogService($db);
        $outputProductId = $catalogService->createProductEntry(
            $_SESSION['company_id'],
            'sales',
            $salesItemId,
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
            'message' => 'Salgsprodukt opprettet',
            'sales_item_id' => $salesItemId,
            'output_product_id' => $outputProductId
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error creating sales item: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
