<?php

require_once __DIR__ . '/rental_schedule_editing.php';
require_once __DIR__ . '/email_template_tags.php';

class RentalConfirmationEmailException extends RuntimeException {}

function formatRentalConfirmationDate(?string $date): string {
    if (!$date) {
        return '';
    }

    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10));
    return $parsed ? $parsed->format('d.m.Y') : $date;
}

function formatRentalConfirmationTime(?string $time): string {
    if (!$time) {
        return '';
    }

    return substr($time, 0, 5);
}

function rentalConfirmationDays(?string $startDate, ?string $endDate): string {
    if (!$startDate || !$endDate) {
        return '';
    }

    $start = DateTimeImmutable::createFromFormat('!Y-m-d', substr($startDate, 0, 10));
    $end = DateTimeImmutable::createFromFormat('!Y-m-d', substr($endDate, 0, 10));
    if (!$start || !$end || $end < $start) {
        return '';
    }

    return (string) ($start->diff($end)->days + 1);
}

function buildRentalConfirmationVariables(array $rental): array {
    $customerName = trim((string) ($rental['customer_name'] ?? ''));
    if ($customerName !== '') {
        $nameParts = preg_split('/\s+/', $customerName, 2) ?: [];
        $firstName = $nameParts[0] ?? '';
        $lastName = $nameParts[1] ?? '';
    } else {
        $firstName = trim((string) ($rental['first_name'] ?? ''));
        $lastName = trim((string) ($rental['last_name'] ?? ''));
        $customerName = trim($firstName . ' ' . $lastName);
    }
    $dateFrom = formatRentalConfirmationDate($rental['start_date'] ?? null);
    $dateTo = formatRentalConfirmationDate($rental['end_date'] ?? null);
    $cleaning = (int) ($rental['cleaning'] ?? 0) === 1 ? 1 : 0;
    $cleaningFee = (float) ($rental['cleaning_fee'] ?? 0);
    $totalRentalExpenses = array_key_exists('total_rental_expenses', $rental)
        ? max(0, (float) $rental['total_rental_expenses'])
        : rentalBaseExpenses($rental['rental_fee'] ?? 0, $cleaning, $cleaningFee);
    $totalAmount = rentalTotalPrice($totalRentalExpenses, $cleaning, $cleaningFee);
    $price = number_format($totalRentalExpenses, 2, ',', ' ');
    $totalPrice = number_format($totalAmount, 2, ',', ' ');
    $adjustmentType = (string) ($rental['price_adjustment_type'] ?? '');
    $adjustmentCents = max(0, (int) ($rental['price_adjustment_cents'] ?? 0));
    $signedAdjustmentCents = match ($adjustmentType) {
        'discount' => -$adjustmentCents,
        'surcharge' => $adjustmentCents,
        default => 0,
    };
    $basePriceCents = max(
        0,
        rentalMoneyToCents($totalRentalExpenses) - $signedAdjustmentCents
    );
    $hasPriceAdjustment = $signedAdjustmentCents !== 0;
    $adjustmentLabel = match ($adjustmentType) {
        'discount' => 'Rabatt',
        'surcharge' => 'Tillegg',
        default => '',
    };
    $companyName = (string) ($rental['company_name'] ?? '');
    $objectName = (string) ($rental['object_name'] ?? '');
    $status = (string) ($rental['status'] ?? '') === 'rejected' ? 'Avslått' : 'Godkjent';
    $companyAddress = trim(implode(', ', array_filter([
        trim((string) ($rental['address_street'] ?? '')),
        trim(
            (string) ($rental['address_zip'] ?? '')
            . ' '
            . (string) ($rental['address_city'] ?? '')
        ),
    ], static fn(string $part): bool => $part !== '')));

    return [
        'customer_name' => $customerName,
        'cus_first_name' => $firstName,
        'cus_last_name' => $lastName,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'object_name' => $objectName,
        'rental_object' => $objectName,
        'rental_text' => (string) ($rental['rental_text'] ?? ''),
        'key_box_code' => (string) ($rental['key_box_code'] ?? ''),
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'start_date' => $dateFrom,
        'end_date' => $dateTo,
        'rental_start' => $dateFrom,
        'rental_end' => $dateTo,
        'rental_days' => rentalConfirmationDays(
            $rental['start_date'] ?? null,
            $rental['end_date'] ?? null
        ),
        'price' => $price,
        'total_rental_expenses' => $price,
        'base_price' => number_format($basePriceCents / 100, 2, ',', ' '),
        'price_adjustment' => $hasPriceAdjustment
            ? number_format($signedAdjustmentCents / 100, 2, ',', ' ')
            : '',
        'price_adjustment_type_label' => $adjustmentLabel,
        'price_adjustment_explanation' => $hasPriceAdjustment
            ? (string) ($rental['price_adjustment_explanation'] ?? '')
            : '',
        'total_price' => $totalPrice,
        'rental_fee' => $totalPrice,
        'status' => $status,
        'order_id' => (string) ($rental['id'] ?? ''),
        'payment_method' => (string) ($rental['payment_method'] ?? ''),
        'pickup_time' => '08:00',
        'end_time' => formatRentalConfirmationTime($rental['end_time'] ?? null),
        'pickup_address' => (string) (
            $rental['delivery_address']
            ?? $rental['pickup_address']
            ?? $companyAddress
        ),
        'contact_person' => (string) ($rental['contact_person'] ?? $rental['company_name'] ?? ''),
        'cleaning' => $cleaning,
        'cleaning_label' => $cleaning === 1 ? 'Ja' : 'Nei',
        'cleaning_fee' => number_format($cleaningFee, 2, ',', ' '),
        'logistics' => (string) ($rental['logistics'] ?? ''),
        'notes' => (string) ($rental['notes'] ?? ''),
        'company_name' => $companyName,
        'company' => $companyName,
        'tenant_name' => $companyName,
        'logo_url' => (string) ($rental['logo_url'] ?? ''),
        'header_bg_color' => (string) ($rental['header_bg_color'] ?? '#ffffff'),
        'company_phone' => (string) ($rental['contact_phone'] ?? ''),
        'contact_phone' => (string) ($rental['contact_phone'] ?? ''),
        'company_address' => $companyAddress,
        'org_number' => (string) ($rental['org_number'] ?? ''),
        'account_number' => (string) ($rental['account_number'] ?? ''),
    ];
}

