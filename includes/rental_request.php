<?php

require_once __DIR__ . '/request_date_time.php';
require_once __DIR__ . '/rental_customer_fields.php';

class RentalRequestConflictException extends RuntimeException
{
}

/**
 * Shared SQL predicate for objects that customers may view and request.
 * The alias is restricted because SQL identifiers cannot be parameterized.
 */
function rentalPublicEligibilitySql(string $alias = ''): string
{
    if ($alias !== '' && !preg_match('/^[a-z_][a-z0-9_]*$/i', $alias)) {
        throw new InvalidArgumentException('Ugyldig tabellalias');
    }
    $prefix = $alias === '' ? '' : $alias . '.';
    return $prefix . 'is_active = 1 AND ' . $prefix . 'is_available = 1';
}

/**
 * Check the browser origin against the externally visible request origin.
 * Replit and other TLS terminators communicate the first forwarded protocol
 * separately from the backend's HTTP connection.
 */
function rentalRequestSameOrigin(array $server): bool
{
    $host = strtolower(trim((string) ($server['HTTP_HOST'] ?? '')));
    $origin = $server['HTTP_ORIGIN'] ?? $server['HTTP_REFERER'] ?? '';
    if ($host === '' || $origin === '' || $origin === 'null') {
        return false;
    }

    $forwarded = trim((string) ($server['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($forwarded !== '') {
        $effectiveScheme = strtolower(trim(explode(',', $forwarded, 2)[0]));
        if (!in_array($effectiveScheme, ['http', 'https'], true)) {
            return false;
        }
    } else {
        $effectiveScheme = (!empty($server['HTTPS']) && $server['HTTPS'] !== 'off')
            ? 'https'
            : 'http';
    }

    $originParts = parse_url($origin);
    $expectedParts = parse_url($effectiveScheme . '://' . $host);
    if (!$originParts || !$expectedParts || empty($originParts['host']) || empty($expectedParts['host'])) {
        return false;
    }
    if (strtolower((string) $originParts['scheme']) !== $effectiveScheme
        || strtolower((string) $originParts['host']) !== strtolower((string) $expectedParts['host'])) {
        return false;
    }

    $originDefaultPort = $effectiveScheme === 'https' ? 443 : 80;
    $originPort = (int) ($originParts['port'] ?? $originDefaultPort);
    $expectedPort = (int) ($expectedParts['port'] ?? $originDefaultPort);
    return $originPort === $expectedPort;
}

function rentalRequestFieldInputType(string $field): string
{
    return match ($field) {
        'email' => 'email',
        'age' => 'number',
        'phone' => 'tel',
        default => 'text',
    };
}

function renderRentalRequestField(array $setting): string
{
    if (empty($setting['selected'])) {
        return '';
    }
    $field = (string) ($setting['field'] ?? '');
    if (!array_key_exists($field, getRentalCustomerFieldDefinitions())) {
        throw new InvalidArgumentException('Ugyldig kundeopplysningsfelt');
    }
    $escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    $label = $escape($setting['label'] ?? getRentalCustomerFieldDefinitions()[$field]);
    $fieldAttr = $escape($field);
    $type = $escape(rentalRequestFieldInputType($field));
    $required = !empty($setting['required']);
    $requiredMark = $required ? '<span class="text-danger">*</span>' : '';
    $requiredAttr = $required ? ' required' : '';
    $ageAttrs = $field === 'age' ? ' min="0" max="130" step="1"' : '';

    return '<div class="mb-3">'
        . '<label class="form-label" for="field-' . $fieldAttr . '">'
        . $label . ' ' . $requiredMark . '</label>'
        . '<input class="form-control" id="field-' . $fieldAttr . '"'
        . ' name="fields[' . $fieldAttr . ']" type="' . $type . '"'
        . $ageAttrs . $requiredAttr . '>'
        . '</div>';
}

function rentalRequestCustomerDisplay(array $submitted, ?array $existing = null): array
{
    $existing = $existing ?? [];
    $firstName = array_key_exists('first_name', $submitted)
        ? (string) $submitted['first_name']
        : (string) ($existing['first_name'] ?? '');
    $lastName = array_key_exists('last_name', $submitted)
        ? (string) $submitted['last_name']
        : (string) ($existing['last_name'] ?? '');
    return [
        'name' => trim($firstName . ' ' . $lastName) ?: null,
        'email' => array_key_exists('email', $submitted)
            ? ($submitted['email'] !== '' ? $submitted['email'] : null)
            : ($existing['email'] ?? null),
        'phone' => array_key_exists('phone', $submitted)
            ? ($submitted['phone'] !== '' ? $submitted['phone'] : null)
            : ($existing['phone'] ?? null),
    ];
}

/**
 * Validate a public request without touching the database.
 */
function validateRentalRequestInput(array $data, array $settings): array
{
    $submittedFields = $data['fields'] ?? [];
    if (!is_array($submittedFields)) {
        throw new InvalidArgumentException('Kundeopplysninger er ugyldige');
    }

    $definitions = getRentalCustomerFieldDefinitions();
    $selected = [];
    foreach ($settings as $setting) {
        if (!empty($setting['selected'])) {
            $selected[$setting['field']] = $setting;
        }
    }

    foreach (array_keys($submittedFields) as $field) {
        if (!array_key_exists($field, $definitions) || !isset($selected[$field])) {
            throw new InvalidArgumentException('Ugyldig eller ikke valgt kundeopplysning');
        }
    }

    $fields = [];
    foreach ($selected as $field => $setting) {
        $value = $submittedFields[$field] ?? '';
        if (!is_scalar($value)) {
            throw new InvalidArgumentException('Kundeopplysning er ugyldig');
        }
        $value = trim((string) $value);
        if (!empty($setting['required']) && $value === '') {
            throw new InvalidArgumentException('Alle påkrevde kundeopplysninger må fylles ut');
        }
        if ($value !== '' && $field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('E-postadressen er ugyldig');
        }
        if ($value !== '' && $field === 'age'
            && (!ctype_digit($value) || (int) $value < 0 || (int) $value > 130)) {
            throw new InvalidArgumentException('Alder er ugyldig');
        }
        if (strlen($value) > 255) {
            throw new InvalidArgumentException('Kundeopplysningen er for lang');
        }
        $fields[$field] = $value;
    }

    $startDate = normalizeRequestDate($data['start_date'] ?? null, 'Startdato', false);
    $endDate = normalizeRequestDate($data['end_date'] ?? null, 'Sluttdato', false);
    $startTime = normalizeRequestTime($data['start_time'] ?? null, 'Starttid', false);
    $endTime = normalizeRequestTime($data['end_time'] ?? null, 'Sluttid', false);
    if ($endDate < $startDate || ($endDate === $startDate && $endTime < $startTime)) {
        throw new InvalidArgumentException('Sluttidspunkt må være etter starttidspunkt');
    }

    $logistics = (string) ($data['logistics'] ?? 'pick_up');
    if (!in_array($logistics, ['pick_up', 'delivery'], true)) {
        throw new InvalidArgumentException('Ugyldig logistikkvalg');
    }
    $notes = trim((string) ($data['notes'] ?? ''));
    if (strlen($notes) > 5000) {
        throw new InvalidArgumentException('Notatet er for langt');
    }

    return [
        'fields' => $fields,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'logistics' => $logistics,
        'notes' => $notes,
    ];
}