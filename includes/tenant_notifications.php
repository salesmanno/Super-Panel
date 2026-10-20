<?php
require_once __DIR__ . '/tenant_audit.php';

/** Notifications retain the existing company-wide acknowledgement semantics. */
function tenantNotificationModules(PDO $db, string $company): array {
    $actor = tenantAuditActor($db, $company);
    if (!empty($_SESSION['company_auth_version'])) {
        require_once __DIR__.'/company_login.php';
        return companyLoginModuleKeys($db,$actor['id']);
    }
    $q = $db->prepare('SELECT sm.module_key FROM system_modules sm
        JOIN tenant_admin_modules tam ON tam.module_id = sm.id
        WHERE tam.tenant_admin_id = ? AND sm.is_active = 1');
    $q->execute([$actor['id']]);
    return $q->fetchAll(PDO::FETCH_COLUMN);
}

function rentalRequireAccess(PDO $db, string $company): void {
    if (!in_array('rental', tenantNotificationModules($db, $company), true)) {
        throw new TenantAuditException('Du har ikke tilgang til utleiemodulen');
    }
}

function rentalRequireDecisionCsrf(array $data): void {
    $expected = (string) ($_SESSION['rental_lifecycle_csrf'] ?? '');
    $given = $data['csrf_token'] ?? '';
    if ($expected === '' || !is_string($given) || !hash_equals($expected, $given)) {
        throw new TenantAuditException('Sikkerhetskontrollen feilet. Last siden på nytt.');
    }
}

/** Minimal event snapshot; called only inside the request's transaction. */
function tenantNotifyRentalRequest(PDO $db, string $company, string $request): void {
    if (!$db->inTransaction()) throw new LogicException('Varsling krever en transaksjon');
    $q = $db->prepare('SELECT rs.id, rs.rental_object_id, rs.customer_name, ro.object_name
        FROM rental_schedule rs JOIN rental_objects ro
        ON ro.id = rs.rental_object_id AND ro.company_id = rs.company_id
        WHERE rs.id = ? AND rs.company_id = ?');
    $q->execute([$request, $company]);
    $row = $q->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new RuntimeException('Forespørselen for varselet finnes ikke');
    $metadata = json_encode([
        'version' => 1, 'event_type' => 'rental.request.created',
        'resource_type' => 'rental_schedule', 'resource_id' => $row['id'],
        'object_id' => $row['rental_object_id'],
        'customer_name' => $row['customer_name'], 'object_name' => $row['object_name'],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    // Existing transport uniqueness is (company, module, polling_source).
    // Events therefore need their own source key, unlike reusable polling jobs.
    $source = 'rental_request:' . $request;
    $db->prepare("INSERT INTO tenant_polling_tracker
        (id, company_id, module_source, action_type, polling_source, polling_type,
         polling_status, items_processed_last_poll, metadata)
        VALUES (?, ?, 'rental', 'rental_booking', ?, 'event', 'pending', 1, ?)")
        ->execute([$request, $company, $source, $metadata]);
}

function tenantPendingNotifications(PDO $db, string $company): array {
    $modules = tenantNotificationModules($db, $company);
    if (!$modules) return [];
    $q = $db->prepare('SELECT id, module_source, action_type, polling_source,
        polling_interval_seconds, items_processed_last_poll, metadata, created_at
        FROM tenant_polling_tracker WHERE company_id = ? AND polling_status = \'pending\'
        AND module_source IN (' . implode(',', array_fill(0, count($modules), '?')) . ')
        ORDER BY created_at DESC, id DESC LIMIT 50');
    $q->execute(array_merge([$company], $modules));
    return array_map(static function($row) {
        $metadata = json_decode((string) ($row['metadata'] ?? ''), true);
        // Legacy sources may carry unrelated secrets. Return display data only.
        $safe = [];
        if (is_array($metadata)) foreach (['event_type','resource_type','resource_id','object_id',
            'customer_name','object_name','event_title'] as $key) {
            if (isset($metadata[$key]) && is_string($metadata[$key])) $safe[$key] = mb_substr($metadata[$key], 0, 255);
        }
        return [
            'id' => $row['id'], 'module_source' => $row['module_source'],
            'action_type' => $row['action_type'], 'polling_source' => $row['polling_source'],
            'interval_seconds' => (int) $row['polling_interval_seconds'],
            'items_count' => (int) $row['items_processed_last_poll'],
            'metadata' => $safe ?: null, 'created_at' => $row['created_at'],
        ];
    }, $q->fetchAll(PDO::FETCH_ASSOC));
}

function tenantAcknowledgeNotification(PDO $db, string $company, string $id, bool $dismissed): void {
    $modules = tenantNotificationModules($db, $company);
    if (!$modules) throw new TenantAuditException('Du har ikke tilgang til varselet');
    $params = array_merge([$id, $company], $modules);
    $scope = 'id = ? AND company_id = ? AND module_source IN ('
        . implode(',', array_fill(0, count($modules), '?')) . ')';
    $q = $db->prepare('SELECT id FROM tenant_polling_tracker WHERE ' . $scope);
    $q->execute($params);
    if (!$q->fetchColumn()) throw new OutOfBoundsException('Varsel ikke funnet');
    $db->prepare('UPDATE tenant_polling_tracker SET polling_status = ?, last_successful_poll = NOW()
        WHERE ' . $scope . " AND polling_status = 'pending'")
        ->execute(array_merge([$dismissed ? 'dismissed' : 'complete'], $params));
}
