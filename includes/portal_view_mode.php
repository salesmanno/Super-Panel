<?php
declare(strict_types=1);

/** Opt-in only. Installing the column must not change anyone's default portal. */
function portalViewMode(PDO $db, string $adminId): string
{
    try {
        $query = $db->prepare('SELECT view_mode FROM tenant_admins WHERE id = ? LIMIT 1');
        $query->execute([$adminId]);
        return $query->fetchColumn() === 'wpreview' ? 'wpreview' : 'legacy';
    } catch (PDOException $error) {
        // Older exports may not have received the optional column yet.
        if (($error->errorInfo[1] ?? null) == 1054) {
            return 'legacy';
        }
        throw $error;
    }
}

function portalPreviewModules(array $userModules, array $companyModules): array
{
    $active = array_column($companyModules, null, 'module_key');
    return array_values(array_filter($userModules, static fn(array $module): bool =>
        isset($active[$module['module_key'] ?? ''])
    ));
}

function portalPreviewModuleForType(string $type): ?string
{
    return [
        'membership' => 'membership', 'rental' => 'rental', 'events' => 'events',
        'product' => 'sales', 'sales_item' => 'sales', 'service' => 'reservations',
    ][$type] ?? null;
}
