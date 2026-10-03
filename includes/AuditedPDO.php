<?php
declare(strict_types=1);
require_once __DIR__ . '/tenant_audit.php';
require_once __DIR__ . '/audit_resources.php';
require_once __DIR__ . '/audit_sql.php';

/**
 * Existing controllers retain their SQL and transaction boundaries. Known portal
 * writes receive a safe row journal inside the SAME transaction, never at shutdown.
 */
final class AuditedPDO extends PDO {
    private int $auditDepth = 0;
    private array $columnCache = [];
    private array $actors = [];
    private array $actorContexts = [];
    private string|false|null $applicationInsertId = null;
    private array $pendingAuditChanges = [];
    private array $contextAuditLinks = [];

    public function withAuditLinks(array $links, callable $operation): mixed {
        $previous = $this->contextAuditLinks;
        $this->contextAuditLinks = $links;
        try { return $operation(); }
        finally { $this->contextAuditLinks = $previous; }
    }

    public function withExplicitAudit(callable $operation): mixed {
        $this->auditDepth++;
        try { return $operation(); }
        finally { $this->auditDepth--; }
    }
    public function isAutomaticAuditSuppressed(): bool { return $this->auditDepth > 0; }

    public function beginTransaction(): bool {
        $result = parent::beginTransaction();
        if ($result) $this->pendingAuditChanges = [];
        return $result;
    }
    public function commit(): bool {
        $result = parent::commit();
        if ($result) $this->pendingAuditChanges = [];
        return $result;
    }
    public function rollBack(): bool {
        $pending = $this->pendingAuditChanges;
        $result = parent::rollBack();
        $this->pendingAuditChanges = [];
        if ($result && $pending) {
            $this->auditDepth++;
            try {
                foreach ($pending as $event) {
                    tenantAuditRecord($this, $event['company'], $event['module'], $event['action'],
                        $event['table'], $event['id'], $event['name'], [], [], 'rolled_back', [], $event['actor']);
                }
            } catch (Throwable $error) {
                error_log('CRITICAL: rollback audit unavailable');
            } finally { $this->auditDepth--; }
        }
        return $result;
    }

    public function lastInsertId(?string $name = null): string|false {
        return $name === null && $this->applicationInsertId !== null
            ? $this->applicationInsertId : parent::lastInsertId($name);
    }

