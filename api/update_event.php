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

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['event_id']) || empty($data['title']) || empty($data['start_date']) || empty($data['start_time'])) {
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

    $db = getDBConnection();
    
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
        $stmt = $db->prepare("SELECT id FROM events_eventdata WHERE id = ? AND company_id = ?");
        $stmt->execute([$data['event_id'], $_SESSION['company_id']]);
        if (!$stmt->fetch()) {
            throw new Exception('Arrangement ikke funnet');
        }
        
        // Validate registration_type and payment_timing
        // If payment_timing_methods is not provided, fetch existing data to avoid breaking edits
        $registrationType = $data['registration_type'] ?? null;
        $paymentTiming = null;
        
        if (isset($data['payment_timing_methods']) && is_array($data['payment_timing_methods'])) {
            // New flow: calculate from provided data
            $hasEconomyPayment = false;
            foreach ($data['payment_timing_methods'] as $method) {
                if ($method !== 'on_arrival') {
                    $hasEconomyPayment = true;
                    break;
                }
            }
            
            $paymentTiming = $hasEconomyPayment ? 'both' : 'on_arrival';
            
            // Validate binding type requires payment method
            if ($registrationType === 'binding' && !$hasEconomyPayment) {
                throw new Exception('Bindende arrangement krever at minst én betalingsmetode fra economy er valgt');
            }
        } else {
            // Legacy flow: fetch existing values from database
            $stmt = $db->prepare("SELECT registration_type, payment_timing FROM events_eventdata WHERE id = ? AND company_id = ?");
            $stmt->execute([$data['event_id'], $_SESSION['company_id']]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                $registrationType = $registrationType ?? $existing['registration_type'] ?? 'signup';
                $paymentTiming = $existing['payment_timing'] ?? 'on_arrival';
            } else {
                $registrationType = $registrationType ?? 'signup';
                $paymentTiming = 'on_arrival';
            }
        }
        
        // ENFORCE: "Ingen påmelding" must be free (no pricing, no participant limits)
        if ($registrationType === 'none') {
            $data['ticket_price'] = 0;
            $data['ticket_price'] = null;
            $data['max_attendees'] = null;
            $data['min_attendees'] = null;
            $data['registration_deadline'] = null;
            
            // Delete all membership pricing for this event
            $stmt = $db->prepare("DELETE FROM event_membership_plans WHERE event_id = ?");
            $stmt->execute([$data['event_id']]);
        }
        
        // Prepare payment methods array for update
        // Payment methods are controlled by Economy module configuration
        $paymentMethodsJson = null;
        if (isset($data['allowed_payment_methods']) && is_array($data['allowed_payment_methods']) && count($data['allowed_payment_methods']) > 0) {
            $paymentMethodsArray = $data['allowed_payment_methods'];
            $paymentMethodsJson = json_encode($paymentMethodsArray);
        }
        
        // Update the event in events_eventdata
        $memberverification = (isset($data['memberverification']) && $data['memberverification']) ? 1 : 0;
        
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
        
        // Update registration fields config (skip for 'none' type)
        if ($registrationType !== 'none') {
            // Check if field selections were provided in the request
            $fieldSelectionsProvided = isset($data['field_email']) || isset($data['field_phone']) || 
                                      isset($data['field_street']) || isset($data['field_zip']) ||
                                      isset($data['field_city']) || isset($data['field_postal']) ||
                                      isset($data['field_age']);
            
            // Only update fields if selections were provided
            if ($fieldSelectionsProvided) {
                // Validate that at least one contact field (email or phone) is selected
                $hasEmail = isset($data['field_email']) && $data['field_email'];
                $hasPhone = isset($data['field_phone']) && $data['field_phone'];
                
                if (!$hasEmail && !$hasPhone) {
                    throw new Exception('Minst ett kontaktfelt (E-post eller Telefonnummer) må være valgt');
                }
                
                // Delete all existing fields except required name fields
                $stmt = $db->prepare("
                    DELETE FROM event_registration_fields 
                    WHERE event_id = ? AND field_name NOT IN ('first_name', 'last_name')
                ");
                $stmt->execute([$data['event_id']]);
            
            // Add contact fields (email and/or phone) - at least one required
            $contactFields = [
                'email' => ['label' => 'E-postadresse', 'type' => 'email', 'required' => false],
                'phone' => ['label' => 'Telefonnummer', 'type' => 'tel', 'required' => false]
            ];
            
            $displayOrder = 3; // After first_name (1) and last_name (2)
            foreach ($contactFields as $fieldName => $fieldConfig) {
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
                        $data['event_id'],
                        $fieldName,
                        $fieldConfig['label'],
                        $fieldConfig['type'],
                        (int)$fieldConfig['required'],
                        1,
                        $displayOrder++
                    ]);
                }
            }
            
            // Add other optional fields if selected
            $optionalFields = [
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
                        $data['event_id'],
                        $fieldName,
                        $fieldConfig['label'],
                        $fieldConfig['type'],
                        0,
                        1,
                        $displayOrder++
                    ]);
                }
            }
            // else: skip field updates if no field selections were provided (preserve existing config)
            } else {
                // For 'none' type, delete all registration fields
                $stmt = $db->prepare("DELETE FROM event_registration_fields WHERE event_id = ?");
                $stmt->execute([$data['event_id']]);
            }
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
