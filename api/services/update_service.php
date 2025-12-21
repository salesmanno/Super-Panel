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
    
    if (empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'Tjeneste-ID mangler']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    
    $updates = [];
    $params = [];
    
    $fieldMap = [
        'name' => 'service_name',
        'description' => 'description',
        'price' => 'price',
        'duration_minutes' => 'duration_minutes',
        'category' => 'service_type',
        'available_from' => 'available_from',
        'available_to' => 'available_to',
        'capacity' => 'capacity',
        'img_url' => 'img_url'
    ];
    
    foreach ($fieldMap as $input => $column) {
        if (array_key_exists($input, $data)) {
            $updates[] = "$column = ?";
            $params[] = $data[$input];
        }
    }
    
    if (array_key_exists('is_active', $data)) {
        $updates[] = "is_active = ?";
        $params[] = $data['is_active'] ? 1 : 0;
    }
    
    if (!empty($updates)) {
        $params[] = $data['id'];
        $params[] = $companyId;
        
        $stmt = $db->prepare("
            UPDATE reservation_services 
            SET " . implode(', ', $updates) . "
            WHERE id = ? AND company_id = ?
        ");
        
        $stmt->execute($params);
        
        // Also update output_products if name or description changed
        if (array_key_exists('name', $data) || array_key_exists('description', $data)) {
            $opUpdates = [];
            $opParams = [];
            
            if (array_key_exists('name', $data)) {
                $opUpdates[] = "name = ?";
                $opParams[] = $data['name'];
            }
            if (array_key_exists('description', $data)) {
                $opUpdates[] = "description = ?";
                $opParams[] = $data['description'];
            }
            
            if (!empty($opUpdates)) {
                $opParams[] = $data['id'];
                $opParams[] = $companyId;
                
                $opStmt = $db->prepare("
                    UPDATE output_products 
                    SET " . implode(', ', $opUpdates) . "
                    WHERE product_id = ? AND product_type = 'service' AND company_id = ?
                ");
                $opStmt->execute($opParams);
            }
        }
    }
    
    if (array_key_exists('staff_ids', $data) && is_array($data['staff_ids'])) {
        $deactivateStmt = $db->prepare("
            UPDATE staff_services SET is_active = 0 
            WHERE service_id = ? AND company_id = ?
        ");
        $deactivateStmt->execute([$data['id'], $companyId]);
        
        if (!empty($data['staff_ids'])) {
            $primaryStaffId = $data['primary_staff_id'] ?? null;
            $verifyStmt = $db->prepare("SELECT id FROM tenant_staff WHERE id = ? AND company_id = ?");
            foreach ($data['staff_ids'] as $staffId) {
                $verifyStmt->execute([$staffId, $companyId]);
                if (!$verifyStmt->fetch()) {
                    continue;
                }
                
                $isPrimary = ($staffId == $primaryStaffId) ? 1 : 0;
                
                $checkStmt = $db->prepare("
                    SELECT id FROM staff_services 
                    WHERE service_id = ? AND staff_id = ? AND company_id = ?
                ");
                $checkStmt->execute([$data['id'], $staffId, $companyId]);
                $existing = $checkStmt->fetch();
                
                if ($existing) {
                    $updateStmt = $db->prepare("
                        UPDATE staff_services SET is_active = 1, worker_primary = ? WHERE id = ?
                    ");
                    $updateStmt->execute([$isPrimary, $existing['id']]);
                } else {
                    $insertStmt = $db->prepare("
                        INSERT INTO staff_services (company_id, staff_id, service_id, is_active, worker_primary)
                        VALUES (?, ?, ?, 1, ?)
                    ");
                    $insertStmt->execute([$companyId, $staffId, $data['id'], $isPrimary]);
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Tjeneste oppdatert'
    ]);
    
} catch (Exception $e) {
    error_log("Error updating service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
