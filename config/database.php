<?php

// MySQL credentials for production (Domeneshop)
define('DB_HOST', 'salesmanno04.mysql.domeneshop.no');
define('DB_NAME', 'salesmanno04');
define('DB_USER', 'salesmanno04');
define('DB_PASS', 'samle-lime-2Salgs-kd');
define('DB_CHARSET', 'utf8mb4');

function getDBConnection() {
    static $conn = null;
    static $dbType = null;
    
    if ($conn === null) {
        // PRIMARY: Try MySQL first (Domeneshop - used in both production and development)
        try {
            $mysqlDsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ];
            
            $conn = new PDO($mysqlDsn, DB_USER, DB_PASS, $options);
            $dbType = 'mysql';
            error_log("Connected to MySQL (Primary)");
            
        } catch (PDOException $e) {
            error_log("MySQL connection failed: " . $e->getMessage());
            
            // FALLBACK: Try PostgreSQL if MySQL fails (Replit environment)
            try {
                $pgHost = getenv('PGHOST');
                $pgPort = getenv('PGPORT') ?: '5432';
                $pgDatabase = getenv('PGDATABASE');
                $pgUser = getenv('PGUSER');
                $pgPassword = getenv('PGPASSWORD');
                
                if (!$pgHost || !$pgDatabase || !$pgUser || !$pgPassword) {
                    throw new Exception("PostgreSQL environment variables not set");
                }
                
                $dsn = "pgsql:host={$pgHost};port={$pgPort};dbname={$pgDatabase};sslmode=require";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $conn = new PDO($dsn, $pgUser, $pgPassword, $options);
                $dbType = 'postgresql';
                error_log("Connected to PostgreSQL (Fallback)");
                
            } catch (Exception $pgError) {
                error_log("PostgreSQL fallback also failed: " . $pgError->getMessage());
                throw new Exception("KRITISK FEIL: Kan ikke koble til database. MySQL: " . $e->getMessage() . " | PostgreSQL: " . $pgError->getMessage());
            }
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
            $dbType = $driver === 'pgsql' ? 'postgresql' : 'mysql';
        } catch (Exception $e) {
            // Default to MySQL (primary database)
            $dbType = 'mysql';
        }
    }
    
    return $dbType;
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