function rentalTemplateCondition(string $expression, array $variables): bool {
    $expression = trim($expression);
    if (preg_match('/^\((eq|neq)\s+([a-zA-Z_][a-zA-Z0-9_]*)\s+"([^"]*)"\)$/', $expression, $matches)) {
        $actual = (string) ($variables[$matches[2]] ?? '');
        $equal = $actual === $matches[3];
        return $matches[1] === 'eq' ? $equal : !$equal;
    }
    if (preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $expression)) {
        return !empty($variables[$expression]);
    }
    throw new RentalConfirmationEmailException('E-postmalen inneholder en ugyldig betingelse.');
}

function validateRentalConditionalBlocks(string $template, array $variables): void {
    preg_match_all(
        '/\{\{#if\s+(.+?)\}\}|\{\{else\}\}|\{\{\/if\}\}/',
        $template,
        $tokens,
        PREG_SET_ORDER
    );
    $stack = [];
    foreach ($tokens as $token) {
        if (str_starts_with($token[0], '{{#if')) {
            rentalTemplateCondition($token[1], $variables);
            $stack[] = false;
        } elseif ($token[0] === '{{else}}') {
            if ($stack === [] || $stack[array_key_last($stack)] === true) {
                throw new RentalConfirmationEmailException('E-postmalen inneholder en ugyldig else-blokk.');
            }
            $stack[array_key_last($stack)] = true;
        } else {
            if ($stack === []) {
                throw new RentalConfirmationEmailException('E-postmalen inneholder en ugyldig avslutning.');
            }
            array_pop($stack);
        }
    }
    if ($stack !== [] || preg_match('/\{\{#if|\{\{else\}\}|\{\{\/if\}\}/', preg_replace(
        '/\{\{#if\s+(.+?)\}\}|\{\{else\}\}|\{\{\/if\}\}/',
        '',
        $template
    ) ?? $template)) {
        throw new RentalConfirmationEmailException('E-postmalen inneholder ugyldige betingede blokker.');
    }
}

