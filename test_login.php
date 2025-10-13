<?php
session_start();

echo "<h1>Session Debug</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Database Connection Test</h2>";
require_once 'config/database.php';
$db = getDBConnection();
if ($db) {
    echo "✅ Database connected<br>";
    
    $stmt = $db->prepare("SELECT username FROM tenant_admins WHERE id = 1");
    $stmt->execute();
    $user = $stmt->fetch();
    echo "Test user: " . ($user['username'] ?? 'NOT FOUND') . "<br>";
} else {
    echo "❌ Database connection failed<br>";
}

echo "<h2>Test Login</h2>";
echo '<form method="POST" action="/auth/company_auth.php">';
echo 'Username: <input type="text" name="username" value="Atle"><br>';
echo 'Password: <input type="password" name="password" value="bergsoy123"><br>';
echo '<button type="submit">Login</button>';
echo '</form>';
