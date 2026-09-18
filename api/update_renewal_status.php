<?php
// MAINTENANCE QUERY: Reset Automailer_Sent for active members with expiry past 2027
// UPDATE membership_members
// SET Automailer_Sent = 0
// WHERE Automailer_Sent = 1
//   AND expiration_date > '2027-01-01'
//   AND status = 'active';

session_start();
session_write_close();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$company_id = $_SESSION['company_id'];
$data       = json_decode(file_get_contents('php://input'), true);

$id     = trim($data['id']     ?? '');
$status = trim($data['status'] ?? '');

$allowed = ['pending', 'payment_pending', 'completed', 'cancelled', 'expired'];

if ($id === '' || !in_array($status, $allowed, true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ugyldig id eller status']);
    exit;
}

try {
    $db = getDBConnection();

    $completed_at = ($status === 'completed') ? 'NOW()' : 'NULL';

    $stmt = $db->prepare("
        UPDATE membership_members_renew
        SET status       = ?,
            completed_at = {$completed_at}
        WHERE id         = ?
          AND company_id = ?
    ");
    $stmt->execute([$status, $id, $company_id]);

    if ($stmt->rowCount() === 0) {
        $existsStmt = $db->prepare("
            SELECT 1
            FROM membership_members_renew
            WHERE id = ? AND company_id = ?
        ");
        $existsStmt->execute([$id, $company_id]);
        if (!$existsStmt->fetchColumn()) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Rad ikke funnet']);
            exit;
        }
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    error_log('update_renewal_status error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kunne ikke oppdatere fornyelse']);
}
