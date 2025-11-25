<?php

// Load local config for webhost deployments (MySQL only)
if (file_exists(__DIR__ . '/database.local.php')) {
    require __DIR__ . '/database.local.php';
} else {
    // Default fallback - configure with your MySQL host
    define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
    define('DB_NAME', getenv('DB_NAME') ?: 'salesman');
    define('DB_USER', getenv('DB_USER') ?: 'root');
    define('DB_PASS', getenv('DB_PASS') ?: '');
}

define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $conn = null;
    static $error = null;
    
    if ($conn === null && $error === null) {
        try {
            $mysqlDsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ];
            
            $conn = new PDO($mysqlDsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            $error = $e->getMessage();
            error_log("DATABASE CONNECTION ERROR: " . $error);
            error_log("DB_HOST: " . DB_HOST . ", DB_NAME: " . DB_NAME . ", DB_USER: " . DB_USER);
            return null;
        }
    }
    
    return $conn;
}

function getDBType() {
    return 'mysql';
}

function dbBool($value) {
    return $value ? '1' : '0';
}

function dbInterval($amount, $unit = 'MINUTE') {
    return "INTERVAL {$amount} {$unit}";
}

function closeDBConnection() {
    global $conn;
    $conn = null;
}
