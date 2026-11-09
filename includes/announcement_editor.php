<?php
declare(strict_types=1);

/** author_id remains an admin ID; staff identity is resolved through its relation. */
function announcement_editor_author(PDO $db, string $adminId, string $companyId, bool $requireActive = true): ?array {
    $q = $db->prepare('SELECT ts.* FROM tenant_admins ta
        JOIN tenant_staff ts ON BINARY ts.id = BINARY ta.tenant_staff_id
        AND BINARY ts.company_id = BINARY ta.company_id
        WHERE BINARY ta.id = BINARY ? AND BINARY ta.company_id = BINARY ?
        ' . ($requireActive ? ' AND ta.is_active = 1' : '') . ' LIMIT 1');
    $q->execute([$adminId, $companyId]);
    $staff = $q->fetch(PDO::FETCH_ASSOC);
    if (!$staff) return null;
    $name = trim((string)($staff['name'] ?? ''));
    if ($name === '') $name = trim(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''));
    if ($name === '') return null;
    return ['staff_id' => (string)$staff['id'], 'name' => $name];
}

/** Only explicit new editor requests change the legacy scheduling behavior. */
function announcement_editor_publish_at(array $clean, ?array $existing, string $databaseNow): ?string {
    $scheduled = $clean['scheduled_publish'] ?? null;
    if ($scheduled === null) return $clean['publish_at'];
    if ($scheduled) {
        $publish = $clean['publish_at'];
        if (!$publish) throw new InvalidArgumentException('Velg dato og klokkeslett for planlagt publisering.');
        if ($publish <= $databaseNow && $publish !== ($existing['publish_at'] ?? null)) {
            throw new InvalidArgumentException('Planlagt publisering må være frem i tid.');
        }
    } elseif (!empty($existing['is_published']) && !empty($existing['publish_at']) &&
              $existing['publish_at'] <= $databaseNow) {
        $publish = $existing['publish_at'];
    } else {
        $publish = $clean['is_published'] ? $databaseNow : null;
    }
    if ($publish && !empty($clean['expire_at']) && $clean['expire_at'] <= $publish) {
        throw new InvalidArgumentException('Utløpstidspunkt må være etter publiseringstidspunktet.');
    }
    return $publish;
}
