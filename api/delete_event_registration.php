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
    
    if (empty($data['registration_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler registration_id']);
        exit;
    }
    
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $registrationId = $data['registration_id'];
    
    // First verify the registration exists and belongs to this company
    $checkStmt = $db->prepare("
        SELECT r.id, r.customer_id, r.event_id, c.first_name, c.last_name
        FROM event_registrations r
        LEFT JOIN tenant_customers c ON r.customer_id = c.id
        WHERE r.id = ? AND r.company_id = ?
    ");
    $checkStmt->execute([$registrationId, $companyId]);
    $registration = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        echo json_encode(['success' => false, 'message' => 'Påmelding ikke funnet']);
        exit;
    }
    
    $db->beginTransaction();
    
    try {
        // Delete related events_checkin records (if any) - with company_id filter for security
        $deleteCheckin = $db->prepare("
            DELETE FROM events_checkin
            WHERE EXISTS (
                SELECT 1 FROM events_eventdata e WHERE e.id = events_checkin.event_id AND e.company_id = ?
            ) AND (registration_id = ? OR (customer_id = ? AND event_id = ?))
        ");
        $deleteCheckin->execute([
            $companyId,
            $registrationId, 
            $registration['customer_id'], 
            $registration['event_id']
        ]);
        $checkinDeleted = $deleteCheckin->rowCount();
        
        // Delete the event registration
        $deleteReg = $db->prepare("
            DELETE FROM event_registrations 
            WHERE id = ? AND company_id = ?
        ");
        $deleteReg->execute([$registrationId, $companyId]);
        
        if ($deleteReg->rowCount() === 0) {
            throw new Exception('Kunne ikke slette påmeldingen');
        }
        
        $db->commit();
        
        $customerName = trim(($registration['first_name'] ?? '') . ' ' . ($registration['last_name'] ?? ''));
        
        echo json_encode([
            'success' => true,
            'message' => 'Påmelding slettet',
            'customer_name' => $customerName,
            'checkins_deleted' => $checkinDeleted
        ]);
        
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error deleting event registration: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting: ' . $e->getMessage()
    ]);
}
