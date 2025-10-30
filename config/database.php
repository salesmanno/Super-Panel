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
        // Use PostgreSQL directly (faster for development)
        try {
            // Check /tmp/replitdb first (for deployed apps), then environment variable
            $pgUrl = null;
            if (file_exists('/tmp/replitdb')) {
                $pgUrl = trim(file_get_contents('/tmp/replitdb'));
            }
            if (!$pgUrl) {
                $pgUrl = getenv('DATABASE_URL');
            }
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
            error_log("Using PostgreSQL connection");
        } catch (PDOException $e) {
            error_log("PostgreSQL connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
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
    $dbType = getDBType();
    if ($dbType === 'postgresql') {
        return $value ? 'TRUE' : 'FALSE';
    }
    return $value ? '1' : '0';
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
