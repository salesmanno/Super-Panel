<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ProductCatalogService.php';
require_once __DIR__ . '/../includes/ShareLinkService.php';
require_once __DIR__ . '/../includes/request_date_time.php';
require_once __DIR__ . '/../includes/event_image_url.php';
require_once __DIR__ . '/../includes/entity_form_fields.php';
require_once __DIR__ . '/../includes/event_settings.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    entityFormRequireCsrf($data ?? []);
    
    // Validate required fields
    if (empty($data['title']) || empty($data['start_date']) || empty($data['start_time'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }

    $data['start_date'] = normalizeRequestDate($data['start_date'], 'Startdato', false);
    $data['start_time'] = normalizeRequestTime($data['start_time'], 'Starttid', false);
    $data['end_date'] = normalizeRequestDate($data['end_date'] ?? null, 'Sluttdato');
    $data['end_time'] = normalizeRequestTime($data['end_time'] ?? null, 'Sluttid');
    $data['registration_deadline'] = normalizeRequestDate(
        $data['registration_deadline'] ?? null,
        'Påmeldingsfrist'
    );
    $data['event_image_url'] = normalizeEventImageUrl($data['event_image_url'] ?? null);
    validateDateIsNotPast($data['start_date']);

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
        
        // Generate unique share_code for this event
        $shareLinkService = new ShareLinkService($db, $_SESSION['company_id']);
        $shareCode = $shareLinkService->generateCode();
        
        $settings = eventSettings($db, (string)$_SESSION['company_id'], $data);
        $registrationType = $settings['registration_type'];
        
        // ENFORCE: "Ingen påmelding" must be free (no pricing, no participant limits)
        if ($registrationType === 'none') {
            $data['ticket_price'] = 0;
            $data['max_attendees'] = null;
            $data['min_attendees'] = null;
            $data['registration_deadline'] = null;
            // Skip registration fields and membership pricing for 'none' type
        }
        
        $paymentMethodsJson = $settings['payment_methods_json'];
        $paymentTiming = $settings['payment_timing'];
        $defaultPaymentMethod = $settings['default_payment_method'];
        $memberverification = $settings['memberverification'];
        $data['is_member_exclusive'] = $settings['is_member_exclusive'];
        
        // INSERT order matches actual table schema (cost_price removed per user request):
        // id, company_id, title, description, start_date, start_time, end_date, end_time,
        // venue_name, event_address, event_city, event_postal, max_attendees, registration_deadline,
        // min_attendees, age_limit, is_member_exclusive, ticket_price, event_image_url,
        // is_active, created_by, created_at, updated_at, default_payment_method, payment_timing,
        // registration_type, payment_methods_json, memberverification
        $stmt = $db->prepare("
            INSERT INTO events_eventdata (
                id,
                company_id,
                title,
                description,
                start_date,
                start_time,
                end_date,
                end_time,
                venue_name,
                event_address,
                event_city,
                event_postal,
                max_attendees,
                registration_deadline,
                min_attendees,
                age_limit,
                is_member_exclusive,
                ticket_price,
                event_image_url,
                is_active,
                created_by,
                created_at,
                updated_at,
                default_payment_method,
                payment_timing,
                registration_type,
                payment_methods_json,
                memberverification,
                share_code
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?, ?, ?, ?,
                1, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?
            )
        ");
        
        $stmt->execute([
            $eventId,
            $_SESSION['company_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['start_date'],
            $data['start_time'],
            $data['end_date'] ?? null,
            $data['end_time'] ?? null,
            $data['venue_name'] ?? null,
            $data['event_address'] ?? null,
            $data['event_city'] ?? null,
            $data['event_postal'] ?? null,
            $data['max_attendees'] ?? null,
            $data['registration_deadline'] ?? null,
            $data['min_attendees'] ?? null,
            $data['age_limit'] ?? null,
            $data['is_member_exclusive'] ?? 0,
            $data['ticket_price'] ?? 0,
            $data['event_image_url'] ?? null,
            $_SESSION['tenant_admin_id'],
            $defaultPaymentMethod,
            $paymentTiming,
            $registrationType,
            $paymentMethodsJson,
            $memberverification,
            $shareCode
        ]);
        
        // 4. Save membership pricing if provided (skip for 'none' type)
        if ($registrationType !== 'none' && isset($data['membership_pricing']) && is_array($data['membership_pricing'])) {
            try {
                $stmt = $db->prepare("
                    INSERT INTO event_membership_plans (event_id, membership_plan_id, ticket_price, public_display, created_at)
                    VALUES (?, ?, ?, ?, NOW())
                ");
                
                foreach ($data['membership_pricing'] as $pricing) {
                    if (isset($pricing['plan_id']) && isset($pricing['price'])) {
                        // Verify membership plan belongs to company
                        $planCheck = $db->prepare("SELECT company_id FROM membership_plans WHERE id = ?");
                        $planCheck->execute([$pricing['plan_id']]);
                        $plan = $planCheck->fetch(PDO::FETCH_ASSOC);
                        
                        if ($plan && $plan['company_id'] === $_SESSION['company_id']) {
                            $publicDisplay = isset($pricing['public_display']) ? (int)$pricing['public_display'] : 1;
                            $stmt->execute([$eventId, $pricing['plan_id'], $pricing['price'], $publicDisplay]);
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Warning: Failed to save membership pricing for event {$eventId}: " . $e->getMessage());
            }
        }
        
        $formFields = $registrationType === 'none' ? [] : ($data['form_fields'] ?? entityFormEventSelection($data));
        if (!is_array($formFields)) throw new InvalidArgumentException('Ugyldig feltoppsett');
        entityFormSave($db, (string)$_SESSION['company_id'], 'event', $eventId, $formFields);
        
        // 5. Auto-generate output_products entry using service
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
        
        // Link output_products back to events_eventdata
        $stmt = $db->prepare("
            UPDATE events_eventdata 
            SET output_product_id = ?
            WHERE id = ?
        ");
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
    
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error creating event: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
