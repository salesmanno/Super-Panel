<?php
declare(strict_types=1);
require_once __DIR__.'/entity_form_fields.php';
require_once __DIR__.'/active_payment_methods.php';

function portalServicePaymentTarget(string $module): array {
    return match ($module) {
        'events' => ['events_eventdata', 'event', 'title'],
        'rental' => ['rental_objects', 'rental_object', 'object_name'],
        'membership' => ['membership_plans', 'membership_plan', 'name'],
        default => throw new InvalidArgumentException('Denne tjenesten støtter ikke valg av betalingsmetoder.'),
    };
}

function portalServicePaymentSelection($json): array {
    $raw = trim($json ?? '');
    if ($raw === '') return [];
    try {
        $decoded = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $error) {
        // Older services stored a plain method or SQL-style single-quoted list.
        // Accept only recognizable payment names, never arbitrary broken JSON.
        if (preg_match("/^\[\s*'[a-zA-Z_ -]+'\s*(?:,\s*'[a-zA-Z_ -]+'\s*)*\]$/D", $raw)) {
            preg_match_all("/'([^']+)'/", $raw, $matches);
            return array_values(array_unique(array_map('activePaymentMethodName', $matches[1])));
        }
        try {
            $method = activePaymentMethodName(trim($raw, "'"));
            if ($method !== null) return [$method];
        } catch (InvalidArgumentException $ignored) {
            // Report a useful settings error instead of the JSON parser's English message.
        }
        throw new InvalidArgumentException('Lagrede betalingsmetoder har ugyldig format. Ingen verdier er endret.', 0, $error);
    }
    if ($decoded === null) return [];
    if (is_string($decoded)) {
        $method = activePaymentMethodName($decoded);
        return $method === null ? [] : [$method];
    }
    if (!is_array($decoded) || !array_is_list($decoded)) {
        throw new InvalidArgumentException('Lagrede betalingsmetoder må være en JSON-liste.');
    }
    return array_values(array_unique(array_filter(array_map('historicalPaymentMethodName', $decoded))));
}

function portalServicePaymentValidate(array $selected, array $available, string $registrationType = ''): array {
    if (!array_is_list($selected)) throw new InvalidArgumentException('Velg betalingsmetoder som en liste.');
    $selected = array_values(array_unique(array_map('activePaymentMethodName', $selected)));
    if (in_array(null, $selected, true) || array_diff($selected, $available)) {
        throw new InvalidArgumentException('Velg bare aktive og konfigurerte betalingsmetoder fra Økonomi.');
    }
    if ($registrationType === 'none' && $selected) {
        throw new InvalidArgumentException('Arrangement uten påmelding skal ikke ha betalingsmetoder.');
    }
    if ($registrationType === 'binding' && (!$selected || in_array('cash', $selected, true))) {
        throw new InvalidArgumentException('Bindende arrangement krever elektronisk betaling og kan ikke bruke kontant.');
    }
    return $selected;
}

function portalServicePaymentSnapshot(array $row): string {
    return hash('sha256', json_encode([$row['payment_methods_json'], $row['registration_type'] ?? null,
        $row['default_payment_method'] ?? null], JSON_THROW_ON_ERROR));
}

/** Caller owns the transaction, so permission, source, configuration and audit stay atomic. */
function portalServicePayments(PDO $db, string $company, string $module, string $id, ?array $input = null): array {
    [$table, $type, $name] = portalServicePaymentTarget($module);
    $actor = entityFormRequireModuleAccess($db, $company, $type);
    $columns = $module === 'events' ? ',registration_type,default_payment_method,payment_timing' : '';
    $query = $db->prepare("SELECT id,{$name} AS name,payment_methods_json{$columns}
        FROM {$table} WHERE id=? AND company_id=?" . ($input !== null ? ' FOR UPDATE' : ''));
    $query->execute([$id, $company]);
    $row = $query->fetch(PDO::FETCH_ASSOC);
    if (!$row) throw new OutOfBoundsException('Tjenesten ble ikke funnet i denne bedriften.');
    $version = portalServicePaymentSnapshot($row);
    $selected = portalServicePaymentSelection($row['payment_methods_json']);
    if ($input !== null) activePaymentMethodsLock($db, $company);
    $methods = array_values(array_filter(activePaymentMethods($db, $company),
        static fn(array $method): bool => !empty($method['is_configured'])));
    $registration = $row['registration_type'] ?? '';
    if ($registration === 'binding') {
        $methods = array_values(array_filter($methods, static fn(array $method): bool => $method['method_type'] !== 'cash'));
    } elseif ($registration === 'none') {
        $methods = [];
    }
    $changed = false;
    if ($input !== null) {
        if (!isset($input['version']) || !is_string($input['version']) || !hash_equals($version, $input['version'])) {
            throw new DomainException('Betalingsoppsettet er endret siden du åpnet det. Lukk og åpne vinduet på nytt.');
        }
        if (!isset($input['methods']) || !is_array($input['methods'])) {
            throw new InvalidArgumentException('Betalingsmetoder mangler.');
        }
        $next = portalServicePaymentValidate($input['methods'], array_column($methods, 'method_type'), $registration);
        $beforeSorted = $selected; $afterSorted = $next;
        sort($beforeSorted); sort($afterSorted);
        // Repair a recognized legacy representation only on an explicit save.
        $storedJson = $row['payment_methods_json'];
        $legacyFormat = $storedJson !== null && trim($storedJson) !== 'null'
            && !is_array(json_decode($storedJson, true));
        $changed = $beforeSorted !== $afterSorted || $legacyFormat;
        if ($changed) {
            $after = ['payment_methods_json'=>json_encode($next, JSON_THROW_ON_ERROR)];
            if ($module === 'events') {
                $default = historicalPaymentMethodName($row['default_payment_method']);
                $after['default_payment_method'] = in_array($default, $next, true) ? $default : ($next[0] ?? null);
                $after['payment_timing'] = array_diff($next, ['cash']) ? 'both' : 'on_arrival';
            }
            $write = function () use ($db, $company, $module, $id, $table, $row, $after, $actor): void {
                $set = implode(',', array_map(static fn(string $column): string => "{$column}=?", array_keys($after)));
                $db->prepare("UPDATE {$table} SET {$set} WHERE id=? AND company_id=?")
                    ->execute([...array_values($after), $id, $company]);
                tenantAuditRecord($db, $company, $module, $module.'.update', $table, $id,
                    $row['name'], array_intersect_key($row, $after), $after, 'success', [], $actor);
            };
            if ($db instanceof AuditedPDO) $db->withExplicitAudit($write); else $write();
            $row = array_replace($row, $after);
            $selected = $next;
        }
    }
    return ['name'=>$row['name'], 'methods'=>array_map(static fn(array $method): array =>
        ['value'=>$method['method_type'], 'label'=>$method['method_name']], $methods),
        'selected'=>$selected, 'registration_type'=>$registration,
        'version'=>portalServicePaymentSnapshot($row), 'changed'=>$changed];
}
