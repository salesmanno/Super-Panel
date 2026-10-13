<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/event_membership_prices.php';

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
        if (!is_array($pricingData) || !array_is_list($pricingData)) throw new InvalidArgumentException('Ugyldig prisoppsett');
        saveEventMembershipPrices($db, (string)$companyId, (string)$eventId, $pricingData);
        
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
