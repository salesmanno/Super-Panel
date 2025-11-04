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
        // Detect environment: Replit has REPL_ID, production does not
        $isReplit = getenv('REPL_ID') !== false;
        
        if ($isReplit) {
            // REPLIT: Use PostgreSQL
            try {
                $databaseUrl = getenv('DATABASE_URL');
                if (!$databaseUrl) {
                    throw new Exception("DATABASE_URL not found in Replit environment");
                }
                
                // Parse DATABASE_URL (postgresql://user:pass@host/dbname?sslmode=require)
                $parsed = parse_url($databaseUrl);
                $host = $parsed['host'];
                $dbname = ltrim($parsed['path'], '/');
                $user = $parsed['user'];
                $pass = $parsed['pass'];
                
                // Extract sslmode from query string if present
                $sslmode = '';
                if (isset($parsed['query'])) {
                    parse_str($parsed['query'], $queryParams);
                    if (isset($queryParams['sslmode'])) {
                        $sslmode = ';sslmode=' . $queryParams['sslmode'];
                    }
                }
                
                $dsn = "pgsql:host={$host};dbname={$dbname}{$sslmode}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ];
                
                $conn = new PDO($dsn, $user, $pass, $options);
                $dbType = 'postgresql';
                error_log("Connected to PostgreSQL (Replit)");
            } catch (PDOException $e) {
                error_log("PostgreSQL connection failed: " . $e->getMessage());
                throw new Exception("FEIL: Kan ikke koble til PostgreSQL database (Replit). Feilmelding: " . $e->getMessage());
            }
        } else {
            // PRODUCTION: Use MySQL
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
                error_log("Connected to MySQL (Production)");
            } catch (PDOException $e) {
                error_log("MySQL connection failed: " . $e->getMessage());
                throw new Exception("KRITISK FEIL: Kan ikke koble til MySQL database (Domeneshop). Feilmelding: " . $e->getMessage());
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
            // Fallback detection
            $isReplit = getenv('REPL_ID') !== false;
            $dbType = $isReplit ? 'postgresql' : 'mysql';
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
