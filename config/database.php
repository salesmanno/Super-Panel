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
        // MYSQL ONLY - Domeneshop production database
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
            error_log("Connected to MySQL");
            
        } catch (PDOException $e) {
            error_log("MySQL connection FAILED: " . $e->getMessage());
            throw new Exception("KRITISK FEIL: Kan ikke koble til MySQL på Domeneshop. Sjekk databasetilkoblingen.\n\nFeil: " . $e->getMessage());
        }
    }
    
    return $conn;
}

function getDBType() {
    // MYSQL ONLY - No PostgreSQL fallback
    return 'mysql';
}

function closeDBConnection() {
    global $conn;
    $conn = null;
}
