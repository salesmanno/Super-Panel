<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['title']) || empty($data['start_date']) || empty($data['start_time'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }

    $db = getDBConnection();
    
    $stmt = $db->prepare("
        INSERT INTO tenant_events (
            id,
            company_id,
            title,
            description,
            max_attendees,
            min_attendees,
            age_limit,
            is_member_exclusive,
            event_address,
            event_city,
            event_postal,
            venue_name,
            start_time,
            start_date,
            event_image_url,
            ticket_price,
            created_by
        ) VALUES (
            UUID(),
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )
    ");
    
    $stmt->execute([
        $_SESSION['company_id'],
        $data['title'],
        $data['description'] ?? null,
        $data['max_attendees'] ?? null,
        $data['min_attendees'] ?? null,
        $data['age_limit'] ?? null,
        $data['is_member_exclusive'] ?? 0,
        $data['event_address'] ?? null,
        $data['event_city'] ?? null,
        $data['event_postal'] ?? null,
        $data['venue_name'] ?? null,
        $data['start_time'],
        $data['start_date'],
        $data['event_image_url'] ?? null,
        $data['ticket_price'] ?? 0,
        $_SESSION['tenant_admin_id']
    ]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Arrangement opprettet'
    ]);
    
} catch (Exception $e) {
    error_log("Error creating event: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
