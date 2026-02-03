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
    
    // Customer data from tenant_customers used for display
    // Membership info is in membership_members table
    // Payment status comes from events_checkin
    // ticket_price: Use membership price if available, otherwise use standard event price
    $stmt = $db->prepare("
        SELECT 
            r.id,
            r.event_id,
            r.customer_id,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            c.customer_code,
            c.address_street,
            c.address_zip,
            c.address_city,
            c.address_country,
            c.date_of_birth,
            c.notes AS customer_notes,
            CASE WHEN mm.membership_plan_id IS NOT NULL THEN 1 ELSE 0 END AS has_valid_membership,
            mm.membership_plan_id AS customer_membership_plan_id,
            mp.name AS membership_plan_name,
            COALESCE(emp.ticket_price, e.ticket_price) AS ticket_price,
            e.ticket_price AS standard_ticket_price,
            emp.ticket_price AS membership_ticket_price,
            ec.id AS checkin_id,
            ec.payment_status,
            ec.payment_method,
            ec.checked_in,
            ec.payment_reference,
            ec.payment_authorization,
            ec.payment_state,
            ec.payment_checked_at,
            ec.ticket_price AS paid_ticket_price,
            r.created_at,
            r.approval_status,
            r.approved_at,
            r.approved_by,
            approver_staff.first_name AS approver_first_name,
            approver_staff.last_name AS approver_last_name,
            e.title AS event_title,
            e.start_date,
            e.start_time
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id
        INNER JOIN tenant_customers c ON r.customer_id = c.id AND c.company_id = r.company_id
        LEFT JOIN membership_members mm ON mm.customer_id = c.id AND mm.company_id = r.company_id AND mm.status = 'active'
        LEFT JOIN membership_plans mp ON mp.id = mm.membership_plan_id
        LEFT JOIN event_membership_plans emp ON emp.event_id = r.event_id AND emp.membership_plan_id = mm.membership_plan_id
        LEFT JOIN events_checkin ec ON ec.registration_id = r.id
        LEFT JOIN tenant_admins approver ON r.approved_by = approver.id
        LEFT JOIN tenant_staff approver_staff ON approver.tenant_staff_id = approver_staff.id
        WHERE r.company_id = ? AND r.approval_status != 'pending'
        ORDER BY e.title ASC, r.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['company_id']]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Count approved registrations for summary
    $approvedCount = 0;
    $rejectedCount = 0;
    
    foreach ($registrations as $reg) {
        if ($reg['approval_status'] === 'approved') {
            $approvedCount++;
        } elseif ($reg['approval_status'] === 'rejected') {
            $rejectedCount++;
        }
    }
    
    // Group by arrangement
    $groupedByEvent = [];
    foreach ($registrations as $reg) {
        $eventKey = $reg['event_title'] . '_' . $reg['event_id'];
        if (!isset($groupedByEvent[$eventKey])) {
            $groupedByEvent[$eventKey] = [
                'event_id' => $reg['event_id'],
                'event_title' => $reg['event_title'],
                'start_date' => $reg['start_date'],
                'start_time' => $reg['start_time'],
                'approved_count' => 0,
                'rejected_count' => 0,
                'registrations' => []
            ];
        }
        if ($reg['approval_status'] === 'approved') {
            $groupedByEvent[$eventKey]['approved_count']++;
        } elseif ($reg['approval_status'] === 'rejected') {
            $groupedByEvent[$eventKey]['rejected_count']++;
        }
        $groupedByEvent[$eventKey]['registrations'][] = $reg;
    }
    
    echo json_encode([
        'success' => true,
        'total_registrations' => count($registrations),
        'total_approved' => $approvedCount,
        'total_rejected' => $rejectedCount,
        'grouped_by_event' => array_values($groupedByEvent)
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching all registrations: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av påmeldinger: ' . $e->getMessage()
    ]);
}
?>
