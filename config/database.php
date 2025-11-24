<?php

function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            // Get credentials from environment variables
            $host = getenv('DB_HOST') ?: $_ENV['DB_HOST'] ?? 'salesmanno04.mysql.domeneshop.no';
            $dbname = getenv('DB_NAME') ?: $_ENV['DB_NAME'] ?? 'salesmanno04';
            $user = getenv('DB_USER') ?: $_ENV['DB_USER'] ?? 'salesmanno04';
            $password = getenv('DB_PASSWORD') ?: $_ENV['DB_PASSWORD'] ?? '';
            
            $dsn = "mysql:host={$host};dbname={$dbname};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ];
            
            $conn = new PDO($dsn, $user, $password, $options);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("KRITISK FEIL: Kan ikke koble til database.\n\nFeil: " . $e->getMessage());
        }
    }
    
    return $conn;
}

function getDBType() {
    return 'mysql';
}

function closeDBConnection() {
    global $conn;
    $conn = null;
}
