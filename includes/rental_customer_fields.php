<?php
require_once __DIR__ . '/entity_form_fields.php';

/**
 * Customer data that may be requested for a rental object.
 *
 * The table is deliberately optional at runtime. This allows an existing
 * installation to keep serving rental objects while the migration is being
 * deployed.
 */
function getRentalCustomerFieldDefinitions(): array
{
    return [
        'first_name' => 'Fornavn',
        'last_name' => 'Etternavn',
        'phone' => 'Telefon',
        'email' => 'E-post',
        'postal_code' => 'Postnummer',
        'street_address' => 'Gateadresse',
        'city' => 'Poststed',
        'age' => 'Alder',
    ];
}

function defaultRentalCustomerFieldSettings(): array
{
    $settings = [];
    foreach (array_keys(getRentalCustomerFieldDefinitions()) as $order => $field) {
        $settings[] = [
            'field' => $field,
            'label' => getRentalCustomerFieldDefinitions()[$field],
            'selected' => 1,
            'required' => 0,
            'order' => $order,
        ];
    }
    return $settings;
}

function rentalCustomerFieldsTableExists(PDO $db): bool
{
    try {
        $stmt = $db->prepare(
            'SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?
             LIMIT 1'
        );
        $stmt->execute(['rental_customer_field_settings']);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        // Compatibility path for restricted DB users.
        try {
            $db->query('SELECT 1 FROM rental_customer_field_settings LIMIT 1');
            return true;
        } catch (Throwable $ignored) {
            return false;
        }
    }
}

/**
 * Normalize and validate a submitted field setup.
 *
 * Both a list of {field, selected, required, order/sort_order} and an associative
 * field => options payload are accepted to keep old clients compatible.
 */
function normalizeRentalCustomerFieldSettings($input): array
{
    $definitions = getRentalCustomerFieldDefinitions();
    $normalized = [];
    $items = is_array($input) ? $input : [];

    if ($items && array_keys($items) !== range(0, count($items) - 1)) {
        $converted = [];
        foreach ($items as $field => $options) {
            $converted[] = is_array($options)
                ? array_merge(['field' => $field], $options)
                : ['field' => $field, 'selected' => $options];
        }
        $items = $converted;
    }

    foreach ($items as $index => $item) {
        if (!is_array($item)) {
            throw new InvalidArgumentException('Kundeoppsett er ugyldig');
        }
        $field = (string) ($item['field'] ?? $item['key'] ?? '');
        if (!array_key_exists($field, $definitions)) {
            throw new InvalidArgumentException('Ugyldig kundeopplysningsfelt');
        }
        if (isset($normalized[$field])) {
            throw new InvalidArgumentException('Kundeopplysningsfelt er valgt flere ganger');
        }
        $order = $item['order'] ?? $item['display_order'] ?? $item['sort_order'] ?? $index;
        if (!is_numeric($order) || (int) $order < 0) {
            throw new InvalidArgumentException('Rekkefølge for kundeopplysninger er ugyldig');
        }
        $normalized[$field] = [
            'field' => $field,
            'label' => $definitions[$field],
            'selected' => !empty($item['selected']) ? 1 : 0,
            'required' => !empty($item['required']) ? 1 : 0,
            'order' => (int) $order,
        ];
        if ($normalized[$field]['required']) {
            $normalized[$field]['selected'] = 1;
        }
    }

    // Missing known fields remain available and get a deterministic order.
    foreach ($definitions as $field => $label) {
        if (!isset($normalized[$field])) {
            $normalized[$field] = [
                'field' => $field,
                'label' => $label,
                'selected' => 0,
                'required' => 0,
                'order' => count($normalized),
            ];
        }
    }
    usort($normalized, static function (array $a, array $b): int {
        return $a['order'] <=> $b['order'];
    });
    return array_values($normalized);
}

function getRentalCustomerFieldSettings(PDO $db, $companyId, $objectId): array
{
    $loaded = entityFormLoad($db, (string)$companyId, 'rental_object', (string)$objectId);
    return array_map(static fn($f) => $f + ['field'=>$f['field_name'],'label'=>$f['field_label'],
        'selected'=>$f['is_visible'],'required'=>$f['is_required'],'order'=>$f['sort_order']], $loaded['fields']);
}

function saveRentalCustomerFieldSettings(PDO $db, $companyId, $objectId, $input): array
{
    $settings = normalizeRentalCustomerFieldSettings($input);
    $defaults = array_column(entityFormDefaults('rental_object'), null, 'field_name');
    $fields = array_map(static fn($s) => array_replace($defaults[$s['field']], [
        'is_visible'=>$s['selected'],'is_required'=>$s['required'],'sort_order'=>$s['order']
    ]), $settings);
    entityFormSave($db, (string)$companyId, 'rental_object', (string)$objectId, $fields);
    return $settings;
}

function rentalObjectColumnExists(PDO $db, string $column): bool
{
    // Column names are from a hard-coded allow-list at call sites.
    try {
        $stmt = $db->prepare(
            'SELECT 1 FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?
             LIMIT 1'
        );
        $stmt->execute(['rental_objects', $column]);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        try {
            $stmt = $db->prepare('SHOW COLUMNS FROM rental_objects LIKE ?');
            $stmt->execute([$column]);
            return (bool) $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $ignored) {
            return false;
        }
    }
}