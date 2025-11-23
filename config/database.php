<?php

// Require SSH tunnel handler
require_once __DIR__ . '/ssh_tunnel.php';

// MySQL ONLY - Domeneshop production database
define('DB_HOST', 'salesmanno04.mysql.domeneshop.no');
define('DB_NAME', 'salesmanno04');
define('DB_USER', 'salesmanno04');
define('DB_PASS', 'samle-lime-2Salgs-kd');
define('DB_CHARSET', 'utf8mb4');

// SSH Tunnel configuration
define('USE_SSH_TUNNEL', getenv('SSH_ENABLED') === 'true');
define('SSH_TUNNEL_HOST', '127.0.0.1');
define('SSH_TUNNEL_PORT', 3306);

function getDBConnection() {
    static $conn = null;
    
    if ($conn === null) {
        try {
            $host = DB_HOST;
            $port = 3306;
            
            // If SSH tunnel is enabled, use localhost
            if (USE_SSH_TUNNEL) {
                if (!SSHTunnel::initialize()) {
                    error_log("Warning: SSH tunnel initialization failed, attempting direct MySQL connection");
                } else {
                    $host = SSH_TUNNEL_HOST;
                    error_log("Using SSH tunnel for MySQL connection");
                }
            }
            
            $mysqlDsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_TIMEOUT => 5,
            ];
            
            $conn = new PDO($mysqlDsn, DB_USER, DB_PASS, $options);
            error_log("Connected to MySQL at $host");
            
        } catch (PDOException $e) {
            error_log("MySQL connection FAILED: " . $e->getMessage());
            throw new Exception("KRITISK FEIL: Kan ikke koble til MySQL på Domeneshop. Sjekk databasetilkoblingen.\n\nFeil: " . $e->getMessage());
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
    SSHTunnel::close();
}
