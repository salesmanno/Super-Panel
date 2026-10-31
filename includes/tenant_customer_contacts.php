<?php

/**
 * Normalize profile contacts before storage and duplicate matching.
 * Email comparison is case-insensitive; phone punctuation/spacing is ignored.
 */
function tenantCustomerNormalizeContact(string $type, ?string $value): string
{
    $value = trim((string) $value);
    if ($type === 'email') {
        return mb_strtolower($value, 'UTF-8');
    }
    if ($type === 'phone') {
        return preg_replace('/[\s().-]+/u', '', $value) ?? $value;
    }
    throw new InvalidArgumentException('Ukjent kontakttype.');
}

function tenantCustomerValidateContact(string $type, string $value, bool $allowBlank = false): void
{
    $normalized = tenantCustomerNormalizeContact($type, $value);
    if ($allowBlank && $value === '') return;
    if ($normalized === '' || mb_strlen($value, 'UTF-8') > 255) {
        throw new InvalidArgumentException('Kontaktverdien må være utfylt og maksimalt 255 tegn.');
    }
    if ($type === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
        throw new InvalidArgumentException('E-postadressen er ugyldig.');
    }
}

/**
 * SQL expression returning the primary profile contact, with the legacy
 * customer column as a transition fallback.
 */
function tenantCustomerContactSql(string $customerAlias, string $type): string
{
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $customerAlias)) {
        throw new InvalidArgumentException('Ugyldig SQL-alias for kunde.');
    }
    [$column, $contactType] = match ($type) {
        'email' => ['email', 'email'],
        'phone' => ['phone', 'phone'],
        default => throw new InvalidArgumentException('Ukjent kontakttype.')
    };

    return "COALESCE(
        (
            SELECT NULLIF(TRIM(tcc.contact_value), '')
            FROM tenant_customer_contacts tcc
            WHERE tcc.customer_id = {$customerAlias}.id
              AND tcc.contact_type = '{$contactType}'
              AND tcc.is_primary = 1
            ORDER BY tcc.created_at ASC, tcc.id ASC
            LIMIT 1
        ),
        NULLIF(TRIM({$customerAlias}.{$column}), '')
    )";
}

/**
 * SQL expression for comparing legacy profile values using the same
 * normalization rules as tenantCustomerNormalizeContact().
 */
function tenantCustomerLegacyNormalizedSql(string $customerAlias, string $type): string
{
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $customerAlias)) {
        throw new InvalidArgumentException('Ugyldig SQL-alias for kunde.');
    }
    return match ($type) {
        'email' => "LOWER(TRIM({$customerAlias}.email))",
        'phone' => "REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM({$customerAlias}.phone), ' ', ''), CHAR(9), ''), '(', ''), ')', ''), '-', ''), '.', '')",
        default => throw new InvalidArgumentException('Ukjent kontakttype.')
    };
}

/**
 * Find a company-scoped customer by any matching profile contact.
 * Callers that create or edit linked entities should hold their transaction.
 */
function tenantCustomerFindByContact(
    PDO $db,
    string $companyId,
    string $type,
    string $value,
    bool $forUpdate = false
): ?array {
    $normalized = tenantCustomerNormalizeContact($type, $value);
    if ($normalized === '') {
        return null;
    }
    $legacyNormalized = tenantCustomerLegacyNormalizedSql('tc', $type);
    $lock = $forUpdate ? ' FOR UPDATE' : '';
    $stmt = $db->prepare("
        SELECT tc.id, tc.company_id, tc.first_name, tc.last_name
        FROM tenant_customers tc
        WHERE tc.company_id = ?
          AND (
              EXISTS (
                  SELECT 1
                  FROM tenant_customer_contacts tcc
                  WHERE tcc.customer_id = tc.id
                    AND tcc.contact_type = ?
                    AND LOWER(TRIM(tcc.normalized_value)) = ?
              )
              OR (
                  NOT EXISTS (
                      SELECT 1 FROM tenant_customer_contacts known_contact
                      WHERE known_contact.customer_id = tc.id
                        AND known_contact.contact_type = ?
                        AND known_contact.is_primary = 1
                  )
                  AND {$legacyNormalized} = ?
              )
          )
        ORDER BY tc.created_at ASC, tc.id ASC
        LIMIT 2{$lock}
    ");
    $stmt->execute([$companyId, $type, $normalized, $type, $normalized]);
    $customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($customers) > 1) {
        throw new InvalidArgumentException('Kontaktopplysningen er knyttet til flere kunder. Velg kunden uttrykkelig.');
    }
    return $customers[0] ?? null;
}

