<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['service_name'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler tjenestenavn']);
        exit;
    }
    
    if (empty($data['duration'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler varighet']);
        exit;
    }
    
    if (empty($data['assigned_staff']) || !is_array($data['assigned_staff'])) {
        echo json_encode(['success' => false, 'message' => 'Velg minst én ansatt']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    
    try {
        // Generate IDs
        $serviceId = bin2hex(random_bytes(16));
        $outputProductId = bin2hex(random_bytes(16));
        
        // Convert assigned staff array to JSON
        $assignedStaffJson = json_encode($data['assigned_staff']);
        
        // Get reservations module_id (UUID from system_modules)
        $moduleStmt = $db->prepare("SELECT id FROM system_modules WHERE module_key = 'reservations' LIMIT 1");
        $moduleStmt->execute();
        $reservationsModuleId = $moduleStmt->fetchColumn();
        
        if (!$reservationsModuleId) {
            throw new Exception("Reservations module not found in system_modules");
        }
        
        // FIRST: Insert into output_products (parent table)
        $stmt = $db->prepare("
            INSERT INTO output_products (
                id, company_id, module_id, product_type, product_id, name, description, price,
                is_visible, is_public, is_marketplace_featured,
                created_at, created_by
            ) VALUES (?, ?, ?, 'service', ?, ?, ?, ?, 1, 1, 0, NOW(), ?)
        ");
        
        $stmt->execute([
            $outputProductId,
            $_SESSION['company_id'],
            $reservationsModuleId,
            $serviceId,
            $data['service_name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $_SESSION['tenant_admin_id']
        ]);
        
        // THEN: Insert reservation service (child table with foreign key)
        $stmt = $db->prepare("
            INSERT INTO reservation_services (
                id, company_id, output_product_id, service_name, description,
                price, duration_minutes, service_type, available_from, available_to,
                assigned_staff_ids, capacity, is_active, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $serviceId,
            $_SESSION['company_id'],
            $outputProductId,
            $data['service_name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $data['duration'],
            $data['service_type'] ?? 'other',
            $data['available_from'] ?? '09:00',
            $data['available_to'] ?? '17:00',
            $assignedStaffJson,
            $data['capacity'] ?? 1,
            $data['is_active'] ?? 1
        ]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Tjeneste opprettet',
            'service_id' => $serviceId,
            'output_product_id' => $outputProductId
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error creating reservation service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
