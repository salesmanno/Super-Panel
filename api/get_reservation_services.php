<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    // Fetch all reservation services
    $stmt = $db->prepare("
        SELECT 
            id, service_name, description, price, duration_minutes,
            service_type, available_from, available_to,
            assigned_staff_ids, capacity, is_active, img_url, created_at
        FROM reservation_services
        WHERE company_id = ?
        ORDER BY created_at DESC
    ");
    
    $stmt->execute([$companyId]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get all staff for name lookup
    $stmtStaff = $db->prepare("
        SELECT id, first_name, last_name
        FROM tenant_staff
        WHERE company_id = ?
    ");
    $stmtStaff->execute([$companyId]);
    $staffList = $stmtStaff->fetchAll(PDO::FETCH_ASSOC);
    
    // Create staff lookup array
    $staffMap = [];
    foreach ($staffList as $staff) {
        $staffMap[$staff['id']] = $staff['first_name'] . ' ' . $staff['last_name'];
    }
    
    // Enrich services with staff names
    foreach ($services as &$service) {
        $staffIds = json_decode($service['assigned_staff_ids'], true) ?? [];
        $staffNames = [];
        
        foreach ($staffIds as $staffId) {
            if (isset($staffMap[$staffId])) {
                $staffNames[] = $staffMap[$staffId];
            }
        }
        
        $service['assigned_staff_names'] = implode(', ', $staffNames);
        $service['staff_count'] = count($staffNames);
    }
    
    echo json_encode([
        'success' => true,
        'services' => $services
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching reservation services: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Kunne ikke hente tjenester'
    ]);
}
