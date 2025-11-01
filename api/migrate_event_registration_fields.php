<?php
/**
 * Migration: Create event_registration_fields table
 * Purpose: Add support for configurable event registration fields
 * Run this once via: https://your-domain.com/api/migrate_event_registration_fields.php
 */

require_once '../config/database.php';

header('Content-Type: application/json');

// Security: Only allow from localhost or authorized admins
if ($_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && $_SERVER['REMOTE_ADDR'] !== '::1') {
    session_start();
    if (!isset($_SESSION['tenant_admin_id'])) {
        die(json_encode(['success' => false, 'message' => 'Unauthorized']));
    }
}

$db = getDBConnection();
$dbType = getDBType();
$results = [];
$errors = [];

try {
    // Create event_registration_fields table
    try {
        if ($dbType === 'mysql') {
            // MySQL version
            $db->exec("
                CREATE TABLE IF NOT EXISTS event_registration_fields (
                    id VARCHAR(255) PRIMARY KEY,
                    event_id VARCHAR(255) NOT NULL,
                    field_name VARCHAR(100) NOT NULL,
                    field_label VARCHAR(255) NOT NULL,
                    field_type VARCHAR(50) NOT NULL DEFAULT 'text',
                    is_required TINYINT(1) NOT NULL DEFAULT 0,
                    is_visible TINYINT(1) NOT NULL DEFAULT 1,
                    display_order INT NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (event_id) REFERENCES events_eventdata(id) ON DELETE CASCADE,
                    INDEX idx_event_registration_fields_event_id (event_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");
        } else {
            // PostgreSQL version
            $db->exec("
                CREATE TABLE IF NOT EXISTS event_registration_fields (
                    id VARCHAR(255) PRIMARY KEY,
                    event_id VARCHAR(255) NOT NULL,
                    field_name VARCHAR(100) NOT NULL,
                    field_label VARCHAR(255) NOT NULL,
                    field_type VARCHAR(50) NOT NULL DEFAULT 'text',
                    is_required SMALLINT NOT NULL DEFAULT 0,
                    is_visible SMALLINT NOT NULL DEFAULT 1,
                    display_order INTEGER NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (event_id) REFERENCES events_eventdata(id) ON DELETE CASCADE
                )
            ");
            
            // Create index for PostgreSQL
            $db->exec("CREATE INDEX IF NOT EXISTS idx_event_registration_fields_event_id ON event_registration_fields(event_id)");
        }
        
        $results[] = "✅ Created event_registration_fields table for $dbType";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || 
            strpos($e->getMessage(), 'Duplicate') !== false) {
            $results[] = "ℹ️  event_registration_fields table already exists";
        } else {
            $errors[] = "event_registration_fields: " . $e->getMessage();
        }
    }
    
    // Output results
    echo json_encode([
        'success' => count($errors) === 0,
        'results' => $results,
        'errors' => $errors,
        'database_type' => $dbType
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Migration failed: ' . $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
