<?php
require_once __DIR__ . '/config/paths.php';

// Let API and auth requests pass through if their files exist
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_file = __DIR__ . $request_uri;

if ((strpos($request_uri, '/api/') !== false || strpos($request_uri, '/auth/') !== false) && file_exists($request_file)) {
    // This is an API/auth request with an existing file, don't intercept it
    return false;
}

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['tenant_admin_id']) && isset($_SESSION['company_id'])) {
    header('Location: ' . url('pages/company_dashboard.php'));
    exit;
}

// Include the refined login page
include 'pages/company_login.php';
exit;
