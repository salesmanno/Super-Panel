<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'error' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    // TODO: Replace with actual table name when created
    // For now, return empty array
    echo json_encode([
        'success' => true,
        'services' => []
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching reservation services: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Kunne ikke hente tjenester'
    ]);
}
