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
        LEFT JOIN tenant_customers c ON mm.customer_id = c.id
        LEFT JOIN membership_plans p ON mm.membership_plan_id = p.id
        WHERE mm.company_id = ?
        ORDER BY mm.created_at DESC
    ");
    
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
