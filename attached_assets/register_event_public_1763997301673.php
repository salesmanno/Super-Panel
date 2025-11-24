<?php
// Public event registration endpoint (no authentication required)
// Supports both single and multi-attendee registration
// Streamlined flow: event_registrations → events_checkin (if approved)
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');
mb_internal_encoding('UTF-8');

require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Ugyldig forespørsel']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['event_id'])) {
    echo json_encode(['success' => false, 'message' => 'Manglende påkrevde felt']);
    exit;
}

// Check if this is a multi-registration request
$isMultiRegistration = isset($input['attendees']) && is_array($input['attendees']) && count($input['attendees']) > 0;

// Convert single registration to multi-registration format for unified processing
if (!$isMultiRegistration) {
    // Legacy single registration - convert to array format
    $input['attendees'] = [$input];
}

$attendees = $input['attendees'];
$totalAttendees = count($attendees);

if ($totalAttendees === 0) {
    echo json_encode(['success' => false, 'message' => 'Ingen deltakere oppgitt']);
    exit;
}

try {
    $db = getDBConnection();
    
    $event_id = $input['event_id'];
    
    // ========== PHASE 1: PRE-TRANSACTION VALIDATION (SHARED CHECKS) ==========
    
    // Fetch event details
    $stmt = $db->prepare("
        SELECT 
            ed.id, 
            ed.company_id, 
            ed.title, 
            ed.max_attendees, 
            ed.ticket_price,
            ed.member_ticket_price,
            ed.is_member_exclusive,
            ed.registration_type,
            (SELECT COUNT(*) FROM events_checkin WHERE event_id = ed.id) as registered_count
        FROM events_eventdata ed
        WHERE ed.id = ? AND ed.is_active = TRUE
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$event) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet eller ikke tilgjengelig']);
        exit;
    }
    
    // Check if event allows registration (either 'signup', 'approval', or 'binding')
    if ($event['registration_type'] !== 'signup' && $event['registration_type'] !== 'approval' && $event['registration_type'] !== 'binding') {
        echo json_encode(['success' => false, 'message' => 'Dette arrangementet tillater ikke påmelding']);
        exit;
    }
    
    // Check if event has enough capacity for all attendees
    if ($event['max_attendees']) {
        $availableSpots = $event['max_attendees'] - $event['registered_count'];
        if ($totalAttendees > $availableSpots) {
            $message = $availableSpots > 0 
                ? "Kun {$availableSpots} ledig(e) plass(er) igjen. Du prøver å registrere {$totalAttendees} personer."
                : "Arrangementet er fullt.";
            echo json_encode(['success' => false, 'message' => $message]);
            exit;
        }
    }
    
    // Fetch registration fields for validation
    $dbType = getDBType();
    $stmt = $db->prepare("
        SELECT field_name, field_label, is_required 
        FROM event_registration_fields 
        WHERE event_id = ? AND is_visible = " . ($dbType === 'postgresql' ? 'TRUE' : '1') . "
    ");
    $stmt->execute([$event_id]);
    $registrationFields = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // ========== PHASE 2: PER-ATTENDEE VALIDATION (BEFORE TRANSACTION) ==========
    
    $processedAttendees = [];
    $totalPrice = 0;
    $emailsSeen = [];
    
    foreach ($attendees as $index => $attendee) {
        $attendeeNum = $index + 1;
        
        // Validate required fields per attendee
        $missingFields = [];
        foreach ($registrationFields as $field) {
            if ($field['is_required'] && (!isset($attendee[$field['field_name']]) || trim($attendee[$field['field_name']]) === '')) {
                $missingFields[] = $field['field_label'];
            }
        }
        
        if (!empty($missingFields)) {
            echo json_encode([
                'success' => false, 
                'message' => "Deltaker #{$attendeeNum}: Manglende påkrevde felt: " . implode(', ', $missingFields)
            ]);
            exit;
        }
        
        // Extract standard fields
        $first_name = isset($attendee['first_name']) ? trim($attendee['first_name']) : '';
        $last_name = isset($attendee['last_name']) ? trim($attendee['last_name']) : '';
        
        // Support legacy 'name' field
        if (empty($first_name) && empty($last_name) && isset($attendee['name'])) {
            $name = trim($attendee['name']);
        } else {
            $name = trim($first_name . ' ' . $last_name);
        }
        
        $email = isset($attendee['email']) ? trim($attendee['email']) : null;
        $phone = isset($attendee['phone']) ? trim($attendee['phone']) : null;
        $street = isset($attendee['street']) ? trim($attendee['street']) : null;
        $zip = isset($attendee['zip']) ? trim($attendee['zip']) : null;
        $city = isset($attendee['city']) ? trim($attendee['city']) : null;
        $postal = isset($attendee['postal']) ? trim($attendee['postal']) : null;
        $payment_method = isset($attendee['payment_method']) ? trim($attendee['payment_method']) : null;
        
        // Enforce minimum required fields
        if (empty($name)) {
            echo json_encode(['success' => false, 'message' => "Deltaker #{$attendeeNum}: Navn er påkrevd"]);
            exit;
        }
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => "Deltaker #{$attendeeNum}: Gyldig e-postadresse er påkrevd"]);
            exit;
        }
        
        // Check for duplicate emails in this batch (case-insensitive)
        $emailLower = strtolower($email);
        if (isset($emailsSeen[$emailLower])) {
            echo json_encode(['success' => false, 'message' => "Deltaker #{$attendeeNum}: E-postadressen {$email} er brukt flere ganger i samme påmelding"]);
            exit;
        }
        $emailsSeen[$emailLower] = true;
        
        // Check if email is already registered in database (check BOTH registration and checkin)
        $stmt = $db->prepare("
            SELECT 
                'registration' as source,
                er.id as id,
                er.approval_status
            FROM event_registrations er
            WHERE er.event_id = ? AND er.customer_id IN (
                SELECT id FROM tenant_customers WHERE LOWER(email) = LOWER(?)
            )
            UNION
            SELECT 
                'checkin' as source,
                ec.id as id,
                'approved' as approval_status
            FROM events_checkin ec
            WHERE ec.event_id = ? AND LOWER(ec.attendee_email) = LOWER(?)
            LIMIT 1
        ");
        $stmt->execute([$event_id, $email, $event_id, $email]);
        $existingReg = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingReg) {
            // Already registered
            if ($existingReg['approval_status'] === 'approved') {
                echo json_encode([
                    'success' => false,
                    'message' => "Du er allerede godkjent og registrert, ditt reservasjonsnummer: {$existingReg['id']}",
                    'duplicate' => true,
                    'status' => 'approved',
                    'reservation_number' => $existingReg['id']
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "Du er allerede registrert! Reservasjonen venter godkjenning.",
                    'duplicate' => true,
                    'status' => 'pending',
                    'reservation_number' => $existingReg['id']
                ]);
            }
            exit;
        }
        
        // Check membership status: EITHER email OR phone (100% full match required)
        $isMember = false;
        $finalPrice = $event['ticket_price'] ?? 0;
        
        // 1. Try EXACT EMAIL match first
        $stmt = $db->prepare("
            SELECT mm.id 
            FROM membership_members mm
            JOIN tenant_customers tc ON mm.customer_id = tc.id
            WHERE mm.company_id = ? 
                AND LOWER(tc.email) = LOWER(?)
                AND mm.status = 'active'
                AND (mm.expiration_date IS NULL OR mm.expiration_date > CURRENT_DATE)
            LIMIT 1
        ");
        $stmt->execute([$event['company_id'], $email]);
        
        if ($stmt->fetch()) {
            $isMember = true;
            if ($event['member_ticket_price'] !== null) {
                $finalPrice = $event['member_ticket_price'];
            }
        } elseif (!empty($phone)) {
            // 2. Try EXACT PHONE match (last 5 digits) if email didn't work
            $inputPhoneDigits = preg_replace('/\D/', '', $phone);
            $inputPhoneLast5 = substr($inputPhoneDigits, -5);
            
            if (strlen($inputPhoneLast5) === 5) {
                // Get all members and check phone match in PHP
                $stmt = $db->prepare("
                    SELECT tc.phone
                    FROM membership_members mm
                    JOIN tenant_customers tc ON mm.customer_id = tc.id
                    WHERE mm.company_id = ? 
                        AND mm.status = 'active'
                        AND (mm.expiration_date IS NULL OR mm.expiration_date > CURRENT_DATE)
                ");
                $stmt->execute([$event['company_id']]);
                $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($members as $member) {
                    if (!empty($member['phone'])) {
                        $dbPhoneDigits = preg_replace('/\D/', '', $member['phone']);
                        $dbPhoneLast5 = substr($dbPhoneDigits, -5);
                        
                        if ($dbPhoneLast5 === $inputPhoneLast5) {
                            $isMember = true;
                            if ($event['member_ticket_price'] !== null) {
                                $finalPrice = $event['member_ticket_price'];
                            }
                            break;
                        }
                    }
                }
            }
        }
        
        // Check member-exclusive restriction
        if ($event['is_member_exclusive'] && !$isMember) {
            // Get membership plan info
            $stmt = $db->prepare("
                SELECT id, plan_name, price, duration_months 
                FROM membership_plans 
                WHERE company_id = ? AND is_active = TRUE 
                ORDER BY price ASC 
                LIMIT 1
            ");
            $stmt->execute([$event['company_id']]);
            $membershipPlan = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $message = "Deltaker #{$attendeeNum} ({$name}): Dette arrangementet er kun for medlemmer.";
            
            if ($membershipPlan) {
                $message .= sprintf(
                    ' Bli medlem for %s kr (gjelder %d måneder) for å delta!',
                    number_format($membershipPlan['price'], 0, ',', ' '),
                    $membershipPlan['duration_months']
                );
            }
            
            echo json_encode([
                'success' => false, 
                'message' => $message,
                'reason' => 'members_only',
                'membership_plan' => $membershipPlan
            ]);
            exit;
        }
        
        // Store processed attendee data
        $processedAttendees[] = [
            'name' => $name,
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'phone' => $phone,
            'street' => $street,
            'zip' => $zip,
            'city' => $city,
            'postal' => $postal,
            'payment_method' => $payment_method,
            'is_member' => $isMember,
            'final_price' => $finalPrice
        ];
        
        $totalPrice += $finalPrice;
    }
    
    // ========== PHASE 3: DATABASE TRANSACTION (INSERT ALL ATTENDEES) ==========
    // STREAMLINED FLOW:
    // 1. Create tenant_customers (if needed)
    // 2. Create event_registrations FIRST (with approval_status)
    // 3. Create events_checkin ONLY IF approved (with registration_id FK)
    
    $db->beginTransaction();
    
    try {
        $registrationIds = [];
        
        foreach ($processedAttendees as $attendee) {
            // Step 1: Handle tenant_customers
            $customer_id = null;
            $stmt = $db->prepare("SELECT id FROM tenant_customers WHERE company_id = ? AND LOWER(email) = LOWER(?) LIMIT 1");
            $stmt->execute([$event['company_id'], $attendee['email']]);
            $existing_customer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_customer) {
                $customer_id = $existing_customer['id'];
                
                // Update customer information
                $updateFields = [];
                $updateValues = [];
                
                $nameParts = explode(' ', $attendee['name'], 2);
                $customerFirstName = $nameParts[0];
                $customerLastName = isset($nameParts[1]) ? $nameParts[1] : '';
                
                if ($customerFirstName) {
                    $updateFields[] = "first_name = ?";
                    $updateValues[] = $customerFirstName;
                }
                if ($customerLastName) {
                    $updateFields[] = "last_name = ?";
                    $updateValues[] = $customerLastName;
                }
                if ($attendee['phone']) {
                    $updateFields[] = "phone = ?";
                    $updateValues[] = $attendee['phone'];
                }
                if ($attendee['street']) {
                    $updateFields[] = "address_street = ?";
                    $updateValues[] = $attendee['street'];
                }
                if ($attendee['zip']) {
                    $updateFields[] = "address_zip = ?";
                    $updateValues[] = $attendee['zip'];
                }
                if ($attendee['city']) {
                    $updateFields[] = "address_city = ?";
                    $updateValues[] = $attendee['city'];
                }
                if ($attendee['postal']) {
                    $updateFields[] = "address_state = ?";
                    $updateValues[] = $attendee['postal'];
                }
                
                if (!empty($updateFields)) {
                    $updateFields[] = "updated_at = NOW()";
                    $updateValues[] = $customer_id;
                    
                    $updateSql = "UPDATE tenant_customers SET " . implode(', ', $updateFields) . " WHERE id = ?";
                    $stmt = $db->prepare($updateSql);
                    $stmt->execute($updateValues);
                }
            } else {
                // Create new customer
                $customer_code = 'CUST-' . time() . '-' . rand(1000, 9999);
                $customer_id = bin2hex(random_bytes(18));
                
                $nameParts = explode(' ', $attendee['name'], 2);
                $customerFirstName = $nameParts[0];
                $customerLastName = isset($nameParts[1]) ? $nameParts[1] : '';
                
                $stmt = $db->prepare("
                    INSERT INTO tenant_customers 
                    (id, company_id, customer_code, first_name, last_name, email, phone, 
                     address_street, address_zip, address_city, customer_type, status, 
                     source, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'individual', 'active', 'event_registration', NOW(), NOW())
                ");
                $stmt->execute([
                    $customer_id,
                    $event['company_id'],
                    $customer_code,
                    $customerFirstName,
                    $customerLastName,
                    $attendee['email'],
                    $attendee['phone'],
                    $attendee['street'],
                    $attendee['zip'],
                    $attendee['city']
                ]);
            }
            
            // Step 2: Determine approval_status based on event type
            $approvalStatus = 'pending';
            
            if ($event['registration_type'] === 'signup') {
                // Signup: Always auto-approve
                $approvalStatus = 'approved';
            } elseif ($event['registration_type'] === 'approval') {
                // Approval: Auto-approve if member OR payment
                if ($attendee['is_member'] || !empty($attendee['payment_method'])) {
                    $approvalStatus = 'approved';
                }
            } elseif ($event['registration_type'] === 'binding') {
                // Binding: Auto-approve only if payment
                if (!empty($attendee['payment_method'])) {
                    $approvalStatus = 'approved';
                }
            }
            
            // Step 3: Generate registration ID
            $dbType = getDBType();
            if ($dbType === 'pgsql') {
                $stmt = $db->query("SELECT gen_random_uuid()");
                $registration_id = $stmt->fetchColumn();
            } else {
                $registration_id = sprintf(
                    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                    mt_rand(0, 0xffff),
                    mt_rand(0, 0x0fff) | 0x4000,
                    mt_rand(0, 0x3fff) | 0x8000,
                    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                );
            }
            
            // Step 4: Create event_registrations FIRST
            $stmt = $db->prepare("
                INSERT INTO event_registrations 
                (id, event_id, company_id, customer_id, approval_status, approved_at, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, " . ($approvalStatus === 'approved' ? 'NOW()' : 'NULL') . ", NOW(), NOW())
            ");
            
            $stmt->execute([
                $registration_id,
                $event_id,
                $event['company_id'],
                $customer_id,
                $approvalStatus
            ]);
            
            $registrationIds[] = $registration_id;
            
            // Step 5: Create events_checkin ONLY IF approved (with registration_id FK)
            if ($approvalStatus === 'approved') {
                // Generate checkin ID
                if ($dbType === 'pgsql') {
                    $stmt = $db->query("SELECT gen_random_uuid()");
                    $checkin_id = $stmt->fetchColumn();
                } else {
                    $checkin_id = sprintf(
                        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                }
                
                $stmt = $db->prepare("
                    INSERT INTO events_checkin 
                    (id, registration_id, event_id, customer_id, attendee_name, attendee_email, attendee_phone, 
                     attendee_street, attendee_zip, attendee_city, attendee_postal,
                     ticket_type, ticket_price, payment_status, payment_method, checked_in, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, " . dbBool(false) . ", NOW(), NOW())
                ");
                
                $stmt->execute([
                    $checkin_id,
                    $registration_id,
                    $event_id,
                    $customer_id,
                    $attendee['name'],
                    $attendee['email'],
                    $attendee['phone'],
                    $attendee['street'] ?: null,
                    $attendee['zip'] ?: null,
                    $attendee['city'] ?: null,
                    $attendee['postal'] ?: null,
                    'online',
                    $attendee['final_price'],
                    $attendee['payment_method'] ?: null
                ]);
            }
        }
        
        // COMMIT TRANSACTION
        $db->commit();
        
        // Build success message
        $memberCount = count(array_filter($processedAttendees, function($a) { return $a['is_member']; }));
        
        // Count approved vs pending
        $approvedCount = 0;
        foreach ($processedAttendees as $attendee) {
            $approvalStatus = 'pending';
            if ($event['registration_type'] === 'signup') {
                $approvalStatus = 'approved';
            } elseif ($event['registration_type'] === 'approval') {
                if ($attendee['is_member'] || !empty($attendee['payment_method'])) {
                    $approvalStatus = 'approved';
                }
            } elseif ($event['registration_type'] === 'binding') {
                if (!empty($attendee['payment_method'])) {
                    $approvalStatus = 'approved';
                }
            }
            if ($approvalStatus === 'approved') {
                $approvedCount++;
            }
        }
        
        $successMessage = '';
        if ($totalAttendees === 1) {
            if ($approvedCount === 1) {
                $successMessage = 'Registreringen er gjennomført og godkjent!';
            } else {
                $successMessage = 'Registreringen er mottatt! Du vil få beskjed når den er godkjent.';
            }
        } else {
            if ($approvedCount === $totalAttendees) {
                $successMessage = "Alle {$totalAttendees} personer er registrert og godkjent!";
            } else {
                $successMessage = "{$approvedCount} av {$totalAttendees} personer er godkjent. Resten venter på godkjenning.";
            }
        }
        
        if ($memberCount > 0) {
            $successMessage .= " ($memberCount medlem" . ($memberCount > 1 ? 'mer' : '') . ")";
        }
        
        echo json_encode([
            'success' => true,
            'message' => $successMessage,
            'reservation_numbers' => $registrationIds,
            'total_price' => $totalPrice,
            'total_attendees' => $totalAttendees,
            'approved_count' => $approvedCount,
            'pending_count' => $totalAttendees - $approvedCount,
            'member_count' => $memberCount
        ]);
        
    } catch (PDOException $e) {
        $db->rollBack();
        
        // Handle duplicate entry error
        if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
            $errorMessage = 'En eller flere e-postadresser er allerede registrert for dette arrangementet.';
        } else {
            error_log("Database error in event registration: " . $e->getMessage());
            $errorMessage = 'Teknisk feil under registrering. Vennligst prøv igjen.';
        }
        
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error in event registration: " . $e->getMessage());
    echo json_encode([
        'success' => false, 
        'message' => 'Teknisk feil. Vennligst kontakt administrator.'
    ]);
}
?>
