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
    // Customer data from tenant_customers used for dynamic field display
    // Membership info is in membership_members table
    $stmt = $db->prepare("
        SELECT 
            r.id,
            r.event_id,
            r.customer_id,
            r.created_at,
            r.approval_status,
            c.first_name,
            c.last_name,
            c.email,
            c.phone,
            c.customer_code,
            c.customer_type,
            c.address,
            c.postal_code,
            c.city,
            c.country,
            c.date_of_birth,
            c.gender,
            c.notes AS customer_notes,
            c.is_member AS has_valid_membership,
            mm.membership_plan_id AS customer_membership_plan_id,
            e.title AS event_title,
            e.start_date,
            e.start_time,
            e.ticket_price AS regular_price
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id
        INNER JOIN tenant_customers c ON r.customer_id = c.id AND c.company_id = r.company_id
        LEFT JOIN membership_members mm ON mm.customer_id = c.id AND mm.company_id = r.company_id AND mm.status = 'active'
        WHERE r.company_id = ?
        AND r.approval_status = 'pending'
        ORDER BY r.created_at DESC
    ");
    
    $stmt->execute([$companyId]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get membership prices for all events, indexed by event_id AND membership_plan_id
    // Note: membership_plans table uses 'name' column, not 'plan_name'
    $membershipPrices = [];
    $mpStmt = $db->prepare("
        SELECT emp.event_id, emp.membership_plan_id, emp.ticket_price, mp.name AS plan_name
        FROM event_membership_plans emp
        LEFT JOIN membership_plans mp ON emp.membership_plan_id = mp.id
    ");
    $mpStmt->execute();
    foreach ($mpStmt->fetchAll(PDO::FETCH_ASSOC) as $mp) {
        $key = $mp['event_id'] . '_' . $mp['membership_plan_id'];
        $membershipPrices[$key] = $mp;
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
    
    // Process each registration - include actual customer entry data
    foreach ($registrations as &$reg) {
        // Build entry data with actual values from customer registration
        $reg['entry_data'] = [
            'first_name' => $reg['first_name'] ?? '',
            'last_name' => $reg['last_name'] ?? '',
            'email' => $reg['email'] ?? '',
            'phone' => $reg['phone'] ?? '',
            'address' => $reg['address'] ?? '',
            'city' => $reg['city'] ?? '',
            'postal_code' => $reg['postal_code'] ?? '',
            'date_of_birth' => $reg['date_of_birth'] ?? ''
        ];
        
        // Calculate price based on membership - match customer's specific plan to event's membership pricing
        $regularPrice = floatval($reg['regular_price'] ?? 0);
        $memberPrice = null;
        $memberPlanName = null;
        $hasValidMemberPrice = false;
        
        // Only check membership price if customer is a member AND has a membership plan
        if ($reg['has_valid_membership'] && $reg['customer_membership_plan_id']) {
            $priceKey = $reg['event_id'] . '_' . $reg['customer_membership_plan_id'];
            if (isset($membershipPrices[$priceKey])) {
                $mp = $membershipPrices[$priceKey];
                $memberPrice = floatval($mp['ticket_price']);
                $memberPlanName = $mp['plan_name'];
                $hasValidMemberPrice = true;
            }
        }
        
        $reg['price_info'] = [
            'is_member' => $hasValidMemberPrice,
            'display_price' => $hasValidMemberPrice ? $memberPrice : $regularPrice,
            'regular_price' => $regularPrice,
            'member_price' => $memberPrice,
            'member_plan' => $memberPlanName
        ];
        
        // Clean up redundant data
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
