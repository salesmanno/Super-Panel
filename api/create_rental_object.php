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

function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

try {
    $db = getDBConnection();
    $db->beginTransaction();
    
    $object_id = generateUUID();
    
    // INSERT into base rental_objects table
    // Columns match actual production table: rental_objects
    $imagesJson = null;
    $primaryImageUrl = $data['image_url'] ?? null;
    if (!empty($data['images']) && is_array($data['images'])) {
        $imagesJson = json_encode($data['images']);
        foreach ($data['images'] as $img) {
            if (!empty($img['is_primary']) && !empty($img['url'])) {
                $primaryImageUrl = $img['url'];
                break;
            }
        }
        if (!$primaryImageUrl && !empty($data['images'][0]['url'])) {
            $primaryImageUrl = $data['images'][0]['url'];
        }
    }
    
    $columns = ['id', 'company_id', 'output_product_id', 'object_name', 'serial_number', 
                'description', 'category', 'rental_price', 'deposit_amount', 
                'condition_notes', 'image_url', 'images', 'is_available', 'is_active', 'created_by',
                'market_price', 'rental_periods'];
    $params = [
        $object_id,
        $admin['company_id'],
        $data['output_product_id'] ?? null,
        $data['object_name'],
        $data['serial_number'] ?? null,
        $data['description'] ?? null,
        $data['category'],
        array_key_exists('rental_price', $data) ? $data['rental_price'] : 0,
        array_key_exists('deposit_amount', $data) ? $data['deposit_amount'] : 0,
        $data['condition_notes'] ?? null,
        $primaryImageUrl,
        $imagesJson,
        $data['is_available'] ?? 1,
        $data['is_active'] ?? 1,
        $admin['tenant_admin_id'],
        $data['market_price'] ?? null,
        isset($data['rental_periods']) ? (is_array($data['rental_periods']) ? json_encode($data['rental_periods']) : $data['rental_periods']) : null
    ];
    
    // Optional price columns vary between older installations. Include
    // explicit null/zero values as well; zero is a meaningful price.
    $optionalPriceCols = ['weekend_price', 'saturday_price', 'sunday_price'];
    if (array_key_exists('weekday_prices', $data)) {
        $weekdayJson = rentalWeekdayPriceWrite($db, $data['weekday_prices']);
        if (rentalObjectColumnExists($db, 'weekday_prices')) {
            $columns[] = 'weekday_prices';
            $params[] = $weekdayJson;
        }
    }
    foreach ($optionalPriceCols as $col) {
        if (array_key_exists($col, $data) && rentalObjectColumnExists($db, $col)) {
            $columns[] = $col;
            $params[] = $data[$col] === null || $data[$col] === '' ? null : (float) $data[$col];
        }
    }
    if (array_key_exists('rental_text', $data) && rentalObjectColumnExists($db, 'rental_text')) {
        $columns[] = 'rental_text';
        $params[] = $data['rental_text'] === '' ? null : mb_substr((string) $data['rental_text'], 0, 1000);
    }
    
    $placeholders = implode(',', array_fill(0, count($columns), '?'));
    $columnList = implode(', ', $columns);
    
    $stmt = $db->prepare("INSERT INTO rental_objects ($columnList) VALUES ($placeholders)");
    $stmt->execute($params);
    
    // The base row and its category row are one atomic operation. Do not
    // silently create a base object when the category migration is missing.
    if (array_key_exists('type_data', $data) && !is_array($data['type_data'])) {
        throw new InvalidArgumentException('Objektets typedata er ugyldig');
    }
    $typeTable = $schema['table'];
    $typeFields = ['object_id'];
    $typeValues = ['?'];
    $typeParams = [$object_id];
    $typeData = $data['type_data'] ?? [];

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
        $typeValues[] = '?';

        if ($schema['fields'][$field]['type'] === 'checkbox') {
            $typeParams[] = ($value && $value !== '0' && $value !== 0) ? 1 : 0;
        } else {
            $typeParams[] = $value !== '' ? $value : null;
        }
    }
    if ($typeData && count($typeFields) === 1) {
        throw new InvalidArgumentException('Ingen gyldige felt ble sendt for objektkategorien');
    }

    $typeStmt = $db->prepare(
        "INSERT INTO $typeTable (" . implode(', ', $typeFields) . ")
         VALUES (" . implode(', ', $typeValues) . ")"
    );
    $typeStmt->execute($typeParams);

    $customerFieldInput = $data['customer_fields'] ?? $data['customer_field_settings'] ?? null;
    $customerFields = isset($data['form_fields'])
        ? entityFormSave($db, (string)$admin['company_id'], 'rental_object', $object_id, $data['form_fields'])
        : saveRentalCustomerFieldSettings(
        $db,
        $admin['company_id'],
        $object_id,
        $customerFieldInput === null ? defaultRentalCustomerFieldSettings() : $customerFieldInput
    );
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Utleieobjekt opprettet',
        'object_id' => $object_id,
        'customer_fields' => $customerFields
    ]);
    
} catch (InvalidArgumentException $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('create_rental_object validation error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e instanceof RentalWeekdayPriceException ? $e->getMessage() : 'Ugyldige objektdata'
    ]);
} catch (\Throwable $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('create_rental_object error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse av utleieobjekt'
    ]);
}
