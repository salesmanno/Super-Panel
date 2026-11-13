<?php
declare(strict_types=1);
require_once __DIR__.'/tenant_audit.php';

function economyVippsAccess(PDO $db, array $session): string {
    $company = (string)($session['company_id'] ?? '');
    if ($company === '' || empty($session['tenant_admin_id'])) {
        throw new RuntimeException('Ikke autentisert.', 401);
    }
    // Also checks that this administrator still belongs to an active company.
    $actor = tenantAuditActor($db, $company);
    $q = $db->prepare("SELECT 1 FROM tenant_admin_modules tam JOIN system_modules sm ON sm.id=tam.module_id
        WHERE tam.tenant_admin_id=? AND sm.module_key='economy' AND sm.is_active=1");
    $q->execute([$actor['id']]);
    if (!$q->fetchColumn()) throw new RuntimeException('Du trenger tilgang til Økonomi.', 403);
    return $company;
}
function economyVippsCsrf(array $session, $token): void {
    if (!is_string($token) || empty($session['economy_bank_csrf'])
        || !hash_equals((string)$session['economy_bank_csrf'], $token)) {
        throw new RuntimeException('Sikkerhetskontrollen utløp. Last betalingsløsninger på nytt.', 403);
    }
}
