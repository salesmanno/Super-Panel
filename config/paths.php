<?php

// Automatically detect the base path of the application
// This works whether the app is in root (/) or a subdirectory (/salesman/)
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
define('BASE_PATH', $scriptName === '/' ? '' : $scriptName);

// Helper function to create path-aware URLs
function url($path) {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . $path;
}

// Helper function for asset URLs
function asset($path) {
    $path = ltrim($path, '/');
    return BASE_PATH . '/' . $path;
}
