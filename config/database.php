<?php

function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $mysqlDsn = 'mysql:host=salesmanno04.mysql.domeneshop.no;dbname=salesmanno04;charset=utf8mb4';
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ];
            
            $conn = new PDO($mysqlDsn, 'salesmanno04', 'samle-lime-2Salgs-kd', $options);
            
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
