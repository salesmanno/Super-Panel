<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDBConnection();
    
    // Check if tenant_staff table exists
    $stmt = $conn->query("SHOW TABLES LIKE 'tenant_staff'");
    $tableExists = $stmt->rowCount() > 0;
    
    echo "tenant_staff table exists: " . ($tableExists ? "YES" : "NO") . "\n\n";
    
    if ($tableExists) {
        // Show current structure
        $stmt = $conn->query("DESCRIBE tenant_staff");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "Current tenant_staff structure:\n";
        echo str_repeat("-", 80) . "\n";
        foreach ($columns as $col) {
            echo sprintf("%-20s %-20s %-10s %-10s\n", 
                $col['Field'], 
                $col['Type'], 
                $col['Null'], 
                $col['Key']
            );
        }
        echo str_repeat("-", 80) . "\n\n";
    }
    
    // Check tenant_admins structure
    $stmt = $conn->query("DESCRIBE tenant_admins");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Current tenant_admins structure:\n";
    echo str_repeat("-", 80) . "\n";
    foreach ($columns as $col) {
        echo sprintf("%-20s %-20s %-10s %-10s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key']
        );
    }
    echo str_repeat("-", 80) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
