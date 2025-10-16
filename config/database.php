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
            // Try MySQL first
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
        } catch (PDOException $e) {
            error_log("MySQL connection failed: " . $e->getMessage() . ". Falling back to PostgreSQL.");
            
            // Fallback to PostgreSQL if MySQL fails
            $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
            if ($databaseUrl) {
                try {
                    // Parse PostgreSQL URL: postgresql://user:pass@host:port/dbname
                    $parsed = parse_url($databaseUrl);
                    $pgDsn = sprintf(
                        "pgsql:host=%s;port=%s;dbname=%s",
                        $parsed['host'],
                        $parsed['port'] ?? 5432,
                        ltrim($parsed['path'], '/')
                    );
                    $conn = new PDO($pgDsn, $parsed['user'], $parsed['pass'], [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]);
                } catch (PDOException $pgErr) {
                    error_log("PostgreSQL connection also failed: " . $pgErr->getMessage());
                    return null;
                }
            } else {
                return null;
            }
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
