<?php
session_start();
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

// Hent input
$data = json_decode(file_get_contents('php://input'), true);

// Valider required fields
if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Objekt-ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    $db->beginTransaction();
    
    // Verify object exists and belongs to company
    $stmt = $db->prepare("SELECT * FROM rental_objects WHERE id = ? AND company_id = ?");
    $stmt->execute([$data['id'], $admin['company_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Objekt ikke funnet']);
        exit;
    }
    
    // Use existing category if not provided in update
    $category = $data['category'] ?? $existing['category'];
    
    // UPDATE base rental_objects table
    $updateFields = [];
    $updateParams = [];
    
    $allowedFields = ['object_name', 'serial_number', 'description', 'category', 
                      'rental_price', 'deposit_amount', 'condition_notes', 'image_url',
                      'is_available', 'is_active', 'market_price'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = ?";
            $updateParams[] = $data[$field];
        }
    }
    
    // Handle rental_periods (JSON encode if array)
    if (isset($data['rental_periods'])) {
        $updateFields[] = "rental_periods = ?";
        $updateParams[] = is_array($data['rental_periods']) ? json_encode($data['rental_periods']) : $data['rental_periods'];
    }
    
    // Only update price columns if non-zero (columns may not exist yet in DB)
    $optionalPriceCols = ['weekend_price', 'saturday_price', 'sunday_price'];
    foreach ($optionalPriceCols as $col) {
        if (!empty($data[$col]) && floatval($data[$col]) > 0) {
            $updateFields[] = "$col = ?";
            $updateParams[] = floatval($data[$col]);
        }
    }
    
    if (!empty($updateFields)) {
        $updateParams[] = $data['id'];
        $updateParams[] = $admin['company_id'];
        $sql = "UPDATE rental_objects SET " . implode(', ', $updateFields) . " 
                WHERE id = ? AND company_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($updateParams);
    }
    
    // UPDATE or INSERT type-specific data (graceful if table doesn't exist yet)
    if (!empty($data['type_data']) && !empty($category)) {
        $schema = getRentalSchema($category);
        
        if ($schema) {
            try {
                $typeTable = $schema['table'];
                
                $checkStmt = $db->prepare("SELECT object_id FROM $typeTable WHERE object_id = ?");
                $checkStmt->execute([$data['id']]);
                $typeExists = $checkStmt->fetch();
                
                if ($typeExists) {
                    $typeUpdateFields = [];
                    $typeParams = [];
                    
                    foreach ($data['type_data'] as $field => $value) {
                        if (isset($schema['fields'][$field])) {
                            $typeUpdateFields[] = "$field = ?";
                            
                            if ($schema['fields'][$field]['type'] === 'checkbox') {
                                $typeParams[] = ($value && $value !== '0' && $value !== 0) ? 1 : 0;
                            } else {
                                $typeParams[] = $value !== '' ? $value : null;
                            }
                        }
                    }
                    
                    if (!empty($typeUpdateFields)) {
                        $typeParams[] = $data['id'];
                        $sql = "UPDATE $typeTable SET " . implode(', ', $typeUpdateFields) . " 
                                WHERE object_id = ?";
                        $typeStmt = $db->prepare($sql);
                        $typeStmt->execute($typeParams);
                    }
                } else {
                    $typeFields = ['object_id'];
                    $typeValues = ['?'];
                    $typeParams = [$data['id']];
                    
                    foreach ($data['type_data'] as $field => $value) {
                        if (isset($schema['fields'][$field])) {
                            $typeFields[] = $field;
                            $typeValues[] = '?';
                            
                            if ($schema['fields'][$field]['type'] === 'checkbox') {
                                $typeParams[] = ($value && $value !== '0' && $value !== 0) ? 1 : 0;
                            } else {
                                $typeParams[] = $value !== '' ? $value : null;
                            }
                        }
                    }
                    
                    $sql = "INSERT INTO $typeTable (" . implode(', ', $typeFields) . ") 
                            VALUES (" . implode(', ', $typeValues) . ")";
                    $typeStmt = $db->prepare($sql);
                    $typeStmt->execute($typeParams);
                }
            } catch (Exception $e) {
                // Type-specific table may not exist yet - base object still updated
            }
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Utleieobjekt oppdatert'
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering av utleieobjekt',
        'error' => $e->getMessage()
    ]);
}
