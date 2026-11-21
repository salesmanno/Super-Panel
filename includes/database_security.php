<?php
declare(strict_types=1);

/**
 * Connection-level defense in depth, not a filter for user text.
 * Values must still use placeholders; identifiers must come from fixed allowlists.
 */
function databaseSecureOptions(string $dsn, ?array $options = null): array {
    $options ??= [];
    if (!str_starts_with(strtolower($dsn), 'mysql:')) return $options;
    $options[PDO::ATTR_EMULATE_PREPARES] = false;
    $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
    $options[PDO::MYSQL_ATTR_LOCAL_INFILE] = false;
    return $options;
}
