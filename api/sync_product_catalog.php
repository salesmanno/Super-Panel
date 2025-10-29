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
    $db = getDBConnection();
    $service = new ProductCatalogService($db);
    
    $companyId = $_SESSION['company_id'] ?? null;
    $productType = $_GET['product_type'] ?? null;
    
    $stats = $service->syncProductData($companyId, $productType);
    
    echo json_encode([
        'success' => true,
        'message' => "Synkronisert {$stats['updated']} produkter",
        'stats' => $stats
    ]);
    
} catch (Exception $e) {
    error_log("Error syncing product catalog: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved synkronisering av produktkatalog'
    ]);
}
