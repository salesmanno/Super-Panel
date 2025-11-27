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
            e.title AS event_title,
            e.start_date,
            e.start_time
        FROM event_registrations r
        INNER JOIN events_eventdata e ON r.event_id = e.id COLLATE utf8mb4_unicode_ci
        INNER JOIN tenant_customers c ON r.customer_id = c.id COLLATE utf8mb4_unicode_ci
        WHERE r.company_id = ?
        AND r.approval_status = 'pending'
        ORDER BY e.title ASC, r.created_at DESC
    ");
    
    $stmt->execute([$_SESSION['company_id']]);
    $registrations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $groupedByEvent = [];
    foreach ($registrations as $reg) {
        $eventKey = $reg['event_id'];
        if (!isset($groupedByEvent[$eventKey])) {
            $groupedByEvent[$eventKey] = [
                'event_id' => $reg['event_id'],
                'event_title' => $reg['event_title'],
                'event_start_date' => $reg['start_date'],
                'event_start_time' => $reg['start_time'],
                'count' => 0,
                'registrations' => []
            ];
        }
        $groupedByEvent[$eventKey]['registrations'][] = $reg;
        $groupedByEvent[$eventKey]['count']++;
    }
    
    echo json_encode([
        'success' => true,
        'total_pending' => count($registrations),
        'grouped_by_event' => array_values($groupedByEvent)
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching pending registrations: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av påmeldinger: ' . $e->getMessage()
    ]);
}
