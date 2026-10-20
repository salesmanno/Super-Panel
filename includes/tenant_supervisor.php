<?php
declare(strict_types=1);

/** Read the current flag, never trust an old session role or a posted company ID. */
function tenantIsSupervisor(PDO $db, string $company, bool $lock = false): bool {
    if ($company === '' || $company !== (string) ($_SESSION['company_id'] ?? '')
        || empty($_SESSION['tenant_admin_id'])) return false;
    $q = $db->prepare('SELECT ta.tenant_supervisor FROM tenant_admins ta
        JOIN tenant_companies c ON c.id = ta.company_id AND c.is_active = 1
        WHERE ta.id = ? AND ta.company_id = ? AND ta.is_active = 1' . ($lock ? ' FOR UPDATE' : ''));
    $q->execute([(string) $_SESSION['tenant_admin_id'], $company]);
    return (int) $q->fetchColumn() === 1;
}

function tenantRequireSupervisor(PDO $db, string $company, bool $lock = false): void {
    if (!tenantIsSupervisor($db, $company, $lock)) {
        throw new DomainException('Kun firmaveileder kan administrere firmabrukere.');
    }
}

function tenantStaffDisplayName(array $staff): string {
    return trim((string) ($staff['name'] ?? ''))
        ?: trim(($staff['first_name'] ?? '') . ' ' . ($staff['last_name'] ?? ''));
}

function tenantManagedUsers(PDO $db, string $company): array {
    tenantRequireSupervisor($db, $company);
    // SELECT staff.* supports both deployed staff-name variants; return only the allowlist below.
    $q = $db->prepare('SELECT ts.*, ta.id AS admin_id, ta.username, ta.is_active AS account_active
        FROM tenant_admins ta JOIN tenant_staff ts
        ON ts.id = ta.tenant_staff_id AND ts.company_id = ta.company_id
        WHERE ta.company_id = ? ORDER BY ta.username');
    $q->execute([$company]);
    $users = [];
    $grants = $db->prepare('SELECT module_id FROM tenant_admin_modules WHERE tenant_admin_id = ?');
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $grants->execute([$row['admin_id']]);
        $users[] = [
            'id' => (string) $row['admin_id'], 'name' => tenantStaffDisplayName($row) ?: $row['username'],
            'username' => $row['username'], 'is_active' => (bool) $row['account_active'],
            'modules' => array_map('strval', $grants->fetchAll(PDO::FETCH_COLUMN)),
        ];
    }
    $q = $db->prepare('SELECT DISTINCT sm.id, sm.name FROM system_modules sm
        JOIN tenant_company_modules cm ON cm.module_id = sm.id
        WHERE cm.company_id = ? AND cm.is_active = 1 AND sm.is_active = 1 ORDER BY sm.name');
    $q->execute([$company]);
    return ['users' => $users, 'modules' => $q->fetchAll(PDO::FETCH_ASSOC)];
}

function tenantChangeManagedUser(PDO $db, string $company, array $data): void {
    $id = $data['user_id'] ?? '';
    $action = $data['action'] ?? '';
    if (!is_string($id) || $id === '' || strlen($id) > 36
        || !in_array($action, ['grant', 'revoke', 'password'], true)) {
        throw new InvalidArgumentException('Ugyldig bruker eller handling.');
    }
    if ($action === 'password') {
        $password = $data['password'] ?? null;
        if (!is_string($password) || mb_strlen($password) < 12 || strlen($password) > 72) {
            throw new InvalidArgumentException('Passordet må ha minst 12 tegn og maksimalt 72 byte.');
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
    } else {
        $module = $data['module_id'] ?? '';
        if (!is_string($module) || $module === '' || strlen($module) > 36) {
            throw new InvalidArgumentException('Ugyldig modul.');
        }
    }
    $db->beginTransaction();
    try {
        tenantRequireSupervisor($db, $company, true);
        // Serialize all delegate changes for this company, including changes for different users.
        $db->prepare('SELECT id FROM tenant_companies WHERE id = ? FOR UPDATE')->execute([$company]);
        $q = $db->prepare('SELECT ta.id, ta.username FROM tenant_admins ta JOIN tenant_staff ts
            ON ts.id = ta.tenant_staff_id AND ts.company_id = ta.company_id
            WHERE ta.id = ? AND ta.company_id = ? FOR UPDATE');
        $q->execute([$id, $company]);
        $target = $q->fetch(PDO::FETCH_ASSOC);
        if (!$target) throw new DomainException('Brukeren må være knyttet til en ansatt i denne bedriften.');
        $changed = false;
        if ($action === 'password') {
            $db->prepare('UPDATE tenant_admins SET password_hash = ? WHERE id = ? AND company_id = ?')
                ->execute([$hash, $id, $company]);
            $changed = true;
            // Revoke old sessions, but retain this verified supervisor's current session when resetting self.
            $db->prepare('UPDATE tenant_admin_sessions SET is_active = 0, logout_at = NOW()
                WHERE tenant_admin_id = ? AND is_active = 1 AND session_id <> ?')
                ->execute([$id, $id === (string) $_SESSION['tenant_admin_id'] ? session_id() : '']);
        } else {
            $q = $db->prepare('SELECT sm.id FROM system_modules sm JOIN tenant_company_modules cm
                ON cm.module_id = sm.id WHERE cm.company_id = ? AND sm.id = ?
                AND sm.is_active = 1 AND cm.is_active = 1 LOCK IN SHARE MODE');
            $q->execute([$company, $module]);
            if (!$q->fetchColumn()) throw new DomainException('Modulen er ikke aktivert for bedriften.');
            if ($action === 'revoke') {
                $delete = $db->prepare('DELETE FROM tenant_admin_modules WHERE tenant_admin_id = ? AND module_id = ?');
                $delete->execute([$id, $module]);
                $changed = $delete->rowCount() > 0;
            } else {
                $q = $db->prepare('SELECT module_id FROM tenant_admin_modules
                    WHERE tenant_admin_id = ? AND module_id = ? FOR UPDATE');
                $q->execute([$id, $module]);
                if (!$q->fetchColumn()) {
                    // Existing schema uses UUID defaults and presence-based per-user grants.
                    $db->prepare('INSERT INTO tenant_admin_modules
                        (tenant_admin_id, module_id, granted_by, granted_at) VALUES (?, ?, ?, NOW())')
                        ->execute([$id, $module, (string) $_SESSION['tenant_admin_id']]);
                    $changed = true;
                }
            }
        }
        if ($changed) {
            require_once __DIR__ . '/tenant_audit.php';
            tenantAuditRecord($db, $company, 'company', 'update', 'tenant_admins', $id,
                (string) $target['username'], [], ['operation' => $action, 'module_id' => $module ?? null],
                'success', [], tenantAuditActor($db, $company),
                $action === 'password' ? ['password_hash'] : []);
        }
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
}
