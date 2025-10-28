<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    // Sjekk om deleted_at kolonnen eksisterer
    $hasDeletedAt = false;
    try {
        $dbType = getDBType();
        if ($dbType === 'mysql') {
            $stmt = $db->query("SHOW COLUMNS FROM events_eventdata LIKE 'deleted_at'");
            $hasDeletedAt = $stmt->rowCount() > 0;
        } else {
            $stmt = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = 'events_eventdata' AND column_name = 'deleted_at'");
            $hasDeletedAt = $stmt->rowCount() > 0;
        }
    } catch (Exception $e) {
        // Ignore error, assume no deleted_at
    }
    
    $deletedFilter = $hasDeletedAt ? " AND e.deleted_at IS NULL" : "";
    
    // Hent totalt antall arrangementer
    $stmt = $db->prepare("
        SELECT COUNT(*) as total 
        FROM events_eventdata e
        WHERE e.company_id = ?" . $deletedFilter . "
    ");
    $stmt->execute([$companyId]);
    $totalEvents = $stmt->fetchColumn();
    
    // Hent total inntekt fra events_checkin
    $stmt = $db->prepare("
        SELECT COALESCE(SUM(ec.ticket_price), 0) as revenue
        FROM events_checkin ec
        INNER JOIN events_eventdata e ON ec.event_id = e.id
        WHERE e.company_id = ?" . $deletedFilter . "
        AND ec.payment_status = 'paid'
    ");
    $stmt->execute([$companyId]);
    $totalRevenue = $stmt->fetchColumn();
    
    // Hent totale påmeldinger
    $stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM events_checkin ec
        INNER JOIN events_eventdata e ON ec.event_id = e.id
        WHERE e.company_id = ?" . $deletedFilter . "
    ");
    $stmt->execute([$companyId]);
    $totalAttendees = $stmt->fetchColumn();
    
    // Hent kommende arrangementer
    $dbType = getDBType();
    $nowComparison = $dbType === 'pgsql' 
        ? "(e.start_date || ' ' || e.start_time)::timestamp > NOW()"
        : "CONCAT(e.start_date, ' ', e.start_time) > NOW()";
    
    $stmt = $db->prepare("
        SELECT COUNT(*) as upcoming
        FROM events_eventdata e
        WHERE e.company_id = ?" . $deletedFilter . "
        AND " . $nowComparison . "
    ");
    $stmt->execute([$companyId]);
    $upcomingEvents = $stmt->fetchColumn();
    
    // Hent detaljert liste over arrangementer med statistikk
    $statusCase = $dbType === 'pgsql' 
        ? "CASE 
            WHEN (e.start_date || ' ' || e.start_time)::timestamp > NOW() THEN 'Kommende'
            WHEN (e.start_date || ' ' || e.start_time)::timestamp <= NOW() AND 
                 (e.end_date IS NULL OR (e.end_date || ' ' || COALESCE(e.end_time, '23:59:59'))::timestamp >= NOW()) THEN 'Pågår'
            ELSE 'Avsluttet'
        END"
        : "CASE 
            WHEN CONCAT(e.start_date, ' ', e.start_time) > NOW() THEN 'Kommende'
            WHEN CONCAT(e.start_date, ' ', e.start_time) <= NOW() AND 
                 (e.end_date IS NULL OR CONCAT(e.end_date, ' ', COALESCE(e.end_time, '23:59:59')) >= NOW()) THEN 'Pågår'
            ELSE 'Avsluttet'
        END";
    
    $stmt = $db->prepare("
        SELECT 
            e.id,
            e.title,
            e.start_date,
            e.start_time,
            e.ticket_price,
            COUNT(ec.id) as attendee_count,
            COALESCE(SUM(CASE WHEN ec.payment_status = 'paid' THEN ec.ticket_price ELSE 0 END), 0) as event_revenue,
            " . $statusCase . " as status
        FROM events_eventdata e
        LEFT JOIN events_checkin ec ON e.id = ec.event_id
        WHERE e.company_id = ?" . $deletedFilter . "
        GROUP BY e.id, e.title, e.start_date, e.start_time, e.ticket_price
        ORDER BY e.start_date DESC, e.start_time DESC
    ");
    $stmt->execute([$companyId]);
    $eventsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'summary' => [
            'total_events' => $totalEvents,
            'total_revenue' => number_format($totalRevenue, 2, '.', ''),
            'total_attendees' => $totalAttendees,
            'upcoming_events' => $upcomingEvents
        ],
        'events' => $eventsList
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching events results: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Kunne ikke hente arrangement resultat'
    ]);
}
