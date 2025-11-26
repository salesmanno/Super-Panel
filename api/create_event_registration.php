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
    
    if (empty($data['event_id']) || empty($data['customer_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt (event_id, customer_id)']);
        exit;
    }
    
    $db = getDBConnection();
    $db->beginTransaction();
    
    try {
        $stmt = $db->prepare("SELECT id FROM events_eventdata WHERE id = ? AND company_id = ? AND is_active = 1");
        $stmt->execute([$data['event_id'], $_SESSION['company_id']]);
        if (!$stmt->fetch()) {
            throw new Exception('Arrangement ikke funnet');
        }
        
        $stmt = $db->prepare("SELECT id FROM tenant_customers WHERE id = ? AND company_id = ?");
        $stmt->execute([$data['customer_id'], $_SESSION['company_id']]);
        if (!$stmt->fetch()) {
            throw new Exception('Kunde ikke funnet');
        }
        
        $stmt = $db->prepare("
            SELECT id FROM event_registrations 
            WHERE event_id = ? AND customer_id = ?
        ");
        $stmt->execute([$data['event_id'], $data['customer_id']]);
        if ($stmt->fetch()) {
            throw new Exception('Kunden er allerede påmeldt dette arrangementet');
        }
        
        $registrationId = bin2hex(random_bytes(16));
        
        $approvalStatus = isset($data['auto_approve']) && $data['auto_approve'] ? 'approved' : 'pending';
        
        $createdByAdmin = $_SESSION['tenant_admin_id'] ?? null;
        $createdByStaff = $data['staff_id'] ?? null;
        
        if ($createdByStaff) {
            $staffStmt = $db->prepare("SELECT id FROM tenant_staff WHERE id = ? AND company_id = ?");
            $staffStmt->execute([$createdByStaff, $_SESSION['company_id']]);
            if (!$staffStmt->fetch()) {
                throw new Exception('Staff ikke funnet');
            }
        }
        
        $stmt = $db->prepare("
            INSERT INTO event_registrations (
                id,
                event_id,
                company_id,
                customer_id,
                created_by_admin,
                created_by_staff,
                approval_status,
                approved_by,
                approved_at,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $approvedBy = ($approvalStatus === 'approved') ? $_SESSION['tenant_admin_id'] : null;
        $approvedAt = ($approvalStatus === 'approved') ? date('Y-m-d H:i:s') : null;
        
        $stmt->execute([
            $registrationId,
            $data['event_id'],
            $_SESSION['company_id'],
            $data['customer_id'],
            $createdByAdmin,
            $createdByStaff,
            $approvalStatus,
            $approvedBy,
            $approvedAt
        ]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Påmelding opprettet' . ($approvalStatus === 'approved' ? ' og godkjent' : ''),
            'registration_id' => $registrationId,
            'approval_status' => $approvalStatus
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error creating event registration: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
