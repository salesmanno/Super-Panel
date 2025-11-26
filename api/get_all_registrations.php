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
            r.approved_at,
            r.approved_by,
            approver.first_name AS approver_first_name,
            approver.last_name AS approver_last_name,
            r.rejected_by,
            r.rejected_at,
            r.approval_reason,
            e.title AS event_title,
            e.start_date,
            e.start_time,
            e.id as event_id_sort
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id COLLATE utf8mb4_unicode_ci
        INNER JOIN tenant_customers c ON r.customer_id = c.id COLLATE utf8mb4_unicode_ci
        LEFT JOIN tenant_admins approver ON r.approved_by = approver.id
        WHERE r.company_id = ?
        ORDER BY e.title ASC, r.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['company_id']]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
                'registrations' => []
            ];
        }
        $groupedByEvent[$eventKey]['registrations'][] = $reg;
    }
    
    echo json_encode([
        'success' => true,
        'total_registrations' => count($registrations),
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
