<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    
    try {
        $db->exec("ALTER TABLE event_registrations ADD COLUMN created_by_admin CHAR(36) DEFAULT NULL AFTER company_id");
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            throw $e;
        }
    }
    
    try {
        $db->exec("ALTER TABLE event_registrations ADD COLUMN created_by_staff CHAR(36) DEFAULT NULL AFTER created_by_admin");
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            throw $e;
        }
    }
    
    try {
        $db->exec("ALTER TABLE event_registrations ADD KEY fk_event_reg_created_by_admin (created_by_admin)");
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
    }
    
    try {
        $db->exec("ALTER TABLE event_registrations ADD KEY fk_event_reg_created_by_staff (created_by_staff)");
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false) {
            throw $e;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Migration completed: created_by_admin and created_by_staff columns added to event_registrations'
    ]);
    
} catch (Exception $e) {
    error_log("Migration error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Migration failed: ' . $e->getMessage()
    ]);
}
