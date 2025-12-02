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
    $companyId = $_SESSION['company_id'];
    
    // Get pending registrations with event, customer, and pricing info
    $stmt = $db->prepare("
        SELECT 
            r.id,
            r.event_id,
            r.customer_id,
            r.registration_data,
            r.number_of_attendees,
            r.payment_status,
            r.payment_method,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            c.is_member AS has_valid_membership,
            r.created_at,
            r.approval_status,
            e.title AS event_title,
            e.start_date,
            e.start_time,
            e.ticket_price AS regular_price
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id
        INNER JOIN tenant_customers c ON r.customer_id = c.id
        WHERE r.company_id = ?
        AND r.approval_status = 'pending'
        ORDER BY r.created_at DESC
    ");
    
    $stmt->execute([$companyId]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get membership prices for all events
    $membershipPrices = [];
    $mpStmt = $db->prepare("
        SELECT emp.event_id, emp.membership_plan_id, emp.ticket_price, mp.plan_name
        FROM event_membership_plans emp
        LEFT JOIN membership_plans mp ON emp.membership_plan_id = mp.id
    ");
    $mpStmt->execute();
    foreach ($mpStmt->fetchAll(PDO::FETCH_ASSOC) as $mp) {
        if (!isset($membershipPrices[$mp['event_id']])) {
            $membershipPrices[$mp['event_id']] = [];
        }
        $membershipPrices[$mp['event_id']][] = $mp;
    }
    
    // Get registration fields for each event (max 4)
    $eventFields = [];
    $fieldsStmt = $db->prepare("
        SELECT event_id, field_name, field_label, is_required, is_visible, display_order
        FROM event_registration_fields
        WHERE is_visible = 1
        ORDER BY event_id, display_order ASC
    ");
    $fieldsStmt->execute();
    foreach ($fieldsStmt->fetchAll(PDO::FETCH_ASSOC) as $field) {
        $eventId = $field['event_id'];
        if (!isset($eventFields[$eventId])) {
            $eventFields[$eventId] = [];
        }
        if (count($eventFields[$eventId]) < 4) {
            $eventFields[$eventId][] = $field;
        }
    }
    
    // Process each registration
    foreach ($registrations as &$reg) {
        // Parse registration_data JSON
        $regData = json_decode($reg['registration_data'] ?? '{}', true) ?: [];
        
        // Get dynamic fields (max 4)
        $fields = $eventFields[$reg['event_id']] ?? [];
        $dynamicFields = [];
        foreach ($fields as $field) {
            $fieldName = $field['field_name'];
            $dynamicFields[] = [
                'label' => $field['field_label'],
                'value' => $regData[$fieldName] ?? '',
                'field_name' => $fieldName
            ];
        }
        $reg['dynamic_fields'] = $dynamicFields;
        
        // Calculate price based on membership
        $regularPrice = floatval($reg['regular_price'] ?? 0);
        $memberPrice = null;
        $memberPlanName = null;
        
        if ($reg['has_valid_membership'] && isset($membershipPrices[$reg['event_id']])) {
            $mp = $membershipPrices[$reg['event_id']][0] ?? null;
            if ($mp) {
                $memberPrice = floatval($mp['ticket_price']);
                $memberPlanName = $mp['plan_name'];
            }
        }
        
        $reg['price_info'] = [
            'is_member' => (bool)$reg['has_valid_membership'],
            'display_price' => ($reg['has_valid_membership'] && $memberPrice !== null) 
                ? $memberPrice 
                : $regularPrice,
            'regular_price' => $regularPrice,
            'member_price' => $memberPrice,
            'member_plan' => $memberPlanName
        ];
        
        // Clean up redundant data
        unset($reg['registration_data']);
        unset($reg['regular_price']);
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