/**
 * Set the requested profile contact as primary without deleting secondary
 * contacts. A blank value clears the primary designation for this type.
 * The legacy column is kept synchronized until all deployed callers migrate.
 */
function tenantCustomerSetPrimaryContact(
    PDO $db,
    string $companyId,
    string $customerId,
    string $type,
    ?string $value,
    bool $preserveLegacy = true
): void {
    $column = match ($type) {
        'email' => 'email',
        'phone' => 'phone',
        default => throw new InvalidArgumentException('Ukjent kontakttype.')
    };
    $value = trim((string) $value);
    $normalized = tenantCustomerNormalizeContact($type, $value);
    tenantCustomerValidateContact($type, $value, true);

    $ownsTransaction = !$db->inTransaction();
    if ($ownsTransaction) {
        $db->beginTransaction();
    }
    try {
        $customerStmt = $db->prepare(
            "SELECT id, {$column} AS legacy_contact
             FROM tenant_customers WHERE id = ? AND company_id = ? FOR UPDATE"
        );
        $customerStmt->execute([$customerId, $companyId]);
        $customer = $customerStmt->fetch(PDO::FETCH_ASSOC);
        if (!$customer) {
            throw new InvalidArgumentException('Kunden tilhører ikke valgt bedrift.');
        }

        // Preserve a legacy-only contact when the profile is edited before backfill.
        $legacyValue = trim((string) ($customer['legacy_contact'] ?? ''));
        if ($preserveLegacy && $legacyValue !== '') {
            $legacyNormalized = tenantCustomerNormalizeContact($type, $legacyValue);
            $db->prepare(
                'INSERT INTO tenant_customer_contacts
                    (id, customer_id, contact_type, contact_value, normalized_value, is_primary, created_at)
                 SELECT ?, ?, ?, ?, ?, 0, NOW()
                 WHERE NOT EXISTS (
                     SELECT 1 FROM tenant_customer_contacts
                     WHERE customer_id = ? AND contact_type = ? AND normalized_value = ?
                 )'
            )->execute([
                tenantCustomerContactUuid(), $customerId, $type, $legacyValue, $legacyNormalized,
                $customerId, $type, $legacyNormalized,
            ]);
        }

        $db->prepare(
            'UPDATE tenant_customer_contacts
             SET is_primary = 0
             WHERE customer_id = ? AND contact_type = ? AND is_primary = 1'
        )->execute([$customerId, $type]);

        if ($value !== '') {
            $existingStmt = $db->prepare(
                'SELECT id
                 FROM tenant_customer_contacts
                 WHERE customer_id = ? AND contact_type = ? AND normalized_value = ?
                 ORDER BY created_at ASC, id ASC
                 LIMIT 1
                 FOR UPDATE'
            );
            $existingStmt->execute([$customerId, $type, $normalized]);
            $contactId = $existingStmt->fetchColumn();
            if ($contactId) {
                // Do not alter verified_at when the contact value is unchanged.
                $db->prepare(
                    'UPDATE tenant_customer_contacts
                     SET contact_value = ?, normalized_value = ?, is_primary = 1
                     WHERE id = ? AND customer_id = ?'
                )->execute([$value, $normalized, $contactId, $customerId]);
            } else {
                $db->prepare(
                    'INSERT INTO tenant_customer_contacts
                        (id, customer_id, contact_type, contact_value, normalized_value, is_primary, created_at)
                     VALUES (?, ?, ?, ?, ?, 1, NOW())'
                )->execute([
                    tenantCustomerContactUuid(),
                    $customerId,
                    $type,
                    $value,
                    $normalized
                ]);
            }
        }

        $db->prepare(
            "UPDATE tenant_customers
             SET {$column} = ?, updated_at = NOW()
             WHERE id = ? AND company_id = ?"
        )->execute([$value !== '' ? $value : null, $customerId, $companyId]);

        if ($ownsTransaction) {
            $db->commit();
        }
    } catch (Throwable $e) {
        if ($ownsTransaction && $db->inTransaction()) {
            $db->rollBack();
        }
        throw $e;
    }
}

function tenantCustomerContactUuid(): string
{
    $bytes = random_bytes(16);
    $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
    $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
}
