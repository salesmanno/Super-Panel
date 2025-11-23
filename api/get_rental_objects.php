<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/rental_schemas.php';

header('Content-Type: application/json');

// Autentiser bruker
$admin = authenticateTenantAdmin();
if (!$admin) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

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
        
        // Fetch type-specific data
        if ($object['category']) {
            $schema = getRentalSchema($object['category']);
            if ($schema) {
                $typeStmt = $db->prepare("SELECT * FROM {$schema['table']} WHERE object_id = ?");
                $typeStmt->execute([$object_id]);
                $typeData = $typeStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($typeData) {
                    unset($typeData['object_id']); // Remove FK
                    $object['type_data'] = $typeData;
                    $object['type_label'] = $schema['label'];
                }
            }
        }
        
        echo json_encode(['success' => true, 'object' => $object]);
        
    } else {
        // Get all objects for company
        $stmt = $db->prepare("
            SELECT ro.*, 
                   CASE WHEN ro.is_available = ? AND ro.is_active = ? THEN 'available'
                        WHEN ro.is_available = ? THEN 'rented'
                        ELSE 'inactive'
                   END as display_status
            FROM rental_objects ro
            WHERE ro.company_id = ?
            ORDER BY ro.created_at DESC
        ");
        $stmt->execute([1, 1, 0, $admin['company_id']]);
        $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch type-specific data for each object
        foreach ($objects as &$object) {
            if ($object['category']) {
                $schema = getRentalSchema($object['category']);
                if ($schema) {
                    $object['type_label'] = $schema['label'];
                    
                    // Get type-specific summary data (key fields only)
                    $typeStmt = $db->prepare("SELECT * FROM {$schema['table']} WHERE object_id = ?");
                    $typeStmt->execute([$object['id']]);
                    $typeData = $typeStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($typeData) {
                        unset($typeData['object_id']);
                        $object['type_data'] = $typeData;
                        
                        // Create summary badges based on type
                        $object['badges'] = createTypeBadges($object['category'], $typeData);
                    }
                }
            }
        }
        
        echo json_encode(['success' => true, 'objects' => $objects]);
    }
    
} catch (Exception $e) {
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
