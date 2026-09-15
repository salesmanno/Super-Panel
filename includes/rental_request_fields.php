<?php

function rentalRequestFieldValuesTableExists(PDO $db): bool
{
    try {
        $stmt = $db->prepare(
            'SELECT 1 FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = ?
             LIMIT 1'
        );
        $stmt->execute(['rental_request_field_values']);
        return (bool) $stmt->fetchColumn();
    } catch (Throwable $e) {
        try {
            $db->query('SELECT 1 FROM rental_request_field_values LIMIT 1');
            return true;
        } catch (Throwable $ignored) {
            return false;
        }
    }
}

function saveRentalRequestFieldValues(
    PDO $db,
    string $companyId,
    string $objectId,
    string $requestId,
    array $fields
): void {
    if (!rentalRequestFieldValuesTableExists($db)) {
        throw new RuntimeException(
            'Forespørselsfelter kan ikke lagres før rental_request_field_values-migrasjonen er kjørt'
        );
    }
    $stmt = $db->prepare(
        'INSERT INTO rental_request_field_values
         (id, company_id, rental_object_id, rental_request_id, field_key, field_value)
         VALUES (UUID(), ?, ?, ?, ?, ?)'
    );
    foreach ($fields as $field => $value) {
        $stmt->execute([$companyId, $objectId, $requestId, $field, (string) $value]);
    }
}

function getRentalRequestFieldValues(PDO $db, string $companyId, string $objectId, string $requestId): array
{
    if (!rentalRequestFieldValuesTableExists($db)) {
        return [];
    }
    $stmt = $db->prepare(
        'SELECT field_key AS field, field_value AS value
         FROM rental_request_field_values
         WHERE company_id = ? AND rental_object_id = ? AND rental_request_id = ?
         ORDER BY field_key ASC'
    );
    $stmt->execute([$companyId, $objectId, $requestId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}