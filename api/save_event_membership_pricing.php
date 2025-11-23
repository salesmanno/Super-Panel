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
    
    if (!isset($data['event_id']) || !isset($data['pricing'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
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
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    $db->beginTransaction();
    
    try {
        $deleteStmt = $db->prepare("DELETE FROM event_membership_plans WHERE event_id = ?");
        $deleteStmt->execute([$eventId]);
        
        $insertStmt = $db->prepare("
            INSERT INTO event_membership_plans (event_id, membership_plan_id, ticket_price, public_display, created_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        
        foreach ($pricingData as $pricing) {
            if (isset($pricing['plan_id']) && isset($pricing['price'])) {
                // Verify membership plan belongs to company
                $planCheckStmt = $db->prepare("SELECT company_id FROM membership_plans WHERE id = ?");
                $planCheckStmt->execute([$pricing['plan_id']]);
                $plan = $planCheckStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($plan && $plan['company_id'] === $companyId) {
                    $publicDisplay = isset($pricing['public_display']) ? (int)$pricing['public_display'] : 1;
                    $insertStmt->execute([
                        $eventId,
                        $pricing['plan_id'],
                        $pricing['price'],
                        $publicDisplay
                    ]);
                }
            }
        }
        
        $db->commit();
        
        echo json_encode(['success' => true, 'message' => 'Medlemspriser lagret']);
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error saving event membership pricing: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Kunne ikke lagre priser']);
}
