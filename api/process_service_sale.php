<?php
session_start();
require_once '../config/database.php';
require_once '../includes/UnifiedLedgerService.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$db = getDBConnection();
$admin_id = $_SESSION['tenant_admin_id'];
$company_id = $_SESSION['company_id'];

$data = json_decode(file_get_contents('php://input'), true);

$output_product_id = $data['output_product_id'] ?? null;
$service_type = $data['service_type'] ?? null;
$quantity = intval($data['quantity'] ?? 1);
$customer_first_name = trim($data['customer_first_name'] ?? '');
$customer_last_name = trim($data['customer_last_name'] ?? '');
$customer_email = trim($data['customer_email'] ?? '');
$customer_phone = trim($data['customer_phone'] ?? '');
$payment_method = $data['payment_method'] ?? 'cash';
$notes = $data['notes'] ?? '';

// Membership-specific fields
$join_date = $data['join_date'] ?? null;
$expiry_date = $data['expiry_date'] ?? null;
$auto_renew = isset($data['auto_renew']) ? (bool)$data['auto_renew'] : false;

$customer_full_name = trim($customer_first_name . ' ' . $customer_last_name);

if (!$output_product_id || !$service_type || !$customer_first_name || !$customer_last_name || !$customer_email) {
    echo json_encode(['success' => false, 'message' => 'Mangler påkrevd informasjon']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Check if this is a member price purchase (output_product_id ends with _member)
    $is_member_price = false;
    $actual_output_product_id = $output_product_id;
    
    if (substr($output_product_id, -7) === '_member') {
        $is_member_price = true;
        $actual_output_product_id = str_replace('_member', '', $output_product_id);
    }
    
    // Get output_product first
    $stmt = $db->prepare("SELECT product_id, product_type FROM output_products WHERE id = ? AND company_id = ?");
    $stmt->execute([$actual_output_product_id, $company_id]);
    $outputProduct = $stmt->fetch();
    
    if (!$outputProduct || $outputProduct['product_type'] !== $service_type) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Produkt ikke funnet']);
        exit;
    }
    
    $product_id = $outputProduct['product_id'];
    $service = null;
    
    // Fetch service details based on type (separate queries to avoid column conflicts)
    switch ($service_type) {
        case 'event':
            $stmt = $db->prepare("SELECT id, title as service_name, ticket_price as price, member_ticket_price FROM events_eventdata WHERE id = ?");
            $stmt->execute([$product_id]);
            $service = $stmt->fetch();
            
            // Use member price if this is a member purchase
            if ($is_member_price && !empty($service['member_ticket_price'])) {
                $service['price'] = $service['member_ticket_price'];
                $service['service_name'] .= ' (Medlemspris)';
            }
            break;
            
        case 'membership':
            $stmt = $db->prepare("SELECT id as plan_id, name as service_name, price, billing_cycle FROM membership_plans WHERE id = ?");
            $stmt->execute([$product_id]);
            $service = $stmt->fetch();
            break;
            
        case 'rental':
            $stmt = $db->prepare("SELECT object_id as id, object_name as service_name, daily_price as price FROM rental_objects WHERE object_id = ?");
            $stmt->execute([$product_id]);
            $service = $stmt->fetch();
            break;
            
        case 'service':
            $stmt = $db->prepare("SELECT id, service_name, price FROM reservation_services WHERE id = ?");
            $stmt->execute([$product_id]);
            $service = $stmt->fetch();
            break;
            
        default:
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Ukjent tjenestetype']);
            exit;
    }
    
    if (!$service || !$service['price']) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Tjeneste ikke funnet eller mangler pris']);
        exit;
    }
    
    $total = floatval($service['price']) * $quantity;
    
    // Initialize unified ledger service
    $ledgerService = new UnifiedLedgerService($db, $company_id);
    $orderId = strtoupper($service_type) . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $transactionId = null;
    
    // Check if customer exists in tenant_customers, if not create
    $customer_id = null;
    $stmt = $db->prepare("SELECT id FROM tenant_customers WHERE company_id = ? AND email = ? LIMIT 1");
    $stmt->execute([$company_id, $customer_email]);
    $existing_customer = $stmt->fetch();
    
    if ($existing_customer) {
        $customer_id = $existing_customer['id'];
    } else {
        // Generate customer_code
        $customer_code = 'CUST-' . time() . '-' . rand(1000, 9999);
        $customer_id = bin2hex(random_bytes(18));
        
        $stmt = $db->prepare("
            INSERT INTO tenant_customers 
            (id, company_id, customer_code, first_name, last_name, email, phone, customer_type, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'individual', 'active', NOW(), NOW())
        ");
        $stmt->execute([
            $customer_id,
            $company_id,
            $customer_code,
            $customer_first_name,
            $customer_last_name,
            $customer_email,
            $customer_phone
        ]);
    }
    
    // Create appropriate transaction based on service type
    switch ($service_type) {
        case 'event':
            // Record event check-in or registration (customer already created in tenant_customers)
            $checkin_id = bin2hex(random_bytes(16));
            $transactionId = $checkin_id;
            
            // Add member price indicator to notes
            $event_notes = $notes;
            if ($is_member_price) {
                $event_notes = "Medlemspris brukt. " . $notes;
            }
            
            // Payment status for accurate financial tracking
            // on_arrival means payment not yet received
            $event_payment_status = ($payment_method === 'on_arrival') ? 'pending' : 'paid';
            
            $stmt = $db->prepare("
                INSERT INTO events_checkin 
                (id, event_id, customer_id, attendee_name, attendee_email, attendee_phone, 
                 ticket_price, payment_method, payment_status, checked_in, 
                 checked_in_by, notes, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                $checkin_id,
                $product_id,
                $customer_id,
                $customer_full_name,
                $customer_email,
                $customer_phone,
                $total,
                $payment_method,
                $event_payment_status,
                false, // not checked in yet
                $admin_id,
                $event_notes
            ]);
            
            // Log to unified_ledger (always, even for pending payments)
            // Get event cost_price
            $costStmt = $db->prepare("SELECT cost_price FROM events_eventdata WHERE id = ?");
            $costStmt->execute([$product_id]);
            $costResult = $costStmt->fetch(PDO::FETCH_ASSOC);
            $costPrice = ($costResult && $costResult['cost_price']) ? ($costResult['cost_price'] * $quantity) : null;
            
            // Set ledger payment_status based on payment_method
            $ledger_payment_status = ($payment_method === 'on_arrival') ? 'pending' : 'completed';
            
            $ledgerService->logTransaction([
                'order_id' => $orderId,
                'module_source' => 'events',
                'item_type' => 'event',
                'source_id' => $checkin_id,
                'item_name' => $service['service_name'],
                'quantity' => $quantity,
                'selling_price' => $total,
                'cost_price' => $costPrice,
                'customer_name' => $customer_full_name,
                'customer_email' => $customer_email,
                'payment_method' => $payment_method,
                'payment_status' => $ledger_payment_status,
                'status' => 'active',
                'transaction_date' => date('Y-m-d H:i:s'),
                'metadata' => [
                    'event_id' => $product_id,
                    'is_member_price' => $is_member_price
                ]
            ]);
            break;
            
        case 'membership':
            // Create member record (active membership)
            // Use provided dates or calculate from plan duration
            $finalJoinDate = $join_date ?: date('Y-m-d');
            $finalExpiryDate = $expiry_date;
            
            // If no expiry date provided, calculate it from plan duration
            if (!$finalExpiryDate) {
                if (isset($service['duration_months'])) {
                    $durationMonths = $service['duration_months'];
                } elseif (isset($service['billing_cycle'])) {
                    $durationMonths = match($service['billing_cycle']) {
                        'monthly' => 1,
                        'quarterly' => 3,
                        'yearly' => 12,
                        default => 12
                    };
                } else {
                    $durationMonths = 12;
                }
                $finalExpiryDate = date('Y-m-d', strtotime("+{$durationMonths} months", strtotime($finalJoinDate)));
            }
            
            // Generate member_code
            $member_code = 'MEM-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            
            // Update customer record with is_member flag and additional info
            $updateCustomerStmt = $db->prepare("
                UPDATE tenant_customers 
                SET is_member = 1,
                    date_of_birth = COALESCE(date_of_birth, ?),
                    address_street = COALESCE(address_street, ?),
                    address_zip = COALESCE(address_zip, ?),
                    address_city = COALESCE(address_city, ?),
                    notes = CASE 
                        WHEN notes IS NULL OR notes = '' THEN ? 
                        ELSE GROUP_CONCAT(notes, '\n', ?) 
                    END,
                    updated_at = NOW()
                WHERE id = ?
            ");
            
            $membershipNote = "Medlemskap kjøpt " . date('Y-m-d') . " - Plan: " . $service['service_name'] . " - Betalt: {$total} NOK ({$payment_method})";
            
            $updateCustomerStmt->execute([
                $data['customer_birth_date'] ?? null,
                $data['customer_address'] ?? null,
                $data['customer_postal_code'] ?? null,
                $data['customer_city'] ?? null,
                $membershipNote,
                $membershipNote,
                $customer_id
            ]);
            
            // Insert membership record (MySQL only)
            $stmt = $db->prepare("
                INSERT INTO membership_members 
                (company_id, customer_id, membership_plan_id, member_code, 
                 join_date, expiration_date, auto_renew, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
            ");
            $stmt->execute([
                $company_id,
                $customer_id,
                $product_id,
                $member_code,
                $finalJoinDate,
                $finalExpiryDate,
                $auto_renew ? 1 : 0
            ]);
            $transactionId = $db->lastInsertId();
            
            // membership_plans doesn't have cost_price field
            $costPrice = null;
            
            // Set ledger payment_status based on payment_method
            $ledger_payment_status = ($payment_method === 'on_arrival') ? 'pending' : 'completed';
            
            $ledgerService->logTransaction([
                'order_id' => $orderId,
                'module_source' => 'membership',
                'item_type' => 'membership',
                'source_id' => $transactionId,
                'item_name' => $service['service_name'],
                'quantity' => $quantity,
                'selling_price' => $total,
                'cost_price' => $costPrice,
                'customer_name' => $customer_full_name,
                'customer_email' => $customer_email,
                'payment_method' => $payment_method,
                'payment_status' => $ledger_payment_status,
                'status' => 'active',
                'transaction_date' => date('Y-m-d H:i:s'),
                'metadata' => [
                    'plan_id' => $product_id
                ]
            ]);
            break;
            
        case 'rental':
            // Create rental reservation
            $stmt = $db->prepare("
                INSERT INTO rental_reservations 
                (company_id, object_id, customer_name, customer_email, customer_phone, 
                 start_date, end_date, daily_price, total_price, deposit_paid, status, notes, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 DAY), ?, ?, 0, 'confirmed', ?, NOW(), NOW())
            ");
            
            // Calculate daily price from total (assuming 1 day rental by default)
            $daily_price = $total / $quantity;
            
            $stmt->execute([
                $company_id,
                $product_id,
                $customer_full_name,
                $customer_email,
                $customer_phone,
                $daily_price,
                $total,
                "Kjøpt via varesalg - Betalingsmetode: {$payment_method}. " . $notes
            ]);
            $rentalResult = $stmt->fetch(PDO::FETCH_ASSOC);
            $transactionId = $rentalResult['reservation_id'] ?? bin2hex(random_bytes(16));
            
            // Log to unified_ledger
            // Get rental object cost_price if available
            $costStmt = $db->prepare("SELECT cost_price FROM rental_objects WHERE object_id = ?");
            $costStmt->execute([$product_id]);
            $costResult = $costStmt->fetch(PDO::FETCH_ASSOC);
            $costPrice = ($costResult && $costResult['cost_price']) ? ($costResult['cost_price'] * $quantity) : null;
            
            // Set ledger payment_status based on payment_method
            $ledger_payment_status = ($payment_method === 'on_arrival') ? 'pending' : 'completed';
            
            $ledgerService->logTransaction([
                'order_id' => $orderId,
                'module_source' => 'rental',
                'item_type' => 'rental',
                'source_id' => $transactionId,
                'item_name' => $service['service_name'],
                'quantity' => $quantity,
                'selling_price' => $total,
                'cost_price' => $costPrice,
                'customer_name' => $customer_full_name,
                'customer_email' => $customer_email,
                'payment_method' => $payment_method,
                'payment_status' => $ledger_payment_status,
                'status' => 'active',
                'transaction_date' => date('Y-m-d H:i:s'),
                'metadata' => [
                    'object_id' => $product_id,
                    'daily_price' => $daily_price
                ]
            ]);
            break;
            
        case 'service':
            // Handle reservation service booking
            if (!isset($data['is_reservation']) || !$data['is_reservation']) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Ugyldig tjenestedata']);
                exit;
            }
            
            $reservation_date = $data['reservation_date'] ?? null;
            $reservation_time = $data['reservation_time'] ?? null;
            $reservation_staff_id = $data['reservation_staff_id'] ?? null;
            
            if (!$reservation_date || !$reservation_time || !$reservation_staff_id) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Mangler booking-informasjon']);
                exit;
            }
            
            // SECURITY: Validate that product_id matches the reservation service and get authoritative data
            // Never trust client-supplied duration or service_id - fetch from database
            $stmt = $db->prepare("
                SELECT id, service_name, price, cost_price, duration_minutes, assigned_staff_ids 
                FROM reservation_services 
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([$product_id, $company_id]);
            $service = $stmt->fetch();
            
            if (!$service) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Reserverbar tjeneste ikke funnet']);
                exit;
            }
            
            // Use authoritative duration from database, not client input
            $reservation_duration = intval($service['duration_minutes']);
            $reservation_service_id = $service['id'];
            
            // Validate that selected staff is assigned to this service
            $assigned_staff_array = json_decode($service['assigned_staff_ids'], true) ?? [];
            if (!in_array($reservation_staff_id, $assigned_staff_array)) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Valgt ansatt er ikke tilgjengelig for denne tjenesten']);
                exit;
            }
            
            $total = floatval($service['price']);
            
            // Calculate end date and time
            $startDateTime = new DateTime($reservation_date . ' ' . $reservation_time);
            $endDateTime = clone $startDateTime;
            $endDateTime->add(new DateInterval('PT' . $reservation_duration . 'M'));
            
            // Check if time slot is still available
            $checkStmt = $db->prepare("
                SELECT COUNT(*) as count
                FROM service_bookings
                WHERE company_id = ?
                AND assigned_staff_id = ?
                AND date_from = ?
                AND status NOT IN ('cancelled', 'completed')
                AND (
                    (time_from < ? AND time_to > ?) OR
                    (time_from >= ? AND time_from < ?) OR
                    (time_to > ? AND time_to <= ?)
                )
            ");
            
            $startTime = $startDateTime->format('H:i:s');
            $endTime = $endDateTime->format('H:i:s');
            
            $checkStmt->execute([
                $company_id,
                $reservation_staff_id,
                $reservation_date,
                $endTime, $startTime,
                $startTime, $endTime,
                $startTime, $endTime
            ]);
            
            $availabilityResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if ($availabilityResult['count'] > 0) {
                $db->rollBack();
                echo json_encode(['success' => false, 'message' => 'Valgt tidspunkt er ikke lenger ledig']);
                exit;
            }
            
            // Create service booking
            $booking_id = bin2hex(random_bytes(16));
            $transactionId = $booking_id;
            
            $booking_payment_status = ($payment_method === 'on_arrival') ? 'pending' : 'confirmed';
            
            $stmt = $db->prepare("
                INSERT INTO service_bookings (
                    id, company_id, tenant_admin_id,
                    service_name, service_description, selling_price, cost_price,
                    duration, date_from, time_from, date_to, time_to,
                    assigned_staff_id, customer_name, customer_email, customer_phone,
                    status, payment_method, notes, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            
            $stmt->execute([
                $booking_id,
                $company_id,
                $admin_id,
                $service['service_name'],
                $notes,
                $total,
                $service['cost_price'] ?? null,
                $reservation_duration,
                $reservation_date,
                $startTime,
                $endDateTime->format('Y-m-d'),
                $endTime,
                $reservation_staff_id,
                $customer_full_name,
                $customer_email,
                $customer_phone,
                $booking_payment_status,
                $payment_method,
                $notes
            ]);
            
            // Log to unified_ledger
            $ledger_payment_status = ($payment_method === 'on_arrival') ? 'pending' : 'completed';
            
            $ledgerService->logTransaction([
                'order_id' => $orderId,
                'module_source' => 'reservations',
                'item_type' => 'service',
                'source_id' => $booking_id,
                'item_name' => $service['service_name'],
                'quantity' => 1,
                'selling_price' => $total,
                'cost_price' => $service['cost_price'] ?? null,
                'customer_name' => $customer_full_name,
                'customer_email' => $customer_email,
                'payment_method' => $payment_method,
                'payment_status' => $ledger_payment_status,
                'status' => 'active',
                'transaction_date' => date('Y-m-d H:i:s'),
                'metadata' => [
                    'reservation_service_id' => $reservation_service_id,
                    'booking_date' => $reservation_date,
                    'booking_time' => $reservation_time,
                    'staff_id' => $reservation_staff_id,
                    'duration_minutes' => $reservation_duration
                ]
            ]);
            break;
            
        default:
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'Ukjent tjenestetype']);
            exit;
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Tjeneste solgt',
        'total' => $total,
        'customer' => $customer_full_name,
        'customer_id' => $customer_id,
        'service_type' => $service_type
    ]);
    
} catch (Exception $e) {
    $db->rollBack();
    error_log("Service sale error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved salg av tjeneste: ' . $e->getMessage()]);
}
