<?php

function getDBConnection() {
    static $conn = null;
    static $error = null;
    
    if ($conn === null && $error === null) {
        try {
            // Use Replit PostgreSQL if DATABASE_URL is available
            $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?? null;
            
            if ($databaseUrl) {
                // Replit PostgreSQL - use DATABASE_URL directly
                $conn = new PDO($databaseUrl, null, null, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
            } else {
                // Fallback to local config for webhost deployments
                if (file_exists(__DIR__ . '/database.local.php')) {
                    require __DIR__ . '/database.local.php';
                } else {
                    define('DB_HOST', 'localhost');
                    define('DB_NAME', 'salesman');
                    define('DB_USER', 'root');
                    define('DB_PASS', '');
                }
                
                define('DB_CHARSET', 'utf8mb4');
                
                $mysqlDsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                $conn = new PDO($mysqlDsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 5,
                ]);
            }
        } catch (PDOException $e) {
            $error = $e->getMessage();
            error_log("DATABASE CONNECTION ERROR: " . $error);
            return null;
        }
    }
    
    return $conn;
}

function getDBType() {
    $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?? null;
    if ($databaseUrl && stripos($databaseUrl, 'postgresql') !== false) {
        return 'postgresql';
    }
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
