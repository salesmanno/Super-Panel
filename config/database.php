<?php

function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $database_url = getenv('DATABASE_URL');
            
            if ($database_url) {
                $parsed = parse_url($database_url);
                
                $dsn = "pgsql:";
                $dsn .= "host=" . ($parsed['host'] ?? 'localhost');
                
                if (isset($parsed['port'])) {
                    $dsn .= ";port=" . $parsed['port'];
                }
                
                if (isset($parsed['path'])) {
                    $dbname = ltrim($parsed['path'], '/');
                    $dsn .= ";dbname=" . $dbname;
                }
                
                if (isset($parsed['query'])) {
                    $queryParams = [];
                    parse_str($parsed['query'], $queryParams);
                    foreach ($queryParams as $key => $value) {
                        $dsn .= ";{$key}={$value}";
                    }
                }
                
                $username = $parsed['user'] ?? null;
                $password = $parsed['pass'] ?? null;
            } else {
                $dsn = "mysql:host=salesmanno04.mysql.domeneshop.no;port=3306;dbname=salesmanno04;charset=utf8mb4";
                $username = "salesmanno04";
                $password = "samle-lime-2Salgs-kd";
            }
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 10
            ];
            
            $conn = new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed");
        }
    }
    
    return $conn;
}

function getDBType() {
    $database_url = getenv('DATABASE_URL');
    if ($database_url && (strpos($database_url, 'postgresql://') === 0 || strpos($database_url, 'postgres://') === 0)) {
        return 'postgresql';
    }
    return 'mysql';
}

function dbBool($value) {
    $dbType = getDBType();
    if ($dbType === 'postgresql') {
        return $value ? 'true' : 'false';
    }
    return $value ? '1' : '0';
}

function dbInterval($amount, $unit = 'MINUTE') {
    $dbType = getDBType();
    if ($dbType === 'postgresql') {
        return "INTERVAL '" . intval($amount) . " " . strtoupper($unit) . "'";
    }
    return "INTERVAL " . intval($amount) . " {$unit}";
}

function closeDBConnection() {
    global $conn;
    $conn = null;
}
