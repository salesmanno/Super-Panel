<?php
// Don't intercept API requests
$request_uri = $_SERVER['REQUEST_URI'];
if (strpos($request_uri, '/api/') !== false) {
    // This is an API request, don't handle it here
    http_response_code(404);
    exit;
}

session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['tenant_admin_id']) && isset($_SESSION['company_id'])) {
    header('Location: pages/company_dashboard.php');
    exit;
}

// Include the refined login page
include 'pages/company_login.php';
exit;
