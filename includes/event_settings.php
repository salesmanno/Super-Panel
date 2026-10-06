<?php
/** Shared create/edit policy. UI hints never authorize payment methods. */
function eventSettingsFlag(array $input, string $key, array $existing = []): int {
    $value = array_key_exists($key,$input) ? $input[$key] : ($existing[$key]??0);
    if (!in_array($value,[true,false,0,1,'0','1'],true)) {
        throw new InvalidArgumentException('Ugyldig medlemsinnstilling');
    }
    return (int)(bool)$value;
}

function eventSettings(PDO $db, string $company, array $input, array $existing = []): array {
    $type = $input['registration_type'] ?? $existing['registration_type'] ?? 'signup';
    if (!in_array($type,['signup','none','binding'],true)) throw new InvalidArgumentException('Ugyldig påmeldingstype');
    $q=$db->prepare('SELECT cash_payment FROM tenant_companies WHERE id=?');$q->execute([$company]);
    $available=(int)$q->fetchColumn()===1 ? ['cash'] : [];
    $q=$db->prepare('SELECT gateway_name FROM economy_payment_gateways WHERE company_id=? AND is_active=1');
    $q->execute([$company]);
    foreach($q->fetchAll(PDO::FETCH_COLUMN) as $name) $available[]=strtolower(trim($name));
    $available=array_values(array_unique($available));
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
    foreach($methods as $method) {
        if (!is_string($method) || !in_array($method,$available,true)) {
            throw new InvalidArgumentException('Velg aktive betalingsmetoder fra Økonomi.');
        }
    }
    $methods=array_values(array_unique($methods));
    if ($type==='none') $methods=[];
    if ($type==='binding' && (!$methods || in_array('cash',$methods,true))) {
        throw new InvalidArgumentException('Bindende arrangement krever elektronisk betaling. Kontantbetaling er ikke tillatt.');
    }
    $default=$input['default_payment_method']??$existing['default_payment_method']??null;
    if (!$methods) $default=null;
    elseif (!$default) $default=$methods[0];
    elseif (!in_array($default,$methods,true)) throw new InvalidArgumentException('Standardbetalingen må være en av de valgte betalingsmetodene.');
    return [
        'registration_type'=>$type, 'payment_methods_json'=>json_encode($methods,JSON_THROW_ON_ERROR),
        'default_payment_method'=>$default,
        'payment_timing'=>array_diff($methods,['cash']) ? 'both' : 'on_arrival',
        'memberverification'=>eventSettingsFlag($input,'memberverification',$existing),
        'is_member_exclusive'=>eventSettingsFlag($input,'is_member_exclusive',$existing),
    ];
}
