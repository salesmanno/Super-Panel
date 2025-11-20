<?php
session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['event_id']) || empty($data['customer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }
    
    $db = getDBConnection();
    
    // Check if already registered
    $checkStmt = $db->prepare("
        SELECT id FROM event_registrations 
        WHERE event_id = ? AND customer_id = ?
    ");
    $checkStmt->execute([$data['event_id'], $data['customer_id']]);
    
    if ($checkStmt->rowCount() > 0) {
        echo json_encode(['success' => false, 'message' => 'Allerede registrert for dette arrangementet']);
        exit;
    }
    
    // Get customer info
    $custStmt = $db->prepare("
        SELECT is_member FROM tenant_customers WHERE id = ? AND company_id = ?
    ");
    $custStmt->execute([$data['customer_id'], $data['company_id'] ?? null]);
    $customer = $custStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$customer) {
        echo json_encode(['success' => false, 'message' => 'Kunde ikke funnet']);
        exit;
    }
    
    // Create registration - default to pending if unpaid/non-member, auto-approve if member or paid
    $approvalStatus = ($customer['is_member'] || (!empty($data['is_paid']))) ? 'approved' : 'pending';
    
    $regId = bin2hex(random_bytes(16));
    $stmt = $db->prepare("
        INSERT INTO event_registrations (
            id, event_id, customer_id, company_id, approval_status
        ) VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $regId,
        $data['event_id'],
        $data['customer_id'],
        $data['company_id'] ?? $_SESSION['company_id'],
        $approvalStatus
    ]);
    
    echo json_encode([
        'success' => true,
        'registration_id' => $regId,
        'message' => $approvalStatus === 'approved' ? 'Registrering godkjent!' : 'Registrering mottatt - venter på godkjenning'
    ]);
    
} catch (Exception $e) {
    error_log("Error registering for event: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved registrering: ' . $e->getMessage()
    ]);
}
