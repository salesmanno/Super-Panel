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
            r.customer_id,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            c.is_member AS has_valid_membership,
            r.created_at,
            r.created_by_admin,
            r.created_by_staff,
            admin_creator.first_name AS admin_creator_first_name,
            admin_creator.last_name AS admin_creator_last_name,
            staff_creator.first_name AS staff_creator_first_name,
            staff_creator.last_name AS staff_creator_last_name,
            e.title AS event_title,
            e.start_date,
            e.start_time
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id COLLATE utf8mb4_unicode_ci
        INNER JOIN tenant_customers c ON r.customer_id = c.id COLLATE utf8mb4_unicode_ci
        LEFT JOIN tenant_admins admin_creator ON r.created_by_admin = admin_creator.id
        LEFT JOIN tenant_staff staff_creator ON r.created_by_staff = staff_creator.id
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
