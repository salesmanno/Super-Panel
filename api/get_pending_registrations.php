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
            r.approval_status,
            r.approved_by,
            r.approved_at,
            e.title AS event_title,
            e.start_date,
            e.start_time,
            COALESCE(tpm.name, '') AS membership_plan_name,
            COALESCE(tpm.image_url, '') AS membership_plan_image,
            COALESCE(emp.ticket_price, 0) AS membership_ticket_price,
            COALESCE(ts.first_name, 'Ukjent') AS approver_first_name,
            COALESCE(ts.last_name, 'bruker') AS approver_last_name
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id
        INNER JOIN tenant_customers c ON r.customer_id = c.id
        LEFT JOIN membership_plans tpm ON r.membership_plan_id = tpm.id
        LEFT JOIN event_membership_plans emp ON emp.event_id = r.event_id AND emp.membership_plan_id = r.membership_plan_id
        LEFT JOIN tenant_admins ta ON r.approved_by = ta.id
        LEFT JOIN tenant_staff ts ON ta.tenant_staff_id = ts.id
        WHERE r.company_id = ?
        AND r.approval_status = 'pending'
        ORDER BY e.title ASC, r.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['company_id']]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get registration fields for each event
    $eventFields = [];
    $fieldsStmt = $db->prepare("
        SELECT event_id, field_name, field_label, is_required, is_visible
        FROM event_registration_fields
        WHERE is_visible = 1
        ORDER BY display_order ASC
    ");
    $fieldsStmt->execute();
    foreach ($fieldsStmt->fetchAll(PDO::FETCH_ASSOC) as $field) {
        $eventId = $field['event_id'];
        if (!isset($eventFields[$eventId])) {
            $eventFields[$eventId] = [];
        }
        $eventFields[$eventId][] = $field;
    }
    
    // Add fields to each registration
    foreach ($registrations as &$reg) {
        $reg['required_fields'] = $eventFields[$reg['event_id']] ?? [];
    }
    
    echo json_encode([
        'success' => true,
        'total_pending' => count($registrations),
        'registrations' => $registrations
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching pending registrations: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av påmeldinger: ' . $e->getMessage()
    ]);
}
