<?php

define('DB_HOST', 'salesmanno04.mysql.domeneshop.no');
define('DB_NAME', 'salesmanno04');
define('DB_USER', 'salesmanno04');
define('DB_PASS', 'samle-lime-2Salgs-kd');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $conn = null;
    static $failed = false;
    
    if ($failed) {
        return null;
    }
    
    if ($conn === null) {
        try {
            ini_set('default_socket_timeout', '2');
            
            $databaseUrl = getenv('DATABASE_URL');
            
            if ($databaseUrl && strpos($databaseUrl, 'postgresql') !== false) {
                $dbConfig = parse_url($databaseUrl);
                $dsn = sprintf(
                    "pgsql:host=%s;port=%s;dbname=%s;sslmode=require",
                    $dbConfig['host'],
                    $dbConfig['port'] ?? 5432,
                    ltrim($dbConfig['path'], '/')
                );
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 2
                ];
                
                $conn = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
            } else {
                ini_set('mysql.connect_timeout', '2');
                
                $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 2,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            }
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            $failed = true;
            return null;
        }
    }
    
    return $conn;
}

function getDBType() {
    $databaseUrl = getenv('DATABASE_URL');
    if ($databaseUrl && strpos($databaseUrl, 'postgresql') !== false) {
        return 'postgresql';
    }
    return 'mysql';
}

function dbBool($value) {
    $dbType = getDBType();
    if ($dbType === 'postgresql') {
        return $value ? 'TRUE' : 'FALSE';
    }
    return $value ? 1 : 0;
}

function dbInterval($amount, $unit = 'MINUTE') {
    $dbType = getDBType();
    if ($dbType === 'postgresql') {
        return "INTERVAL '{$amount} {$unit}'";
    }
    return "INTERVAL {$amount} {$unit}";
}

function closeDBConnection() {
    global $conn;
    $conn = null;
}
