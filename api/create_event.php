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
        
        // Validate registration_type and payment_timing
        $registrationType = $data['registration_type'] ?? 'signup';
        $paymentTiming = 'on_arrival'; // Default to on_arrival only (MySQL enum value)
        
        // ENFORCE: "Ingen påmelding" must be free (no pricing, no participant limits)
        if ($registrationType === 'none') {
            $data['ticket_price'] = 0;
            $data['max_attendees'] = null;
            $data['min_attendees'] = null;
            $data['registration_deadline'] = null;
            // Skip registration fields and membership pricing for 'none' type
        }
        
        // Calculate payment_timing based on selected options
        $hasEconomyPayment = false;
        if (isset($data['payment_timing_methods']) && is_array($data['payment_timing_methods'])) {
            foreach ($data['payment_timing_methods'] as $method) {
                if ($method !== 'on_arrival') {
                    $hasEconomyPayment = true;
                    break;
                }
            }
        }
        
        if ($hasEconomyPayment) {
            $paymentTiming = 'both'; // Both on_arrival and economy payment methods
        }
        
        // Validate binding type requires payment method
        if ($registrationType === 'binding' && !$hasEconomyPayment) {
            throw new Exception('Bindende arrangement krever at minst én betalingsmetode fra economy er valgt');
        }
        
        // 3a. Prepare payment methods array BEFORE INSERT
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
        
        // Convert payment methods array to JSON
        $paymentMethodsJson = json_encode($paymentMethodsArray);
        
        $memberverification = (isset($data['memberverification']) && $data['memberverification']) ? 1 : 0;
        
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
                
                cost_price,
                registration_type,
                payment_timing,
                payment_methods_json,
                memberverification,
                is_active,
                created_by,
                created_at,
                updated_at
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
                ?,
                ?,
                ?,
                ?,
                1,
                ?,
                NOW(),
                NOW()
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
            $data['cost_price'] ?? 0,
            $registrationType,
            $paymentTiming,
            $paymentMethodsJson,
            $memberverification,
            $_SESSION['tenant_admin_id']
        ]);
        
        // 3b. Save default payment method (first of the array, always cash)
        $defaultPaymentMethod = $paymentMethodsArray[0];
        $stmt = $db->prepare("UPDATE events_eventdata SET default_payment_method = ? WHERE id = ?");
        $stmt->execute([$defaultPaymentMethod, $eventId]);
        
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
        
        // 4b. Create registration fields for new event
        // LOGIC:
        // - If registration_type = 'none': Skip creating fields (no registration at all)
        // - If registration_type = 'signup' or 'binding': Create fields based on user selections
        //   - REQUIRED: User must select at least one contact field (email or phone)
        //   - Optional: User can select additional fields (street, zip, city, postal, age)
        
        $shouldCreateFields = ($registrationType !== 'none');
        
        if ($shouldCreateFields) {
            try {
                // STEP 1: Validate contact fields (at least one must be selected)
                $hasEmail = isset($data['field_email']) && $data['field_email'];
                $hasPhone = isset($data['field_phone']) && $data['field_phone'];
                
                if (!$hasEmail && !$hasPhone) {
                    throw new Exception('Minst ett kontaktfelt (E-post eller Telefonnummer) må være valgt');
                }
                
                // STEP 2: Begin creating fields (required + selected optional)
                $displayOrder = 1;
                
                // Add required name fields first
                $nameFields = [
                    'first_name' => ['label' => 'Fornavn', 'type' => 'text', 'required' => true],
                    'last_name' => ['label' => 'Etternavn', 'type' => 'text', 'required' => true]
                ];
                
                foreach ($nameFields as $fieldName => $fieldConfig) {
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
                        (int)$fieldConfig['required'],
                        1,
                        $displayOrder++
                    ]);
                }
                
                // Add contact fields (email and/or phone)
                // Note: At least one is REQUIRED (validated above)
                // But user can select both, or just one
                $contactFields = [
                    'email' => ['label' => 'E-postadresse', 'type' => 'email', 'required' => false],
                    'phone' => ['label' => 'Telefonnummer', 'type' => 'tel', 'required' => false]
                ];
                
                foreach ($contactFields as $fieldName => $fieldConfig) {
                    // Only create if user explicitly selected this field
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
                            (int)$fieldConfig['required'],
                            1,
                            $displayOrder++
                        ]);
                    }
                }
                
                // Add optional fields (street, zip, city, postal, age)
                // Note: These are all optional and only created if explicitly selected
                $optionalFields = [
                    'street' => ['label' => 'Gateadresse', 'type' => 'text'],
                    'zip' => ['label' => 'Postnummer', 'type' => 'text'],
                    'city' => ['label' => 'Poststed', 'type' => 'text'],
                    'postal' => ['label' => 'Postboks', 'type' => 'text'],
                    'age' => ['label' => 'Alder', 'type' => 'number']
                ];
                
                foreach ($optionalFields as $fieldName => $fieldConfig) {
                    // Only create if user explicitly selected this field
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
                            0,
                            1,
                            $displayOrder++
                        ]);
                    }
                }
            } catch (Exception $e) {
                error_log("Warning: Failed to create registration fields for event {$eventId}: " . $e->getMessage());
                // Validation error (e.g., no contact field selected) - re-throw to block event creation
                throw $e;
            }
        } else {
            // No fields will be created because:
            // - registration_type = 'none' means "Ingen påmelding" (no registration at all)
            // - No registration = no fields needed
        }
        
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
        
        // Sync name, description, and price to output_products
        $stmt = $db->prepare("
            UPDATE output_products 
            SET name = ?, 
                description = ?,
                price = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['ticket_price'] ?? 0,
            $outputProductId
        ]);
        
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
