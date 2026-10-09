<?php
require_once __DIR__.'/tenant_audit.php';
require_once __DIR__.'/active_payment_methods.php';

function eventSettingsHasMembershipAccess(PDO $db, string $company): bool {
    $actor = tenantAuditActor($db, $company);
    if (!empty($_SESSION['company_auth_version'])) {
        require_once __DIR__.'/company_login.php';
        return in_array('membership',companyLoginModuleKeys($db,$actor['id']),true);
    }
    $q = $db->prepare("SELECT sm.id FROM system_modules sm
        JOIN tenant_admin_modules tam ON tam.module_id=sm.id
        WHERE tam.tenant_admin_id=? AND sm.module_key='membership' AND sm.is_active=1");
    $q->execute([$actor['id']]);
    return (bool)$q->fetchColumn();
}

function eventSettingsRequireMembership(PDO $db, string $company, array $input, array $existing = []): void {
    if (eventSettingsHasMembershipAccess($db, $company)) return;
    foreach (['memberverification','is_member_exclusive'] as $key) {
        if (array_key_exists($key,$input) && eventSettingsFlag($input,$key) !== eventSettingsFlag([],$key,$existing)) {
            throw new TenantAuditException('Du trenger Membership-modulen for å endre medlemsinnstillinger.');
        }
    }
    if (!empty($input['membership_pricing'])) {
        throw new TenantAuditException('Du trenger Membership-modulen for å endre medlemspriser.');
    }
}
/** Shared create/edit policy. UI hints never authorize payment methods. */
function eventSettingsFlag(array $input, string $key, array $existing = []): int {
    $value = array_key_exists($key,$input) ? $input[$key] : ($existing[$key]??0);
    if (!in_array($value,[true,false,0,1,'0','1'],true)) {
        throw new InvalidArgumentException('Ugyldig medlemsinnstilling');
    }
    return (int)(bool)$value;
}

function eventSettings(PDO $db, string $company, array $input, array $existing = []): array {
    eventSettingsRequireMembership($db, $company, $input, $existing);
    $type = $input['registration_type'] ?? $existing['registration_type'] ?? 'signup';
    if (!in_array($type,['signup','none','binding'],true)) throw new InvalidArgumentException('Ugyldig påmeldingstype');
    activePaymentMethodsLock($db,$company);
    $available=array_column(activePaymentMethods($db,$company),'method_type');
    if (array_key_exists('allowed_payment_methods',$input)) {
        $methods=$input['allowed_payment_methods'];
    } elseif (!$existing && !empty($input['default_payment_method'])) {
        $methods=[$input['default_payment_method']];
    } elseif ($existing) {
        $methods=json_decode($existing['payment_methods_json']??'[]',true)??[];
    } else {
        $methods=$available;
        if ($type==='binding') $methods=array_values(array_diff($methods,['cash']));
    }
    if (!is_array($methods) || !array_is_list($methods)) throw new InvalidArgumentException('Ugyldige betalingsmetoder');
    $oldMethods=json_decode($existing['payment_methods_json']??'[]',true)??[];
    $normalizedOld=array_map('historicalPaymentMethodName',$oldMethods);
    $methods=array_values(array_unique(array_map('historicalPaymentMethodName',$methods)));
    $sameMethods=$existing && $type===($existing['registration_type']??'signup') && !array_diff($methods,$normalizedOld) && !array_diff($normalizedOld,$methods);
    // Basic edits must not silently drop an old, now unavailable configuration.
    foreach($methods as $method) {
        if (!is_string($method) || (!in_array($method,$available,true) && !($sameMethods && in_array($method,$normalizedOld,true)))) {
            throw new InvalidArgumentException('Velg aktive betalingsmetoder fra Økonomi.');
        }
    }
    $methods=array_values(array_unique($methods));
    if ($type==='none') $methods=[];
    if ($type==='binding' && (!$methods || in_array('cash',$methods,true))) {
        throw new InvalidArgumentException('Bindende arrangement krever elektronisk betaling. Kontantbetaling er ikke tillatt.');
    }
    $default=historicalPaymentMethodName($input['default_payment_method']??$existing['default_payment_method']??null);
    if (!$methods) $default=null;
    elseif (!$default) $default=$methods[0];
    elseif (!in_array($default,$methods,true)) throw new InvalidArgumentException('Standardbetalingen må være en av de valgte betalingsmetodene.');
    if ($default && !in_array($default,$available,true) && (!$sameMethods || $default!==historicalPaymentMethodName($existing['default_payment_method']??null))) {
        throw new InvalidArgumentException('Ny standardbetaling må være aktivert i Økonomi.');
    }
    return [
        'registration_type'=>$type, 'payment_methods_json'=>$sameMethods && $type!=='none' ? $existing['payment_methods_json'] : json_encode($methods,JSON_THROW_ON_ERROR),
        'default_payment_method'=>$sameMethods && $default===historicalPaymentMethodName($existing['default_payment_method']??null) ? ($existing['default_payment_method']??null) : $default,
        'payment_timing'=>array_diff($methods,['cash']) ? 'both' : 'on_arrival',
        'memberverification'=>eventSettingsFlag($input,'memberverification',$existing),
        'is_member_exclusive'=>eventSettingsFlag($input,'is_member_exclusive',$existing),
    ];
}
