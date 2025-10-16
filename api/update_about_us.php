<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['about_us']) || !isset($data['company_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
        exit;
    }
    
    $db = getDBConnection();
    
    // Verifiser at admin tilhører dette firmaet
    $stmt = $db->prepare("SELECT company_id FROM tenant_admins WHERE id = ?");
    $stmt->execute([$_SESSION['tenant_admin_id']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$admin || $admin['company_id'] != $data['company_id']) {
        echo json_encode(['success' => false, 'message' => 'Ingen tilgang']);
        exit;
    }
    
    // Oppdater about_us tekst
    $stmt = $db->prepare("UPDATE tenant_companies SET about_us = ? WHERE id = ?");
    $stmt->execute([$data['about_us'], $data['company_id']]);
    
    echo json_encode(['success' => true, 'message' => 'Om oss tekst oppdatert']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Feil: ' . $e->getMessage()]);
}
