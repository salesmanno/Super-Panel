<?php
/** Economy's configuration activation is authoritative; legacy gateway rows are not. */
function activePaymentMethodName($value): ?string {
    if ($value === null || $value === '') return null;
    if (!is_string($value)) throw new InvalidArgumentException('Ugyldig betalingsmetode.');
    $name = strtolower(trim($value));
    return match ($name) {
        'kontant', 'cash' => 'cash',
        'vipps' => 'vipps',
        'kort', 'card', 'stripe' => 'stripe',
        'paypal' => 'paypal',
        'klarna' => 'klarna',
        'bank', 'bank_transfer', 'bankoverføring' => 'bank',
        'invoice', 'faktura' => 'faktura',
        default => throw new InvalidArgumentException('Ukjent betalingsmetode.'),
    };
}

/** An unavailable historical label may be displayed/preserved, never authorized. */
function historicalPaymentMethodName($value): ?string {
    try {return activePaymentMethodName($value);}
    catch (InvalidArgumentException $e) {
        if (!is_string($value)) throw $e;
        return strtolower(trim($value));
    }
}

function activePaymentMethodsLock(PDO $db, string $company): void {
    if (!$db->inTransaction()) throw new LogicException('Betalingsvalidering krever en transaksjon.');
    $stmt = $db->prepare('SELECT id FROM tenant_companies WHERE id=? FOR UPDATE');
    $stmt->execute([$company]);
    if (!$stmt->fetchColumn()) throw new OutOfBoundsException('Firma ikke funnet.');
}

function activePaymentMethods(PDO $db, string $company): array {
    $currentRead=$db->inTransaction() ? ' FOR UPDATE' : '';
    $stmt = $db->prepare('SELECT cash_payment FROM tenant_companies WHERE id=?'.$currentRead);
    $stmt->execute([$company]);
    $cash = (int)$stmt->fetchColumn() === 1;
    $names = ['cash'=>'Kontant','vipps'=>'Vipps','stripe'=>'Kort','paypal'=>'PayPal',
        'klarna'=>'Klarna','bank'=>'Bankoverføring','faktura'=>'Faktura'];
    $icons = ['cash'=>'cash-stack','vipps'=>'phone','stripe'=>'credit-card','paypal'=>'paypal',
        'klarna'=>'bag-check','bank'=>'bank','faktura'=>'receipt-cutoff'];
    // Only presence flags are read, never credential values.
    $stmt = $db->prepare("SELECT LOWER(gateway_type) AS method_type, config_name, test_mode,
        CASE LOWER(gateway_type)
            WHEN 'vipps' THEN (COALESCE(client_id,'')<>'' AND COALESCE(client_secret_encrypted,'')<>'' AND COALESCE(merchant_serial_number,'')<>'' AND COALESCE(subscription_key,'')<>'')
            WHEN 'stripe' THEN (COALESCE(public_key,'')<>'' AND COALESCE(private_key_encrypted,'')<>'')
            WHEN 'paypal' THEN (COALESCE(client_id,'')<>'' AND COALESCE(client_secret_encrypted,'')<>'')
            WHEN 'klarna' THEN (COALESCE(client_id,'')<>'' AND COALESCE(client_secret_encrypted,'')<>'')
            WHEN 'bank' THEN EXISTS(SELECT 1 FROM economy_bank_accounts b WHERE b.company_id=c.company_id AND b.is_active=1 AND b.account_number IS NOT NULL)
            WHEN 'faktura' THEN EXISTS(SELECT 1 FROM economy_bank_accounts b WHERE b.company_id=c.company_id AND b.is_active=1 AND b.account_number IS NOT NULL)
            ELSE 0 END AS is_configured
        FROM economy_configuration_data c WHERE c.company_id=? AND c.is_active=1 ORDER BY c.config_name,c.id".$currentRead);
    $stmt->execute([$company]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($cash) array_unshift($rows, ['method_type'=>'cash','config_name'=>'Kontant','test_mode'=>0,'is_configured'=>1]);
    $methods = [];
    foreach ($rows as $row) {
        $type = $row['method_type'];
        if (!isset($names[$type]) || ($type === 'cash' && !$cash) || isset($methods[$type])) continue;
        $name = $row['config_name'] ?: $names[$type];
        $methods[$type] = [
            'method_type'=>$type, 'method_name'=>$name, 'is_active'=>true,
            'is_demo'=>(bool)$row['test_mode'], 'is_configured'=>(bool)$row['is_configured'],
            'config'=>['display_name'=>$name,'icon'=>$icons[$type],
                'description'=>$row['is_configured'] ? '' : '(Mangler konfigurasjon)'],
        ];
    }
    return array_values($methods);
}

function requireActivePaymentMethod(PDO $db, string $company, $value): string {
    $name = activePaymentMethodName($value);
    if ($name === null || !in_array($name, array_column(activePaymentMethods($db,$company),'method_type'),true)) {
        throw new InvalidArgumentException('Velg en aktivert betalingsmetode fra Økonomi.');
    }
    return $name;
}

/** Ticket payments additionally obey this event's selected methods and binding rule. */
function requireActiveEventPayment(PDO $db, string $company, string $eventId, $value, float $amount): ?string {
    $stmt = $db->prepare('SELECT registration_type,payment_methods_json FROM events_eventdata WHERE id=? AND company_id=?');
    $stmt->execute([$eventId,$company]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$event) throw new OutOfBoundsException('Arrangement ikke funnet.');
    if ($amount <= 0 && ($value === null || $value === '' || $value === 'free')) return null;
    $name = requireActivePaymentMethod($db,$company,$value);
    if ($event['registration_type'] === 'binding' && $name === 'cash') {
        throw new InvalidArgumentException('Kontantbetaling er ikke tillatt for bindende arrangementer.');
    }
    $allowed = json_decode($event['payment_methods_json'] ?? '[]',true);
    $allowed = array_map('historicalPaymentMethodName', is_array($allowed) ? $allowed : []);
    if (!in_array($name,$allowed,true)) throw new InvalidArgumentException('Betalingsmetoden er ikke valgt for dette arrangementet.');
    return $name;
}
