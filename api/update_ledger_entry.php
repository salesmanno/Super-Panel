<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$action = $data['action'] ?? null; // 'update' or 'delete'
$ledger_id = $data['ledger_id'] ?? null;

if (!$ledger_id || !$action) {
    echo json_encode(['success' => false, 'message' => 'Mangler påkrevde felt']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];
    
    // Verify the entry exists and belongs to this company
    $entryStmt = $db->prepare("
        SELECT * FROM unified_ledger 
        WHERE id = ? AND company_id = ?
    ");
    $entryStmt->execute([$ledger_id, $company_id]);
    $entry = $entryStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$entry) {
        echo json_encode(['success' => false, 'message' => 'Oppføring ikke funnet']);
        exit;
    }
    
    // Check if this is a manual entry (can be edited) or system-generated (cannot be edited)
    $metadata = json_decode($entry['metadata'] ?? '{}', true);
    $isManualEntry = $metadata['manual_entry'] ?? false;
    
    if ($action === 'delete') {
        // Only allow deletion of manual entries or if explicitly marked as deletable
        if (!$isManualEntry && $entry['module_source'] !== 'manual') {
            echo json_encode(['success' => false, 'message' => 'Kan kun slette manuelle oppføringer. Automatiske transaksjoner kan ikke slettes.']);
            exit;
        }
        
        // Also delete from events_expenses if linked - with company_id scoping via join
        if ($metadata['events_expense_id'] ?? null) {
            $deleteExpenseStmt = $db->prepare("
                DELETE ee FROM events_expenses ee
                INNER JOIN events_eventdata e ON ee.event_id = e.id AND e.company_id = ?
                WHERE ee.id = ?
            ");
            $deleteExpenseStmt->execute([$company_id, $metadata['events_expense_id']]);
        }
        
        // Delete the ledger entry
        $deleteStmt = $db->prepare("DELETE FROM unified_ledger WHERE id = ? AND company_id = ?");
        $deleteStmt->execute([$ledger_id, $company_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Oppføring slettet'
        ]);
        
    } elseif ($action === 'update') {
        if (!$isManualEntry && $entry['module_source'] !== 'manual') {
            echo json_encode(['success' => false, 'message' => 'Kan kun redigere manuelle oppføringer']);
            exit;
        }
        
        $item_name = $data['item_name'] ?? $entry['item_name'];
        $selling_price = $data['selling_price'] ?? $entry['selling_price'];
        $category = $data['category'] ?? $entry['category'];
        
        $updateStmt = $db->prepare("
            UPDATE unified_ledger 
            SET item_name = ?, selling_price = ?, category = ?, updated_at = NOW()
            WHERE id = ? AND company_id = ?
        ");
        $updateStmt->execute([$item_name, $selling_price, $category, $ledger_id, $company_id]);
        
        // Also update events_expenses if linked - with company_id scoping via join
        if ($metadata['events_expense_id'] ?? null) {
            $updateExpenseStmt = $db->prepare("
                UPDATE events_expenses ee
                INNER JOIN events_eventdata e ON ee.event_id = e.id AND e.company_id = ?
                SET ee.description = ?, ee.amount = ?, ee.updated_at = NOW()
                WHERE ee.id = ?
            ");
            $updateExpenseStmt->execute([$company_id, $item_name, abs($selling_price), $metadata['events_expense_id']]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Oppføring oppdatert'
        ]);
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Ugyldig handling']);
    }
    
} catch (Exception $e) {
    error_log("Update ledger entry error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved oppdatering: ' . $e->getMessage()
    ]);
}
