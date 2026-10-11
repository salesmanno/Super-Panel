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
require_once __DIR__ . '/../includes/rental_weekday_pricing.php';

$admin = [
    'tenant_admin_id' => $_SESSION['tenant_admin_id'],
    'company_id' => $_SESSION['company_id']
];

// Hent input
$data = json_decode(file_get_contents('php://input'), true);
$data = is_array($data) ? $data : [];
try { entityFormRequireCsrf($data); } catch (Throwable $e) {
    http_response_code(403); echo json_encode(['success'=>false,'message'=>$e->getMessage()]); exit;
}

// Valider required fields
if (empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Objekt-ID mangler']);
    exit;
}

try {
    $db = getDBConnection();
    entityFormRequireModuleAccess($db, (string)$admin['company_id'], 'rental_object');
    $db->beginTransaction();
    
    // Verify object exists and belongs to company
    $stmt = $db->prepare("SELECT * FROM rental_objects WHERE id = ? AND company_id = ? FOR UPDATE");
    $stmt->execute([$data['id'], $admin['company_id']]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$existing) {
        $db->rollBack();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Objekt ikke funnet']);
        exit;
    }
    
    // Object type is fixed at creation, including for direct API callers.
    $category = $existing['category'];
    if (array_key_exists('category', $data) && $data['category'] !== $category) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Objekttypen kan ikke endres etter opprettelse.']);
        exit;
    }
    if (!$category || !getRentalSchema($category)) {
        $db->rollBack();
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ugyldig objektkategori']);
        exit;
    }
    
    if (array_key_exists('type_data', $data) && !is_array($data['type_data'])) {
        throw new InvalidArgumentException('Objektets typedata er ugyldig');
    }

    /*
     * Persist details for the fixed object category in the same transaction
     * as the base edit, so a failure cannot leave a half-edit.
     */
    $hasTypeData = array_key_exists('type_data', $data);
    if ($hasTypeData) {
        $schema = getRentalSchema($category);
        $typeTable = $schema['table'];
        $typeData = $hasTypeData ? $data['type_data'] : [];
        $typeFields = [];
        $typeParams = [];

        foreach ($typeData as $field => $value) {
            if (!array_key_exists($field, $schema['fields'])) {
                continue;
            }
            if ($field === 'access_code') {
                $value = trim((string) $value);
                if ($value === '0000') {
                    throw new InvalidArgumentException('Tilgangskoden 0000 kan ikke brukes');
                }
                if ($value !== '' && !preg_match('/^[A-Za-z0-9]{1,6}$/', $value)) {
                    throw new InvalidArgumentException('Tilgangskoden kan bare inneholde 1–6 bokstaver eller tall');
                }
                $value = $value === '' ? null : $value;
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
    
    $allowedFields = ['object_name', 'serial_number', 'description',
                      'rental_price', 'deposit_amount', 'condition_notes', 'image_url',
                      'is_available', 'is_active', 'market_price', 'rental_text'];
    
    foreach ($allowedFields as $field) {
        if ($field === 'market_price' && $category === 'venue') continue;
        if (array_key_exists($field, $data)) {
            $updateFields[] = "$field = ?";
            $updateParams[] = $field === 'rental_text'
                ? ($data[$field] === '' ? null : mb_substr((string) $data[$field], 0, 1000))
                : $data[$field];
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
    if (array_key_exists('weekday_prices', $data)) {
        $weekdayJson = rentalWeekdayPriceWrite($db, $data['weekday_prices']);
        if (rentalObjectColumnExists($db, 'weekday_prices')) {
            $updateFields[] = 'weekday_prices = ?';
            $updateParams[] = $weekdayJson;
        }
    }
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
    
    $customerFields = null;
    if (array_key_exists('form_fields', $data)) {
        if (!is_array($data['form_fields'])) throw new InvalidArgumentException('Ugyldig feltoppsett');
        $customerFields = entityFormSave($db, (string)$admin['company_id'], 'rental_object', (string)$data['id'], $data['form_fields']);
    } elseif (array_key_exists('customer_fields', $data) || array_key_exists('customer_field_settings', $data)) {
        $customerInput = $data['customer_fields'] ?? $data['customer_field_settings'];
        $customerFields = saveRentalCustomerFieldSettings(
            $db,
            $admin['company_id'],
            $data['id'],
            $customerInput
        );
    }
    
    $db->commit();
    
    $response = [
        'success' => true,
        'message' => 'Utleieobjekt oppdatert'
    ];
    if ($customerFields !== null) $response['customer_fields'] = $customerFields;
    echo json_encode($response);
    
} catch (TenantAuditException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('update_rental_object validation error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e instanceof RentalWeekdayPriceException ? $e->getMessage() : 'Ugyldige objektdata'
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
