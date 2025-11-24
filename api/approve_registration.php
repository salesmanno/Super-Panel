<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['registration_id'])) {
        echo json_encode(['success' => false, 'message' => 'Mangler påmeldings-ID']);
        exit;
    }
    
    $db = getDBConnection();
    
    $stmt = $db->prepare("
        UPDATE event_registrations
        SET 
            approval_status = 'approved',
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ?
        AND company_id = ?
        AND approval_status = 'pending'
    ");
    
    $stmt->execute([
        $_SESSION['tenant_admin_id'],
        $data['registration_id'],
        $_SESSION['company_id']
    ]);
    
    if ($stmt->rowCount() > 0) {
        // PHASE 2: Create events_checkin entry from approved registration
        $dbType = getDBType();
        try {
            // Get registration details
            $stmt = $db->prepare("
                SELECT er.*, tc.email as customer_email
                FROM event_registrations er
                JOIN tenant_customers tc ON er.customer_id = tc.id
                WHERE er.id = ?
            ");
            $stmt->execute([$data['registration_id']]);
            $registration = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($registration) {
                // Generate checkin ID
                if ($dbType === 'postgresql') {
                    $stmt = $db->query("SELECT gen_random_uuid()");
                    $checkin_id = $stmt->fetchColumn();
                } else {
                    $checkin_id = sprintf(
                        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                        mt_rand(0, 0xffff),
                        mt_rand(0, 0x0fff) | 0x4000,
                        mt_rand(0, 0x3fff) | 0x8000,
                        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
                    );
                }
                
                // Create events_checkin with event_registration_id FK
                $stmt = $db->prepare("
                    INSERT INTO events_checkin 
                    (id, event_registration_id, event_id, customer_id, attendee_email, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $checkin_id,
                    $data['registration_id'],
                    $registration['event_id'],
                    $registration['customer_id'],
                    $registration['customer_email']
                ]);
            }
        } catch (Exception $e) {
            error_log("Warning: Could not create events_checkin for registration " . $data['registration_id'] . ": " . $e->getMessage());
            // Don't fail the approval if checkin creation fails
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Påmelding godkjent og registrert i innsjekk'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Fant ikke påmelding eller allerede behandlet'
        ]);
    }
    
} catch (Exception $e) {
    error_log("Error approving registration: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved godkjenning: ' . $e->getMessage()
    ]);
}
