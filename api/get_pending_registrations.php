<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        SELECT 
            r.id,
            r.event_id,
            r.first_name,
            r.last_name,
            r.email,
            r.phone,
            r.has_valid_membership,
            r.is_paid,
            r.created_at,
            e.title AS event_title,
            e.start_date,
            e.start_time
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id COLLATE utf8mb4_unicode_ci
        WHERE r.company_id = ?
        AND r.approval_status = 'pending'
        ORDER BY r.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['company_id']]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'registrations' => $registrations
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching pending registrations: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av påmeldinger: ' . $e->getMessage()
    ]);
}
