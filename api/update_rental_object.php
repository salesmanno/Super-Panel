<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/rental_schemas.php';
require_once __DIR__ . '/../includes/rental_customer_fields.php';

$admin = [
    'tenant_admin_id' => $_SESSION['tenant_admin_id'],
    'company_id' => $_SESSION['company_id']
];

// Hent input
$data = json_decode(file_get_contents('php://input'), true);
$data = is_array($data) ? $data : [];

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
    $stmt = $db->prepare("SELECT * FROM rental_objects WHERE id = ? AND company_id = ? FOR UPDATE");
    $stmt->execute([$data['id'], $admin['company_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Objekt ikke funnet']);
        exit;
    }
    
    // Use existing category if not provided in update
    $category = $data['category'] ?? $existing['category'];
    if (!$category || !getRentalSchema($category)) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ugyldig objektkategori']);
        exit;
    }
    
    $oldCategory = $existing['category'] ?? null;
    $categoryChanged = $oldCategory !== $category;
    if (array_key_exists('type_data', $data) && !is_array($data['type_data'])) {
        throw new InvalidArgumentException('Objektets typedata er ugyldig');
    }

    /*
     * Upsert the new category row before changing the base category. Both
     * writes are in this transaction; a missing type table or invalid field
     * therefore rolls back the complete edit instead of leaving a half-edit.
     */
    $hasTypeData = array_key_exists('type_data', $data);
    if ($categoryChanged || $hasTypeData) {
        $schema = getRentalSchema($category);
        $typeTable = $schema['table'];
        $typeData = $hasTypeData ? $data['type_data'] : [];
        $typeFields = [];
        $typeParams = [];

        foreach ($typeData as $field => $value) {
            if (!array_key_exists($field, $schema['fields'])) {
                continue;
            }
            $typeFields[] = $field;
            if ($schema['fields'][$field]['type'] === 'checkbox') {
                $typeParams[] = ($value && $value !== '0' && $value !== 0) ? 1 : 0;
            } else {
                $typeParams[] = $value !== '' ? $value : null;
            }
        }
        if ($typeData && !$typeFields) {
            throw new InvalidArgumentException('Ingen gyldige felt ble sendt for objektkategorien');
        }

        $checkStmt = $db->prepare("SELECT object_id FROM $typeTable WHERE object_id = ?");
        $checkStmt->execute([$data['id']]);
        $typeExists = (bool) $checkStmt->fetchColumn();

        if ($typeExists) {
            if ($typeFields) {
                $typeParams[] = $data['id'];
                $db->prepare(
                    "UPDATE $typeTable SET " . implode(', ', array_map(
                        static fn (string $field): string => "$field = ?",
                        $typeFields
                    )) . " WHERE object_id = ?"
                )->execute($typeParams);
            }
        } else {
            $insertFields = array_merge(['object_id'], $typeFields);
            $insertValues = array_fill(0, count($insertFields), '?');
            array_unshift($typeParams, $data['id']);
            $db->prepare(
                "INSERT INTO $typeTable (" . implode(', ', $insertFields) . ")
                 VALUES (" . implode(', ', $insertValues) . ")"
            )->execute($typeParams);
        }
    }

    // UPDATE base rental_objects table
    $updateFields = [];
    $updateParams = [];
    
    $allowedFields = ['object_name', 'serial_number', 'description', 'category', 
                      'rental_price', 'deposit_amount', 'condition_notes', 'image_url',
                      'is_available', 'is_active', 'market_price'];
    
    foreach ($allowedFields as $field) {
        if (array_key_exists($field, $data)) {
            $updateFields[] = "$field = ?";
            $updateParams[] = $data[$field];
        }
    }
    
    if (isset($data['images'])) {
        $imagesArray = is_array($data['images']) ? $data['images'] : json_decode($data['images'], true);
        $updateFields[] = "images = ?";
        $updateParams[] = is_array($imagesArray) ? json_encode($imagesArray) : '[]';
        
        if (is_array($imagesArray) && count($imagesArray) > 0) {
            $primaryUrl = null;
            foreach ($imagesArray as $img) {
                if (!empty($img['is_primary']) && !empty($img['url'])) {
                    $primaryUrl = $img['url'];
                    break;
                }
            }
            if (!$primaryUrl && !empty($imagesArray[0]['url'])) {
                $primaryUrl = $imagesArray[0]['url'];
            }
            if ($primaryUrl) {
                $updateFields[] = "image_url = ?";
                $updateParams[] = $primaryUrl;
            }
        }
    }
    
    if (isset($data['rental_periods'])) {
        $updateFields[] = "rental_periods = ?";
        $updateParams[] = is_array($data['rental_periods']) ? json_encode($data['rental_periods']) : $data['rental_periods'];
    }
    
    // Optional price columns vary between older installations. Explicit
    // null/zero values must still be persisted when supplied.
    $optionalPriceCols = ['weekend_price', 'saturday_price', 'sunday_price'];
    foreach ($optionalPriceCols as $col) {
        if (array_key_exists($col, $data) && rentalObjectColumnExists($db, $col)) {
            $updateFields[] = "$col = ?";
            $updateParams[] = $data[$col] === null || $data[$col] === '' ? null : (float) $data[$col];
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
    
    if ($categoryChanged && $oldCategory && getRentalSchema($oldCategory)) {
        $oldTypeTable = getRentalSchema($oldCategory)['table'];
        $db->prepare("DELETE FROM $oldTypeTable WHERE object_id = ?")->execute([$data['id']]);
    }

    if (array_key_exists('customer_fields', $data) || array_key_exists('customer_field_settings', $data)) {
        $customerInput = $data['customer_fields'] ?? $data['customer_field_settings'];
        $customerFields = saveRentalCustomerFieldSettings(
            $db,
            $admin['company_id'],
            $data['id'],
            $customerInput
        );
    } else {
        $customerFields = getRentalCustomerFieldSettings($db, $admin['company_id'], $data['id']);
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Utleieobjekt oppdatert',
        'customer_fields' => $customerFields
    ]);
    
} catch (InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('update_rental_object validation error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Ugyldige objektdata'
    ]);
} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('update_rental_object error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering av utleieobjekt'
    ]);
}
