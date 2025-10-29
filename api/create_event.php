<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ProductCatalogService.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['title']) || empty($data['start_date']) || empty($data['start_time'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }

    $db = getDBConnection();
    
    // 1. Ensure columns exist (outside transaction)
    try {
        $db->exec("ALTER TABLE events_eventdata ADD COLUMN default_payment_method VARCHAR(50) DEFAULT NULL");
    } catch (Exception $e) {
        // Column already exists, ignore
    }
    
    // 2. Server-side validation for registration_deadline
    if (!empty($data['min_attendees']) && intval($data['min_attendees']) > 0) {
        if (empty($data['registration_deadline'])) {
            echo json_encode(['success' => false, 'message' => 'Frist for min. deltakere er påkrevd når min. deltakere er satt']);
            exit;
        }
        if ($data['registration_deadline'] >= $data['start_date']) {
            echo json_encode(['success' => false, 'message' => 'Frist for min. deltakere må være før startdato']);
            exit;
        }
    }
    
    $db->beginTransaction();
    
    try {
        // 3. Create the event in events_eventdata
        $eventId = bin2hex(random_bytes(16));
        
        $stmt = $db->prepare("
            INSERT INTO events_eventdata (
                id,
                company_id,
                title,
                description,
                max_attendees,
                min_attendees,
                registration_deadline,
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
                default_payment_method,
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
            $data['registration_deadline'] ?? null,
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
            $data['default_payment_method'] ?? null,
            $_SESSION['tenant_admin_id']
        ]);
        
        // 2. Save registration fields config
        // Always add standard fields first (first_name, last_name, email)
        $standardFields = [
            'first_name' => ['label' => 'Fornavn', 'type' => 'text', 'required' => true],
            'last_name' => ['label' => 'Etternavn', 'type' => 'text', 'required' => true],
            'email' => ['label' => 'E-postadresse', 'type' => 'email', 'required' => true]
        ];
        
        $displayOrder = 1;
        foreach ($standardFields as $fieldName => $fieldConfig) {
            $fieldId = bin2hex(random_bytes(16));
            $stmt = $db->prepare("
                INSERT INTO event_registration_fields (
                    id, event_id, field_name, field_label, field_type,
                    is_required, is_visible, display_order
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $fieldId,
                $eventId,
                $fieldName,
                $fieldConfig['label'],
                $fieldConfig['type'],
                $fieldConfig['required'],
                true,
                $displayOrder++
            ]);
        }
        
        // Then add optional fields if selected
        $optionalFields = [
            'phone' => ['label' => 'Telefonnummer', 'type' => 'tel'],
            'street' => ['label' => 'Gateadresse', 'type' => 'text'],
            'zip' => ['label' => 'Postnummer', 'type' => 'text'],
            'city' => ['label' => 'Poststed', 'type' => 'text'],
            'postal' => ['label' => 'Postboks', 'type' => 'text'],
            'age' => ['label' => 'Alder', 'type' => 'number']
        ];
        
        foreach ($optionalFields as $fieldName => $fieldConfig) {
            if (isset($data['field_' . $fieldName]) && $data['field_' . $fieldName]) {
                $fieldId = bin2hex(random_bytes(16));
                $stmt = $db->prepare("
                    INSERT INTO event_registration_fields (
                        id, event_id, field_name, field_label, field_type,
                        is_required, is_visible, display_order
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $fieldId,
                    $eventId,
                    $fieldName,
                    $fieldConfig['label'],
                    $fieldConfig['type'],
                    false,
                    true,
                    $displayOrder++
                ]);
            }
        }
        
        // 3. Auto-generate output_products entry using service
        $catalogService = new ProductCatalogService($db);
        $outputProductId = $catalogService->createProductEntry(
            $_SESSION['company_id'],
            'events',
            $eventId,
            [
                'is_visible' => true,
                'is_public' => true,
                'is_marketplace_featured' => false
            ],
            $_SESSION['tenant_admin_id']
        );
        
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
