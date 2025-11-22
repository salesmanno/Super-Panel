<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Get all output_products for this company
    $stmt = $db->prepare("
        SELECT 
            id,
            module_source,
            item_type,
            source_id,
            name,
            selling_price,
            is_available,
            created_at
        FROM output_products
        WHERE company_id = ?
        ORDER BY module_source, created_at DESC
    ");
    
    $stmt->execute([$company_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count by module
    $countByModule = [];
    $totalModules = [];
    
    foreach ($products as $product) {
        $module = $product['module_source'];
        if (!isset($countByModule[$module])) {
            $countByModule[$module] = 0;
        }
        $countByModule[$module]++;
        
        if (!in_array($module, $totalModules)) {
            $totalModules[] = $module;
        }
    }
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'count_by_module' => $countByModule,
        'total_products' => count($products),
        'total_modules_with_products' => count($totalModules),
        'modules' => $totalModules
    ]);
    
} catch (Exception $e) {
    error_log("Get output products error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av oppføringer: ' . $e->getMessage()
    ]);
}
