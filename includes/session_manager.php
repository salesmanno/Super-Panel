<?php

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/functions.php';

if (isset($_SESSION['session_id']) && isset($_SESSION['tenant_admin_id'])) {
    try {
        updateSessionActivity($_SESSION['session_id']);
        endInactiveSessions(30);
    } catch (Exception $e) {
        // Database unavailable - continue anyway
        error_log("Session update failed (database unavailable): " . $e->getMessage());
    }
}
