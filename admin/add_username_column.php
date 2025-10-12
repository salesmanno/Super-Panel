<?php
require_once __DIR__ . '/../config/database.php';

echo "=== Adding username column to tenant_admins ===\n\n";

try {
    $conn = getDBConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Step 1: Checking if username column exists...\n";
    $stmt = $conn->query("SHOW COLUMNS FROM tenant_admins LIKE 'username'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "ℹ Username column already exists\n\n";
    } else {
        echo "Step 2: Adding username column after company_id...\n";
        $conn->exec("ALTER TABLE tenant_admins 
                     ADD COLUMN username VARCHAR(255) NULL AFTER company_id");
        echo "✓ Username column added successfully\n\n";
        
        echo "Step 3: Populating username from email (using part before @)...\n";
        $conn->exec("UPDATE tenant_admins 
                     SET username = SUBSTRING_INDEX(email, '@', 1) 
                     WHERE username IS NULL");
        echo "✓ Username populated from email\n\n";
        
        echo "Step 4: Making username NOT NULL...\n";
        $conn->exec("ALTER TABLE tenant_admins 
                     MODIFY COLUMN username VARCHAR(255) NOT NULL");
        echo "✓ Username column set to NOT NULL\n\n";
        
        echo "Step 5: Adding unique constraint on (company_id, username)...\n";
        try {
            $conn->exec("ALTER TABLE tenant_admins 
                         ADD UNIQUE KEY unique_company_username (company_id, username)");
            echo "✓ Unique constraint added successfully\n\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "⚠ Warning: Duplicate usernames found. Please resolve manually.\n\n";
            } elseif (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "ℹ Unique constraint already exists\n\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "=== Migration completed successfully! ===\n\n";
    
    // Show final structure
    echo "Final tenant_admins structure:\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $conn->query("DESCRIBE tenant_admins");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo sprintf("%-25s %-20s %-10s %-10s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key']
        );
    }
    echo str_repeat("-", 80) . "\n\n";
    
    // Show sample data
    echo "Sample tenant_admins data:\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $conn->query("SELECT id, company_id, username, email, name FROM tenant_admins LIMIT 3");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo sprintf("Username: %-20s Email: %-30s Name: %s\n", 
            $row['username'], 
            $row['email'], 
            $row['name']
        );
    }
    echo str_repeat("-", 80) . "\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
