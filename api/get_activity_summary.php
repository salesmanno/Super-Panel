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
    
    // Get last_login timestamp for this admin (use as baseline for "new" activity)
    $stmt = $db->prepare("
        SELECT last_login 
        FROM tenant_admins 
        WHERE id = ?
    ");
    $stmt->execute([$admin_id]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $lastLogin = $admin['last_login'] ?? null;
    
    // If no previous login (first login), default to 7 days ago
    if (!$lastLogin) {
        $lastLogin = date('Y-m-d H:i:s', strtotime('-7 days'));
    }
    
    $activities = [];
    
    // 1. New transactions (unified_ledger)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM unified_ledger 
        WHERE company_id = ? 
        AND transaction_date > ?
    ");
    $stmt->execute([$company_id, $lastLogin]);
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
    $stmt->execute([$company_id, $lastLogin]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Medlemskap',
            'count' => $result['count'],
            'description' => $result['count'] . ' ny' . ($result['count'] > 1 ? 'e' : 't') . ' medlem' . ($result['count'] > 1 ? 'mer' : '')
        ];
    }
    
    // 3. New events (tenant_events)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM tenant_events 
        WHERE company_id = ? 
        AND created_at > ?
    ");
    $stmt->execute([$company_id, $lastLogin]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Arrangementer',
            'count' => $result['count'],
            'description' => $result['count'] . ' ny' . ($result['count'] > 1 ? 'e' : 't') . ' arrangement' . ($result['count'] > 1 ? 'er' : '')
        ];
    }
    
    // 4. New event registrations (tenant_event_registrations)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM tenant_event_registrations ter
        INNER JOIN tenant_events te ON ter.event_id = te.id
        WHERE te.company_id = ? 
        AND ter.created_at > ?
    ");
    $stmt->execute([$company_id, $lastLogin]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Arrangementer',
            'count' => $result['count'],
            'description' => $result['count'] . ' ny' . ($result['count'] > 1 ? 'e påmeldinger' : ' påmelding')
        ];
    }
    
    // 5. New reservations (service_bookings)
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM service_bookings 
        WHERE company_id = ? 
        AND created_at > ?
    ");
    $stmt->execute([$company_id, $lastLogin]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Reservasjoner',
            'count' => $result['count'],
            'description' => $result['count'] . ' ny' . ($result['count'] > 1 ? 'e reservasjon' : ' reservasjon') . ($result['count'] > 1 ? 'er' : '')
        ];
    }
    
    // 6. New sales products
    $stmt = $db->prepare("
        SELECT COUNT(*) as count 
        FROM sales_products 
        WHERE company_id = ? 
        AND created_at > ?
    ");
    $stmt->execute([$company_id, $lastLogin]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result['count'] > 0) {
        $activities[] = [
            'module' => 'Salg',
            'count' => $result['count'],
            'description' => $result['count'] . ' ny' . ($result['count'] > 1 ? 'e produkt' : 't produkt') . ($result['count'] > 1 ? 'er' : '')
        ];
    }
    
    echo json_encode([
        'success' => true,
        'last_login' => $lastLogin,
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
