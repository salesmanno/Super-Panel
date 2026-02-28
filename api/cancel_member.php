<?php
ob_start();
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Server-feil: ' . $error['message']]);
        exit;
    }
});

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Ugyldig forespørsel']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    $data = json_decode(file_get_contents('php://input'), true);

    if (empty($data['member_id'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'member_id er påkrevd']);
        exit;
    }

    $memberId = $data['member_id'];
    $reason   = trim($data['reason'] ?? '');

    $check = $db->prepare("SELECT id FROM membership_members WHERE id = ? AND company_id = ?");
    $check->execute([$memberId, $companyId]);
    if (!$check->fetch()) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Medlem ikke funnet']);
        exit;
    }

    $reactivate = !empty($data['reactivate']);

    if ($reactivate) {
        $stmt = $db->prepare("
            UPDATE membership_members
            SET status = 'active',
                cancellation_active = 0,
                cancellation_reasoning = NULL
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$memberId, $companyId]);
    } else {
        $stmt = $db->prepare("
            UPDATE membership_members
            SET status = 'cancelled',
                cancellation_active = 1,
                cancellation_reasoning = ?
            WHERE id = ? AND company_id = ?
        ");
        $stmt->execute([$reason ?: null, $memberId, $companyId]);
    }

    ob_end_clean();
    echo json_encode(['success' => true, 'message' => $reactivate ? 'Medlemskap reaktivert' : 'Medlemskap kansellert']);

} catch (\Throwable $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
