<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autorisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database tilkobling feilet']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

$eventId = $input['event_id'] ?? null;
$title = $input['title'] ?? null;
$description = $input['description'] ?? null;
$startDate = $input['start_date'] ?? null;
$startTime = $input['start_time'] ?? null;
$endDate = $input['end_date'] ?? null;
$endTime = $input['end_time'] ?? null;
$venueName = $input['venue_name'] ?? null;
$eventAddress = $input['event_address'] ?? null;
$eventCity = $input['event_city'] ?? null;
$eventPostal = $input['event_postal'] ?? null;
$minAttendees = $input['min_attendees'] ?? null;
$maxAttendees = $input['max_attendees'] ?? null;
$ageLimit = $input['age_limit'] ?? null;
$ticketPrice = $input['ticket_price'] ?? 0;
$memberTicketPrice = $input['member_ticket_price'] ?? null;
$eventImageUrl = $input['event_image_url'] ?? null;
$isMemberExclusive = $input['is_member_exclusive'] ?? 0;

if (!$eventId || !$title || !$startDate || !$startTime) {
    echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
    exit;
}

try {
    // Verify event belongs to this company
    $stmt = $db->prepare("SELECT company_id FROM events_eventdata WHERE id = ?");
    $stmt->execute([$eventId]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event || $event['company_id'] != $_SESSION['company_id']) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet']);
        exit;
    }
    
    // Update event
    $sql = "UPDATE events_eventdata SET 
            title = ?,
            description = ?,
            start_date = ?,
            start_time = ?,
            end_date = ?,
            end_time = ?,
            venue_name = ?,
            event_address = ?,
            event_city = ?,
            event_postal = ?,
            min_attendees = ?,
            max_attendees = ?,
            age_limit = ?,
            ticket_price = ?,
            member_ticket_price = ?,
            event_image_url = ?,
            is_member_exclusive = ?
            WHERE id = ? AND company_id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $title,
        $description,
        $startDate,
        $startTime,
        $endDate,
        $endTime,
        $venueName,
        $eventAddress,
        $eventCity,
        $eventPostal,
        $minAttendees,
        $maxAttendees,
        $ageLimit,
        $ticketPrice,
        $memberTicketPrice,
        $eventImageUrl,
        $isMemberExclusive,
        $eventId,
        $_SESSION['company_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Arrangement oppdatert',
        'event_id' => $eventId
    ]);
    
} catch (Exception $e) {
    error_log("Edit event error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database feil']);
}
