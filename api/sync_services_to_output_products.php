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
    $companyId = $_SESSION['company_id'];
    $adminId = $_SESSION['tenant_admin_id'];
    
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
    
    $servicesStmt = $db->prepare("
        SELECT rs.id, rs.service_name, rs.description, rs.output_product_id
        FROM reservation_services rs
        WHERE rs.company_id = ?
    ");
    $servicesStmt->execute([$companyId]);
    $services = $servicesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $synced = 0;
    $skipped = 0;
    
    foreach ($services as $service) {
        $checkStmt = $db->prepare("
            SELECT id FROM output_products 
            WHERE product_id = ? AND product_type = 'service' AND company_id = ?
        ");
        $checkStmt->execute([$service['id'], $companyId]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            $skipped++;
            continue;
        }
        
        $outputProductId = bin2hex(random_bytes(16));
        
        $insertStmt = $db->prepare("
            INSERT INTO output_products (
                id, company_id, module_id, product_type, product_id,
                name, description,
                is_visible, is_public, is_marketplace_featured, discount_percent,
                created_at, created_by
            ) VALUES (?, ?, ?, 'service', ?, ?, ?, 1, 1, 0, 0, NOW(), ?)
        ");
        
        $insertStmt->execute([
            $outputProductId,
            $companyId,
            $reservationsModuleId,
            $service['id'],
            $service['service_name'],
            $service['description'],
            $adminId
        ]);
        
        $updateStmt = $db->prepare("
            UPDATE reservation_services SET output_product_id = ? WHERE id = ?
        ");
        $updateStmt->execute([$outputProductId, $service['id']]);
        
        $synced++;
    }
    
    echo json_encode([
        'success' => true,
        'message' => "Synkronisering fullført: $synced tjenester lagt til, $skipped allerede eksisterende",
        'synced' => $synced,
        'skipped' => $skipped,
        'total' => count($services)
    ]);
    
} catch (Exception $e) {
    error_log("Error syncing services: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved synkronisering: ' . $e->getMessage()
    ]);
}
