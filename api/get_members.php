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
            p.plan_name as membership_type
        FROM membership_members m
        LEFT JOIN x_membership_plan p ON m.plan_id = p.plan_id
        WHERE m.company_id = ?
        ORDER BY m.created_at DESC
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
