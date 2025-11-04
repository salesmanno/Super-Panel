<?php
// Database connection management
// Supports dual database: MySQL (production) and PostgreSQL (development/backup)

function getDBConnection() {
    static $db = null;
    
    if ($db !== null) {
        return $db;
    }
    
    // Try MySQL first (production)
    $mysqlHost = getenv('MYSQL_HOST');
    $mysqlUser = getenv('MYSQL_USER');
    $mysqlPassword = getenv('MYSQL_PASSWORD');
    $mysqlDatabase = getenv('MYSQL_DATABASE');
    
    if ($mysqlHost && $mysqlUser && $mysqlDatabase) {
        try {
            $db = new PDO(
                "mysql:host={$mysqlHost};dbname={$mysqlDatabase};charset=utf8mb4",
                $mysqlUser,
                $mysqlPassword,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $db;
        } catch (PDOException $e) {
            error_log("MySQL connection failed: " . $e->getMessage());
        }
    }
    
    // Fallback to PostgreSQL (Replit development)
    $databaseUrl = getenv('DATABASE_URL');
    if ($databaseUrl) {
        try {
            $db = new PDO(
                $databaseUrl,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $db;
        } catch (PDOException $e) {
            error_log("PostgreSQL connection failed: " . $e->getMessage());
        }
    }
    
    throw new Exception("FEIL: Kan ikke koble til PostgreSQL database (Replit). Feilmelding: could not find driver");
}

// Wrapper for compatibility
function getDB() {
    return getDBConnection();
}
