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
    
    // Try PostgreSQL table name first (x_membership_plan)
    try {
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
            LEFT JOIN x_membership_plan p ON m.membership_plan_id = p.plan_id
            WHERE m.company_id = ?
            ORDER BY m.created_at DESC
        ");
        
        $stmt->execute([$_SESSION['company_id']]);
        $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        // If x_membership_plan doesn't exist or column names differ, try MySQL fallback
        if (strpos($e->getMessage(), 'doesn\'t exist') !== false || 
            strpos($e->getMessage(), 'does not exist') !== false ||
            strpos($e->getMessage(), 'Unknown column') !== false ||
            strpos($e->getMessage(), 'Column not found') !== false) {
            $stmt = $db->prepare("
                SELECT 
                    m.id as member_id,
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
                    p.name as membership_type
                FROM membership_members m
                LEFT JOIN membership_plans p ON m.membership_plan_id = p.id
                WHERE m.company_id = ?
                ORDER BY m.created_at DESC
            ");
            
            $stmt->execute([$_SESSION['company_id']]);
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            throw $e;
        }
    }
    
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
