<?php
declare(strict_types=1);
session_start();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/tenant_audit.php';
require_once __DIR__ . '/../includes/tenant_supervisor.php';
if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Ikke autentisert']);
    exit;
}
$companyId = (string) $_SESSION['company_id'];
try {
    $db = getDBConnection();
    if (!tenantIsSupervisor($db, $companyId)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Du har ikke tilgang til aktivitetsloggen.']);
        exit;
    }
    $columns = 'id,company_id,user_id,user_type,action,resource_type,resource_id,resource_name,old_values,new_values,performed_at,module';
    if (isset($_GET['id'])) {
        $query = $db->prepare("SELECT $columns FROM audit_logs WHERE id = ? AND company_id = ?");
        $query->execute([(string) $_GET['id'], $companyId]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Hendelsen ble ikke funnet.']);
            exit;
        }
        echo json_encode(['success' => true, 'log' => tenantAuditPublicRow($row)], JSON_THROW_ON_ERROR);
        exit;
    }
    $where = ['company_id = ?'];
    $params = [$companyId];
    foreach (['admin_id' => 'user_id', 'module' => 'module', 'action' => 'action'] as $input => $column) {
        if (isset($_GET[$input]) && $_GET[$input] !== '') {
            $where[] = "$column = ?";
            $params[] = mb_substr((string) $_GET[$input], 0, 100);
        }
    }
    foreach (['from' => '>=', 'to' => '<'] as $input => $operator) {
        if (!empty($_GET[$input])) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', (string) $_GET[$input]);
            if (!$date || $date->format('Y-m-d') !== $_GET[$input]) {
                throw new InvalidArgumentException('Tidsfilteret har ugyldig dato.');
            }
            $where[] = "performed_at $operator ?";
            $params[] = ($input === 'to' ? $date->modify('+1 day') : $date)->format('Y-m-d H:i:s');
        }
    }
    if (!empty($_GET['from']) && !empty($_GET['to']) && $_GET['from'] > $_GET['to']) {
        throw new InvalidArgumentException('Sluttdato kan ikke være før startdato.');
    }
    $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1,'max_range' => 100000]]);
    if ($page === false) throw new InvalidArgumentException('Ugyldig sidenummer.');
    $sqlWhere = implode(' AND ', $where);
    $count = $db->prepare("SELECT COUNT(*) FROM audit_logs WHERE $sqlWhere");
    $count->execute($params);
    $total = (int) $count->fetchColumn();
    $offset = ($page - 1) * 50;
    $query = $db->prepare("SELECT $columns FROM audit_logs WHERE $sqlWhere ORDER BY performed_at DESC,id DESC LIMIT 50 OFFSET $offset");
    $query->execute($params);
    $logs = array_map('tenantAuditPublicRow', $query->fetchAll(PDO::FETCH_ASSOC));
    // Filter values are derived ONLY from this tenant's existing audit rows.
    $filters = [];
    foreach (['module','action','user_id'] as $column) {
        $query = $db->prepare("SELECT DISTINCT $column FROM audit_logs WHERE company_id = ? AND $column IS NOT NULL ORDER BY $column");
        $query->execute([$companyId]);
        $filters[$column === 'user_id' ? 'admin_ids' : $column . 's'] = $query->fetchAll(PDO::FETCH_COLUMN);
    }
    $filters['admins'] = [];
    $actorQuery = $db->prepare('SELECT new_values FROM audit_logs WHERE company_id = ? AND user_id = ?
        ORDER BY performed_at DESC,id DESC LIMIT 1');
    foreach ($filters['admin_ids'] as $actorId) {
        $actorQuery->execute([$companyId,$actorId]);
        $values = json_decode((string) $actorQuery->fetchColumn(), true);
        $name = $values['_audit']['actor_name'] ?? $values['admin_name'] ?? null;
        $filters['admins'][] = ['id' => $actorId, 'name' => is_string($name) && $name !== '' ? $name : $actorId];
    }
    echo json_encode([
        'success' => true, 'logs' => $logs, 'filters' => $filters,
        'pagination' => ['page' => $page, 'total' => $total, 'pages' => max(1, (int) ceil($total / 50)), 'limit' => 50],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $error) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => $error->getMessage()]);
} catch (Throwable $error) {
    error_log('Tenant audit read failed: ' . get_class($error));
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Aktivitetsloggen kunne ikke hentes.']);
}