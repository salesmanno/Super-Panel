<?php
session_start();
require_once '../config/database.php';

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

$customer_full_name = trim($customer_first_name . ' ' . $customer_last_name);

if (!$output_product_id || !$service_type || !$customer_first_name || !$customer_last_name || !$customer_email) {
    echo json_encode(['success' => false, 'message' => 'Mangler påkrevd informasjon']);
    exit;
}

try {
    $db->beginTransaction();
    
    // Get output_product first
    $stmt = $db->prepare("SELECT product_id, product_type FROM output_products WHERE id = ? AND company_id = ?");
    $stmt->execute([$output_product_id, $company_id]);
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
            $stmt = $db->prepare("SELECT id, title as service_name, ticket_price as price FROM events_eventdata WHERE id = ?");
            $stmt->execute([$product_id]);
            $service = $stmt->fetch();
            break;
            
        case 'membership':
            $stmt = $db->prepare("SELECT id, name as service_name, price FROM membership_plans WHERE id = ?");
            $stmt->execute([$product_id]);
            $service = $stmt->fetch();
            break;
            
        case 'rental':
            $stmt = $db->prepare("SELECT object_id as id, object_name as service_name, daily_price as price FROM rental_objects WHERE object_id = ?");
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
            // Record event check-in or registration
            $checkin_id = bin2hex(random_bytes(16));
            $stmt = $db->prepare("
                INSERT INTO events_checkin 
                (id, event_id, customer_id, attendee_name, attendee_email, attendee_phone, 
                 ticket_price, payment_method, payment_status, paid_at, checked_in, 
                 checked_in_by, notes, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, NOW(), NOW())
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
                'paid',
                false, // not checked in yet
                $admin_id,
                $notes
            ]);
            break;
            
        case 'membership':
            // Create membership transaction
            $stmt = $db->prepare("
                INSERT INTO membership_transactions 
                (company_id, membership_plan_id, customer_name, customer_email, customer_phone, 
                 quantity, total_price, payment_method, notes, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $company_id,
                $product_id,
                $customer_full_name,
                $customer_email,
                $customer_phone,
                $quantity,
                $total,
                $payment_method,
                $notes,
                $admin_id
            ]);
            break;
            
        case 'rental':
            // Create rental reservation
            $stmt = $db->prepare("
                INSERT INTO rental_reservations 
                (rental_object_id, customer_name, customer_email, customer_phone, 
                 quantity, total_price, payment_method, notes, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $product_id,
                $customer_full_name,
                $customer_email,
                $customer_phone,
                $quantity,
                $total,
                $payment_method,
                $notes,
                $admin_id
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
