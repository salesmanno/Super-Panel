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

// Hent input
$data = json_decode(file_get_contents('php://input'), true);

// Valider required base fields
if (empty($data['object_name']) || empty($data['category'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Objektnavn og kategori er påkrevd']);
    exit;
}

// Valider kategori
$schema = getRentalSchema($data['category']);
if (!$schema) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig objektkategori']);
    exit;
}

try {
    $db = getDBConnection();
    $db->beginTransaction();
    
    $object_id = generateUUID();
    
    // INSERT into base rental_objects table
    $stmt = $db->prepare("
        INSERT INTO rental_objects (
            id, company_id, output_product_id, object_name, serial_number, description,
            category, rental_price, deposit_amount, condition_notes, image_url,
            is_available, is_active, created_by
        ) VALUES (
            :id, :company_id, :output_product_id, :object_name, :serial_number, :description,
            :category, :rental_price, :deposit_amount, :condition_notes, :image_url,
            :is_available, :is_active, :created_by
        )
    ");
    
    $stmt->execute([
        ':id' => $object_id,
        ':company_id' => $admin['company_id'],
        ':output_product_id' => $data['output_product_id'] ?? null,
        ':object_name' => $data['object_name'],
        ':serial_number' => $data['serial_number'] ?? null,
        ':description' => $data['description'] ?? null,
        ':category' => $data['category'],
        ':rental_price' => $data['rental_price'] ?? 0,
        ':deposit_amount' => $data['deposit_amount'] ?? 0,
        ':condition_notes' => $data['condition_notes'] ?? null,
        ':image_url' => $data['image_url'] ?? null,
        ':is_available' => $data['is_available'] ?? 1,
        ':is_active' => $data['is_active'] ?? 1,
        ':created_by' => $admin['tenant_admin_id']
    ]);
    
    // INSERT into type-specific table
    if (!empty($data['type_data']) && is_array($data['type_data'])) {
        $typeTable = $schema['table'];
        $typeFields = ['object_id'];
        $typeValues = [':object_id'];
        $typeParams = [':object_id' => $object_id];
        
        foreach ($data['type_data'] as $field => $value) {
            if (isset($schema['fields'][$field])) {
                $typeFields[] = $field;
                $typeValues[] = ":$field";
                
                // Handle checkbox (boolean) values
                if ($schema['fields'][$field]['type'] === 'checkbox') {
                    $typeParams[":$field"] = $value ? 1 : 0;
                } else {
                    $typeParams[":$field"] = $value !== '' ? $value : null;
                }
            }
        }
        
        if (count($typeFields) > 1) { // More than just object_id
            $sql = "INSERT INTO $typeTable (" . implode(', ', $typeFields) . ") 
                    VALUES (" . implode(', ', $typeValues) . ")";
            
            $typeStmt = $db->prepare($sql);
            $typeStmt->execute($typeParams);
        }
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Utleieobjekt opprettet',
        'object_id' => $object_id
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse av utleieobjekt',
        'error' => $e->getMessage()
    ]);
}
