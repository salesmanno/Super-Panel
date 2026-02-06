<?php
session_start();
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$id = $data['id'] ?? null;

if (!$id) {
    echo json_encode(['success' => false, 'message' => 'Mangler ID']);
    exit;
}

try {
    $db = getDBConnection();
    $company_id = $_SESSION['company_id'];

    $stmt = $db->prepare("
        SELECT id FROM unified_ledger
        WHERE id = ? AND company_id = ?
    ");
    $stmt->execute([$id, $company_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Oppføring ikke funnet']);
        exit;
    }

    $db->beginTransaction();

    $clearRef = $db->prepare("
        UPDATE events_expenses SET unified_ledger_id = NULL, updated_at = NOW()
        WHERE unified_ledger_id = ?
    ");
    $clearRef->execute([$id]);

    $del = $db->prepare("DELETE FROM unified_ledger WHERE id = ? AND company_id = ?");
    $del->execute([$id, $company_id]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Oppføring slettet'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error deleting ledger entry: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting: ' . $e->getMessage()
    ]);
}
