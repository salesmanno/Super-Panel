<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/entity_form_fields.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['event_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler event_id']);
        exit;
    }

    $db = getDBConnection();
    $deleteMode = $data['delete_mode'] ?? 'soft'; // 'soft' or 'hard'
    
    // SECURITY: Verify event ownership BEFORE any deletions
    $ownershipStmt = $db->prepare("
        SELECT id FROM events_eventdata 
        WHERE id = ? AND company_id = ?
    ");
    $ownershipStmt->execute([$data['event_id'], $_SESSION['company_id']]);
    
    if (!$ownershipStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Arrangement ikke funnet eller ingen tilgang']);
        exit;
    }
    
    if ($deleteMode === 'hard') {
        // HARD DELETE: Completely remove from database
        // WARNING: This also removes all related data (registrations, transactions, etc.)
        $db->beginTransaction();
        
        try {
            // 1. Delete from output_products
            $stmt = $db->prepare("
                DELETE FROM output_products 
                WHERE product_id = ? 
                AND product_type = 'events'
                AND company_id = ?
            ");
            $stmt->execute([$data['event_id'], $_SESSION['company_id']]);
            
            // 2. Clear related_event_id in announcements (SET NULL due to FK constraint)
            $stmt = $db->prepare("
                UPDATE tenant_announcements 
                SET related_event_id = NULL 
                WHERE related_event_id = ?
            ");
            $stmt->execute([$data['event_id']]);
            
            // 3. Delete event results
            $stmt = $db->prepare("
                DELETE FROM events_results 
                WHERE event_id = ?
            ");
            $stmt->execute([$data['event_id']]);
            
            // 4. Delete event registrations and check-ins
            $stmt = $db->prepare("
                DELETE FROM event_registrations 
                WHERE event_id = ? AND company_id = ?
            ");
            $stmt->execute([$data['event_id'], $_SESSION['company_id']]);
            
            $stmt = $db->prepare("
                DELETE FROM events_checkin 
                WHERE event_id = ?
            ");
            $stmt->execute([$data['event_id']]);
            
            // 5. Delete registration fields
            entityFormDelete($db, (string)$_SESSION['company_id'], 'event', (string)$data['event_id']);
            
            // 6. Delete membership pricing
            $stmt = $db->prepare("
                DELETE FROM event_membership_plans 
                WHERE event_id = ?
            ");
            $stmt->execute([$data['event_id']]);
            
            // 7. Delete from unified_ledger (event transactions)
            $stmt = $db->prepare("
                DELETE FROM unified_ledger 
                WHERE reference_id = ? AND reference_type = 'event' AND company_id = ?
            ");
            $stmt->execute([$data['event_id'], $_SESSION['company_id']]);
            
            // 8. Delete the event itself
            $stmt = $db->prepare("
                DELETE FROM events_eventdata 
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([$data['event_id'], $_SESSION['company_id']]);
            
            $db->commit();
            
            echo json_encode([
                'success' => true,
                'message' => 'Arrangement og all tilknyttet data slettet permanent'
            ]);
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Hard delete event error: " . $e->getMessage());
            throw $e;
        }
    } else {
        // SOFT DELETE: Mark as inactive instead of deleting
        // This preserves output_products and historical data
        $stmt = $db->prepare("
            UPDATE events_eventdata 
            SET is_active = 0
            WHERE id = ? AND company_id = ?
        ");
        
        $stmt->execute([
            $data['event_id'],
            $_SESSION['company_id']
        ]);
        
        if ($stmt->rowCount() > 0) {
            // Also hide the product from output_products catalog
            $hideStmt = $db->prepare("
                UPDATE output_products 
                SET is_visible = 0, is_public = 0
                WHERE product_id = ? 
                AND product_type = 'events'
                AND company_id = ?
            ");
            $hideStmt->execute([
                $data['event_id'],
                $_SESSION['company_id']
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Arrangement gjort usynlig (data bevart)'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Kunne ikke slette arrangement'
            ]);
        }
    }
    
} catch (Exception $e) {
    error_log("Error deleting event: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting'
    ]);
}
