<?php

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/functions.php';

if (isset($_SESSION['session_id']) && isset($_SESSION['tenant_admin_id'])) {
    updateSessionActivity($_SESSION['session_id']);
    endInactiveSessions(30);
}
