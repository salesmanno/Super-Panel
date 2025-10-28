<?php

define('DB_HOST', 'salesmanno04.mysql.domeneshop.no');
define('DB_NAME', 'salesmanno04');
define('DB_USER', 'salesmanno04');
define('DB_PASS', 'samle-lime-2Salgs-kd');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $conn = null;
    
    // Test if connection is still alive
    if ($conn !== null) {
        try {
            $conn->query('SELECT 1');
        } catch (PDOException $e) {
            // Connection died, reset it
            error_log("MySQL connection died, reconnecting...");
            $conn = null;
        }
    }
    
    if ($conn === null) {
        $maxRetries = 3;
        $retryDelay = 100000; // 100ms in microseconds
        
        for ($i = 0; $i < $maxRetries; $i++) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 15,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    PDO::ATTR_PERSISTENT => false // Don't use persistent connections
                ];
                
                $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
                
                if ($i > 0) {
                    error_log("MySQL connection successful after " . ($i + 1) . " attempts");
                }
                
                break; // Success!
            } catch (PDOException $e) {
                if ($i < $maxRetries - 1) {
                    error_log("MySQL connection attempt " . ($i + 1) . " failed: " . $e->getMessage() . " - retrying...");
                    usleep($retryDelay * ($i + 1)); // Exponential backoff
                } else {
                    error_log("MySQL connection failed after " . $maxRetries . " attempts: " . $e->getMessage());
                    throw new Exception("Database connection failed");
                }
            }
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
    return "INTERVAL ? {$unit}";
}

function closeDBConnection() {
    global $conn;
    $conn = null;
}
