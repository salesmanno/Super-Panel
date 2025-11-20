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
        echo json_encode(['success' => false, 'message' => 'Mangler påmeldings-ID']);
        exit;
    }
    
    $db = getDBConnection();
    
    // Get registration details first
    $getStmt = $db->prepare("
        SELECT r.*, e.requires_manual_approval 
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id
        WHERE r.id = ? AND r.company_id = ?
    ");
    $getStmt->execute([$data['registration_id'], $_SESSION['company_id']]);
    $registration = $getStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registration) {
        echo json_encode(['success' => false, 'message' => 'Påmelding ikke funnet']);
        exit;
    }
    
    // Auto-approve members regardless of requires_manual_approval setting
    // Only require manual approval for non-members if requires_manual_approval is true
    if ($registration['has_valid_membership'] || !$registration['requires_manual_approval']) {
        $stmt = $db->prepare("
            UPDATE event_registrations
            SET 
                approval_status = 'approved',
                approved_by = ?,
                approved_at = NOW()
            WHERE id = ?
            AND company_id = ?
            AND approval_status = 'pending'
        ");
        
        $stmt->execute([
            $_SESSION['tenant_admin_id'],
            $data['registration_id'],
            $_SESSION['company_id']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Medlemmer godkjennes alltid automatisk']);
        exit;
    }
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Påmelding godkjent'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fant ikke påmelding eller allerede behandlet'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error approving registration: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved godkjenning: ' . $e->getMessage()
    ]);
}
