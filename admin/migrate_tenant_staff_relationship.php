<?php
require_once __DIR__ . '/../config/database.php';

echo "=== Tenant Staff Relationship Migration ===\n\n";

try {
    $conn = getDBConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Step 1: Adding tenant_staff_id column to tenant_admins...\n";
    try {
        $conn->exec("ALTER TABLE tenant_admins 
                     ADD COLUMN tenant_staff_id CHAR(36) NULL AFTER company_id");
        echo "✓ Column added successfully\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
            echo "ℹ Column already exists, skipping...\n\n";
        } else {
            throw $e;
        }
    }
    
    echo "Step 2: Copying existing relationships (reversing tenant_admin_id in tenant_staff)...\n";
    $stmt = $conn->query("SELECT id, tenant_admin_id FROM tenant_staff WHERE tenant_admin_id IS NOT NULL");
    $staffRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    foreach ($staffRecords as $record) {
        $updateStmt = $conn->prepare("UPDATE tenant_admins 
                                       SET tenant_staff_id = :staff_id 
                                       WHERE id = :admin_id");
        $updateStmt->execute([
            'staff_id' => $record['id'],
            'admin_id' => $record['tenant_admin_id']
        ]);
        $updated++;
    }
    echo "✓ Updated $updated admin records with staff references\n\n";
    
    echo "Step 3: Adding foreign key constraint...\n";
    try {
        // Check if foreign key already exists
        $stmt = $conn->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
            AND TABLE_NAME = 'tenant_admins' 
            AND COLUMN_NAME = 'tenant_staff_id' 
            AND REFERENCED_TABLE_NAME = 'tenant_staff'
        ");
        $existingFk = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existingFk) {
            echo "ℹ Foreign key constraint '{$existingFk['CONSTRAINT_NAME']}' already exists, skipping...\n\n";
        } else {
            $conn->exec("ALTER TABLE tenant_admins 
                         ADD CONSTRAINT fk_tenant_admins_staff 
                         FOREIGN KEY (tenant_staff_id) REFERENCES tenant_staff(id) ON DELETE SET NULL");
            echo "✓ Foreign key constraint added successfully\n\n";
        }
    } catch (PDOException $e) {
        echo "⚠ Warning: Could not add foreign key: " . $e->getMessage() . "\n\n";
    }
    
    echo "Step 4: Removing foreign key constraint on tenant_admin_id...\n";
    try {
        // First, find the foreign key name
        $stmt = $conn->query("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
            AND TABLE_NAME = 'tenant_staff' 
            AND COLUMN_NAME = 'tenant_admin_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        $fkResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($fkResult) {
            $fkName = $fkResult['CONSTRAINT_NAME'];
            $conn->exec("ALTER TABLE tenant_staff DROP FOREIGN KEY {$fkName}");
            echo "✓ Foreign key constraint '{$fkName}' dropped successfully\n\n";
        } else {
            echo "ℹ No foreign key constraint found on tenant_admin_id\n\n";
        }
    } catch (PDOException $e) {
        echo "⚠ Warning: Could not drop foreign key: " . $e->getMessage() . "\n\n";
    }
    
    echo "Step 5: Removing tenant_admin_id column from tenant_staff...\n";
    try {
        $conn->exec("ALTER TABLE tenant_staff DROP COLUMN tenant_admin_id");
        echo "✓ Column removed successfully\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "check that it exists") !== false) {
            echo "ℹ Column already removed, skipping...\n\n";
        } else {
            throw $e;
        }
    }
    
    echo "Step 6: Removing salary column from tenant_staff...\n";
    try {
        $conn->exec("ALTER TABLE tenant_staff DROP COLUMN salary");
        echo "✓ Salary column removed successfully\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "check that it exists") !== false) {
            echo "ℹ Salary column already removed, skipping...\n\n";
        } else {
            throw $e;
        }
    }
    
    echo "Step 7: Removing hire_date column from tenant_staff...\n";
    try {
        $conn->exec("ALTER TABLE tenant_staff DROP COLUMN hire_date");
        echo "✓ Hire_date column removed successfully\n\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "check that it exists") !== false) {
            echo "ℹ Hire_date column already removed, skipping...\n\n";
        } else {
            throw $e;
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
    
    echo "Final tenant_staff structure:\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $conn->query("DESCRIBE tenant_staff");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $col) {
        echo sprintf("%-25s %-20s %-10s %-10s\n", 
            $col['Field'], 
            $col['Type'], 
            $col['Null'], 
            $col['Key']
        );
    }
    echo str_repeat("-", 80) . "\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
