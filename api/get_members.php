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
    // membership_members columns: id, company_id, customer_id, membership_plan_id, 
    // member_code, status, join_date, expiration_date, auto_renew, created_at
    
    // Try to JOIN with tenant_customers table (MySQL) or customers table (PostgreSQL)
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
        } else {
            $checkStmt = $db->query("SELECT 1 FROM information_schema.tables WHERE table_name = 'tenant_customers'");
            if ($checkStmt->rowCount() == 0) {
                $checkStmt = $db->query("SELECT 1 FROM information_schema.tables WHERE table_name = 'customers'");
                if ($checkStmt->rowCount() > 0) {
                    $customerTable = 'customers';
                }
            }
        }
    } catch (Exception $e) {
        // Use default
    }
    
    // MySQL with membership_plans table
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
                p.plan_name as membership_type
            FROM membership_members mm
            LEFT JOIN $customerTable c ON mm.customer_id = c.id
            LEFT JOIN membership_plans p ON mm.membership_plan_id = p.plan_id
            WHERE mm.company_id = ?
            ORDER BY mm.created_at DESC
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
