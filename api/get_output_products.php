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
    
    // Fetch names from source tables if empty
    foreach ($products as &$product) {
        if (empty($product['name'])) {
            try {
                switch ($product['product_type']) {
                    case 'events':
                        $nameStmt = $db->prepare("SELECT title FROM events_eventdata WHERE id = ?");
                        $nameStmt->execute([$product['product_id']]);
                        $row = $nameStmt->fetch(PDO::FETCH_ASSOC);
                        if ($row) {
                            $product['name'] = $row['title'];
                            $updateStmt = $db->prepare("UPDATE output_products SET name = ? WHERE id = ?");
                            $updateStmt->execute([$row['title'], $product['id']]);
                        }
                        break;
                    case 'product':
                        $nameStmt = $db->prepare("SELECT name FROM sales_products WHERE id = ?");
                        $nameStmt->execute([$product['product_id']]);
                        $row = $nameStmt->fetch(PDO::FETCH_ASSOC);
                        if ($row) {
                            $product['name'] = $row['name'];
                            $updateStmt = $db->prepare("UPDATE output_products SET name = ? WHERE id = ?");
                            $updateStmt->execute([$row['name'], $product['id']]);
                        }
                        break;
                    case 'membership':
                        $nameStmt = $db->prepare("SELECT name FROM membership_plans WHERE id = ?");
                        $nameStmt->execute([$product['product_id']]);
                        $row = $nameStmt->fetch(PDO::FETCH_ASSOC);
                        if ($row) {
                            $product['name'] = $row['name'];
                            $updateStmt = $db->prepare("UPDATE output_products SET name = ? WHERE id = ?");
                            $updateStmt->execute([$row['name'], $product['id']]);
                        }
                        break;
                    case 'rental':
                        $nameStmt = $db->prepare("SELECT name FROM rental_objects WHERE id = ?");
                        $nameStmt->execute([$product['product_id']]);
                        $row = $nameStmt->fetch(PDO::FETCH_ASSOC);
                        if ($row) {
                            $product['name'] = $row['name'];
                            $updateStmt = $db->prepare("UPDATE output_products SET name = ? WHERE id = ?");
                            $updateStmt->execute([$row['name'], $product['id']]);
                        }
                        break;
                }
            } catch (Exception $e) {
                error_log("Error fetching product name: " . $e->getMessage());
            }
        }
    }
    
    // DEDUPLICATE: Remove duplicate products by (product_type, product_id) - keep the most recent
    $seen = [];
    $uniqueProducts = [];
    foreach ($products as $product) {
        $key = $product['product_type'] . '::' . $product['product_id'];
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $uniqueProducts[] = $product;
        }
    }
    
    // Count by module
    $countByModule = [];
    $totalModules = [];
    
    foreach ($uniqueProducts as $product) {
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
        'products' => $uniqueProducts,
        'count_by_module' => $countByModule,
        'total_products' => count($uniqueProducts),
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
