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
    
    if (empty($data['member_id'])) {
        echo json_encode(['success' => false, 'message' => 'Medlem-ID mangler']);
        exit;
    }

    $db = getDBConnection();
    $db->beginTransaction();
    
    $memberId = $data['member_id'];
    $companyId = $_SESSION['company_id'];
    
    // Get the member record to find customer_id
    $memberStmt = $db->prepare("SELECT customer_id FROM membership_members WHERE id = ? AND company_id = ?");
    $memberStmt->execute([$memberId, $companyId]);
    $member = $memberStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$member) {
        echo json_encode(['success' => false, 'message' => 'Medlem ikke funnet']);
        exit;
    }
    
    $customerId = $member['customer_id'];
    
    // Fields that belong to tenant_customers
    $customerFields = ['first_name', 'last_name', 'email', 'phone', 'notes'];
    $customerFieldMapping = [
        'first_name' => 'first_name',
        'last_name' => 'last_name',
        'email' => 'email',
        'phone' => 'phone',
        'address' => 'address_street',
        'poststed' => 'address_city',
        'dateofbirth' => 'date_of_birth',
        'notes' => 'notes'
    ];
    
    // Fields that belong to membership_members
    $memberFields = ['membership_plan_id', 'status', 'auto_renew'];
    
    $customerUpdates = [];
    $customerParams = [];
    $memberUpdates = [];
    $memberParams = [];
    
    // Build customer updates
    foreach ($customerFieldMapping as $inputField => $dbField) {
        if (isset($data[$inputField])) {
            $customerUpdates[] = "$dbField = ?";
            $customerParams[] = $data[$inputField] ?: null;
        }
    }
    
    // Build member updates
    foreach ($memberFields as $field) {
        if (isset($data[$field])) {
            $memberUpdates[] = "$field = ?";
            $memberParams[] = $data[$field] ?: null;
        }
    }
    
    $updatedCustomer = false;
    $updatedMember = false;
    
    // Update tenant_customers if there are customer field changes
    if (!empty($customerUpdates) && $customerId) {
        $customerParams[] = $customerId;
        $customerParams[] = $companyId;
        
        $sql = "UPDATE tenant_customers SET " . implode(', ', $customerUpdates) . ", updated_at = NOW() WHERE id = ? AND company_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($customerParams);
        $updatedCustomer = true;
    }
    
    // Update membership_members if there are member field changes
    if (!empty($memberUpdates)) {
        $memberParams[] = $memberId;
        $memberParams[] = $companyId;
        
        $sql = "UPDATE membership_members SET " . implode(', ', $memberUpdates) . ", updated_at = NOW() WHERE id = ? AND company_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute($memberParams);
        $updatedMember = true;
    }
    
    if (!$updatedCustomer && !$updatedMember) {
        echo json_encode(['success' => false, 'message' => 'Ingen felt å oppdatere']);
        exit;
    }
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Medlem oppdatert',
        'updated_customer' => $updatedCustomer,
        'updated_member' => $updatedMember
    ]);
    
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error updating member: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
