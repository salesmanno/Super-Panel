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
        SELECT ee.id, ee.unified_ledger_id
        FROM events_expenses ee
        INNER JOIN events_eventdata e ON ee.event_id = e.id
        WHERE ee.id = ? AND e.company_id = ?
    ");
    $stmt->execute([$id, $company_id]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        echo json_encode(['success' => false, 'message' => 'Oppføring ikke funnet']);
        exit;
    }

    $db->beginTransaction();

    if ($existing['unified_ledger_id']) {
        $delLedger = $db->prepare("DELETE FROM unified_ledger WHERE id = ? AND company_id = ?");
        $delLedger->execute([$existing['unified_ledger_id'], $company_id]);
    }

    $delExpense = $db->prepare("DELETE FROM events_expenses WHERE id = ?");
    $delExpense->execute([$id]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Oppføring slettet'
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Error deleting event expense: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Feil ved sletting: ' . $e->getMessage()
    ]);
}
