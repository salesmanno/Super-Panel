<?php
session_start();

require_once __DIR__ . '/../includes/functions.php';

// Try to log session end in database, but don't fail if it doesn't work
if (isset($_SESSION['session_id'])) {
    try {
        endTenantAdminSession($_SESSION['session_id']);
    } catch (Exception $e) {
        error_log("Logout DB update failed (non-critical): " . $e->getMessage());
    }
}

// Always clear session regardless of database
session_unset();
session_destroy();

// Clear session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
}

header('Location: /index.php');
exit;
