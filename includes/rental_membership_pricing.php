<?php
/**
 * Server-side rental membership pricing. The caller supplies a company-scoped,
 * trusted customer id, never a plan or discount supplied by a public browser.
 * This helper neither writes bookings nor verifies a public visitor's identity.
 */
require_once __DIR__ . '/rental_schedule_editing.php';

function rentalEligibleMembershipBenefits(PDO $db, string $company, string $object, ?string $customer, ?string $today = null): array {
    if (!$customer) return [];
    $today ??= (new DateTimeImmutable('now', new DateTimeZone('Europe/Oslo')))->format('Y-m-d');
    // Binary id comparisons also work when the shared database has mixed collations.
    $eq = static fn(string $a, string $b): string => $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql'
        ? "BINARY $a = BINARY $b" : "$a = $b";
    $stmt = $db->prepare("
        SELECT DISTINCT m.membership_plan_id AS plan_id, mp.name AS plan_name,
               m.benefit_type, m.benefit_value, m.price_basis
        FROM membership_product_modifications m
        JOIN output_products op ON ".$eq('op.id','m.output_product_id')."
             AND ".$eq('op.company_id','m.company_id')."
        JOIN membership_plans mp ON ".$eq('mp.id','m.membership_plan_id')."
             AND ".$eq('mp.company_id','m.company_id')."
        JOIN membership_members mm ON ".$eq('mm.membership_plan_id','mp.id')."
             AND ".$eq('mm.company_id','m.company_id')."
        WHERE m.company_id = ? AND op.product_type = 'rental' AND op.product_id = ?
          AND m.is_active = 1 AND mp.is_active = 1
          AND mm.customer_id = ? AND mm.status = 'active'
          AND mm.payment_status IN ('paid','completed')
          AND (mm.join_date IS NULL OR mm.join_date <= ?)
          AND (mm.expiration_date IS NULL OR mm.expiration_date >= ?)
        ORDER BY m.membership_plan_id
    ");
    $stmt->execute([$company, $object, $customer, $today, $today]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function rentalApplyMembershipBenefit(int $baseCents, array $rule, int $days): int {
    if ($baseCents < 0 || $days < 1) throw new InvalidArgumentException('Ugyldig prisgrunnlag.');
    $type = $rule['benefit_type'] ?? '';
    $basis = $rule['price_basis'] ?? 'unit';
    if (!in_array($basis, ['unit','booking_total','rental_day'], true)
        || ($basis === 'rental_day' && $type !== 'amount_discount')) {
        throw new InvalidArgumentException('Ugyldig prisgrunnlag for medlemsfordelen.');
    }
    if ($type === 'included') return 0;
    if ($type === 'access') return $baseCents;
    $value = str_replace(',', '.', trim((string)($rule['benefit_value'] ?? '')));
    if (!preg_match('/^\d+(?:\.\d{1,2})?$/D', $value) || (float)$value > 1000000000) {
        throw new InvalidArgumentException('Medlemsfordelen har et ugyldig beløp.');
    }
    $cents = rentalMoneyToCents($value);
    return match ($type) {
        'amount_discount' => max(0, $baseCents - $cents * ($basis === 'rental_day' ? $days : 1)),
        'percent_discount' => $cents <= 10000
            ? intdiv($baseCents * (10000 - $cents) + 5000, 10000)
            : throw new InvalidArgumentException('Medlemsrabatten kan ikke overstige 100 prosent.'),
        'fixed_price' => min($baseCents, $cents),
        default => throw new InvalidArgumentException('Ukjent medlemsfordel.'),
    };
}

function rentalMembershipPrice(int $baseCents, string $start, string $end, array $rules): array {
    $from = DateTimeImmutable::createFromFormat('!Y-m-d', $start, new DateTimeZone('Europe/Oslo'));
    $to = DateTimeImmutable::createFromFormat('!Y-m-d', $end, new DateTimeZone('Europe/Oslo'));
    if (!$from || !$to || $from->format('Y-m-d') !== $start || $to->format('Y-m-d') !== $end || $to < $from) {
        throw new InvalidArgumentException('Ugyldig leieperiode.');
    }
    $days = (int)$from->diff($to)->days + 1;
    $price = $baseCents;
    $selected = null;
    // Best eligible plan, never stacked; a fixed "benefit" cannot increase a price.
    foreach ($rules as $rule) {
        $candidate = rentalApplyMembershipBenefit($baseCents, $rule, $days);
        if ($candidate < $price) {
            $price = $candidate;
            $selected = $rule;
        }
    }
    return ['ordinary_base_cents'=>$baseCents, 'member_base_cents'=>$price,
        'discount_cents'=>$baseCents - $price, 'rental_days'=>$days, 'benefit'=>$selected];
}
