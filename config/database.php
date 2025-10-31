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
        // Try MySQL first (Domeneshop production)
        try {
            $mysqlDsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 3,
            ];
            
            $conn = new PDO($mysqlDsn, DB_USER, DB_PASS, $options);
            $dbType = 'mysql';
            return $conn;
        } catch (PDOException $e) {
            error_log("MySQL connection failed: " . $e->getMessage());
            
            // Fallback to PostgreSQL
            $pgUrl = null;
            if (file_exists('/tmp/replitdb')) {
                $pgUrl = trim(file_get_contents('/tmp/replitdb'));
            }
            if (!$pgUrl) {
                $pgUrl = getenv('DATABASE_URL');
            }
            
            if ($pgUrl) {
                try {
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
                } catch (PDOException $e) {
                    error_log("PostgreSQL connection failed: " . $e->getMessage());
                    throw new Exception("Database connection failed");
                }
            } else {
                throw new Exception("No database connection available");
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
