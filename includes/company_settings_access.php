<?php
declare(strict_types=1);

/** Company settings require a current tenant and supervisor or delegated website access. */
function companySettingsRequireAccess(PDO $db, string $company): void
{
    $admin = (string)($_SESSION['tenant_admin_id'] ?? '');
    if ($admin === '' || $company === '' || $company !== (string)($_SESSION['company_id'] ?? '')) {
        throw new DomainException('Du må være innlogget i riktig bedrift.');
    }
    $query = $db->prepare(
        "SELECT a.id FROM tenant_admins a
         JOIN tenant_companies c ON c.id = a.company_id AND c.is_active = 1
         WHERE a.id = ? AND a.company_id = ? AND a.is_active = 1
         AND (a.tenant_supervisor = 1 OR EXISTS (
             SELECT 1 FROM tenant_admin_modules am
             JOIN system_modules sm ON sm.id = am.module_id AND sm.is_active = 1
             JOIN tenant_company_modules cm ON cm.module_id = sm.id
                 AND cm.company_id = a.company_id AND cm.is_active = 1
             WHERE am.tenant_admin_id = a.id AND sm.module_key = 'ditt_nettsted'
         ))"
    );
    $query->execute([$admin, $company]);
    if (!$query->fetchColumn()) throw new DomainException('Du har ikke tilgang til bedriftsinnstillingene.');
}
