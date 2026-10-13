<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/event_visibility.php';
try {
    $data = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($data) || !is_string($data['event_id'] ?? null) || $data['event_id'] === ''
        || !in_array($data['is_active'] ?? null, [0, 1, '0', '1'], true)) {
        throw new InvalidArgumentException('Arrangement-ID og synlighet (0/1) må oppgis');
    }
    entityFormRequireCsrf($data);
    setEventVisibility(getDBConnection(), (string)($_SESSION['company_id'] ?? ''), $data['event_id'], (int)$data['is_active']);
    echo json_encode(['success' => true, 'message' => (int)$data['is_active'] === 1
        ? 'Arrangementet er synlig igjen.' : 'Arrangementet er skjult. Historikken er bevart.']);
} catch (Throwable $e) {
    http_response_code($e instanceof TenantAuditException ? 403 : ($e instanceof OutOfBoundsException ? 404 : 400));
    error_log('set_event_visibility: ' . get_class($e));
    echo json_encode(['success' => false, 'message' => $e instanceof PDOException
        ? 'Kunne ikke endre synligheten. Ingen endringer ble lagret.' : $e->getMessage()]);
}
