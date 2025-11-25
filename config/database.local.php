<?php
/**
 * Local database configuration for Replit development
 * 
 * For production webhost deployment:
 * 1. Update these values with your webhost database credentials
 * 2. Do NOT commit this file to git
 */

// Try to get from environment variables first (for CI/CD or deployment)
$db_host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? null;
$db_name = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? null;
$db_user = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? null;
$db_pass = getenv('DB_PASS') ?: $_ENV['DB_PASS'] ?? null;

// If environment variables not set, use these defaults
// ** CHANGE THESE VALUES FOR YOUR DEPLOYMENT **
if (empty($db_host)) {
    $db_host = 'localhost';
}
if (empty($db_name)) {
    $db_name = 'salesman';
}
if (empty($db_user)) {
    $db_user = 'root';
}
if (empty($db_pass)) {
    $db_pass = '';
}

define('DB_HOST', $db_host);
define('DB_NAME', $db_name);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
?>
