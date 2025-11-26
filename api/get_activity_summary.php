<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$admin_id = $_SESSION['tenant_admin_id'];
$company_id = $_SESSION['company_id'];

try {
    $db = getDBConnection();
    
    // Get baseline timestamp for "new" activity
    // Try last_logout first (preferred), fall back to last_login, then 7 days ago
    // This handles schema differences between MySQL (no last_logout) and PostgreSQL (has last_logout)
    try {
        $stmt = $db->prepare("
            SELECT last_login 
            FROM tenant_admins 
            WHERE id = ?
        ");
        $stmt->execute([$admin_id]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $baselineTime = $admin['last_login'] ?? date('Y-m-d H:i:s', strtotime('-1 hour'));
    } catch (Exception $e) {
        // Fallback to 1 hour ago if query fails
        $baselineTime = date('Y-m-d H:i:s', strtotime('-1 hour'));
    }
    
    $activities = [];
    
    // 1. New transactions (unified_ledger)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM unified_ledger 
        WHERE company_id = ? 
        AND transaction_date > ?
    ");
    $stmt->execute([$company_id, $baselineTime]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Økonomi',
            'count' => $result['count'],
            'description' => $result['count'] . ' nye transaksjon' . ($result['count'] > 1 ? 'er' : '')
        ];
    }
    
    // 2. New members (tenant_membership_members)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM tenant_membership_members 
        WHERE company_id = ? 
        AND created_at > ?
    ");
    $stmt->execute([$company_id, $baselineTime]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Medlemskap',
            'count' => $result['count'],
            'description' => $result['count'] . ' ny' . ($result['count'] > 1 ? 'e' : 't') . ' medlem' . ($result['count'] > 1 ? 'mer' : '')
        ];
    }
    
    // 3. New events (events_eventdata)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM events_eventdata 
        WHERE company_id = ? 
        AND created_at > ?
    ");
    $stmt->execute([$company_id, $baselineTime]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Arrangementer',
            'count' => $result['count'],
            'description' => $result['count'] . ' ny' . ($result['count'] > 1 ? 'e' : 't') . ' arrangement' . ($result['count'] > 1 ? 'er' : '')
        ];
    }
    
    // 4. New event registrations (event_registrations)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM event_registrations er
        INNER JOIN events_eventdata ee ON er.event_id = ee.id
        WHERE ee.company_id = ? 
        AND er.created_at > ?
    ");
    $stmt->execute([$company_id, $baselineTime]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Arrangementer',
            'count' => $result['count'],
            'description' => $result['count'] . ' ny' . ($result['count'] > 1 ? 'e påmeldinger' : ' påmelding')
        ];
    }
    
    // 4b. New event check-ins (events_checkin)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM events_checkin ec
        INNER JOIN events_eventdata ee ON ec.event_id = ee.id
        WHERE ee.company_id = ? 
        AND ec.created_at > ?
    ");
    $stmt->execute([$company_id, $baselineTime]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Arrangementer (innsjekk)',
            'count' => $result['count'],
            'description' => $result['count'] . ' ny' . ($result['count'] > 1 ? 'e innsjekk' : ' innsjekk')
        ];
    }
    
    // 5. New reservations (service_bookings)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM service_bookings 
        WHERE company_id = ? 
        AND created_at > ?
    ");
    $stmt->execute([$company_id, $baselineTime]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Reservasjoner',
            'count' => $result['count'],
            'description' => $result['count'] . ' ny' . ($result['count'] > 1 ? 'e reservasjon' : ' reservasjon') . ($result['count'] > 1 ? 'er' : '')
        ];
    }
    
    // 6. New sales orders (orders table - from checkout_cart.php)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM orders 
        WHERE company_id = ? 
        AND created_at > ?
    ");
    $stmt->execute([$company_id, $baselineTime]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Salg',
            'count' => $result['count'],
            'description' => $result['count'] . ' ny' . ($result['count'] > 1 ? 'e bestilling' : ' bestilling') . ($result['count'] > 1 ? 'er' : '')
        ];
    }
    
    // 7. New individual sales (process_service_sale creates these in unified_ledger)
    // Already counted in #1 "New transactions (unified_ledger)"
    
    echo json_encode([
        'success' => true,
        'baseline_time' => $baselineTime,
        'activities' => $activities,
        'total_count' => array_sum(array_column($activities, 'count'))
    ]);
    
} catch (Exception $e) {
    error_log("Activity summary error: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Feil ved henting av aktivitetssammendrag',
        'activities' => []
    ]);
}
