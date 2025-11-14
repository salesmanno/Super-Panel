<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['event_id']) || !isset($data['pricing'])) {
        echo json_encode(['success' => false, 'error' => 'Mangler påkrevde felt']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $eventId = $data['event_id'];
    $pricingData = $data['pricing'];
    
    // Verify event belongs to company
    $checkStmt = $db->prepare("SELECT company_id FROM events_eventdata WHERE id = ?");
    $checkStmt->execute([$eventId]);
    $event = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event || $event['company_id'] !== $companyId) {
        echo json_encode(['success' => false, 'error' => 'Arrangement ikke funnet']);
        exit;
    }
    
    $db->beginTransaction();
    
    $deleteStmt = $db->prepare("DELETE FROM event_membership_plans WHERE event_id = ?");
    $deleteStmt->execute([$eventId]);
    
    $insertStmt = $db->prepare("
        INSERT INTO event_membership_plans (event_id, membership_plan_id, ticket_price, created_at)
        VALUES (?, ?, ?, CURRENT_TIMESTAMP)
    ");
    
    foreach ($pricingData as $pricing) {
        if (isset($pricing['plan_id']) && isset($pricing['price'])) {
            // Verify membership plan belongs to company
            $planCheckStmt = $db->prepare("SELECT company_id FROM membership_plans WHERE id = ?");
            $planCheckStmt->execute([$pricing['plan_id']]);
            $plan = $planCheckStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($plan && $plan['company_id'] === $companyId) {
                $insertStmt->execute([
                    $eventId,
                    $pricing['plan_id'],
                    $pricing['price']
                ]);
            }
        }
    }
    
    $db->commit();
    
    echo json_encode(['success' => true, 'message' => 'Medlemspriser lagret']);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error saving event membership pricing: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Kunne ikke lagre priser']);
}
