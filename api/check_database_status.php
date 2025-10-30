<?php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

require_once __DIR__ . '/../config/database.php';

$response = ['status' => 'disconnected'];

try {
    $conn = getDBConnection();
    if ($conn !== null) {
        $stmt = $conn->query("SELECT 1");
        if ($stmt) {
            $response['status'] = 'connected';
        }
    }
} catch (Exception $e) {
    error_log("Database status check error: " . $e->getMessage());
    $response['status'] = 'disconnected';
}

echo json_encode($response);
exit;
