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
    
    $stmt = $db->prepare("
        UPDATE event_registrations
        SET 
            approval_status = 'rejected',
            rejected_by = ?,
            rejected_at = NOW(),
            approval_reason = ?
        WHERE id = ?
        AND company_id = ?
        AND approval_status = 'pending'
    ");
    
    $stmt->execute([
        $_SESSION['tenant_admin_id'],
        $data['reason'] ?? null,
        $data['registration_id'],
        $_SESSION['company_id']
    ]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Påmelding avvist'
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
