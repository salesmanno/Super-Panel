<?php
session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $eventId = $_GET['event_id'] ?? null;
    
    if (!$eventId) {
        echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    // Verify event belongs to company
    $checkStmt = $db->prepare("SELECT company_id FROM events_eventdata WHERE id = ?");
    $checkStmt->execute([$eventId]);
    $event = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event || $event['company_id'] !== $companyId) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    try {
        $stmt = $db->prepare("
            SELECT 
                emp.membership_plan_id as plan_id,
                emp.ticket_price,
                mp.name as plan_name,
                emp.public_display
            FROM event_membership_plans emp
            LEFT JOIN membership_plans mp ON emp.membership_plan_id = mp.id
            WHERE emp.event_id = ?
            ORDER BY emp.ticket_price ASC
        ");
        
        $stmt->execute([$eventId]);
        $pricing = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'pricing' => $pricing
        ]);
    } catch (Exception $e) {
        error_log("Error fetching event membership pricing: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Kunne ikke hente priser']);
    }
    
} catch (Exception $e) {
    error_log("Error in event membership pricing: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved henting av priser']);
}
