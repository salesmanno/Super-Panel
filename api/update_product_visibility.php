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
    
    if (empty($data['output_product_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler output_product_id']);
        exit;
    }

    $db = getDBConnection();
    $catalogService = new ProductCatalogService($db);
    
    // Prepare settings
    $settings = [];
    
    if (isset($data['is_visible'])) {
        $settings['is_visible'] = $data['is_visible'];
    }
    
    if (isset($data['is_public'])) {
        $settings['is_public'] = $data['is_public'];
    }
    
    if (isset($data['is_marketplace_featured'])) {
        $settings['is_marketplace_featured'] = $data['is_marketplace_featured'];
    }
    
    if (isset($data['marketplace_priority'])) {
        $settings['marketplace_priority'] = $data['marketplace_priority'];
    }
    
    // Update visibility
    $result = $catalogService->updateProductVisibility($data['output_product_id'], $settings);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Produktsynlighet oppdatert'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Ingen endringer gjort'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error updating product visibility: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
