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
    
    // Sjekk om e-post allerede eksisterer
    $checkStmt = $db->prepare("SELECT member_id FROM membership_members WHERE company_id = ? AND email = ?");
    $checkStmt->execute([$_SESSION['company_id'], $data['email']]);
    if ($checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Et medlem med denne e-postadressen eksisterer allerede']);
        exit;
    }
    
    // Beregn utløpsdato basert på plan
    $expiryDate = null;
    if (!empty($data['plan_id'])) {
        $dbType = getDBType();
        
        if ($dbType === 'mysql') {
            $planStmt = $db->prepare("SELECT billing_cycle FROM membership_plans WHERE id = ? AND company_id = ?");
        } else {
            $planStmt = $db->prepare("SELECT duration_months FROM x_membership_plan WHERE plan_id = ? AND company_id = ?");
        }
        $planStmt->execute([$data['plan_id'], $_SESSION['company_id']]);
        $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($plan) {
            if (isset($plan['duration_months'])) {
                $durationMonths = $plan['duration_months'];
            } elseif (isset($plan['billing_cycle'])) {
                $durationMonths = match($plan['billing_cycle']) {
                    'monthly' => 1,
                    'quarterly' => 3,
                    'yearly' => 12,
                    default => 12
                };
            } else {
                $durationMonths = 12;
            }
            $expiryDate = date('Y-m-d', strtotime("+{$durationMonths} months"));
        }
    }
    
    // Opprett medlem
    $stmt = $db->prepare("
        INSERT INTO membership_members (
            company_id,
            plan_id,
            first_name,
            last_name,
            email,
            phone,
            address,
            poststed,
            dateofbirth,
            join_date,
            expiry_date,
            status,
            notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_DATE, ?, 'active', ?)
        RETURNING member_id
    ");
    
    $stmt->execute([
        $_SESSION['company_id'],
        !empty($data['plan_id']) ? $data['plan_id'] : null,
        $data['first_name'],
        $data['last_name'],
        $data['email'],
        $data['phone'] ?? null,
        $data['address'] ?? null,
        $data['poststed'] ?? null,
        $data['dateofbirth'] ?? null,
        $expiryDate,
        $data['notes'] ?? null
    ]);
    
    $memberId = $stmt->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'message' => 'Medlem lagt til',
        'member_id' => $memberId
    ]);
    
} catch (Exception $e) {
    error_log("Error creating member: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
