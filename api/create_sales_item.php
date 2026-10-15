<?php
require __DIR__.'/sales_legacy_disabled.php';
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
    
    $productName = $data['item_name'] ?? $data['name'] ?? null;
    
    if (empty($productName)) {
        echo json_encode(['success' => false, 'message' => 'Mangler produktnavn']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    
    try {
        // Generate sales product ID
        $salesProductId = bin2hex(random_bytes(16));
        
        // 1. Create sales product (CLEAN: using sales_products table)
        $stmt = $db->prepare("
            INSERT INTO sales_products (
                id,
                company_id,
                name,
                sku,
                description,
                category_id,
                selling_price,
                cost_price,
                img_product,
                item_taxable,
                tax_rate,
                unit,
                reorder_level,
                is_active
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $salesProductId,
            $_SESSION['company_id'],
            $productName,
            $data['sku'] ?? null,
            $data['description'] ?? null,
            $data['category'] ?? $data['category_id'] ?? null,
            $data['price'] ?? $data['selling_price'] ?? 0,
            $data['cost_price'] ?? 0,
            $data['img_product'] ?? null,
            $data['item_taxable'] ?? 1,
            $data['tax_rate'] ?? 0,
            $data['unit'] ?? 'stk',
            $data['reorder_level'] ?? $data['low_stock_threshold'] ?? 5,
            $data['is_active'] ?? true
        ]);
        
        // 2. Auto-generate output_products entry
        $catalogService = new ProductCatalogService($db);
        $outputProductId = $catalogService->createProductEntry(
            $_SESSION['company_id'],
            'product',
            $salesProductId,
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
            'product_id' => $salesProductId,
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
