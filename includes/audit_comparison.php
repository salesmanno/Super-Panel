<?php
declare(strict_types=1);

/** Compare business values before truncation/redaction. Never persist this data. */
function tenantAuditComparable(string $field, mixed $value): mixed {
    if ($value === null) return null;
    $jsonFields = ['payment_methods_json','weekday_prices','images','rental_periods',
        'options','validation_rules','metadata','settings','configuration'];
    if (in_array($field, $jsonFields, true)) {
        try {
            $decoded = is_string($value) ? json_decode($value, false, 512, JSON_THROW_ON_ERROR) : $value;
            return ['json' => tenantAuditCanonicalJson($decoded)];
        } catch (JsonException $error) {
            // Invalid stored JSON is not equivalent to an empty/default value.
            return ['invalid_json' => $value];
        }
    }
    if ((is_scalar($value) && is_numeric($value)) && (
        preg_match('/(?:_price|_fee|_amount|_cents)$/D', $field)
        || preg_match('/^(?:is_|has_|automail_)/', $field)
        || in_array($field, ['price','amount','quantity','approved','cleaning','checked_in',
            'priority','news_priority','capacity','duration_months','auto_renew','cancellation_active',
            'sort_order','display_order','public_display','weekday_prices_enabled','memberverification',
            'max_attendees','min_attendees','age_limit','age_required','is_required','is_visible'], true)
    )) {
        // Decimal normalization without float rounding (also handles bools below).
        $text = (string)$value;
        if (preg_match('/^([+-]?)(\d+)(?:\.(\d*))?$/D', $text, $m)) {
            $whole = ltrim($m[2], '0') ?: '0';
            $fraction = rtrim($m[3] ?? '', '0');
            $number = $whole . ($fraction !== '' ? '.' . $fraction : '');
            return ['number' => ($m[1] === '-' && $number !== '0' ? '-' : '') . $number];
        }
    }
    if (is_bool($value)) return tenantAuditComparable($field, (int)$value);
    return $value;
}

function tenantAuditCanonicalJson(mixed $value): mixed {
    if ($value instanceof stdClass) {
        $items = get_object_vars($value);
        ksort($items, SORT_STRING);
        return ['object' => array_map('tenantAuditCanonicalJson', $items)];
    }
    if (is_array($value)) {
        if (!array_is_list($value)) {
            ksort($value, SORT_STRING);
            return ['object' => array_map('tenantAuditCanonicalJson', $value)];
        }
        return ['list' => array_map('tenantAuditCanonicalJson', $value)];
    }
    if (is_int($value) || is_float($value)) return ['number' => json_encode($value, JSON_THROW_ON_ERROR)];
    return $value;
}

function tenantAuditEquivalent(string $field, mixed $before, mixed $after): bool {
    return tenantAuditComparable($field, $before) === tenantAuditComparable($field, $after);
}

/** Only fields selected by the audit policy participate; timestamps are not changes. */
function tenantAuditChangedKeys(string $resource, array $before, array $after): array {
    $allowed = function_exists('tenantAuditResourceSpec')
        ? (tenantAuditResourceSpec($resource)['fields'] ?? []) : (tenantAuditFields()[$resource] ?? []);
    $changed = [];
    foreach (array_unique(array_merge(array_keys($before), array_keys($after))) as $field) {
        $hash = str_starts_with((string)$field, '_audit_hash_');
        if (!$hash && !in_array($field, $allowed, true)
            && !($resource === 'email_configs' && str_starts_with($field, 'automail_'))
            && !($resource === 'email_templates' && in_array($field,
                ['body_template','subject_template','body_template_sha256','subject_template_sha256'], true))) continue;
        if (array_key_exists($field, $before) !== array_key_exists($field, $after)
            || !tenantAuditEquivalent($field, $before[$field] ?? null, $after[$field] ?? null)) {
            $changed[] = $field;
        }
    }
    return $changed;
}
