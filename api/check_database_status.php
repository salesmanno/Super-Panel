<?php
// Set a strict timeout for this script
set_time_limit(1);
ini_set('mysql.connect_timeout', '1');
ini_set('default_socket_timeout', '1');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config/database.php';

$response = [
    'status' => 'disconnected'
];

// Quick check - if database connection fails, return immediately
$conn = @getDBConnection();

if ($conn !== null) {
    try {
        // Try a simple query to verify connection
        $stmt = $conn->query("SELECT 1");
        
        if ($stmt) {
            $response['status'] = 'connected';
        }
    } catch (Exception $e) {
        error_log("Database status check error: " . $e->getMessage());
        $response['status'] = 'disconnected';
    }
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
