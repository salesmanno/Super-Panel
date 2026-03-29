<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/rental_schemas.php';

$admin = [
    'tenant_admin_id' => $_SESSION['tenant_admin_id'],
    'company_id' => $_SESSION['company_id']
];

try {
    $db = getDBConnection();
    
    // Get specific object by ID or all objects
    $object_id = $_GET['id'] ?? null;
    
    if ($object_id) {
        // Get single object with type-specific data
        $stmt = $db->prepare("
            SELECT * FROM rental_objects 
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $object_id,
            $admin['company_id']
        ]);
        $object = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$object) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Objekt ikke funnet']);
            exit;
        }
        
        if (!empty($object['images']) && is_string($object['images'])) {
            $decoded = json_decode($object['images'], true);
            $object['images'] = is_array($decoded) ? $decoded : [];
        } else {
            $object['images'] = [];
        }
        
        // Fetch type-specific data (graceful if table doesn't exist)
        if ($object['category']) {
            $schema = getRentalSchema($object['category']);
            if ($schema) {
                $object['type_label'] = $schema['label'];
                try {
                    $typeStmt = $db->prepare("SELECT * FROM {$schema['table']} WHERE object_id = ?");
                    $typeStmt->execute([$object_id]);
                    $typeData = $typeStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($typeData) {
                        unset($typeData['object_id']);
                        $object['type_data'] = $typeData;
                    }
                } catch (Exception $e) {
                    // Type-specific table may not exist yet - skip gracefully
                }
            }
        }
        
        echo json_encode(['success' => true, 'object' => $object]);
        
    } else {
        // Get all objects for company
        $stmt = $db->prepare("
            SELECT ro.*,
                   CASE
                       WHEN ro.is_available = 1 AND ro.is_active = 1 THEN 'available'
                       WHEN ro.is_available = 0 AND EXISTS (
                           SELECT 1 FROM rental_contracts rc2
                           WHERE rc2.rental_object_id = ro.id AND rc2.company_id = ro.company_id AND rc2.status = 'active'
                       ) THEN 'rented'
                       ELSE 'inactive'
                   END AS display_status
            FROM rental_objects ro
            WHERE ro.company_id = ?
            ORDER BY ro.created_at DESC
        ");
        $stmt->execute([$admin['company_id']]);
        $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($objects as &$object) {
            if (!empty($object['images']) && is_string($object['images'])) {
                $decoded = json_decode($object['images'], true);
                $object['images'] = is_array($decoded) ? $decoded : [];
            } else {
                $object['images'] = [];
            }
        }
        
        // Fetch type-specific data for each object (graceful if table doesn't exist)
        foreach ($objects as &$object) {
            if ($object['category']) {
                $schema = getRentalSchema($object['category']);
                if ($schema) {
                    $object['type_label'] = $schema['label'];
                    
                    try {
                        $typeStmt = $db->prepare("SELECT * FROM {$schema['table']} WHERE object_id = ?");
                        $typeStmt->execute([$object['id']]);
                        $typeData = $typeStmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($typeData) {
                            unset($typeData['object_id']);
                            $object['type_data'] = $typeData;
                            $object['badges'] = createTypeBadges($object['category'], $typeData);
                        }
                    } catch (Exception $e) {
                        // Type-specific table may not exist yet - skip gracefully
                    }
                }
            }
        }
        
        echo json_encode(['success' => true, 'objects' => $objects]);
    }
    
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av utleieobjekter',
        'error' => $e->getMessage()
    ]);
}

/**
 * Create summary badges for list view based on object type
 */
function createTypeBadges($category, $typeData) {
    $badges = [];
    
    switch ($category) {
        case 'residence':
        case 'cabin':
        case 'office':
            if (!empty($typeData['sqm'])) $badges[] = $typeData['sqm'] . 'm²';
            if (!empty($typeData['bedrooms'])) $badges[] = $typeData['bedrooms'] . ' soverom';
            if (!empty($typeData['bathrooms'])) $badges[] = $typeData['bathrooms'] . ' bad';
            break;
            
        case 'venue':
            if (!empty($typeData['capacity_seated'])) $badges[] = $typeData['capacity_seated'] . ' sitteplasser';
            if (!empty($typeData['sqm'])) $badges[] = $typeData['sqm'] . 'm²';
            break;
            
        case 'container':
            if (!empty($typeData['container_type'])) $badges[] = $typeData['container_type'];
            if (!empty($typeData['cubic_meters'])) $badges[] = $typeData['cubic_meters'] . 'm³';
            break;
            
        case 'vehicle':
        case 'rv':
            if (!empty($typeData['make'])) $badges[] = $typeData['make'];
            if (!empty($typeData['model'])) $badges[] = $typeData['model'];
            if (!empty($typeData['year'])) $badges[] = $typeData['year'];
            break;
            
        case 'trailer':
            if (!empty($typeData['trailer_type'])) $badges[] = $typeData['trailer_type'];
            if (!empty($typeData['load_capacity_kg'])) $badges[] = $typeData['load_capacity_kg'] . 'kg';
            break;
            
        case 'tool':
        case 'sports_equipment':
            if (!empty($typeData['brand'])) $badges[] = $typeData['brand'];
            if (!empty($typeData['model'])) $badges[] = $typeData['model'];
            break;
    }
    
    return $badges;
}
