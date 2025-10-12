<?php
// Direct access page for bergsoy company
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set bergsoy as the company context
$company = getCompanyBySubdomain('bergsoy');

if (!$company) {
    die('Bergsoy company not found in database!');
}

$_SESSION['company_context'] = $company;

// Check if user is logged in
if (isset($_SESSION['super_user_id']) && $_SESSION['company_id'] === $company['company_id']) {
    // User is logged in, show dashboard
    include 'pages/company_dashboard.php';
} else {
    // Show login page
    include 'pages/company_login.php';
}
?>