<?php
declare(strict_types=1);

function membershipBenefitsPreviewCanAccess(PDO $db, string $admin, string $company): bool {
    $q = $db->prepare("SELECT a.id FROM tenant_admins a
        JOIN tenant_admin_modules am ON am.tenant_admin_id=a.id
        JOIN system_modules sm ON sm.id=am.module_id
        JOIN tenant_company_modules cm ON cm.module_id=sm.id AND cm.company_id=a.company_id
        WHERE a.id=? AND a.company_id=? AND a.is_active=1 AND sm.module_key='membership'
            AND sm.is_active=1 AND cm.is_active=1");
    $q->execute([$admin, $company]);
    return (bool)$q->fetchColumn();
}

/** Read-only catalog projection. Never expose source rows (e.g. venue access codes). */
function membershipBenefitsPreviewProduct(array $catalog, array $source): array {
    $type = (string)$catalog['product_type'];
    $images = $source['images'] ?? [];
    if (is_string($images)) $images = json_decode($images, true) ?: [];
    $firstImage = is_array($images) ? (array_values($images)[0] ?? '') : '';
    if (is_array($firstImage)) $firstImage = $firstImage['url'] ?? '';
    $image = match ($type) {
        'events' => $source['event_image_url'] ?? '',
        'membership' => $source['image_url'] ?? '',
        'rental' => $firstImage ?: ($source['image_url'] ?? ''),
        'product' => $source['img_product'] ?? '',
        default => $source['img_url'] ?? '',
    };
    $price = match ($type) {
        'events' => $source['ticket_price'] ?? null,
        'rental' => $source['rental_price'] ?? null,
        'product' => $source['selling_price'] ?? null,
        default => $source['price'] ?? null,
    };
    $active = (int)($source['is_active'] ?? 1) === 1;
    if ($type === 'rental') $active = $active && (int)($source['is_available'] ?? 1) === 1;
    if ($type === 'membership') $active = $active && (int)($source['for_sale'] ?? 0) === 1;
    return [
        'id' => (string)$catalog['id'],
        'source_id' => (string)$catalog['product_id'],
        'type' => $type,
        'name' => (string)($source['title'] ?? $source['object_name'] ?? $source['service_name'] ?? $source['name'] ?? $catalog['name'] ?? ''),
        'description' => (string)($source['description'] ?? $catalog['description'] ?? ''),
        'image_url' => (string)$image,
        'price' => is_numeric($price) ? (float)$price : null,
        'start_date' => $type === 'events' ? ($source['start_date'] ?? null) : null,
        'start_time' => $type === 'events' ? ($source['start_time'] ?? null) : null,
        'end_date' => $type === 'events' ? ($source['end_date'] ?? null) : null,
        'registration_type' => $type === 'events' ? ($source['registration_type'] ?? 'none') : null,
        'benefit_eligible' => $type !== 'membership' && ($type !== 'events' || in_array($source['registration_type'] ?? '', ['signup', 'binding'], true)),
        'exclusive' => (bool)($source['is_member_exclusive'] ?? $source['member_exclusive'] ?? false),
        'is_visible' => $active && (int)($catalog['is_visible'] ?? 0) === 1,
        'is_public' => $active && (int)($catalog['is_public'] ?? 0) === 1,
        'membership_prices' => [],
        'assigned_benefits' => [],
    ];
}

/** Resolve event preview rules per plan, without stacking new and legacy prices.
 * An inactive new row is an explicit override, not permission to revive legacy.
 */
function membershipBenefitsPreviewEventRules(array $newRules, array $legacyPrices, ?float $basePrice): array {
    $rules = [];
    $prices = [];
    $covered = [];
    foreach ($newRules as $rule) {
        $covered[(string)$rule['plan_id']] = true;
        if (!$rule['is_active']) continue;
        unset($rule['is_active']);
        $rule['source'] = 'membership_product_modifications';
        $rules[] = $rule;
        $value = $rule['benefit_value'];
        $validValue = is_numeric($value) && is_finite((float)$value) && (float)$value >= 0;
        // Event tickets have a unit price. Do not misapply a booking-total rule.
        $price = $rule['price_basis'] !== 'unit' ? null : match ($rule['benefit_type']) {
            'fixed_price' => $validValue ? (float)$value : null,
            'included' => 0.0,
            'percent_discount' => $validValue && (float)$value <= 100 && $basePrice !== null ? round($basePrice * (1 - (float)$value / 100), 2) : null,
            'amount_discount' => $validValue && $basePrice !== null ? round(max(0, $basePrice - (float)$value), 2) : null,
            'access' => $basePrice,
            default => null,
        };
        if ($price !== null) $prices[] = ['plan_id'=>$rule['plan_id'], 'price'=>$price, 'public_display'=>$rule['public_display'], 'source'=>$rule['source']];
    }
    foreach ($legacyPrices as $legacy) {
        if (isset($covered[(string)$legacy['plan_id']])) continue;
        $legacy['source'] = 'event_membership_plans';
        $prices[] = $legacy;
        $rules[] = ['plan_id'=>$legacy['plan_id'], 'benefit_type'=>'fixed_price',
            'benefit_value'=>$legacy['price'], 'price_basis'=>'unit',
            'public_display'=>$legacy['public_display'], 'source'=>'event_membership_plans'];
    }
    return ['rules'=>$rules, 'prices'=>$prices];
}

function membershipBenefitsPreviewLoad(PDO $db, string $company): array {
    require_once __DIR__ . '/announcement_preview.php';
    $preview = announcement_preview_company($db, $company);
    // News-specific wording is inappropriate here; keep failures visible.
    $warnings = array_map(static fn($warning) => str_replace('Sakene kan fortsatt administreres.', 'Forhåndsvisningen kan mangle profilinnstillinger.', $warning), $preview['warnings']);
    $q = $db->prepare('SELECT id,name,description,price,image_url FROM membership_plans WHERE company_id=? AND is_active=1 ORDER BY name,id');
    $q->execute([$company]);
    $plans = $q->fetchAll(PDO::FETCH_ASSOC);
    $q = $db->prepare('SELECT id,product_type,product_id,name,description,is_visible,is_public FROM output_products WHERE company_id=? ORDER BY created_at DESC,id');
    $q->execute([$company]);
    $catalog = $q->fetchAll(PDO::FETCH_ASSOC);
    // Sales accepts this legacy catalogue type as the same source product.
    foreach ($catalog as $index => $entry) {
        if ($entry['product_type'] === 'sales_item') $catalog[$index]['product_type'] = 'product';
    }
    $tables = ['events'=>'events_eventdata', 'membership'=>'membership_plans', 'rental'=>'rental_objects', 'product'=>'sales_products', 'service'=>'reservation_services'];
    $labels = ['events'=>'arrangementer', 'membership'=>'medlemskap', 'rental'=>'utleie', 'product'=>'varer', 'service'=>'tjenester'];
    $sources = [];
    foreach ($tables as $type => $table) {
        if (!in_array($type, array_column($catalog, 'product_type'), true)) continue;
        try {
            $q = $db->prepare("SELECT * FROM {$table} WHERE company_id=?");
            $q->execute([$company]);
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $source) $sources[$type][(string)$source['id']] = $source;
        } catch (PDOException $e) {
            error_log('Benefits preview source '.$type.': '.$e->getMessage());
            $warnings[] = 'Kunne ikke hente '.$labels[$type].'. Denne delen av forhåndsvisningen er ufullstendig.';
        }
    }
    $pricing = [];
    if (!empty($sources['events'])) {
        try {
            $q = $db->prepare('SELECT emp.event_id,emp.membership_plan_id,emp.ticket_price,emp.public_display
                FROM event_membership_plans emp
                JOIN events_eventdata e ON e.id=emp.event_id
                JOIN membership_plans mp ON mp.id=emp.membership_plan_id
                WHERE e.company_id=? AND mp.company_id=?');
            $q->execute([$company, $company]);
            foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $pricing[(string)$row['event_id']][] = ['plan_id'=>(string)$row['membership_plan_id'], 'price'=>(float)$row['ticket_price'], 'public_display'=>(bool)$row['public_display']];
            }
        } catch (PDOException $e) {
            error_log('Benefits preview pricing: '.$e->getMessage());
            $warnings[] = 'Medlemspriser for arrangementer kunne ikke hentes. Prisvisningen er ufullstendig.';
        }
    }
    // Read new rules first in precedence, retaining inactive rows as fallback blockers.
    $benefits = [];
    try {
        $q = $db->prepare("SELECT m.output_product_id,m.membership_plan_id,m.benefit_type,
                m.benefit_value,m.price_basis,m.public_display,m.is_active
            FROM membership_product_modifications m
            JOIN output_products op ON op.id=m.output_product_id AND op.company_id=m.company_id
            JOIN membership_plans mp ON mp.id=m.membership_plan_id AND mp.company_id=m.company_id
            WHERE m.company_id=? AND mp.is_active=1
                AND op.product_type <> 'membership'
            ORDER BY mp.name,m.membership_plan_id");
        $q->execute([$company]);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $benefits[(string)$row['output_product_id']][] = [
                'plan_id'=>(string)$row['membership_plan_id'],
                'benefit_type'=>(string)$row['benefit_type'],
                'benefit_value'=>is_numeric($row['benefit_value']) ? (float)$row['benefit_value'] : null,
                'price_basis'=>(string)$row['price_basis'],
                'public_display'=>(bool)$row['public_display'],
                'is_active'=>(bool)$row['is_active'],
            ];
        }
    } catch (PDOException $e) {
        error_log('Benefits preview assigned rules: '.$e->getMessage());
        $warnings[] = 'Tildelte produktfordeler kunne ikke hentes. Fordelsindikatorene er ufullstendige.';
    }
    $products = [];
    $seen = [];
    foreach ($catalog as $entry) {
        $type = (string)$entry['product_type'];
        $id = (string)$entry['product_id'];
        $key = $type.':'.$id;
        if (isset($seen[$key]) || !isset($tables[$type])) continue;
        $seen[$key] = true;
        if (!isset($sources[$type][$id])) {
            $warnings[] = 'Et katalogprodukt mangler et tilgjengelig kildeobjekt og vises ikke.';
            continue;
        }
        $item = membershipBenefitsPreviewProduct($entry, $sources[$type][$id]);
        // An event remains available throughout its final calendar day (Oslo).
        $today = (new DateTimeImmutable('now', new DateTimeZone('Europe/Oslo')))->format('Y-m-d');
        if ($type === 'events' && !empty($item['end_date']) && substr((string)$item['end_date'], 0, 10) < $today) continue;
        $item['membership_prices'] = $type === 'events' ? ($pricing[$id] ?? []) : [];
        $newRules = $benefits[(string)$entry['id']] ?? [];
        if ($type === 'events') {
            $resolved = membershipBenefitsPreviewEventRules($newRules, $item['membership_prices'], $item['price']);
            $item['membership_prices'] = $resolved['prices'];
            $item['assigned_benefits'] = $resolved['rules'];
        } else {
            foreach ($newRules as $rule) {
                if (!$rule['is_active']) continue;
                unset($rule['is_active']);
                $item['assigned_benefits'][] = $rule;
            }
        }
        $products[] = $item;
    }
    return ['success'=>true, 'company'=>$preview['company'], 'plans'=>$plans, 'products'=>$products, 'warnings'=>array_values(array_unique($warnings))];
}
