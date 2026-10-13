<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/ProductCatalogService.php';
require_once __DIR__ . '/../includes/request_date_time.php';
require_once __DIR__ . '/../includes/event_image_url.php';
require_once __DIR__ . '/../includes/entity_form_fields.php';
require_once __DIR__ . '/../includes/event_settings.php';
require_once __DIR__ . '/../includes/event_membership_prices.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    entityFormRequireCsrf($data ?? []);
    
    // Validate required fields
    if (empty($data['event_id']) || empty($data['title']) || empty($data['start_date']) || empty($data['start_time'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }

    $data['start_date'] = normalizeRequestDate($data['start_date'], 'Startdato', false);
    $data['start_time'] = normalizeRequestTime($data['start_time'], 'Starttid', false);
    $data['event_image_url'] = normalizeEventImageUrl($data['event_image_url'] ?? null);
    $data['end_date'] = normalizeRequestDate($data['end_date'] ?? null, 'Sluttdato');
    $data['end_time'] = normalizeRequestTime($data['end_time'] ?? null, 'Sluttid');
    $data['registration_deadline'] = normalizeRequestDate(
        $data['registration_deadline'] ?? null,
        'Påmeldingsfrist'
    );

    $db = getDBConnection();
    entityFormRequireModuleAccess($db, (string)($_SESSION['company_id'] ?? ''), 'event');
    
    // Server-side validation for registration_deadline
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
        // Verify event exists and belongs to this company
        $stmt = $db->prepare("SELECT * FROM events_eventdata WHERE id = ? AND company_id = ? FOR UPDATE");
        $stmt->execute([$data['event_id'], $_SESSION['company_id']]);
        $existingEvent = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$existingEvent) {
            throw new Exception('Arrangement ikke funnet');
        }
        
        $settings = eventSettings($db, (string)$_SESSION['company_id'], $data, $existingEvent);
        if (array_key_exists('membership_prices', $data)) {
            if (!eventSettingsHasMembershipAccess($db, (string)$_SESSION['company_id'])) {
                throw new TenantAuditException('Du trenger Membership-modulen for å endre medlemspriser.');
            }
            if (!is_array($data['membership_prices']) || !array_is_list($data['membership_prices'])) {
                throw new InvalidArgumentException('Ugyldig medlemsprisoppsett');
            }
            saveEventMembershipPrices($db, (string)$_SESSION['company_id'], (string)$data['event_id'], $data['membership_prices']);
        }
        // A date-only edit must not erase the time component of an unchanged deadline.
        $oldDeadline = $existingEvent['registration_deadline'] ?? null;
        if ($data['registration_deadline'] !== null && is_string($oldDeadline) &&
            preg_match('/^(\d{4}-\d{2}-\d{2}) \d{2}:\d{2}:\d{2}$/', $oldDeadline, $deadlineParts) &&
            $data['registration_deadline'] === $deadlineParts[1]) {
            $data['registration_deadline'] = $oldDeadline;
        }
        $registrationType = $settings['registration_type'];
        
        // ENFORCE: "Ingen påmelding" must be free (no pricing, no participant limits)
        if ($registrationType === 'none') {
            $data['ticket_price'] = 0;
            $data['max_attendees'] = null;
            $data['min_attendees'] = null;
            $data['registration_deadline'] = null;
            
            // Hidden membership settings/prices must survive ordinary basic edits.
        }
        
        $paymentTiming = $settings['payment_timing'];
        $paymentMethodsJson = $settings['payment_methods_json'];
        $memberverification = $settings['memberverification'];
        $data['is_member_exclusive'] = $settings['is_member_exclusive'];
        $data['default_payment_method'] = $settings['default_payment_method'];
        
        $stmt = $db->prepare("
            UPDATE events_eventdata SET
                title = ?,
                description = ?,
                max_attendees = ?,
                min_attendees = ?,
                registration_deadline = ?,
                age_limit = ?,
                is_member_exclusive = ?,
                event_address = ?,
                event_city = ?,
                event_postal = ?,
                venue_name = ?,
                start_time = ?,
                start_date = ?,
                end_time = ?,
                end_date = ?,
                event_image_url = ?,
                ticket_price = ?,
                default_payment_method = ?,
                registration_type = ?,
                payment_timing = ?,
                payment_methods_json = ?,
                memberverification = ?,
                updated_at = NOW()
            WHERE id = ? AND company_id = ?
        ");
        
        $stmt->execute([
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
            $data['default_payment_method'] ?? null,
            $registrationType,
            $paymentTiming,
            $paymentMethodsJson,
            $memberverification,
            $data['event_id'],
            $_SESSION['company_id']
        ]);
        
        // Basic edits never resave fields, including switching signup on/off.
        // registration_type controls visibility; preserve the configured definitions.
        if (array_key_exists('form_fields', $data)) {
            if (!is_array($data['form_fields'])) throw new InvalidArgumentException('Ugyldig feltoppsett');
            entityFormSave($db, (string)$_SESSION['company_id'], 'event', (string)$data['event_id'], $data['form_fields']);
        }
        
        // Update allowed payment methods - controlled entirely by Economy module configuration
        // Note: payment_methods_json was already updated in the main UPDATE statement above
        // Only update default_payment_method if explicitly provided
        if (isset($data['default_payment_method']) && !empty($data['default_payment_method'])) {
            $stmt = $db->prepare("UPDATE events_eventdata SET default_payment_method = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$data['default_payment_method'], $data['event_id'], $_SESSION['company_id']]);
        }
        
        // Sync output_products entry using service
        $catalogService = new ProductCatalogService($db);
        $catalogService->syncProductData($_SESSION['company_id'], 'events');
        
        $db->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Arrangement oppdatert',
            'event_id' => $data['event_id']
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
    error_log("Error updating event: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
