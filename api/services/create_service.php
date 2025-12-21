<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['name'])) {
        echo json_encode(['success' => false, 'message' => 'Tjenestenavn er påkrevd']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $adminId = $_SESSION['tenant_admin_id'];
    
    $db->beginTransaction();
    
    try {
        $serviceId = bin2hex(random_bytes(16));
        $outputProductId = bin2hex(random_bytes(16));
        
        $moduleStmt = $db->prepare("SELECT id FROM system_modules WHERE module_key = 'reservations' LIMIT 1");
        $moduleStmt->execute();
        $moduleRow = $moduleStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$moduleRow) {
            $dbType = getDBType();
            if ($dbType === 'mysql') {
                $insertModuleStmt = $db->prepare("
                    INSERT INTO system_modules (id, module_key, name, description, icon, is_active, created_at) 
                    VALUES (UUID(), 'reservations', 'Reservasjoner', 'Reservasjonssystem', 'calendar-check', 1, NOW())
                ");
            } else {
                $insertModuleStmt = $db->prepare("
                    INSERT INTO system_modules (module_key, name, description, icon, is_active, created_at) 
                    VALUES ('reservations', 'Reservasjoner', 'Reservasjonssystem', 'calendar-check', true, NOW())
                ");
            }
            $insertModuleStmt->execute();
            $moduleStmt->execute();
            $moduleRow = $moduleStmt->fetch(PDO::FETCH_ASSOC);
        }
        
        $reservationsModuleId = (string)$moduleRow['id'];
        
        $opStmt = $db->prepare("
            INSERT INTO output_products (
                id, company_id, module_id, product_type, product_id,
                name, description,
                is_visible, is_public, is_marketplace_featured, discount_percent,
                created_at, created_by
            ) VALUES (?, ?, ?, 'service', ?, ?, ?, 1, 1, 0, 0, NOW(), ?)
        ");
        $opStmt->execute([
            $outputProductId,
            $companyId,
            $reservationsModuleId,
            $serviceId,
            $data['name'],
            $data['description'] ?? null,
            $adminId
        ]);
        
        $stmt = $db->prepare("
            INSERT INTO reservation_services (
                id, company_id, output_product_id, service_name, description, price, duration_minutes,
                service_type, available_from, available_to, is_active, capacity, img_url
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $serviceId,
            $companyId,
            $outputProductId,
            $data['name'],
            $data['description'] ?? null,
            $data['price'] ?? 0,
            $data['duration_minutes'] ?? 60,
            $data['category'] ?? 'other',
            $data['available_from'] ?? '09:00:00',
            $data['available_to'] ?? '17:00:00',
            isset($data['is_active']) ? ($data['is_active'] ? 1 : 0) : 1,
            $data['capacity'] ?? 1,
            $data['img_url'] ?? null
        ]);
    
        if (!empty($data['staff_ids']) && is_array($data['staff_ids'])) {
            $primaryStaffId = $data['primary_staff_id'] ?? null;
            $verifyStmt = $db->prepare("SELECT id FROM tenant_staff WHERE id = ? AND company_id = ?");
            $staffStmt = $db->prepare("
                INSERT INTO staff_services (company_id, staff_id, service_id, is_active, worker_primary)
                VALUES (?, ?, ?, 1, ?)
            ");
            foreach ($data['staff_ids'] as $staffId) {
                $verifyStmt->execute([$staffId, $companyId]);
                if ($verifyStmt->fetch()) {
                    $isPrimary = ($staffId == $primaryStaffId) ? 1 : 0;
                    $staffStmt->execute([$companyId, $staffId, $serviceId, $isPrimary]);
                }
            }
        }
        
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
    error_log("Error creating service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse av tjeneste: ' . $e->getMessage()
    ]);
}
