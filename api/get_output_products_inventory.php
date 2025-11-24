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
    
    // Enrich products with names from source tables if name is empty
    foreach ($products as &$product) {
        if (empty($product['name']) && !empty($product['product_type']) && !empty($product['product_id'])) {
            try {
                $sourceTable = null;
                $idColumn = 'id';
                $nameColumn = 'name';
                
                switch ($product['product_type']) {
                    case 'membership':
                        $sourceTable = 'membership_plans';
                        $idColumn = 'id';
                        $nameColumn = 'plan_name';
                        break;
                    case 'product':
                        $sourceTable = 'sales_products';
                        $idColumn = 'id';
                        $nameColumn = 'name';
                        break;
                    case 'rental':
                        $sourceTable = 'rental_objects';
                        $idColumn = 'id';
                        $nameColumn = 'object_name';
                        break;
                    case 'event':
                        $sourceTable = 'events_events';
                        $idColumn = 'id';
                        $nameColumn = 'title';
                        break;
                    case 'service':
                        $sourceTable = 'reservation_services';
                        $idColumn = 'id';
                        $nameColumn = 'service_name';
                        break;
                }
                
                if ($sourceTable) {
                    $query = "SELECT {$nameColumn} FROM {$sourceTable} WHERE {$idColumn} = ?";
                    $enrichStmt = $db->prepare($query);
                    $enrichStmt->execute([$product['product_id']]);
                    $sourceData = $enrichStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($sourceData && !empty($sourceData[$nameColumn])) {
                        $product['name'] = $sourceData[$nameColumn];
                    }
                }
            } catch (Exception $e) {
                error_log("Could not enrich product {$product['id']}: " . $e->getMessage());
                // Keep empty name if enrichment fails
            }
        }
    }
    unset($product); // Break reference
    
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
