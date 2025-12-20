<?php
if (!isset($_SESSION)) {
    session_start();
}

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Ikke autentisert'
    ]);
    exit;
}
