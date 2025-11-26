<?php
/**
 * Assign EAN13 barcode to events_checkin record
 * Called after checkin is created or on demand
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/BarcodeService.php';

try {
    $checkInId = $_POST['checkin_id'] ?? $_GET['checkin_id'] ?? null;
    $eventId = $_POST['event_id'] ?? $_GET['event_id'] ?? null;
    
    if (empty($checkInId) || empty($eventId)) {
        echo json_encode(['success' => false, 'message' => 'Mangler checkin_id eller event_id']);
        exit;
    }

    $db = getDBConnection();
    
    // Verify checkin belongs to user's company
    $stmt = $db->prepare("
        SELECT ec.id, ec.event_id, e.company_id
        FROM events_checkin ec
        JOIN events_eventdata e ON ec.event_id = e.id
        WHERE ec.id = ? AND e.company_id = ?
    ");
    $stmt->execute([$checkInId, $_SESSION['company_id']]);
    $checkin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$checkin) {
        echo json_encode(['success' => false, 'message' => 'Innsjekking ikke funnet']);
        exit;
    }
    
    // Generate EAN13 barcode
    $ean13 = BarcodeService::generateEAN13($checkInId, $eventId);
    
    // Save to database
    $stmt = $db->prepare("
        UPDATE events_checkin 
        SET barcode = ? 
        WHERE id = ?
    ");
    $stmt->execute([$ean13, $checkInId]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Barcode opprettet',
        'barcode' => $ean13,
        'checkin_id' => $checkInId,
        'event_id' => $eventId
    ]);
    
} catch (Exception $e) {
    error_log("Error assigning barcode: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse av barcode'
    ]);
}
