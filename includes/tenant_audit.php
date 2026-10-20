<?php
declare(strict_types=1);
require_once __DIR__ . '/audit_comparison.php';

final class TenantAuditException extends RuntimeException {}

/** Only these values may be persisted as before/after data. No deny-list fallback. */
function tenantAuditFields(): array {
    return [
        'rental_schedule' => ['id','rental_object_id','start_date','end_date','start_time','end_time','pickup_time',
            'approved','status','rental_fee','cleaning','price_adjustment_type','price_adjustment_cents',
            'payment_status','payment_method','checkin_email_sent_at'],
        'rental_objects' => ['id','object_name','category','rental_price','weekend_price','saturday_price',
            'sunday_price','deposit_amount','is_available','is_active'],
        'rental_contracts' => ['id','rental_object_id','start_date','end_date','rental_days','total_amount',
            'deposit_amount','status','payment_status','payment_method'],
        'membership_members' => ['id','member_code','membership_plan_id','status','join_date','expiration_date',
            'auto_renew','payment_status','paymentMethod','cancellation_active','kontrollert'],
        'membership_plans' => ['id','name','price','duration_months','is_active'],
        'events_eventdata' => ['id','title','start_date','end_date','start_time','end_time','status','capacity','is_active'],
        'events_checkin' => ['id','event_id','registration_id','membership_plan_id','ticket_price','checked_in',
            'payment_status','payment_method','payment_state','payment_checked_at'],
        'event_registrations' => ['id','event_id','approval_status','approved_by','approved_at','rejected_by','rejected_at',
            'status','payment_status','payment_method','total_amount','quantity'],
        'events_registrations' => ['id','event_id','status','payment_status','payment_method','total_amount','quantity'],
        'unified_ledger' => ['id','order_id','module_source','item_type','source_id','item_name','quantity',
            'selling_price','cost_price','payment_method','payment_status','status','transaction_type',
            'transaction_date','category','rental_schedule_id'],
        'economy_transactions' => ['id','amount','type','category_id','payment_method','status','transaction_date'],
        'economy_bank_accounts' => ['id','bank_name','is_active'],
        'tenant_staff' => ['id','first_name','last_name','job_title','department','is_active'],
        'tenant_admins' => ['id','username','role','is_active','tenant_staff_id','operation','module_id'],
        'tenant_admin_modules' => ['tenant_admin_id','module_id','is_active'],
        'tenant_companies' => ['id','name','legal_name','is_active','industry','subdomain'],
        'announcements' => ['id','title','is_published','is_pinned','priority','publish_at','expire_at'],
        'email_templates' => ['id','name','template_key','is_active'],
        'email_queue' => ['id','template_id','module_source','source_id','status','priority','attempts','attempt_number','max_attempts',
            'scheduled_at','next_retry_at','sent_at'],
        'email_configs' => ['id','display_name','is_active'],
    ];
}

function tenantAuditSafeValues(string $resource, array $values): array {
    $allowed = function_exists('tenantAuditResourceSpec')
        ? (tenantAuditResourceSpec($resource)['fields'] ?? [])
        : (tenantAuditFields()[$resource] ?? []);
    $safe = [];
    foreach ($values as $key => $value) {
        // Automatic-mail switches are booleans, not arbitrary SMTP/configuration values.
        if ($resource === 'email_configs' && preg_match('/^automail_[a-z0-9_]+$/D', (string) $key)) {
            $safe[$key] = (int) (bool) $value;
        } elseif (in_array($key, $allowed, true) && (is_scalar($value) || $value === null)) {
            $safe[$key] = is_string($value) ? mb_substr($value, 0, 500) : $value;
            if ($value !== null && is_numeric($value) && (
                preg_match('/(?:_price|_fee|_amount|_cents)$/D', (string) $key)
                || in_array($key, ['price','amount','quantity','approved','cleaning','checked_in','priority',
                    'news_priority','capacity','duration_months','auto_renew','cancellation_active'], true)
                || str_starts_with((string) $key, 'is_')
            )) $safe[$key] = (string) (float) $value;
        } elseif ($resource === 'email_templates' && in_array($key, ['body_template','subject_template'], true)) {
            $safe[$key . '_sha256'] = hash('sha256', (string) $value);
        } elseif ($resource === 'email_templates' && in_array($key, ['body_template_sha256','subject_template_sha256'], true)
            && is_string($value) && preg_match('/^[a-f0-9]{64}$/D', $value)) {
            $safe[$key] = $value;
        }
    }
    return $safe;
}

