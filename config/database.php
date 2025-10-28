<?php

function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $host = getenv('MYSQL_HOST') ?: 'salesmanno04.mysql.domeneshop.no';
            $database = getenv('MYSQL_DATABASE') ?: 'salesmanno04';
            $username = getenv('MYSQL_USER') ?: 'salesmanno04';
            $password = getenv('MYSQL_PASSWORD') ?: '';
            $port = getenv('MYSQL_PORT') ?: 3306;
            
            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4",
                $host,
                $port,
                $database
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ];
            
            $conn = new PDO($dsn, $username, $password, $options);
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
    return 'mysql';
}

function dbBool($value) {
    return $value ? 1 : 0;
}

function dbInterval($minutes) {
    return "INTERVAL $minutes MINUTE";
}
