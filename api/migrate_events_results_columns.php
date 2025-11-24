<?php
/**
 * Events Results Column Enhancement
 * Adds comprehensive income/expense tracking columns to events_results
 * URL: /api/migrate_events_results_columns.php
 */

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$db = getDBConnection();
$results = [];
$errors = [];

try {
    // Revenue: Ticket sales breakdown by payment method
    $columns = [
        'revenue_tickets_cash' => 'DECIMAL(10,2) DEFAULT 0.00',
        'revenue_tickets_card' => 'DECIMAL(10,2) DEFAULT 0.00',
        'revenue_tickets_vipps' => 'DECIMAL(10,2) DEFAULT 0.00',
        'revenue_tickets_invoice' => 'DECIMAL(10,2) DEFAULT 0.00',
        'revenue_merchandise' => 'DECIMAL(10,2) DEFAULT 0.00',
        'revenue_donations' => 'DECIMAL(10,2) DEFAULT 0.00',
        'expense_staff' => 'DECIMAL(10,2) DEFAULT 0.00',
        'expense_venue' => 'DECIMAL(10,2) DEFAULT 0.00',
        'expense_equipment' => 'DECIMAL(10,2) DEFAULT 0.00',
        'expense_materials' => 'DECIMAL(10,2) DEFAULT 0.00',
        'expense_other' => 'DECIMAL(10,2) DEFAULT 0.00',
        'no_shows' => 'INT DEFAULT 0',
        'attendance_rate' => 'DECIMAL(5,2) DEFAULT 0.00',
        'average_age' => 'INT DEFAULT 0',
        'status' => 'VARCHAR(50) DEFAULT \'planning\'',
        'notes' => 'LONGTEXT',
        'organizer_notes' => 'LONGTEXT',
        'finalized_at' => 'DATETIME'
    ];
    
    foreach ($columns as $colName => $colType) {
        try {
            $query = "ALTER TABLE events_results ADD COLUMN {$colName} {$colType}";
            $db->exec($query);
            $results[] = "✅ Added {$colName}";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                $results[] = "ℹ️  {$colName} already exists";
            } else {
                $errors[] = "{$colName}: " . $e->getMessage();
            }
        }
    }
    
    echo json_encode([
        'success' => count($errors) === 0,
        'message' => 'Migration completed',
        'results' => $results,
        'errors' => $errors
    ], JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'errors' => $errors
    ], JSON_PRETTY_PRINT);
}
?>

