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
    $stmt = $db->prepare("SELECT * FROM rental_objects WHERE id = :id AND company_id = :company_id");
    $stmt->execute([':id' => $data['id'], ':company_id' => $admin['company_id']]);
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
    $updateParams = [':id' => $data['id'], ':company_id' => $admin['company_id']];
    
    $allowedFields = ['object_name', 'serial_number', 'description', 'category', 
                      'rental_price', 'weekend_price', 'deposit_amount', 'condition_notes', 'image_url',
                      'is_available', 'is_active'];
    
    foreach ($allowedFields as $field) {
        if (isset($data[$field])) {
            $updateFields[] = "$field = :$field";
            $updateParams[":$field"] = $data[$field];
        }
    }
    
    if (!empty($updateFields)) {
        $sql = "UPDATE rental_objects SET " . implode(', ', $updateFields) . " 
                WHERE id = :id AND company_id = :company_id";
        $stmt = $db->prepare($sql);
        $stmt->execute($updateParams);
    }
    
    // UPDATE or INSERT type-specific data
    if (!empty($data['type_data']) && !empty($category)) {
        $schema = getRentalSchema($category);
        
        if ($schema) {
            $typeTable = $schema['table'];
            
            // Check if type data exists
            $checkStmt = $db->prepare("SELECT object_id FROM $typeTable WHERE object_id = :object_id");
            $checkStmt->execute([':object_id' => $data['id']]);
            $typeExists = $checkStmt->fetch();
            
            if ($typeExists) {
                // UPDATE existing type data
                $typeUpdateFields = [];
                $typeParams = [':object_id' => $data['id']];
                
                foreach ($data['type_data'] as $field => $value) {
                    if (isset($schema['fields'][$field])) {
                        $typeUpdateFields[] = "$field = :$field";
                        
                        // Handle checkbox values
                        if ($schema['fields'][$field]['type'] === 'checkbox') {
                            $typeParams[":$field"] = $value ? 1 : 0;
                        } else {
                            $typeParams[":$field"] = $value !== '' ? $value : null;
                        }
                    }
                }
                
                if (!empty($typeUpdateFields)) {
                    $sql = "UPDATE $typeTable SET " . implode(', ', $typeUpdateFields) . " 
                            WHERE object_id = :object_id";
                    $typeStmt = $db->prepare($sql);
                    $typeStmt->execute($typeParams);
                }
            } else {
                // INSERT new type data
                $typeFields = ['object_id'];
                $typeValues = [':object_id'];
                $typeParams = [':object_id' => $data['id']];
                
                foreach ($data['type_data'] as $field => $value) {
                    if (isset($schema['fields'][$field])) {
                        $typeFields[] = $field;
                        $typeValues[] = ":$field";
                        
                        if ($schema['fields'][$field]['type'] === 'checkbox') {
                            $typeParams[":$field"] = $value ? 1 : 0;
                        } else {
                            $typeParams[":$field"] = $value !== '' ? $value : null;
                        }
                    }
                }
                
                $sql = "INSERT INTO $typeTable (" . implode(', ', $typeFields) . ") 
                        VALUES (" . implode(', ', $typeValues) . ")";
                $typeStmt = $db->prepare($sql);
                $typeStmt->execute($typeParams);
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
