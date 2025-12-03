<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['first_name']) || empty($data['last_name']) || empty($data['email'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler obligatoriske felt (fornavn, etternavn, e-post)']);
        exit;
    }

    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    // Check if customer with this email already exists
    $checkStmt = $db->prepare("SELECT id FROM tenant_customers WHERE company_id = ? AND email = ?");
    $checkStmt->execute([$companyId, $data['email']]);
    $existingCustomer = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    $customerId = null;
    
    if ($existingCustomer) {
        $customerId = $existingCustomer['id'];
        
        // Check if already a member
        $memberCheck = $db->prepare("SELECT id FROM membership_members WHERE company_id = ? AND customer_id = ?");
        $memberCheck->execute([$companyId, $customerId]);
        if ($memberCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Denne kunden er allerede registrert som medlem']);
            exit;
        }
    } else {
        // Create new customer in tenant_customers
        $customerId = bin2hex(random_bytes(18));
        $customerCode = 'CUST-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        
        $stmt = $db->prepare("
            INSERT INTO tenant_customers (
                id, company_id, customer_code, first_name, last_name, email, phone,
                address, address_city, date_of_birth, notes, customer_type, status, is_member, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'individual', 'active', 1, NOW(), NOW())
        ");
        $stmt->execute([
            $customerId,
            $companyId,
            $customerCode,
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['poststed'] ?? null,
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
    
    if (!empty($data['membership_plan_id'])) {
        $planStmt = $db->prepare("SELECT id, billing_cycle FROM membership_plans WHERE id = ? AND company_id = ?");
        $planStmt->execute([$data['membership_plan_id'], $companyId]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            // Plan validated - use its ID
            $validPlanId = $plan['id'];
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
    
    // Generate member code
    $memberCode = 'MBR-' . date('Y') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    $memberId = bin2hex(random_bytes(18));
    
    // Create membership record
    $stmt = $db->prepare("
        INSERT INTO membership_members (
            id, company_id, customer_id, membership_plan_id, member_code,
            status, join_date, expiration_date, auto_renew, created_at
        ) VALUES (?, ?, ?, ?, ?, 'active', ?, ?, 1, NOW())
    ");
    
    $stmt->execute([
        $memberId,
        $companyId,
        $customerId,
        $validPlanId,
        $memberCode,
        $joinDate,
        $expirationDate
    ]);
    
    // Update customer as member if not already
    if ($existingCustomer) {
        $updateStmt = $db->prepare("UPDATE tenant_customers SET is_member = 1 WHERE id = ?");
        $updateStmt->execute([$customerId]);
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
