<?php
session_start();
session_write_close();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$db = getDBConnection();
$company_id = $_SESSION['company_id'];
$terms_id = $_GET['id'] ?? null;

try {
    if ($terms_id) {
        // Get specific terms
        $stmt = $db->prepare("
            SELECT * FROM tenant_terms_conditions 
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$terms_id, $company_id]);
        $term = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$term) {
            echo json_encode(['success' => false, 'message' => 'Betingelser ikke funnet']);
            exit;
        }
        
        // Get acceptance count
        $countStmt = $db->prepare("
            SELECT COUNT(*) as count FROM tenant_terms_acceptances 
            WHERE terms_conditions_id = ?
        ");
        $countStmt->execute([$terms_id]);
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        $term['acceptance_count'] = $count['count'];
        
        echo json_encode(['success' => true, 'data' => $term]);
    } else {
        // Get all active terms for company
        $stmt = $db->prepare("
            SELECT tc.*, COUNT(ta.id) as acceptance_count
            FROM tenant_terms_conditions tc
            LEFT JOIN tenant_terms_acceptances ta ON tc.id = ta.terms_conditions_id
            WHERE tc.company_id = ? AND tc.is_active = ?
            GROUP BY tc.id
            ORDER BY tc.effective_date DESC
        ");
        $stmt->execute([$company_id, 1]);
        $terms = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $terms]);
    }
} catch (Exception $e) {
    error_log("Get terms error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved henting av betingelser']);
}
