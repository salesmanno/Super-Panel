<?php
declare(strict_types=1);

/** Caller owns the transaction, including the audit record. Never writes legacy rules. */
function membershipProductBenefitSave(PDO $db, string $company, array $input): array {
    if (!$db->inTransaction()) throw new LogicException('Lagring krever en transaksjon.');
    $product = $input['product_id'] ?? null;
    $plan = $input['plan_id'] ?? null;
    if (!is_string($product) || !is_string($plan) || $product === '' || $plan === ''
        || !array_key_exists('benefit', $input)) throw new InvalidArgumentException('Produkt, medlemsplan og fordel må oppgis.');
    $lock = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    // Serializes updates even when the product/plan has no rule yet.
    $q = $db->prepare('SELECT * FROM output_products WHERE id=? AND company_id=?'.$lock);
    $q->execute([$product, $company]);
    $catalog = $q->fetch(PDO::FETCH_ASSOC);
    $tables = ['events'=>'events_eventdata','rental'=>'rental_objects','product'=>'sales_products',
        'sales_item'=>'sales_products','service'=>'reservation_services'];
    $type = $catalog['product_type'] ?? '';
    if (!isset($tables[$type])) throw new InvalidArgumentException('Produktet kan ikke tildeles medlemsfordeler.');
    $q = $db->prepare("SELECT * FROM {$tables[$type]} WHERE id=? AND company_id=?".$lock);
    $q->execute([$catalog['product_id'], $company]);
    $source = $q->fetch(PDO::FETCH_ASSOC);
    if (!$source || (int)($source['is_active'] ?? 1) !== 1) throw new InvalidArgumentException('Kildeobjektet finnes ikke eller er deaktivert.');
    if ($type === 'events' && (!in_array($source['registration_type'] ?? '', ['signup','binding'], true)
        || (!empty($source['end_date']) && substr($source['end_date'],0,10) < (new DateTimeImmutable('now',new DateTimeZone('Europe/Oslo')))->format('Y-m-d')))) {
        throw new InvalidArgumentException('Bare kommende arrangementer med påmelding kan få medlemsfordeler.');
    }
    $q = $db->prepare('SELECT id FROM membership_plans WHERE id=? AND company_id=? AND is_active=1'.$lock);
    $q->execute([$plan, $company]);
    if (!$q->fetchColumn()) throw new InvalidArgumentException('Medlemsplanen er ikke tilgjengelig i valgt bedrift.');
    $q = $db->prepare('SELECT * FROM membership_product_modifications WHERE company_id=? AND output_product_id=? AND membership_plan_id=?'.$lock);
    $q->execute([$company,$product,$plan]);
    $before = $q->fetch(PDO::FETCH_ASSOC) ?: [];
    $rule = $input['benefit'];
    $after = $before;
    if ($rule === null) {
        if ($before && (int)$before['is_active'] === 1) {
            $db->prepare('UPDATE membership_product_modifications SET is_active=0,updated_at=CURRENT_TIMESTAMP WHERE id=? AND company_id=?')->execute([$before['id'],$company]);
            $after['is_active'] = 0;
        }
    } else {
        if (!is_array($rule)) throw new InvalidArgumentException('Ugyldig medlemsfordel.');
        $benefitType = $rule['benefit_type'] ?? '';
        $basis = $rule['price_basis'] ?? 'unit';
        if (!in_array($benefitType,['access','fixed_price','percent_discount','amount_discount','included'],true)
            || !in_array($basis,['unit','booking_total','rental_day'],true)) throw new InvalidArgumentException('Ugyldig fordelstype eller prisgrunnlag.');
        if ($basis === 'rental_day' && ($type !== 'rental' || $benefitType !== 'amount_discount')) {
            throw new InvalidArgumentException('Dagsavslag gjelder bare utleie og beløpsrabatt.');
        }
        if ($basis === 'booking_total' && $type !== 'rental') throw new InvalidArgumentException('Leiesum gjelder bare utleie.');
        $value = in_array($benefitType,['access','included'],true) ? null : ($rule['benefit_value'] ?? null);
        if ($value !== null && (!is_scalar($value) || is_bool($value))) throw new InvalidArgumentException('Ugyldig rabattbeløp.');
        if (!in_array($benefitType,['access','included'],true)) {
            $text = str_replace(',', '.', (string)$value);
            if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D',$text) || ($benefitType === 'percent_discount' && (float)$text > 100)) {
                throw new InvalidArgumentException('Oppgi et gyldig beløp. Prosent må være mellom 0 og 100.');
            }
            $value = number_format((float)$text,2,'.','');
        }
        if ($basis === 'rental_day' && $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
            $column = $db->query("SHOW COLUMNS FROM membership_product_modifications LIKE 'price_basis'")->fetch(PDO::FETCH_ASSOC);
            if (!str_contains((string)($column['Type'] ?? ''), "'rental_day'")) throw new InvalidArgumentException(
                'Dagsavslag krever at price_basis utvides med rental_day i membership_product_modifications. SQL-endringen må kjøres manuelt. Ingenting ble lagret.');
        }
        $public = $rule['public_display'] ?? true;
        if (!in_array($public,[true,false,0,1,'0','1'],true)) throw new InvalidArgumentException('Ugyldig synlighetsvalg.');
        $after = ['id'=>$before['id'] ?? bin2hex(random_bytes(16)),'company_id'=>$company,
            'output_product_id'=>$product,'membership_plan_id'=>$plan,'benefit_type'=>$benefitType,
            'benefit_value'=>$value,'price_basis'=>$basis,'public_display'=>(int)(bool)$public,'is_active'=>1];
        if ($before) {
            $db->prepare('UPDATE membership_product_modifications SET benefit_type=?,benefit_value=?,price_basis=?,public_display=?,is_active=1,updated_at=CURRENT_TIMESTAMP WHERE id=? AND company_id=?')
                ->execute([$benefitType,$value,$basis,$after['public_display'],$before['id'],$company]);
        } else {
            $db->prepare('INSERT INTO membership_product_modifications (id,company_id,output_product_id,membership_plan_id,benefit_type,benefit_value,price_basis,public_display,is_active) VALUES (?,?,?,?,?,?,?,?,1)')
                ->execute([$after['id'],$company,$product,$plan,$benefitType,$value,$basis,$after['public_display']]);
        }
    }
    $active = !empty($after['is_active']) ? [
        'plan_id'=>$plan,'benefit_type'=>$after['benefit_type'],'benefit_value'=>$after['benefit_value'] === null ? null : (float)$after['benefit_value'],
        'price_basis'=>$after['price_basis'],'public_display'=>(bool)$after['public_display'],'source'=>'membership_product_modifications'
    ] : null;
    return ['benefit'=>$active,'before'=>$before,'after'=>$after,'resource_name'=>
        (string)($source['title'] ?? $source['object_name'] ?? $source['service_name'] ?? $source['name'] ?? $product)];
}
