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
    
    if (empty($data['service_name'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler tjenestenavn']);
        exit;
    }

    // TODO: Implement actual database insertion when table is created
    // For now, return success placeholder
    
    echo json_encode([
        'success' => false,
        'message' => 'Database-tabell for reserverbare tjenester er ikke opprettet ennå. Kontakt systemadministrator for å opprette tabellen.'
    ]);
    
} catch (Exception $e) {
    error_log("Error creating reservation service: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved opprettelse: ' . $e->getMessage()
    ]);
}
