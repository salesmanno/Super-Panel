<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $dbType = getDBType();
    
    // Get all rental objects for company
    $query = "
        SELECT 
            ro.*,
            op.id as output_product_id,
            op.is_visible,
            op.is_public
        FROM rental_objects ro
        LEFT JOIN output_products op ON op.product_id = ro.object_id 
            AND op.product_type = 'rental' 
            AND op.company_id = ro.company_id
        WHERE ro.company_id = ?
        ORDER BY ro.created_at DESC
    ";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['company_id']]);
    $objects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format data for frontend
    $formatted = array_map(function($obj) {
        return [
            'id' => $obj['object_id'],
            'name' => $obj['object_name'],
            'type' => $obj['object_type'],
            'category' => $obj['category'],
            'description' => $obj['description'],
            'daily_price' => (float)$obj['daily_price'],
            'is_available' => (bool)$obj['is_available'],
            'requires_deposit' => (bool)$obj['requires_deposit'],
            'deposit_amount' => $obj['deposit_amount'] ? (float)$obj['deposit_amount'] : 0,
            'location' => $obj['location'],
            'serial_number' => $obj['serial_number'],
            'condition_notes' => $obj['condition_notes'],
            'is_visible' => isset($obj['is_visible']) ? (bool)$obj['is_visible'] : false,
            'is_public' => isset($obj['is_public']) ? (bool)$obj['is_public'] : false,
            'created_at' => $obj['created_at']
        ];
    }, $objects);
    
    echo json_encode([
        'success' => true,
        'objects' => $formatted
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching rental objects: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av data: ' . $e->getMessage(),
        'objects' => []
    ]);
}
