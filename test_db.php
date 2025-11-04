<?php
// Database Connection Test for super.salesman.no
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Database Connection Test - super.salesman.no</h1>";
echo "<pre>";

echo "=== ENVIRONMENT CHECK ===\n";
echo "REPL_ID: " . (getenv('REPL_ID') ? 'YES (Replit)' : 'NO (Production)') . "\n";
echo "DATABASE_URL: " . (getenv('DATABASE_URL') ? 'EXISTS' : 'NOT SET') . "\n";
echo "PHP Version: " . phpversion() . "\n\n";

echo "=== LOADING CONFIG ===\n";
try {
    require_once 'config/database.php';
    echo "✅ config/database.php loaded\n\n";
} catch (Exception $e) {
    echo "❌ ERROR loading config: " . $e->getMessage() . "\n";
    exit;
}

echo "=== DATABASE CONNECTION TEST ===\n";
try {
    $db = getDBConnection();
    echo "✅ SUCCESS: Connected to database!\n";
    echo "Database type: " . getDBType() . "\n\n";
    
    echo "=== TEST QUERY ===\n";
    $stmt = $db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    echo "✅ Test query successful!\n";
    echo "Result: " . print_r($result, true) . "\n";
    
} catch (Exception $e) {
    echo "❌ DATABASE ERROR:\n";
    echo "Message: " . $e->getMessage() . "\n\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>";
echo "<hr>";
echo "<p><strong>Neste steg:</strong></p>";
echo "<ol>";
echo "<li>Hvis du ser en feil ovenfor, last opp den oppdaterte <code>config/database.php</code></li>";
echo "<li>Når database kobler til OK, slett denne filen (<code>test_db.php</code>) av sikkerhetsgrunner</li>";
echo "<li>Gå til <a href='/'>hovedsiden</a> og test navigasjonen</li>";
echo "</ol>";
