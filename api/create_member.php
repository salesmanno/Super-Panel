<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/UnifiedLedgerService.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['first_name']) || empty($data['last_name'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler obligatoriske felt (fornavn og etternavn)']);
        exit;
    }

    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    // Check if customer with this phone already exists (if phone provided)
    if (!empty($data['phone'])) {
        $phoneCheck = $db->prepare("
            SELECT tc.id, tc.first_name, tc.last_name, mm.id as member_id 
            FROM tenant_customers tc
            LEFT JOIN membership_members mm ON mm.customer_id = tc.id AND mm.company_id = tc.company_id
            WHERE tc.company_id = ? AND tc.phone = ?
        ");
        $phoneCheck->execute([$companyId, $data['phone']]);
        $existingPhone = $phoneCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($existingPhone) {
            $name = trim($existingPhone['first_name'] . ' ' . $existingPhone['last_name']);
            if ($existingPhone['member_id']) {
                echo json_encode(['success' => false, 'message' => "Mobilnummer er allerede registrert på medlem: $name"]);
            } else {
                echo json_encode(['success' => false, 'message' => "Mobilnummer er allerede registrert på kunde: $name"]);
            }
            exit;
        }
    }
    
    // Check if customer with this email already exists (if email provided) — block if found
    if (!empty($data['email'])) {
        $checkStmt = $db->prepare("SELECT id, first_name, last_name FROM tenant_customers WHERE company_id = ? AND email = ?");
        $checkStmt->execute([$companyId, $data['email']]);
        $existingEmail = $checkStmt->fetch(PDO::FETCH_ASSOC);
        if ($existingEmail) {
            $name = trim($existingEmail['first_name'] . ' ' . $existingEmail['last_name']);
            // Check if already a member
            $memberCheck = $db->prepare("SELECT id FROM membership_members WHERE company_id = ? AND customer_id = ?");
            $memberCheck->execute([$companyId, $existingEmail['id']]);
            if ($memberCheck->fetch()) {
                echo json_encode(['success' => false, 'message' => "E-post er allerede registrert på eksisterende medlem: $name"]);
            } else {
                echo json_encode(['success' => false, 'message' => "E-post er allerede registrert på kunde: $name"]);
            }
            exit;
        }
    }
    
    $customerId = null;
    
    {
        // Create new customer in tenant_customers
        $customerId = bin2hex(random_bytes(18));
        $customerCode = 'CUST-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        
        $stmt = $db->prepare("
            INSERT INTO tenant_customers (
                id, company_id, customer_code, first_name, last_name, email, phone,
                address_street, address_city, address_zip, date_of_birth, notes, customer_type, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'individual', 'active', NOW(), NOW())
        ");
        $stmt->execute([
            $customerId,
            $companyId,
            $customerCode,
            $data['first_name'],
            $data['last_name'],
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address_street'] ?? null,
            $data['address_city'] ?? null,
            $data['address_zip'] ?? null,
            $data['dateofbirth'] ?? null,
            $data['notes'] ?? null
        ]);
    }
    
    // Handle join date - validate and use provided date or default to today
    $joinDate = date('Y-m-d');
    if (!empty($data['join_date'])) {
        $parsedDate = date_create($data['join_date']);
        if ($parsedDate) {
            $joinDate = date_format($parsedDate, 'Y-m-d');
        }
    }
    
    // Calculate expiration date based on plan - validate plan belongs to company
    $expirationDate = null;
    $validPlanId = null;
    
    $planName = 'Medlemskap';
    $planPrice = 0;
    
    if (!empty($data['membership_plan_id'])) {
        $planStmt = $db->prepare("SELECT id, name, price, billing_cycle FROM membership_plans WHERE id = ? AND company_id = ?");
        $planStmt->execute([$data['membership_plan_id'], $companyId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            // Plan validated - use its ID
            $validPlanId = $plan['id'];
            $planName = $plan['name'] ?? 'Medlemskap';
            $planPrice = floatval($plan['price'] ?? 0);
            $durationMonths = match($plan['billing_cycle'] ?? 'yearly') {
                'monthly' => 1,
                'quarterly' => 3,
                'yearly' => 12,
                default => 12
            };
            $expirationDate = date('Y-m-d', strtotime($joinDate . " +{$durationMonths} months"));
        }
        // If plan not found for this company, validPlanId stays null (safe)
    }
    
    // Generate member code and share code
    $memberCode = 'MBR-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $shareCode = strtoupper(substr(bin2hex(random_bytes(8)), 0, 12));
    $memberId = bin2hex(random_bytes(18));
    
    // payment_status: store 'paid' and 'unpaid' explicitly; anything else → NULL
    $rawStatus = $data['payment_status'] ?? null;
    $paymentStatus = in_array($rawStatus, ['paid', 'unpaid'], true) ? $rawStatus : null;
    
    // paymentMethod
    $paymentMethod = !empty($data['paymentMethod']) ? $data['paymentMethod'] : null;
    
    // Create membership record
    $stmt = $db->prepare("
        INSERT INTO membership_members (
            id, company_id, customer_id, membership_plan_id, member_code, share_code,
            status, join_date, expiration_date, payment_status, paymentMethod, auto_renew, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'active', ?, ?, ?, ?, 1, NOW())
    ");
    
    $stmt->execute([
        $memberId,
        $companyId,
        $customerId,
        $validPlanId,
        $memberCode,
        $shareCode,
        $joinDate,
        $expirationDate,
        $paymentStatus,
        $paymentMethod
    ]);
    
    // Log to unified_ledger when payment is confirmed as paid
    if ($paymentStatus === 'paid' && $planPrice > 0) {
        $ledger = new UnifiedLedgerService($db, $companyId);
        $ledger->logTransaction([
            'order_id'         => 'MEM-' . $memberCode,
            'module_source'    => 'membership',
            'item_type'        => 'membership',
            'source_id'        => $memberId,
            'item_name'        => $planName,
            'quantity'         => 1,
            'selling_price'    => $planPrice,
            'customer_id'      => $customerId,
            'payment_method'   => $paymentMethod,
            'payment_status'   => 'completed',
            'transaction_type' => 'income',
            'category'         => 'Medlemskap',
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Medlem lagt til',
        'member_id' => $memberId,
        'member_code' => $memberCode
    ]);
    
} catch (Exception $e) {
    error_log("Error creating member: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
