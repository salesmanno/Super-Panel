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
    if (empty($data['event_id']) || empty($data['title']) || empty($data['start_date']) || empty($data['start_time'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }

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
                if ($method !== 'arrival') {
                    $hasEconomyPayment = true;
                    break;
                }
            }
            
            $paymentTiming = $hasEconomyPayment ? 'both' : 'arrival';
            
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
                $paymentTiming = $existing['payment_timing'] ?? 'arrival';
            } else {
                $registrationType = $registrationType ?? 'signup';
                $paymentTiming = 'arrival';
            }
        }
        
        // ENFORCE: "Ingen påmelding" must be free (no pricing, no participant limits)
        if ($registrationType === 'none') {
            $data['ticket_price'] = 0;
            $data['member_ticket_price'] = null;
            $data['max_attendees'] = null;
            $data['min_attendees'] = null;
            $data['registration_deadline'] = null;
            
            // Delete all membership pricing for this event
            $stmt = $db->prepare("DELETE FROM event_membership_plans WHERE event_id = ?");
            $stmt->execute([$data['event_id']]);
        }
        
        // Update the event in events_eventdata
        // Prepare payment methods JSON
        $paymentMethodsJson = null;
        if (isset($data['allowed_payment_methods']) && is_array($data['allowed_payment_methods']) && count($data['allowed_payment_methods']) > 0) {
            $paymentMethodsJson = json_encode($data['allowed_payment_methods']);
        }
        
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
                member_ticket_price = ?,
                default_payment_method = ?,
                registration_type = ?,
                payment_timing = ?,
                payment_methods_json = ?
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
            $data['member_ticket_price'] ?? null,
            $data['default_payment_method'] ?? null,
            $registrationType,
            $paymentTiming,
            $paymentMethodsJson,
            $data['event_id'],
            $_SESSION['company_id']
        ]);
        
        // Update registration fields config (skip for 'none' type)
        if ($registrationType !== 'none') {
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
        } else {
            // For 'none' type, delete all registration fields
            $stmt = $db->prepare("DELETE FROM event_registration_fields WHERE event_id = ?");
            $stmt->execute([$data['event_id']]);
        }
        
        // Update allowed payment methods
        $paymentMethodsArray = [];
        
        if (isset($data['allowed_payment_methods']) && is_array($data['allowed_payment_methods']) && count($data['allowed_payment_methods']) > 0) {
            // Use provided payment methods
            $paymentMethodsArray = $data['allowed_payment_methods'];
        } elseif (isset($data['default_payment_method']) && !empty($data['default_payment_method'])) {
            // Legacy: Use single default_payment_method if provided
            $paymentMethodsArray = [$data['default_payment_method']];
        } else {
            // Fallback: Use company cash setting or default to cash
            $companyStmt = $db->prepare("SELECT Cash_payment FROM tenant_companies WHERE id = ?");
            $companyStmt->execute([$_SESSION['company_id']]);
            $company = $companyStmt->fetch(PDO::FETCH_ASSOC);
            if ($company && $company['Cash_payment']) {
                $paymentMethodsArray = ['cash'];
            } else {
                $paymentMethodsArray = ['card'];
            }
        }
        
        // ENFORCE: Cash must always be included (insert as first element if not present)
        if (!in_array('cash', $paymentMethodsArray)) {
            array_unshift($paymentMethodsArray, 'cash');
        } elseif ($paymentMethodsArray[0] !== 'cash') {
            // Move cash to first position if present but not first
            $cashIndex = array_search('cash', $paymentMethodsArray);
            unset($paymentMethodsArray[$cashIndex]);
            array_unshift($paymentMethodsArray, 'cash');
            $paymentMethodsArray = array_values($paymentMethodsArray); // Re-index
        }
        
        $defaultPaymentMethod = $paymentMethodsArray[0]; // First method is always default (now guaranteed to be cash)
        
        // Update default_payment_method
        $stmt = $db->prepare("UPDATE events_eventdata SET default_payment_method = ? WHERE id = ? AND company_id = ?");
        $stmt->execute([$defaultPaymentMethod, $data['event_id'], $_SESSION['company_id']]);
        
        // Sync output_products entry using service
        $catalogService = new ProductCatalogService($db);
        $catalogService->syncProductData('events', $data['event_id']);
        
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
    
} catch (Exception $e) {
    error_log("Error updating event: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
