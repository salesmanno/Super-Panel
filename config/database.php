<?php

function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $databaseUrl = getenv('DATABASE_URL');
            
            if (!$databaseUrl) {
                throw new Exception('DATABASE_URL not set');
            }
            
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
                PDO::ATTR_EMULATE_PREPARES => false
            ];
            
            $conn = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    return $conn;
}

function closeDBConnection() {
    global $conn;
    $conn = null;
}

function getDBType() {
    return 'postgresql';
}

function dbBool($value) {
    return $value ? 'TRUE' : 'FALSE';
}

function dbInterval($minutes) {
    return "INTERVAL '$minutes MINUTES'";
}
