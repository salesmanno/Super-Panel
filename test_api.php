<?php
// Simple test to see what check_database_status.php returns
$url = 'http://localhost:5000/api/check_database_status.php';
$context = stream_context_create(['http' => ['timeout' => 3]]);
$response = @file_get_contents($url, false, $context);

echo "Response:\n";
echo $response;
echo "\n\nLength: " . strlen($response);
echo "\n\nFirst 200 chars:\n";
echo substr($response, 0, 200);
