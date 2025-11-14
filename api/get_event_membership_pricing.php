<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $eventId = $_GET['event_id'] ?? null;
    
    if (!$eventId) {
        echo json_encode(['success' => false, 'error' => 'Mangler event_id']);
        exit;
    }
    
    $db = getDBConnection();
    $dbType = getDBType();
    
    if ($dbType === 'mysql') {
        $stmt = $db->prepare("
            SELECT 
                emp.membership_plan_id as plan_id,
                emp.ticket_price,
                mp.name as plan_name
            FROM event_membership_plans emp
            LEFT JOIN membership_plans mp ON emp.membership_plan_id = mp.id
            WHERE emp.event_id = ?
        ");
    } else {
        $stmt = $db->prepare("
            SELECT 
                emp.membership_plan_id as plan_id,
                emp.ticket_price,
                mp.plan_name
            FROM event_membership_plans emp
            LEFT JOIN x_membership_plan mp ON emp.membership_plan_id = mp.plan_id
            WHERE emp.event_id = ?
        ");
    }
    
    $stmt->execute([$eventId]);
    $pricing = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'pricing' => $pricing
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching event membership pricing: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke hente priser']);
}
