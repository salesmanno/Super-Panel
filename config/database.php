<?php

// MySQL production configuration
define('DB_HOST', 'salesmanno04.mysql.domeneshop.no');
define('DB_NAME', 'salesmanno04');
define('DB_USER', 'salesmanno04');
define('DB_PASS', 'samle-lime-2Salgs-kd');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $conn = null;
    static $dbType = null;
    
    // Test if connection is still alive
    if ($conn !== null) {
        try {
            $conn->query('SELECT 1');
            return $conn; // Connection is alive
        } catch (PDOException $e) {
            // Connection died, reset it
            error_log("Database connection died, reconnecting...");
            $conn = null;
            $dbType = null;
        }
    }
    
    if ($conn === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 3,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                PDO::ATTR_PERSISTENT => false
            ];
            
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            $dbType = 'mysql';
        } catch (PDOException $e) {
            error_log("MySQL connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $conn;
}

function getDBType() {
    static $dbType = null;
    
    if ($dbType === null) {
        try {
            $conn = getDBConnection();
            $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
            $dbType = ($driver === 'pgsql') ? 'postgresql' : 'mysql';
        } catch (Exception $e) {
            $dbType = 'mysql'; // Default
        }
    }
    
    return $dbType;
}

function dbBool($value) {
    $dbType = getDBType();
    if ($dbType === 'postgresql') {
        return $value ? 'true' : 'false';
    }
    return $value ? '1' : '0';
}

function dbInterval($amount, $unit = 'MINUTE') {
    return "INTERVAL ? {$unit}";
}

function closeDBConnection() {
    global $conn;
    $conn = null;
}
