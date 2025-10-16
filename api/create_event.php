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
    $db->beginTransaction();
    
    try {
        // 1. Create the event in events_eventdata
        $eventId = bin2hex(random_bytes(16));
        
        $stmt = $db->prepare("
            INSERT INTO events_eventdata (
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
                end_time,
                end_date,
                event_image_url,
                ticket_price,
                member_ticket_price,
                created_by
            ) VALUES (
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
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");
        
        $stmt->execute([
            $eventId,
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
            $data['end_time'] ?? null,
            $data['end_date'] ?? null,
            $data['event_image_url'] ?? null,
            $data['ticket_price'] ?? 0,
            $data['member_ticket_price'] ?? null,
            $_SESSION['tenant_admin_id']
        ]);
        
        // 2. Get events module ID
        $stmt = $db->prepare("SELECT id FROM system_modules WHERE module_key = 'events'");
        $stmt->execute();
        $moduleId = $stmt->fetchColumn();
        
        if (!$moduleId) {
            throw new Exception('Events module ikke funnet');
        }
        
        // 3. Create output_products entry
        $outputProductId = bin2hex(random_bytes(16));
        
        $stmt = $db->prepare("
            INSERT INTO output_products (
                id,
                company_id,
                module_id,
                product_type,
                product_id,
                is_visible,
                is_public,
                is_marketplace_featured,
                created_by
            ) VALUES (
                ?,
                ?,
                ?,
                'event',
                ?,
                TRUE,
                TRUE,
                FALSE,
                ?
            )
        ");
        
        $stmt->execute([
            $outputProductId,
            $_SESSION['company_id'],
            $moduleId,
            $eventId,
            $_SESSION['tenant_admin_id']
        ]);
        
        // 4. Link output_product back to event
        $stmt = $db->prepare("UPDATE events_eventdata SET output_product_id = ? WHERE id = ?");
        $stmt->execute([$outputProductId, $eventId]);
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Arrangement opprettet',
            'event_id' => $eventId,
            'output_product_id' => $outputProductId
        ]);
        
    } catch (Exception $e) {
        $db->rollBack();
        throw $e;
    }
    
} catch (Exception $e) {
    error_log("Error creating event: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
