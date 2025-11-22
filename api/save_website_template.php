<?php
header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['tenant_admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['template_id'])) {
        throw new Exception('Template ID mangler');
    }
    
    $templateId = intval($input['template_id']);
    $companyId = $_SESSION['company_id'];
    $adminId = $_SESSION['tenant_admin_id'];
    
    $db = getDBConnection();
    if (!$db) {
        throw new Exception('Database tilkobling feilet');
    }
    
    $stmt = $db->prepare("SELECT template_id FROM website_templates WHERE template_id = ? AND is_active = 1");
    $stmt->execute([$templateId]);
    if (!$stmt->fetch()) {
        throw new Exception('Ugyldig mal');
    }
    
    $stmt = $db->prepare("SELECT * FROM webconfiguration WHERE company_id = ?");
    $stmt->execute([$companyId]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT tam.granted_at 
        FROM tenant_admin_modules tam 
        JOIN system_modules sm ON tam.module_id = sm.id 
        WHERE tam.tenant_admin_id = ? AND sm.slug = 'ditt_nettsted'
    ");
    $stmt->execute([$adminId]);
    $privilege = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$privilege) {
        throw new Exception('Ingen tilgang til denne modulen');
    }
    
    if ($existing) {
        $configUpdatedAt = strtotime($existing['updated_at']);
        $grantedAt = strtotime($privilege['granted_at']);
        
        if ($grantedAt <= $configUpdatedAt) {
            throw new Exception('Nettside er allerede konfigurert. Du må miste og få tilbake privilegiet for å endre.');
        }
    }
    
    if ($existing) {
        $stmt = $db->prepare("
            UPDATE webconfiguration 
            SET template_id = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE company_id = ?
        ");
        $stmt->execute([$templateId, $companyId]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO webconfiguration (company_id, template_id, updated_at) 
            VALUES (?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$companyId, $templateId]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Mal lagret',
        'template_id' => $templateId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
