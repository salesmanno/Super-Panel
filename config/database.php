<?php

// Load local config first (for webhost deployments)
if (file_exists(__DIR__ . '/database.local.php')) {
    require __DIR__ . '/database.local.php';
} else {
    // Fall back to environment variables or defaults
    define('DB_HOST', $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?? 'salesmanno04.mysql.domeneshop.no');
    define('DB_NAME', $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?? 'salesmanno04');
    define('DB_USER', $_ENV['DB_USER'] ?? getenv('DB_USER') ?? 'salesmanno04');
    define('DB_PASS', $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?? 'samle-lime-2Salgs-kd');
}

define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
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
            throw new Exception($e->getMessage());
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
