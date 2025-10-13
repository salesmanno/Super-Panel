<?php
require_once __DIR__ . '/../config/database.php';

try {
    $conn = getDBConnection();
    
    if (!$conn) {
        die("Could not connect to database\n");
    }
    
    echo "Creating tenant_admin_sessions table...\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS tenant_admin_sessions (
        id CHAR(36) PRIMARY KEY DEFAULT (UUID()),
        tenant_admin_id CHAR(36) NOT NULL,
        session_id VARCHAR(255) NOT NULL,
        ip_address VARCHAR(45),
        user_agent TEXT,
        login_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_activity DATETIME DEFAULT CURRENT_TIMESTAMP,
        logout_at DATETIME NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (tenant_admin_id) REFERENCES tenant_admins(id) ON DELETE CASCADE,
        INDEX idx_tenant_admin_id (tenant_admin_id),
        INDEX idx_session_id (session_id),
        INDEX idx_is_active (is_active),
        INDEX idx_login_at (login_at)
    )";
    
    $conn->exec($sql);
    
    echo "✓ Table tenant_admin_sessions created successfully!\n";
    
    // Verify table creation
    $stmt = $conn->query("SHOW TABLES LIKE 'tenant_admin_sessions'");
    if ($stmt->rowCount() > 0) {
        echo "✓ Table verified in database\n";
        
        // Show table structure
        echo "\nTable structure:\n";
        $stmt = $conn->query("DESCRIBE tenant_admin_sessions");
        $columns = $stmt->fetchAll();
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
