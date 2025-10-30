<?php

define('DB_HOST', 'salesmanno04.mysql.domeneshop.no');
define('DB_NAME', 'salesmanno04');
define('DB_USER', 'salesmanno04');
define('DB_PASS', 'samle-lime-2Salgs-kd');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $conn = null;
    static $dbType = null;
    
    if ($conn === null) {
        // Try MySQL first (production)
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            $conn = new PDO($dsn, DB_USER, DB_PASS, $options);
            $dbType = 'mysql';
        } catch (PDOException $e) {
            error_log("MySQL connection failed: " . $e->getMessage());
            
            // Fallback to PostgreSQL (development/backup)
            try {
                $pgUrl = getenv('DATABASE_URL');
                if (!$pgUrl) {
                    throw new Exception("No PostgreSQL connection available");
                }
                
                // Parse PostgreSQL URL to PDO DSN format
                $parts = parse_url($pgUrl);
                $pgHost = $parts['host'] ?? 'localhost';
                $pgPort = $parts['port'] ?? 5432;
                $pgDb = ltrim($parts['path'] ?? '', '/');
                $pgUser = $parts['user'] ?? '';
                $pgPass = $parts['pass'] ?? '';
                
                $pgDsn = "pgsql:host={$pgHost};port={$pgPort};dbname={$pgDb};sslmode=require";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $conn = new PDO($pgDsn, $pgUser, $pgPass, $options);
                $dbType = 'postgresql';
                error_log("Using PostgreSQL fallback connection");
            } catch (PDOException $pgE) {
                error_log("PostgreSQL connection also failed: " . $pgE->getMessage());
                throw new Exception("Database connection failed");
            }
        }
    }
    
    return $conn;
}

function getDBType() {
    static $dbType = null;
    
    if ($dbType === null) {
        // Trigger connection to set dbType
        getDBConnection();
    }
    
    // Check actual connection driver
    try {
        $conn = getDBConnection();
        $driver = $conn->getAttribute(PDO::ATTR_DRIVER_NAME);
        return $driver === 'pgsql' ? 'postgresql' : 'mysql';
    } catch (Exception $e) {
        return 'mysql'; // Default
    }
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
