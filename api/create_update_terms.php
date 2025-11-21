<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$db = getDBConnection();
$company_id = $_SESSION['company_id'];
$admin_id = $_SESSION['tenant_admin_id'];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        echo json_encode(['success' => false, 'message' => 'Ingen data mottatt']);
        exit;
    }
    
    // Delete action
    if (isset($data['action']) && $data['action'] === 'delete') {
        $stmt = $db->prepare("
            DELETE FROM tenant_terms_conditions 
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$data['id'], $company_id]);
        echo json_encode(['success' => true, 'message' => 'Betingelser slettet']);
        exit;
    }
    
    // Validate required fields
    if (empty($data['title']) || empty($data['content']) || empty($data['effective_date'])) {
        echo json_encode(['success' => false, 'message' => 'Manglende påkrevde felt']);
        exit;
    }
    
    $title = $data['title'];
    $content = $data['content'];
    $version = $data['version'] ?? '1.0';
    $effective_date = $data['effective_date'];
    $acceptance_type = $data['acceptance_type'] ?? 'checkbox';
    $requires_acceptance = $data['requires_customer_acceptance'] ? 1 : 0;
    
    // Update existing
    if (!empty($data['id'])) {
        $stmt = $db->prepare("
            UPDATE tenant_terms_conditions 
            SET title = ?, content = ?, version = ?, effective_date = ?, 
                acceptance_type = ?, requires_customer_acceptance = ?,
                updated_at = NOW()
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([
            $title, $content, $version, $effective_date,
            $acceptance_type, $requires_acceptance,
            $data['id'], $company_id
        ]);
        echo json_encode(['success' => true, 'message' => 'Betingelser oppdatert']);
    } else {
        // Create new
        $id = generateUUID();
        $stmt = $db->prepare("
            INSERT INTO tenant_terms_conditions 
            (id, company_id, title, content, version, effective_date, 
             acceptance_type, requires_customer_acceptance, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $id, $company_id, $title, $content, $version, $effective_date,
            $acceptance_type, $requires_acceptance, $admin_id
        ]);
        echo json_encode(['success' => true, 'message' => 'Betingelser opprettet', 'id' => $id]);
    }
    
} catch (Exception $e) {
    error_log("Create/update terms error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Feil ved lagring av betingelser']);
}

function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}
