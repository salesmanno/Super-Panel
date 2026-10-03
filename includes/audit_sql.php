<?php
declare(strict_types=1);

/** Split SQL lists without treating commas/placeholders inside literals as syntax. */
function tenantAuditSqlParts(string $sql): array {
    $parts = [];
    $start = 0;
    $depth = 0;
    $quote = '';
    for ($i = 0, $length = strlen($sql); $i < $length; $i++) {
        $char = $sql[$i];
        if ($quote !== '') {
            if ($char === '\\') { $i++; continue; }
            if ($char === $quote) {
                if (($sql[$i + 1] ?? '') === $quote) $i++;
                else $quote = '';
            }
        } elseif (in_array($char, ["'",'"','`'], true)) {
            $quote = $char;
        } elseif ($char === '(') {
            $depth++;
        } elseif ($char === ')') {
            $depth--;
        } elseif ($char === ',' && $depth === 0) {
            $parts[] = trim(substr($sql, $start, $i - $start));
            $start = $i + 1;
        }
    }
    $parts[] = trim(substr($sql, $start));
    return $parts;
}

function tenantAuditSqlParameters(string $sql): array {
    // Quoted values/identifiers and SQL comments are never placeholder sources.
    $masked = preg_replace_callback(
        '/\'(?:\\\\.|\'\'|[^\'])*\'|"(?:\\\\.|""|[^"])*"|`(?:``|[^`])*`|\/\*.*?\*\/|--[^\r\n]*/s',
        static fn(array $m): string => str_repeat(' ', strlen($m[0])), $sql
    );
    preg_match_all('/\?|(?<!:):[a-zA-Z_][a-zA-Z0-9_]*/', (string) $masked, $matches, PREG_OFFSET_CAPTURE);
    return $matches[0];
}

function tenantAuditWhereBindings(string $sql, string $where, int $offset, array $params): array {
    $all = tenantAuditSqlParameters($sql);
    $positional = array_values($params);
    $bindings = [];
    foreach ($all as $index => [$token, $position]) {
        if ($position < $offset) continue;
        if ($token === '?') {
            $bindings[] = $positional[$index] ?? null;
        } else {
            $key = substr($token, 1);
            $bindings[$key] = $params[$key] ?? $params[$token] ?? null;
        }
    }
    return $bindings;
}

function tenantAuditMutation(string $sql): ?array {
    $sql = trim($sql);
    if (!preg_match('/^(INSERT(?:\s+IGNORE)?\s+INTO|REPLACE\s+INTO|UPDATE|DELETE(?:\s+`?[a-zA-Z0-9_]+`?)?\s+FROM)\s+`?([a-zA-Z0-9_]+)`?\b/i', $sql, $match)) {
        return null;
    }
    $verb = strtoupper(explode(' ', $match[1])[0]);
    return ['table' => strtolower($match[2]), 'verb' => $verb, 'sql' => $sql];
}

/** Build a narrow read selector for INSERT, including UUID()/bulk VALUES inserts. */
function tenantAuditInsertValues(string $sql): string {
    $depth = 0;
    $quote = '';
    for ($i = 0, $length = strlen($sql); $i < $length; $i++) {
        $char = $sql[$i];
        if ($quote !== '') {
            if ($char === '\\') { $i++; continue; }
            if ($char === $quote) {
                if (($sql[$i + 1] ?? '') === $quote) $i++;
                else $quote = '';
            }
        } elseif (in_array($char, ["'",'"','`'], true)) {
            $quote = $char;
        } elseif ($char === '(') {
            $depth++;
        } elseif ($char === ')') {
            $depth--;
        } elseif ($depth === 0 && preg_match('/\GON\s+DUPLICATE\s+KEY\s+UPDATE\b/i', $sql, $unused, 0, $i)) {
            return trim(substr($sql, 0, $i));
        }
    }
    return rtrim($sql, " \t\r\n;");
}

function tenantAuditInsertSelector(string $sql, array $params, array $columns): array {
    if (!preg_match('/^(?:INSERT(?:\s+IGNORE)?|REPLACE)\s+INTO\s+`?[a-zA-Z0-9_]+`?\s*\(([^)]+)\)\s*VALUES\s*(.+)$/is', $sql, $match)) {
        throw new TenantAuditException('Denne INSERT-formen mangler en sikker auditselektor.');
    }
    $names = array_map(static fn($v) => trim($v, " \t\r\n`"), tenantAuditSqlParts($match[1]));
    $groups = tenantAuditSqlParts(tenantAuditInsertValues($match[2]));
    $position = 0;
    $selectors = [];
    $bindings = [];
    foreach ($groups as $group) {
        if (!preg_match('/^\((.*)\)$/is', $group, $valuesMatch)) {
            throw new TenantAuditException('Denne bulk-INSERT-formen kan ikke spores sikkert.');
        }
        $values = tenantAuditSqlParts($valuesMatch[1]);
        $known = [];
        foreach ($values as $index => $expression) {
            $tokens = tenantAuditSqlParameters($expression);
            if (trim($expression) === '?') {
                $known[$names[$index] ?? ''] = array_values($params)[$position] ?? null;
            } elseif (preg_match('/^:([a-zA-Z_][a-zA-Z0-9_]*)$/D', trim($expression), $named)) {
                $known[$names[$index] ?? ''] = $params[$named[1]] ?? $params[':' . $named[1]] ?? null;
            } elseif (in_array($names[$index] ?? '', ['id','company_id','template_key','event_id','module_id'], true)
                && preg_match("/^'([a-zA-Z0-9_.-]+)'$/D", trim($expression), $literal)) {
                $known[$names[$index]] = $literal[1];
            }
            $position += count($tokens);
        }
        $preferred = isset($known['id']) ? ['id']
            : ['company_id','source_id','object_id','rental_object_id','event_id','tenant_admin_id','module_id','template_key','name','title'];
        $and = [];
        foreach ($preferred as $key) {
            if (in_array($key, $columns, true) && array_key_exists($key, $known)) {
                $and[] = "`$key` <=> ?";
                $bindings[] = $known[$key];
            }
        }
        if (!$and) throw new TenantAuditException('INSERT mangler avgrensning for audit.');
        $selectors[] = '(' . implode(' AND ', $and) . ')';
    }
    return [implode(' OR ', $selectors), $bindings];
}