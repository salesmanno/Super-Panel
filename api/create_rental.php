<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ProductCatalogService.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['object_name'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler objektnavn']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    
    try {
        // 1. Create rental object
        $stmt = $db->prepare("
            INSERT INTO rental_objects (
                company_id,
                object_name,
                object_type,
                category,
                description,
                daily_price,
                is_available,
                requires_deposit,
                deposit_amount,
                location,
                serial_number,
                condition_notes
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_SESSION['company_id'],
            $data['object_name'],
            $data['object_type'] ?? null,
            $data['category'] ?? null,
            $data['description'] ?? null,
            $data['daily_price'] ?? 0,
            $data['is_available'] ?? true,
            $data['requires_deposit'] ?? false,
            $data['deposit_amount'] ?? null,
            $data['location'] ?? null,
            $data['serial_number'] ?? null,
            $data['condition_notes'] ?? null
        ]);
        
        // Get last insert ID (works in both MySQL and PostgreSQL)
        $rentalId = $db->lastInsertId();
        
        // 2. Auto-generate output_products entry
        $catalogService = new ProductCatalogService($db);
        $outputProductId = $catalogService->createProductEntry(
            $_SESSION['company_id'],
            'rental',
            $rentalId,
            [
                'is_visible' => true,
                'is_public' => $data['is_public'] ?? true,
                'is_marketplace_featured' => $data['is_marketplace_featured'] ?? false
            ],
            $_SESSION['tenant_admin_id']
        );
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Utleieobjekt opprettet',
            'rental_id' => $rentalId,
            'output_product_id' => $outputProductId
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error creating rental: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