    public function __construct(string $dsn, ?string $username = null, ?string $password = null, ?array $options = null) {
        parent::__construct($dsn, $username, $password, $options);
        $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, [AuditedPDOStatement::class, [$this]]);
    }

    public function exec(string $statement): int|false {
        if (!$this->shouldAudit($statement)) return parent::exec($statement);
        $query = $this->prepare($statement);
        $query->execute();
        return $query->rowCount();
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false {
        if (!$this->shouldAudit($query)) {
            return $fetchMode === null ? parent::query($query) : parent::query($query, $fetchMode, ...$fetchModeArgs);
        }
        $statement = $this->prepare($query);
        $statement->execute();
        if ($fetchMode !== null) $statement->setFetchMode($fetchMode, ...$fetchModeArgs);
        return $statement;
    }

    private function shouldAudit(string $sql): bool {
        if ($this->auditDepth > 0 || $this->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'mysql') return false;
        // These controllers have deliberate domain/SMTP hooks; never double-log.
        $script = basename($_SERVER['SCRIPT_FILENAME'] ?? '');
        $mutation = tenantAuditMutation($sql);
        if (!$mutation || !tenantAuditResourceSpec($mutation['table'])) return false;
        if ($script === 'update_rental_schedule.php'
            && in_array($mutation['table'], ['rental_schedule','email_queue'], true)) return false;
        if (in_array($script, ['process_email_queue.php','cron_email_queue.php'], true)
            && $mutation['table'] === 'email_queue' && $mutation['verb'] !== 'INSERT') return false;
        if ($script === 'perform_checkin.php' && $mutation['table'] === 'events_checkin') return false;
        // Login timestamps / inbox badges are not administrator business changes.
        if ($mutation['verb'] === 'UPDATE' && preg_match('/\bSET\s+(.+?)(?:\bWHERE\b|$)/is', $sql, $set)) {
            $fields = array_map(static fn($part) => trim(explode('=', $part, 2)[0], " \t\r\n`"), tenantAuditSqlParts($set[1]));
            if (!array_diff($fields, ['updated_at','last_login','last_login_at','alertNew'])) return false;
        }
        return true;
    }

    private function actor(string $company): array {
        $context = (string) ($_SESSION['tenant_admin_id'] ?? $_SESSION['saas_admin_id'] ?? PHP_SAPI);
        if (isset($this->actors[$company]) && ($this->actorContexts[$company] ?? '') === $context) return $this->actors[$company];
        $this->actorContexts[$company] = $context;
        if (!empty($_SESSION['tenant_admin_id'])) return $this->actors[$company] = tenantAuditActor($this, $company);
        $query = $this->prepare('SELECT name FROM tenant_companies WHERE id = ?');
        $query->execute([$company]);
        $name = $query->fetchColumn();
        if ($name === false) throw new TenantAuditException('Audit mangler en eksisterende bedrift.');
        if (!empty($_SESSION['saas_admin_id'])) {
            $adminQuery = $this->prepare('SELECT id,username,role FROM saas_admins WHERE id = ? AND is_active = 1');
            $adminQuery->execute([$_SESSION['saas_admin_id']]);
            $admin = $adminQuery->fetch(PDO::FETCH_ASSOC);
            if (!$admin) throw new TenantAuditException('Systemadministratorens identitet kunne ikke bekreftes.');
            return $this->actors[$company] = [
                'id' => $admin['id'], 'type' => 'saas_admin', 'name' => (string) $admin['username'],
                'role' => (string) $admin['role'], 'company_name' => (string) $name,
            ];
        }
        $system = PHP_SAPI === 'cli';
        return $this->actors[$company] = [
            'id' => null, 'type' => $system ? 'system' : 'unknown',
            'name' => $system ? 'System / ' . basename($_SERVER['SCRIPT_FILENAME'] ?? 'CLI') : 'Ikke identifisert',
            'role' => $system ? 'system' : 'unknown', 'company_name' => (string) $name,
        ];
    }

    private function columns(string $table): array {
        if (!isset($this->columnCache[$table])) {
            $this->columnCache[$table] = array_column(parent::query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
        }
        return $this->columnCache[$table];
    }

    private function rows(string $table, string $where, array $params, array $privateFields = []): array {
        $columns = $this->columns($table);
        $spec = tenantAuditResourceSpec($table);
        $select = array_map(static fn($field) => "`$table`.`$field`", array_intersect($spec['fields'], $columns));
        if ($table === 'email_templates') {
            foreach (['body_template','subject_template'] as $field) {
                if (in_array($field, $columns, true)) $select[] = "SHA2(`$field`,256) AS `{$field}_sha256`";
            }
        }
        if ($table === 'email_configs') {
            foreach ($columns as $field) if (preg_match('/^automail_[a-z0-9_]+$/D', $field)) $select[] = "`$field`";
        }
        $select[] = tenantAuditScopeExpression($table, $columns) . ' AS _audit_company';
        foreach (array_intersect($privateFields, $columns) as $field) {
            $select[] = "SHA2(CAST(`$field` AS CHAR),256) AS `_audit_hash_$field`";
        }
        $query = $this->prepare('SELECT ' . implode(',', $select) . " FROM `$table` WHERE ($where) LIMIT 5001 FOR UPDATE");
        $query->execute($params);
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);
        if (count($rows) > 5000) throw new TenantAuditException('Endringen er for stor for én audittransaksjon.');
        $indexed = [];
        foreach ($rows as $row) {
            $company = (string) ($row['_audit_company'] ?? '');
            if ($company === '') throw new TenantAuditException('Audit kunne ikke bekrefte bedriften for ressursen.');
            $this->actor($company); // Authorized, server-resolved account BEFORE mutation.
            $key = (string) ($row['id'] ?? '');
            if ($key === '') {
                $parts = array_intersect_key($row, array_flip(['object_id','rental_object_id','event_id','member_id',
                    'membership_plan_id','membership_type_id','tenant_admin_id','tenant_staff_id','module_id',
                    'field_name','template_key']));
                $key = $company . ':' . json_encode($parts, JSON_THROW_ON_ERROR);
            }
            if (isset($indexed[$key])) throw new TenantAuditException('Audit mangler entydig ressursnøkkel.');
            $indexed[$key] = $row;
        }
        return $indexed;
    }

    public function auditExecute(string $sql, array $params, callable $execute, callable $count): bool {
        if (!$this->shouldAudit($sql)) {
            if ($this->auditDepth === 0 && tenantAuditMutation($sql)) $this->applicationInsertId = null;
            return $execute();
        }
        $mutation = tenantAuditMutation($sql);
        $table = $mutation['table'];
        $verb = $mutation['verb'];
        $ownTransaction = !$this->inTransaction();
        $this->auditDepth++;
        $before = [];
        $privateFields = [];
        try {
            if ($ownTransaction) $this->beginTransaction();
            if (in_array($verb, ['INSERT','REPLACE'], true)) {
                [$where,$bindings] = tenantAuditInsertSelector($mutation['sql'], $params, $this->columns($table));
            } else {
                // Only single-table writes are supported. Do not silently miss a joined mutation.
                $prefix = $verb === 'UPDATE' ? '/^UPDATE\s+`?[a-zA-Z0-9_]+`?\s+SET\b/i'
                    : '/^DELETE\s+FROM\s+`?[a-zA-Z0-9_]+`?\s+(?:WHERE\b|$)/i';
                if (!preg_match($prefix, $mutation['sql'])) throw new TenantAuditException('Denne SQL-endringen krever en eksplisitt audithook.');
                if ($verb === 'UPDATE' && preg_match('/\bSET\s+(.*?)(?:\bWHERE\b|$)/is', $mutation['sql'], $set)) {
                    $written = array_map(static fn($part) => trim(explode('=', $part, 2)[0], " \t\r\n`"), tenantAuditSqlParts($set[1]));
                    $privateFields = array_values(array_diff($written, tenantAuditResourceSpec($table)['fields'],
                        ['updated_at','created_at','body_template','subject_template']));
                    $privateFields = array_values(array_filter($privateFields,
                        static fn($field) => (bool) preg_match('/^[a-zA-Z_][a-zA-Z0-9_]{0,99}$/D', $field)));
                }
                if (preg_match('/\bWHERE\b(.*)$/is', $mutation['sql'], $match, PREG_OFFSET_CAPTURE)) {
                    $where = trim($match[1][0], " \t\r\n;");
                    if (preg_match('/\b(?:ORDER\s+BY|LIMIT)\b/i', $where)) {
                        throw new TenantAuditException('Begrenset/ordnet SQL-endring krever en eksplisitt audithook.');
                    }
                    $bindings = tenantAuditWhereBindings($mutation['sql'], $where, $match[1][1], $params);
                } else {
                    $where = '1=1';
                    $bindings = [];
                }
            }
            $before = $this->rows($table, $where, $bindings, $privateFields);
            $result = $execute();
            if (!$result) throw new TenantAuditException('Databaseendringen ble ikke utført.');
            $this->applicationInsertId = parent::lastInsertId();
            // UPDATE may change the selector itself (status, role, date). Re-read by immutable ID.
            if ($verb === 'UPDATE' && $before && in_array('id', $this->columns($table), true)) {
                $ids = array_column($before, 'id');
                $after = $this->rows($table, '`id` IN (' . implode(',', array_fill(0, count($ids), '?')) . ')', $ids, $privateFields);
            } elseif ($verb === 'UPDATE' && $before) {
                $groups = [];
                $keyBindings = [];
                foreach ($before as $row) {
                    $parts = array_intersect_key($row, array_flip(['object_id','rental_object_id','event_id','member_id',
                        'membership_plan_id','membership_type_id','tenant_admin_id','tenant_staff_id','module_id',
                        'field_name','template_key']));
                    if (!$parts && in_array('company_id', $this->columns($table), true)) $parts['company_id'] = $row['_audit_company'];
                    if (!$parts) throw new TenantAuditException('UPDATE mangler en stabil auditnøkkel.');
                    $and = [];
                    foreach ($parts as $field => $value) {
                        $and[] = "`$field` <=> ?";
                        $keyBindings[] = $value;
                    }
                    $groups[] = '(' . implode(' AND ', $and) . ')';
                }
                $after = $this->rows($table, implode(' OR ', $groups), $keyBindings, $privateFields);
            } else {
                $after = $verb === 'DELETE' ? [] : $this->rows($table, $where, $bindings, $privateFields);
            }
            $affected = $count();
            if ($affected > 0 && !$before && !$after) throw new TenantAuditException('Endringen mangler en sporbar ressurs.');
            foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $key) {
                $old = $before[$key] ?? [];
                $new = $after[$key] ?? [];
                if ($old === $new && $affected > 0 && in_array($verb, ['INSERT','REPLACE'], true)) continue;
                $row = $new ?: $old;
                $company = $row['_audit_company'];
                $resourceId = (string) ($row['id'] ?? $row['tenant_admin_id'] ?? $row['tenant_staff_id']
                    ?? $row['object_id'] ?? $row['event_id'] ?? $row['member_id'] ?? $row['membership_plan_id'] ?? $company);
                $name = (string) ($row['object_name'] ?? $row['title'] ?? $row['name'] ?? $row['plan_name'] ?? $table);
                $links = $table === 'email_queue' ? array_filter([
                    'queue_id' => $resourceId, 'template_id' => $row['template_id'] ?? null,
                    'source_id' => $row['source_id'] ?? null, 'source_module' => $row['module_source'] ?? null,
                ]) : [];
                if ($table === 'email_queue') $links += $this->contextAuditLinks;
                $action = tenantAuditResourceSpec($table)['module'] . '.' . strtolower($verb);
                $redacted = array_values(array_filter($privateFields,
                    static fn($field) => ($old['_audit_hash_' . $field] ?? null) !== ($new['_audit_hash_' . $field] ?? null)));
                if ($redacted) {
                    tenantAuditRecord($this, $company, tenantAuditResourceSpec($table)['module'], $action,
                        $table, $resourceId, $name, $old, $new, 'success', $links, $this->actor($company), $redacted);
                } else {
                    tenantAuditChange($this, $company, tenantAuditResourceSpec($table)['module'], $action,
                        $table, $resourceId, $name, $old, $new, $links, $this->actor($company));
                }
                if ($redacted || tenantAuditSafeValues($table, $old) !== tenantAuditSafeValues($table, $new)) {
                    $this->pendingAuditChanges[] = [
                        'company' => $company, 'module' => tenantAuditResourceSpec($table)['module'],
                        'action' => $action, 'table' => $table, 'id' => $resourceId, 'name' => $name,
                        'actor' => $this->actor($company),
                    ];
                }
            }
            if (!$before && !$after && $affected === 0 && !empty($_SESSION['company_id'])) {
                $company = (string) $_SESSION['company_id'];
                tenantAuditRecord($this, $company, tenantAuditResourceSpec($table)['module'],
                    strtolower($verb), $table, '', 'Ingen tilgjengelige rader', [], [], 'noop', [], $this->actor($company));
            }
            if ($ownTransaction) $this->commit();
            return $result;
        } catch (Throwable $error) {
            if ($this->inTransaction()) $this->rollBack();
            $company = (string) ($_SESSION['company_id'] ?? ($before ? reset($before)['_audit_company'] : ''));
            if ($company !== '') {
                tenantAuditFailure($this, $company, tenantAuditResourceSpec($table)['module'],
                    strtolower($verb), $table, '', $table, 'failed', $this->actors[$company] ?? null);
            }
            throw $error;
        } finally {
            $this->auditDepth--;
        }
    }
}

final class AuditedPDOStatement extends PDOStatement {
    private AuditedPDO $auditConnection;
    private array $auditBindings = [];
    protected function __construct(AuditedPDO $connection) { $this->auditConnection = $connection; }
    public function bindValue(string|int $param, mixed $value, int $type = PDO::PARAM_STR): bool {
        $this->auditBindings[$param] = $value;
        return parent::bindValue($param, $value, $type);
    }
    public function bindParam(string|int $param, mixed &$var, int $type = PDO::PARAM_STR, int $maxLength = 0, mixed $driverOptions = null): bool {
        $this->auditBindings[$param] =& $var;
        return parent::bindParam($param, $var, $type, $maxLength, $driverOptions);
    }
    public function execute(?array $params = null): bool {
        $bindings = $params ?? $this->auditBindings;
        if ($bindings && count(array_filter(array_keys($bindings), 'is_int')) === count($bindings)) ksort($bindings, SORT_NUMERIC);
        return $this->auditConnection->auditExecute($this->queryString, $bindings,
            fn(): bool => parent::execute($params), fn(): int => $this->rowCount());
    }
}