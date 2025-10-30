<?php
session_start();
require_once 'config/database.php';

// Set session for testing
if (!isset($_SESSION['company_id'])) {
    $_SESSION['company_id'] = '1'; // Test company
}
if (!isset($_SESSION['tenant_admin_id'])) {
    $_SESSION['tenant_admin_id'] = '1'; // Test admin
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Connection Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: green; }
        .error { color: red; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #4CAF50; color: white; }
    </style>
</head>
<body>
    <h1>🔍 Database Connection Test</h1>
    
    <?php
    try {
        $db = getDBConnection();
        $dbType = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        
        echo "<div class='box'>";
        echo "<h2 class='success'>✅ Database Connected</h2>";
        echo "<p><strong>Type:</strong> " . strtoupper($dbType) . "</p>";
        
        if ($dbType === 'mysql') {
            $stmt = $db->query("SELECT DATABASE() as db_name");
            $dbName = $stmt->fetchColumn();
            echo "<p><strong>Database Name:</strong> $dbName</p>";
            echo "<p><strong>Host:</strong> " . DB_HOST . "</p>";
        } else {
            echo "<p><strong>Database:</strong> PostgreSQL (Development/Fallback)</p>";
        }
        echo "</div>";
        
        // Count events
        echo "<div class='box'>";
        echo "<h2>📊 Data Counts</h2>";
        echo "<table>";
        echo "<tr><th>Table</th><th>Count</th></tr>";
        
        $tables = [
            'events_eventdata' => 'Arrangementer',
            'membership_members' => 'Medlemmer',
            'unified_ledger' => 'Unified Ledger',
            'sales_transactions' => 'Sales Transactions',
            'events_checkin' => 'Event Check-ins'
        ];
        
        foreach ($tables as $table => $label) {
            try {
                $stmt = $db->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                echo "<tr><td>$label ($table)</td><td>$count</td></tr>";
            } catch (Exception $e) {
                echo "<tr><td>$label ($table)</td><td class='error'>Table does not exist</td></tr>";
            }
        }
        echo "</table>";
        echo "</div>";
        
        // Show recent events
        echo "<div class='box'>";
        echo "<h2>📅 Recent Events</h2>";
        try {
            $stmt = $db->query("SELECT id, title, start_date FROM events_eventdata ORDER BY start_date DESC LIMIT 5");
            $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($events) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Title</th><th>Start Date</th></tr>";
                foreach ($events as $event) {
                    echo "<tr><td>{$event['id']}</td><td>{$event['title']}</td><td>{$event['start_date']}</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>❌ No events found in database</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        }
        echo "</div>";
        
        // Show recent unified_ledger entries
        echo "<div class='box'>";
        echo "<h2>💰 Recent Unified Ledger Entries</h2>";
        try {
            $stmt = $db->query("SELECT module_source, item_type, item_name, selling_price, transaction_date FROM unified_ledger ORDER BY transaction_date DESC LIMIT 5");
            $ledgers = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($ledgers) > 0) {
                echo "<table>";
                echo "<tr><th>Module</th><th>Type</th><th>Item</th><th>Price</th><th>Date</th></tr>";
                foreach ($ledgers as $ledger) {
                    echo "<tr><td>{$ledger['module_source']}</td><td>{$ledger['item_type']}</td><td>{$ledger['item_name']}</td><td>{$ledger['selling_price']}</td><td>{$ledger['transaction_date']}</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>❌ No ledger entries found</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        }
        echo "</div>";
        
        // Show membership data
        echo "<div class='box'>";
        echo "<h2>👥 Membership Data</h2>";
        try {
            $stmt = $db->query("SELECT id, member_code, status, join_date FROM membership_members ORDER BY join_date DESC LIMIT 5");
            $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($members) > 0) {
                echo "<table>";
                echo "<tr><th>ID</th><th>Member Code</th><th>Status</th><th>Join Date</th></tr>";
                foreach ($members as $member) {
                    echo "<tr><td>{$member['id']}</td><td>{$member['member_code']}</td><td>{$member['status']}</td><td>{$member['join_date']}</td></tr>";
                }
                echo "</table>";
            } else {
                echo "<p class='error'>❌ No members found</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>Error: " . $e->getMessage() . "</p>";
        }
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='box'>";
        echo "<h2 class='error'>❌ Database Connection Failed</h2>";
        echo "<p class='error'>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    ?>
    
    <div class='box'>
        <p><a href='/pages/company_dashboard.php'>← Back to Dashboard</a></p>
    </div>
</body>
</html>
