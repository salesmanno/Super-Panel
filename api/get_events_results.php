<?php
session_start();
session_write_close();

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/event_results_helpers.php';
require_once __DIR__ . '/../includes/event_results_data.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $statusSql = "CASE
        WHEN TIMESTAMP(e.start_date, e.start_time) > NOW() THEN 'Kommende'
        WHEN TIMESTAMP(e.start_date, e.start_time) <= NOW()
             AND (
                 e.end_date IS NULL
                 OR TIMESTAMP(e.end_date, COALESCE(e.end_time, '23:59:59')) >= NOW()
             ) THEN 'Pågår'
        ELSE 'Avsluttet'
    END";

    $stmt = $db->prepare("
        SELECT
            e.id,
            e.company_id,
            e.title,
            e.start_date,
            e.start_time,
            e.ticket_price,
            e.is_active,
            {$statusSql} AS status
        FROM events_eventdata e
        WHERE e.company_id = ?
        ORDER BY e.start_date DESC, e.start_time DESC, e.id ASC
    ");
    $stmt->execute([$companyId]);
    $events = event_results_load($db, (string) $companyId, $stmt->fetchAll(PDO::FETCH_ASSOC))['events'];
    $events = array_values(array_filter($events, static fn(array $event): bool => (int)$event['attendee_count'] > 0));

    echo json_encode([
        'success' => true,
        'summary' => event_results_summary($events),
        'events' => $events,
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    error_log('get_events_results error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke hente arrangementsresultater',
    ], JSON_UNESCAPED_UNICODE);
}