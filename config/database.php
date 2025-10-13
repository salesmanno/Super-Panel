<?php

define('DB_HOST', 'salesmanno04.mysql.domeneshop.no');
define('DB_NAME', 'salesmanno04');
define('DB_USER', 'salesmanno04');
define('DB_PASS', 'samle-lime-2Salgs-kd');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $database_url = getenv('DATABASE_URL');
            
            if ($database_url && strpos($database_url, 'postgresql://') === 0) {
                $url = parse_url($database_url);
                parse_str($url['query'] ?? '', $query_params);
                
                $dsn = sprintf(
                    "pgsql:host=%s;port=%s;dbname=%s;sslmode=%s",
                    $url['host'],
                    $url['port'] ?? 5432,
                    ltrim($url['path'], '/'),
                    $query_params['sslmode'] ?? 'prefer'
                );
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 3
                ];
                
                $conn = new PDO($dsn, $url['user'], $url['pass'], $options);
                $conn->setAttribute(PDO::ATTR_DRIVER_NAME, 'pgsql');
            } else {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 3,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];
                
                $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
                $conn->setAttribute(PDO::ATTR_DRIVER_NAME, 'mysql');
            }
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            return null;
        }
    }
    
    return $conn;
}

function getDBType() {
    $db = getDBConnection();
    if (!$db) return 'mysql';
    return $db->getAttribute(PDO::ATTR_DRIVER_NAME);
}

function dbBool($value) {
    $dbType = getDBType();
    if ($dbType === 'pgsql') {
        return $value ? 'TRUE' : 'FALSE';
    }
    return $value ? '1' : '0';
}

function dbInterval($amount, $unit = 'MINUTE') {
    $dbType = getDBType();
    if ($dbType === 'pgsql') {
        return "INTERVAL '1 {$unit}' * ?";
    }
    return "INTERVAL ? {$unit}";
}

function closeDBConnection() {
    global $conn;
    $conn = null;
}
