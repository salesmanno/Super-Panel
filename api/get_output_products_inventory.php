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
    
    // Get all output_products (both active and inactive)
    $stmt = $db->prepare("
        SELECT 
            id,
            module_id,
            product_type,
            product_id,
            name,
            price,
            is_visible,
            is_public,
            created_at,
            updated_at
        FROM output_products
        WHERE company_id = ?
        ORDER BY is_visible DESC, created_at DESC
    ");
    
    $stmt->execute([$company_id]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get system modules for mapping
    $moduleStmt = $db->prepare("SELECT id, name FROM system_modules");
    $moduleStmt->execute();
    $modules = [];
    foreach ($moduleStmt->fetchAll(PDO::FETCH_ASSOC) as $mod) {
        $modules[$mod['id']] = $mod['name'];
    }
    
    // Count stats
    $activeCount = count(array_filter($products, fn($p) => $p['is_visible'] == 1));
    $inactiveCount = count($products) - $activeCount;
    
    echo json_encode([
        'success' => true,
        'products' => $products,
        'modules' => $modules,
        'stats' => [
            'total' => count($products),
            'active' => $activeCount,
            'inactive' => $inactiveCount
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Get output products inventory error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av oppføringer'
    ]);
}
?>
