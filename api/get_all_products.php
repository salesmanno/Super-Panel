<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ProductCatalogService.php';

try {
    $db = getDBConnection();
    $catalogService = new ProductCatalogService($db);
    
    // Get filters from query params
    $filters = [];
    
    if (isset($_GET['is_visible'])) {
        $filters['is_visible'] = filter_var($_GET['is_visible'], FILTER_VALIDATE_BOOLEAN);
    }
    
    if (isset($_GET['is_public'])) {
        $filters['is_public'] = filter_var($_GET['is_public'], FILTER_VALIDATE_BOOLEAN);
    }
    
    if (isset($_GET['is_marketplace_featured'])) {
        $filters['is_marketplace_featured'] = filter_var($_GET['is_marketplace_featured'], FILTER_VALIDATE_BOOLEAN);
    }
    
    // Get all products
    $products = $catalogService->getAllProducts($_SESSION['company_id'], $filters);
    
    // Group products by module for better visualization
    $groupedProducts = [
        'events' => [],
        'sales' => [],
        'rental' => [],
        'membership' => []
    ];
    
    foreach ($products as $product) {
        $moduleKey = $product['module_key'];
        if (isset($groupedProducts[$moduleKey])) {
            $groupedProducts[$moduleKey][] = $product;
        }
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'grouped' => $groupedProducts,
        'total_count' => count($products),
        'counts_by_module' => [
            'events' => count($groupedProducts['events']),
            'sales' => count($groupedProducts['sales']),
            'rental' => count($groupedProducts['rental']),
            'membership' => count($groupedProducts['membership'])
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av produkter'
    ]);
}