/** Resolve the exact signed-in account, never the first administrator of a company. */
function tenantAuditActor(PDO $db, string $companyId): array {
    $adminId = (string) ($_SESSION['tenant_admin_id'] ?? '');
    if ($adminId === '' || (string) ($_SESSION['company_id'] ?? '') !== $companyId) {
        throw new TenantAuditException('Audit mangler autorisert administrator og bedrift.');
    }
    $query = $db->prepare('
        SELECT ta.id, ta.username, ta.role, ts.first_name, ts.last_name, c.name AS company_name
        FROM tenant_admins ta
        JOIN tenant_companies c ON c.id = ta.company_id
        LEFT JOIN tenant_staff ts ON ts.id = ta.tenant_staff_id AND ts.company_id = ta.company_id
        WHERE ta.id = ? AND ta.company_id = ? AND ta.is_active = 1 AND c.is_active = 1
    ');
    $query->execute([$adminId, $companyId]);
    $row = $query->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new TenantAuditException('Administratorens tilgang kunne ikke bekreftes.');
    $name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
    return [
        'id' => (string) $row['id'], 'type' => 'tenant_admin',
        'name' => mb_substr($name !== '' ? $name : (string) $row['username'], 0, 255),
        'role' => (string) $row['role'], 'company_name' => mb_substr((string) $row['company_name'], 0, 255),
    ];
}

function tenantAuditCanRead(PDO $db, string $companyId): bool {
    try {
        return in_array(tenantAuditActor($db, $companyId)['role'], ['admin','owner','super_admin'], true);
    } catch (TenantAuditException $error) {
        return false;
    }
}

function tenantAuditSystemActor(PDO $db, string $companyId, string $job): array {
    $allowed = ['email_queue','rental_checkin','membership_renewal','membership_reminder','sales_vipps_webhook','sales_payment_reconcile'];
    if (!in_array($job, $allowed, true)) throw new TenantAuditException('Ukjent automatisk auditkilde.');
    $query = $db->prepare('SELECT name FROM tenant_companies WHERE id = ?');
    $query->execute([$companyId]);
    $name = $query->fetchColumn();
    if ($name === false) throw new TenantAuditException('Bedriften for systemhendelsen ble ikke funnet.');
    return ['id' => null, 'type' => 'system', 'name' => 'System / ' . $job,
        'role' => 'system', 'company_name' => mb_substr((string) $name, 0, 255)];
}

function tenantAuditId(): string {
    return bin2hex(random_bytes(16));
}

/** Read only the approved columns, in the current tenant, before/after a change. */
function tenantAuditSnapshot(PDO $db, string $companyId, string $resource, string $id): array {
    $fields = tenantAuditFields()[$resource] ?? null;
    if ($fields === null) throw new TenantAuditException('Ressursen har ingen auditfelt.');
    static $cache;
    $cache ??= new WeakMap();
    $tables = $cache[$db] ?? [];
    if (!isset($tables[$resource])) {
        $tables[$resource] = array_column($db->query("SHOW COLUMNS FROM `$resource`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
        $cache[$db] = $tables;
    }
    $columns = $tables[$resource];
    if (!in_array('company_id', $columns, true)) {
        throw new TenantAuditException('Ressursen mangler direkte bedriftsavgrensning.');
    }
    $select = array_map(static fn(string $field): string => "`$field`", array_intersect($fields, $columns));
    if ($resource === 'email_templates') {
        foreach (['body_template','subject_template'] as $field) {
            if (in_array($field, $columns, true)) $select[] = "SHA2(`$field`,256) AS `{$field}_sha256`";
        }
    }
    if ($resource === 'email_configs') {
        foreach ($columns as $field) {
            if (preg_match('/^automail_[a-z0-9_]+$/D', $field)) $select[] = "`$field`";
        }
    }
    if (!$select) throw new TenantAuditException('Ressursen mangler nødvendige auditfelt.');
    $byId = in_array('id', $columns, true);
    $query = $db->prepare('SELECT ' . implode(',', $select) . " FROM `$resource` WHERE company_id = ?"
        . ($byId ? ' AND id = ?' : '') . ' LIMIT 1');
    $query->execute($byId ? [$companyId, $id] : [$companyId]);
    return $query->fetch(PDO::FETCH_ASSOC) ?: [];
}

function tenantAuditChange(
    PDO $db, string $companyId, string $module, string $action, string $resource,
    string $resourceId, string $resourceName, array $before, array $after, array $links = [],
    ?array $actor = null
): string {
    $old = tenantAuditSafeValues($resource, $before);
    $new = tenantAuditSafeValues($resource, $after);
    $keys = tenantAuditChangedKeys($resource, $before, $after);
    if (!$keys) {
        $actor ??= tenantAuditActor($db, $companyId);
        return '';
    }
    $changedOld = [];
    $changedNew = [];
    foreach ($keys as $key) {
        if (array_key_exists($key, $old)) $changedOld[$key] = $old[$key];
        if (array_key_exists($key, $new)) $changedNew[$key] = $new[$key];
    }
    return tenantAuditRecord($db, $companyId, $module, $action, $resource, $resourceId, $resourceName,
        $changedOld, $changedNew, 'success', $links, $actor);
}

/** Failures are recorded after rollback, never masquerading as successful changes. */
function tenantAuditFailure(
    PDO $db, string $companyId, string $module, string $action, string $resource,
    string $resourceId, string $resourceName, string $outcome, ?array $actor = null
): bool {
    if ($db->inTransaction()) $db->rollBack();
    try {
        tenantAuditRecord($db, $companyId, $module, $action, $resource, $resourceId, $resourceName,
            [], [], $outcome, [], $actor);
        return true;
    } catch (Throwable $error) {
        error_log('CRITICAL: audit for rejected/failed action unavailable; module=' . $module);
        return false;
    }
}

/**
 * The caller passes rows fetched in its authorized scope, not request JSON.
 * Successful changes MUST be recorded before that caller's transaction commits.
 */
function tenantAuditRecord(
    PDO $db, string $companyId, string $module, string $action, string $resource,
    string $resourceId, string $resourceName, array $before, array $after,
    string $outcome = 'success', array $links = [], ?array $actor = null, array $redactedFields = []
): string {
    if (!in_array($outcome, ['success','noop','denied','failed','rolled_back','smtp_accepted'], true)) {
        throw new TenantAuditException('Ugyldig auditutfall.');
    }
    if (in_array($outcome, ['success','smtp_accepted'], true) && !$db->inTransaction()) {
        throw new TenantAuditException('Endringen og auditloggen må være i samme transaksjon.');
    }
    $actor ??= tenantAuditActor($db, $companyId);
    // Empty-string means no new audit ID. Event/security records use their explicit outcomes.
    if ($outcome === 'noop') return '';
    $safeBefore = tenantAuditSafeValues($resource, $before);
    $safeAfter = tenantAuditSafeValues($resource, $after);
    $safeLinks = [];
    foreach (['queue_id','template_id','source_id','source_module','correlation_id','initiator_audit_id',
        'template_body_sha256','template_subject_sha256','message_body_sha256','message_subject_sha256'] as $key) {
        if (isset($links[$key]) && is_scalar($links[$key])) {
            $safeLinks[$key] = mb_substr((string) $links[$key], 0, 100);
        }
    }
    $safeAfter['_audit'] = [
        'version' => 1, 'company_name' => $actor['company_name'],
        'actor_name' => $actor['name'], 'actor_role' => $actor['role'],
        'actor_type' => $actor['type'], 'outcome' => $outcome, 'links' => $safeLinks,
        'redacted_fields' => array_values(array_filter($redactedFields,
            static fn($field): bool => is_string($field) && (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,99}$/D', $field))),
    ];
    $id = tenantAuditId();
    try {
        $statement = $db->prepare('INSERT INTO audit_logs
            (id,company_id,user_id,user_type,user_email,action,resource_type,resource_id,resource_name,
             old_values,new_values,severity,performed_at,module)
            VALUES (?,?,?,?,NULL,?,?,?,?,?,?,?,CURRENT_TIMESTAMP,?)');
        $statement->execute([
            $id, $companyId, $actor['id'], $actor['type'], mb_substr($action, 0, 50),
            mb_substr($resource, 0, 100), $resourceId !== '' ? $resourceId : null,
            mb_substr($resourceName, 0, 255),
            json_encode($safeBefore, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            json_encode($safeAfter, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            in_array($outcome, ['failed','rolled_back'], true) ? 'error' : 'info', $module,
        ]);
    } catch (Throwable $error) {
        // Do not expose SQL parameters, exception strings, or sensitive values.
        error_log('Tenant audit write failed; resource=' . $resource . '; audit_ref=' . $id);
        throw new TenantAuditException('Aktivitetsloggen kunne ikke lagres. Endringen er ikke bekreftet.', 0, $error);
    }
    return $id;
}

/** Shape legacy and new rows without reconstructing or guessing historic names. */
function tenantAuditPublicRow(array $row): array {
    $before = json_decode((string) ($row['old_values'] ?? ''), true);
    $after = json_decode((string) ($row['new_values'] ?? ''), true);
    $before = is_array($before) ? $before : [];
    $after = is_array($after) ? $after : [];
    $meta = is_array($after['_audit'] ?? null) ? $after['_audit'] : [];
    return [
        'id' => $row['id'], 'performed_at' => $row['performed_at'], 'module' => $row['module'],
        'action' => $row['action'], 'resource_type' => $row['resource_type'],
        'resource_id' => $row['resource_id'], 'resource_name' => $row['resource_name'],
        'company_id' => $row['company_id'], 'company_name' => $meta['company_name'] ?? 'Historisk navn ikke registrert',
        'actor_id' => $row['user_id'], 'actor_name' => $meta['actor_name'] ?? $after['admin_name'] ?? 'Historisk navn ikke registrert',
        'actor_role' => $meta['actor_role'] ?? 'Ikke registrert', 'actor_type' => $meta['actor_type'] ?? $row['user_type'],
        'outcome' => $meta['outcome'] ?? 'legacy',
        'redacted_fields' => $meta['redacted_fields'] ?? [],
        'before' => tenantAuditSafeValues((string) $row['resource_type'], $before),
        'after' => tenantAuditSafeValues((string) $row['resource_type'], $after),
        'links' => array_intersect_key((array) ($meta['links'] ?? []),
            array_flip(['queue_id','template_id','source_id','source_module','correlation_id','initiator_audit_id',
                'template_body_sha256','template_subject_sha256','message_body_sha256','message_subject_sha256'])),
    ];
}