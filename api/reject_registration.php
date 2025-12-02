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
    $adminId = $_SESSION['tenant_admin_id'];
    $companyId = $_SESSION['company_id'];
    $registrationId = $data['registration_id'];
    $rejectionReason = $data['rejection_reason'] ?? $data['reason'] ?? null;
    
    $stmt = $db->prepare("
        UPDATE event_registrations
        SET 
            approval_status = 'rejected',
            rejected_by = ?,
            rejected_at = NOW(),
            rejection_reason = ?
        WHERE id = ?
        AND company_id = ?
        AND approval_status = 'pending'
    ");
    
    $stmt->execute([
        $adminId,
        $rejectionReason,
        $registrationId,
        $companyId
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Påmelding avvist',
            'rejection_reason' => $rejectionReason
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fant ikke påmelding eller allerede behandlet'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error rejecting registration: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved avvisning: ' . $e->getMessage()
    ]);
}
