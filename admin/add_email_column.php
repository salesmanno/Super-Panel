<?php
require_once __DIR__ . '/../config/database.php';

echo "=== Adding email column to tenant_admins ===\n\n";

try {
    $conn = getDBConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Step 1: Checking if email column exists...\n";
    $stmt = $conn->query("SHOW COLUMNS FROM tenant_admins LIKE 'email'");
    $columnExists = $stmt->rowCount() > 0;
    
    if ($columnExists) {
        echo "ℹ Email column already exists\n\n";
    } else {
        echo "Step 2: Adding email column after username...\n";
        $conn->exec("ALTER TABLE tenant_admins 
                     ADD COLUMN email VARCHAR(255) NULL AFTER username");
        echo "✓ Email column added successfully\n\n";
        
        echo "Step 3: Populating email from linked tenant_staff records...\n";
        $conn->exec("UPDATE tenant_admins ta
                     INNER JOIN tenant_staff ts ON ta.tenant_staff_id = ts.id
                     SET ta.email = ts.email
                     WHERE ta.email IS NULL AND ts.email IS NOT NULL");
        echo "✓ Email populated from tenant_staff records\n\n";
        
        echo "Step 3b: Setting placeholder for admins without staff link...\n";
        $conn->exec("UPDATE tenant_admins 
                     SET email = CONCAT(username, '@placeholder.local') 
                     WHERE email IS NULL");
        echo "⚠ Placeholder emails set for admins without staff link\n\n";
        
        echo "Step 4: Making email NOT NULL...\n";
        $conn->exec("ALTER TABLE tenant_admins 
                     MODIFY COLUMN email VARCHAR(255) NOT NULL");
        echo "✓ Email column set to NOT NULL\n\n";
        
        echo "Step 5: Adding unique constraint on (company_id, email)...\n";
        try {
            $conn->exec("ALTER TABLE tenant_admins 
                         ADD UNIQUE KEY unique_company_email (company_id, email)");
            echo "✓ Unique constraint added successfully\n\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                echo "⚠ Warning: Duplicate emails found.\n\n";
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
