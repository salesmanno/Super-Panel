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
    
    if (empty($data['service_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler tjeneste-ID']);
        exit;
    }

    // TODO: Implement actual database deletion when table is created
    
    echo json_encode([
        'success' => false,
        'message' => 'Database-tabell for reserverbare tjenester er ikke opprettet ennå.'
    ]);
    
} catch (Exception $e) {
    error_log("Error deleting reservation service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting: ' . $e->getMessage()
    ]);
}
