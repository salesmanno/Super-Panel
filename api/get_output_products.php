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
    
    // Get all output_products for this company with module info
    $stmt = $db->prepare("
        SELECT 
            op.id,
            op.module_id,
            op.product_type,
            op.product_id,
            op.name,
            op.description,
            op.price,
            op.is_visible,
            op.is_public,
            op.created_at,
            sm.module_key as module_source
        FROM output_products op
        LEFT JOIN system_modules sm ON op.module_id = sm.id
        WHERE op.company_id = ?
        ORDER BY op.module_id, op.created_at DESC
    ");
    
    $stmt->execute([$company_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count by module
    $countByModule = [];
    $totalModules = [];
    
    foreach ($products as $product) {
        $module = $product['module_id'];
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
