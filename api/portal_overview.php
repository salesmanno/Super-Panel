<?php
declare(strict_types=1);
session_start();
session_write_close();
header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/tenant_notifications.php';
require_once __DIR__ . '/../includes/tenant_supervisor.php';
if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Logg inn på nytt.']);
    exit;
}
try {
    $db = getDBConnection();
    if (!empty($_SESSION['company_auth_version'])) {
        require_once __DIR__ . '/../includes/company_login.php';
        companyLoginRequire($db);
    }
    $company = (string) $_SESSION['company_id'];
    $modules = tenantNotificationModules($db, $company);
    $agenda = [];
    $errors = [];
    foreach (['rental', 'events'] as $module) {
        if (!in_array($module, $modules, true)) continue;
        try {
            $sql = $module === 'rental'
                ? "SELECT rs.id, ro.object_name AS title, rs.start_date AS date, rs.pickup_time AS time
                    FROM rental_schedule rs JOIN rental_objects ro
                    ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
                    WHERE rs.company_id = ? AND rs.approved = 1 AND rs.status = 'approved'
                    AND rs.start_date >= CURDATE() ORDER BY rs.start_date, rs.pickup_time LIMIT 2"
                : "SELECT id, title, start_date AS date, start_time AS time FROM events_eventdata
                    WHERE company_id = ? AND is_active = 1 AND start_date >= CURDATE()
                    ORDER BY start_date, start_time LIMIT 2";
            $q = $db->prepare($sql);
            $q->execute([$company]);
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) $agenda[] = $row + ['module' => $module];
        } catch (Throwable $e) {
            error_log('Portal agenda ' . $module . ': ' . $e->getMessage());
            $errors[] = 'Kunne ikke hente ' . ($module === 'rental' ? 'kommende utleier.' : 'kommende arrangementer.');
        }
    }
    usort($agenda, static fn($a, $b) => strcmp($a['date'] . $a['time'], $b['date'] . $b['time']));
    $activity = [];
    if (tenantIsSupervisor($db, $company)) {
        $q = $db->prepare('SELECT id,company_id,user_id,user_type,action,resource_type,resource_id,
            resource_name,old_values,new_values,performed_at,module FROM audit_logs
            WHERE company_id = ? ORDER BY performed_at DESC,id DESC LIMIT 3');
        $q->execute([$company]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $safe = tenantAuditPublicRow($row);
            $activity[] = array_intersect_key($safe, array_flip(['id','actor_name','action','resource_name','performed_at']));
        }
    }
    echo json_encode(['success' => true, 'agenda' => array_slice($agenda, 0, 2),
        'activity' => $activity, 'errors' => $errors], JSON_THROW_ON_ERROR);
} catch (Throwable $e) {
    error_log('Portal overview: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Kunne ikke hente oversikten. Prøv igjen senere.']);
}