function renderRentalConditionalBlocks(string $template, array $variables): string {
    $openingPattern = '/\{\{#if\s+(.+?)\}\}/';
    while (preg_match($openingPattern, $template, $opening, PREG_OFFSET_CAPTURE)) {
        $blockStart = $opening[0][1];
        $contentStart = $blockStart + strlen($opening[0][0]);
        $scanOffset = $contentStart;
        $depth = 1;
        $elseStart = null;
        $elseEnd = null;
        $blockEndStart = null;
        $blockEnd = null;

        while (preg_match('/\{\{#if\s+.+?\}\}|\{\{else\}\}|\{\{\/if\}\}/', $template, $token, PREG_OFFSET_CAPTURE, $scanOffset)) {
            $tokenText = $token[0][0];
            $tokenStart = $token[0][1];
            $tokenEnd = $tokenStart + strlen($tokenText);
            if (str_starts_with($tokenText, '{{#if')) {
                $depth++;
            } elseif ($tokenText === '{{/if}}') {
                $depth--;
                if ($depth === 0) {
                    $blockEndStart = $tokenStart;
                    $blockEnd = $tokenEnd;
                    break;
                }
            } elseif ($tokenText === '{{else}}' && $depth === 1 && $elseStart === null) {
                $elseStart = $tokenStart;
                $elseEnd = $tokenEnd;
            }
            $scanOffset = $tokenEnd;
        }

        if ($blockEndStart === null || $blockEnd === null) {
            throw new RentalConfirmationEmailException('E-postmalen inneholder en uavsluttet betinget blokk.');
        }

        $trueEnd = $elseStart ?? $blockEndStart;
        $trueBranch = substr($template, $contentStart, $trueEnd - $contentStart);
        $falseBranch = $elseEnd === null
            ? ''
            : substr($template, $elseEnd, $blockEndStart - $elseEnd);
        $selected = rentalTemplateCondition($opening[1][0], $variables)
            ? $trueBranch
            : $falseBranch;
        $selected = renderRentalConditionalBlocks($selected, $variables);
        $template = substr_replace($template, $selected, $blockStart, $blockEnd - $blockStart);
    }

    if (str_contains($template, '{{else}}') || str_contains($template, '{{/if}}')) {
        throw new RentalConfirmationEmailException('E-postmalen inneholder ugyldige betingede blokker.');
    }
    return $template;
}

function renderRentalConfirmationTemplate(string $template, array $variables): string {
    validateRentalConditionalBlocks($template, $variables);
    $template = renderRentalConditionalBlocks($template, $variables);
    if (array_key_exists('cleaning', $variables)) {
        $variables['cleaning'] = (string) (
            $variables['cleaning_label']
            ?? ((int) $variables['cleaning'] === 1 ? 'Ja' : 'Nei')
        );
    }

    return replaceKnownEmailTemplateTags($template, $variables);
}

function renderRentalConfirmationBody(string $template, array $variables): string {
    // Inspect the authoritative template, never customer data or an inactive-only output.
    if (preg_match('/<\s+\/?\s*table\b[^>]*\b(?:role|width|cellpadding|cellspacing)\s*=/i', $template)
        || preg_match('/&(?:amp;)*lt;\s*\/?\s*table\b[^<>]*\b(?:role|width|cellpadding|cellspacing)\s*=/i', $template)) {
        throw new RentalConfirmationEmailException(
            'E-postmalen inneholder tabellkode som vil vises som tekst. '
            . 'Kontroller tabelltaggene i utleiebekreftelsen før du prøver igjen. '
            . 'En HTML-tabell skal begynne med <table uten mellomrom etter <.'
        );
    }
    validateRentalConditionalBlocks($template, $variables);
    $template = renderRentalConditionalBlocks($template, $variables);
    if (array_key_exists('cleaning', $variables)) {
        $variables['cleaning'] = $variables['cleaning_label']
            ?? ((int) $variables['cleaning'] === 1 ? 'Ja' : 'Nei');
    }
    // Tags are text/attribute values. Stored template HTML itself is left untouched.
    $safeVariables = array_map(
        static fn($value): string => htmlspecialchars(
            (string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'
        ),
        $variables
    );
    return replaceKnownEmailTemplateTags($template, $safeVariables);
}