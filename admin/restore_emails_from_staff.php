<?php
require_once __DIR__ . '/../config/database.php';

echo "=== Restoring emails from tenant_staff ===\n\n";

try {
    $conn = getDBConnection();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Step 1: Checking for linked staff records with emails...\n";
    $stmt = $conn->query("
        SELECT 
            ta.id as admin_id,
            ta.username,
            ta.email as current_email,
            ts.email as staff_email
        FROM tenant_admins ta
        LEFT JOIN tenant_staff ts ON ta.tenant_staff_id = ts.id
        WHERE ts.email IS NOT NULL
    ");
    $linkedRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($linkedRecords) . " admin records with linked staff emails\n\n";
    
    if (count($linkedRecords) > 0) {
        echo "Step 2: Updating admin emails from staff records...\n";
        $updated = 0;
        foreach ($linkedRecords as $record) {
            // Only update if current email is the placeholder
            if (strpos($record['current_email'], '@example.com') !== false) {
                $updateStmt = $conn->prepare("
                    UPDATE tenant_admins 
                    SET email = ? 
                    WHERE id = ?
                ");
                $updateStmt->execute([$record['staff_email'], $record['admin_id']]);
                echo "  ✓ Updated {$record['username']}: {$record['current_email']} → {$record['staff_email']}\n";
                $updated++;
            }
        }
        echo "\nUpdated $updated email addresses from staff records\n\n";
    }
    
    echo "Step 3: Checking for admins without linked staff...\n";
    $stmt = $conn->query("
        SELECT id, username, email, name
        FROM tenant_admins
        WHERE tenant_staff_id IS NULL OR email LIKE '%@example.com'
    ");
    $orphanedAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($orphanedAdmins) > 0) {
        echo "⚠ Found " . count($orphanedAdmins) . " admin accounts without proper email:\n";
        foreach ($orphanedAdmins as $admin) {
            echo "  - Username: {$admin['username']}, Email: {$admin['email']}, Name: {$admin['name']}\n";
        }
        echo "\n⚠ These accounts need manual email updates!\n\n";
    } else {
        echo "✓ All admin accounts have proper email addresses\n\n";
    }
    
    echo "=== Restoration completed! ===\n\n";
    
    // Show final sample
    echo "Sample tenant_admins data:\n";
    echo str_repeat("-", 80) . "\n";
    $stmt = $conn->query("SELECT username, email, name FROM tenant_admins LIMIT 5");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo sprintf("%-20s %-35s %s\n", 
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
