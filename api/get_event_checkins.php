<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $eventId = $_GET['event_id'] ?? null;
    
    if (empty($eventId)) {
        echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
        exit;
    }

    $db = getDBConnection();
    
    // Verify event belongs to user's company and get event details
    $stmt = $db->prepare("
        SELECT id, title, ticket_price as default_ticket_price 
        FROM events_eventdata 
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$eventId, $_SESSION['company_id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Event ikke funnet']);
        exit;
    }
    
    // Get all check-ins for the event with customer names and membership info
    // Note: tenant_admins doesn't have first_name/last_name directly - those are in tenant_staff
    // We join through tenant_staff via tenant_admins.tenant_staff_id
    // Join event_membership_plans to get member price for the specific event/membership combo
    $stmt = $db->prepare("
        SELECT 
            c.id,
            c.customer_id,
            c.registration_id,
            c.ticket_type,
            c.ticket_price,
            c.payment_status,
            c.payment_method,
            c.checked_in,
            c.check_in_time,
            c.checked_in_by,
            c.barcode,
            c.membership_plan_id,
            c.notes,
            c.created_at,
            tc.first_name,
            tc.last_name,
            tc.email as customer_email,
            tc.phone as customer_phone,
            ts.first_name as admin_first_name,
            ts.last_name as admin_last_name,
            mp.name as membership_plan_name,
            emp.ticket_price as membership_ticket_price
        FROM events_checkin c
        LEFT JOIN tenant_customers tc ON c.customer_id = tc.id
        LEFT JOIN tenant_admins ta ON c.checked_in_by = ta.id
        LEFT JOIN tenant_staff ts ON ta.tenant_staff_id = ts.id
        LEFT JOIN membership_plans mp ON c.membership_plan_id = mp.id
        LEFT JOIN event_membership_plans emp ON emp.event_id = c.event_id AND emp.membership_plan_id = c.membership_plan_id
        WHERE c.event_id = ?
        ORDER BY c.checked_in DESC, c.check_in_time DESC, c.created_at DESC
    ");
    
    $stmt->execute([$eventId]);
    $allCheckins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Split into checked_in and not checked_in
    $checkedIn = [];
    $notCheckedIn = [];
    
    foreach ($allCheckins as $checkin) {
        // Build full name for display
        $checkin['customer_name'] = trim(($checkin['first_name'] ?? '') . ' ' . ($checkin['last_name'] ?? ''));
        if (empty($checkin['customer_name'])) {
            $checkin['customer_name'] = 'Ukjent';
        }
        
        if ($checkin['checked_in'] == 1) {
            $checkedIn[] = $checkin;
        } else {
            $notCheckedIn[] = $checkin;
        }
    }
    
    // Calculate summary stats
    $totalAttendees = count($allCheckins);
    $checkedInCount = count($checkedIn);
    $notCheckedInCount = count($notCheckedIn);
    
    // Calculate revenues
    // Actual checked-in revenue (only from checked in attendees who paid)
    $checkinRevenue = 0;
    $checkinPaidCount = 0;
    foreach ($checkedIn as $c) {
        if ($c['payment_status'] === 'paid') {
            $checkinRevenue += floatval($c['ticket_price']);
            $checkinPaidCount++;
        }
    }
    
    // Expected revenue (from all attendees, whether checked in or not)
    $expectedRevenue = 0;
    foreach ($allCheckins as $c) {
        $expectedRevenue += floatval($c['ticket_price']);
    }
    
    // No-show count (marked as checked_in but with no_show notes or payment_status = 'no_show')
    $noShowCount = 0;
    foreach ($checkedIn as $c) {
        if ($c['payment_status'] === 'no_show') {
            $noShowCount++;
        }
    }
    
    // Total revenue (all paid - both checked in and not checked in)
    $totalRevenue = 0;
    $paidCount = 0;
    foreach ($allCheckins as $c) {
        if ($c['payment_status'] === 'paid') {
            $totalRevenue += floatval($c['ticket_price']);
            $paidCount++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'event' => [
            'id' => $event['id'],
            'title' => $event['title'],
            'default_ticket_price' => floatval($event['default_ticket_price'])
        ],
        'checked_in' => $checkedIn,
        'not_checked_in' => $notCheckedIn,
        'summary' => [
            'total_attendees' => $totalAttendees,
            'checked_in_count' => $checkedInCount,
            'not_checked_in_count' => $notCheckedInCount,
            'no_show_count' => $noShowCount,
            'total_revenue' => $totalRevenue,
            'checkin_revenue' => $checkinRevenue,
            'expected_revenue' => $expectedRevenue,
            'paid_count' => $paidCount,
            'checkin_paid_count' => $checkinPaidCount,
            'pending_count' => $totalAttendees - $paidCount
        ]
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching check-ins: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av check-ins'
    ]);
}
