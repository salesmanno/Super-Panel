<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $dbType = getDBType();
    
    // PostgreSQL: membership_members has columns directly (first_name, last_name, email, etc.)
    // MySQL: membership_members links to tenant_customers via customer_id
    
    if ($dbType === 'mysql') {
        // MySQL: JOIN with tenant_customers for member details
        $customerTable = 'tenant_customers';
        
        // Check if tenant_customers exists, otherwise try customers
        try {
            $checkStmt = $db->query("SHOW TABLES LIKE 'tenant_customers'");
            if ($checkStmt->rowCount() == 0) {
                $checkStmt = $db->query("SHOW TABLES LIKE 'customers'");
                if ($checkStmt->rowCount() > 0) {
                    $customerTable = 'customers';
                }
            }
        } catch (Exception $e) {
            // Use default
        }
        
        $stmt = $db->prepare("
            SELECT 
                mm.id as member_id,
                mm.member_code,
                mm.join_date,
                mm.expiration_date as expiry_date,
                mm.auto_renew,
                mm.status,
                c.first_name,
                c.last_name,
                c.email,
                c.phone,
                c.address_street as address,
                c.address_city as poststed,
                c.date_of_birth as dateofbirth,
                c.notes,
                p.name as membership_type
            FROM membership_members mm
            LEFT JOIN $customerTable c ON mm.customer_id = c.id
            LEFT JOIN membership_plans p ON mm.membership_plan_id = p.id
            WHERE mm.company_id = ?
            ORDER BY mm.created_at DESC
        ");
    } else {
        // PostgreSQL: membership_members has all columns directly, JOIN with x_membership_plan
        $stmt = $db->prepare("
            SELECT 
                m.member_id,
                m.first_name,
                m.last_name,
                m.email,
                m.phone,
                m.address,
                m.poststed,
                m.dateofbirth,
                m.join_date,
                m.expiry_date,
                m.status,
                m.notes,
                m.auto_renew,
                p.plan_name as membership_type
            FROM membership_members m
            LEFT JOIN x_membership_plan p ON m.membership_plan_id = p.plan_id
            WHERE m.company_id = ?
            ORDER BY m.created_at DESC
        ");
    }
    
    $stmt->execute([$_SESSION['company_id']]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'members' => $members
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching members: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved henting av medlemmer: ' . $e->getMessage()
    ]);
}
