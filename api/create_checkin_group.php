<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Ugyldig metode']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$data = json_decode(file_get_contents('php://input'), true);

$eventId = $data['event_id'] ?? null;
$checkinIds = $data['checkin_ids'] ?? [];
$paymentMethod = $data['payment_method'] ?? null;

if (empty($eventId) || empty($checkinIds) || count($checkinIds) < 2) {
    echo json_encode(['success' => false, 'message' => 'Mangler event_id eller må velge minst 2 gjester']);
    exit;
}

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $adminId = $_SESSION['tenant_admin_id'];
    
    // Verify event belongs to this company
    $eventCheck = $db->prepare("SELECT id FROM events_eventdata WHERE id = ? AND company_id = ?");
    $eventCheck->execute([$eventId, $companyId]);
    if (!$eventCheck->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet eller tilhører ikke ditt firma']);
        exit;
    }
    
    $db->beginTransaction();
    require_once __DIR__.'/../includes/active_payment_methods.php';
    activePaymentMethodsLock($db,(string)$companyId);
    
    // Verify all checkins belong to this event (multi-tenant safety)
    $placeholders = implode(',', array_fill(0, count($checkinIds), '?'));
    $stmt = $db->prepare("
        SELECT c.id, c.customer_id, c.ticket_price, c.payment_status 
        FROM events_checkin c
        JOIN events_eventdata e ON c.event_id = e.id
        WHERE c.id IN ($placeholders) 
        AND c.event_id = ?
        AND e.company_id = ?
    ");
    $params = array_merge($checkinIds, [$eventId, $companyId]);
    $stmt->execute($params);
    $checkins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($checkins) !== count($checkinIds)) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Noen gjester ble ikke funnet eller tilhører et annet firma']);
        exit;
    }
    
    // Calculate total for unpaid guests only
    $totalAmount = 0;
    $primaryPayerId = null;
    
    foreach ($checkins as $checkin) {
        if ($checkin['payment_status'] !== 'paid') {
            $totalAmount += floatval($checkin['ticket_price']);
        }
        if (!$primaryPayerId && $checkin['customer_id']) {
            $primaryPayerId = $checkin['customer_id'];
        }
    }
    
    $paymentMethod=requireActiveEventPayment($db,(string)$companyId,(string)$eventId,$paymentMethod,(float)$totalAmount);
    // Generate UUID for group
    $groupId = bin2hex(random_bytes(18));
    
    // Create group record
    $insertGroup = $db->prepare("
        INSERT INTO events_checkin_groups 
        (id, event_id, group_name, total_amount, payment_status, payment_method, paid_by_customer_id, created_by, created_at) 
        VALUES (?, ?, ?, ?, 'paid', ?, ?, ?, NOW())
    ");
    $groupName = 'Gruppe ' . date('H:i');
    $insertGroup->execute([$groupId, $eventId, $groupName, $totalAmount, $paymentMethod, $primaryPayerId, $adminId]);
    
    // Update each checkin
    $isFirst = true;
    $checkedInCount = 0;
    
    foreach ($checkins as $checkin) {
        $alreadyPaid = $checkin['payment_status'] === 'paid';
        
        // Preserve original payment method for already paid guests
        $updateCheckin = $db->prepare("
            UPDATE events_checkin 
            SET checked_in = 1, 
                check_in_time = NOW(),
                checked_in_by = ?,
                payment_method = CASE WHEN payment_status = 'paid' THEN payment_method ELSE ? END,
                payment_status = 'paid',
                group_id = ?,
                is_primary_payer = ?
            WHERE id = ?
        ");
        $updateCheckin->execute([
            $adminId, 
            $paymentMethod, 
            $groupId, 
            $isFirst ? 1 : 0,
            $checkin['id']
        ]);
        
        $checkedInCount++;
        $isFirst = false;
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Gruppe sjekket inn',
        'group_id' => $groupId,
        'checked_in_count' => $checkedInCount,
        'total_amount' => $totalAmount
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error creating checkin group: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved gruppesjekk: ' . $e->getMessage()
    ]);
}
