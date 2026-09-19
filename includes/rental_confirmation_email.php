<?php

require_once __DIR__ . '/rental_schedule_editing.php';

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
    $companyName = (string) ($rental['company_name'] ?? '');
    $objectName = (string) ($rental['object_name'] ?? '');
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
        'total_price' => $totalPrice,
        'rental_fee' => $totalPrice,
        'order_id' => (string) ($rental['id'] ?? ''),
        'payment_method' => (string) ($rental['payment_method'] ?? ''),
        'pickup_time' => formatRentalConfirmationTime($rental['pickup_time'] ?? null),
        'end_time' => formatRentalConfirmationTime($rental['end_time'] ?? null),
        'cleaning' => $cleaning === 1 ? 'Ja' : 'Nei',
        'cleaning_fee' => number_format($cleaningFee, 2, ',', ' '),
        'logistics' => (string) ($rental['logistics'] ?? ''),
        'notes' => (string) ($rental['notes'] ?? ''),
        'company_name' => $companyName,
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

function renderRentalConfirmationTemplate(string $template, array $variables): string {
    foreach ($variables as $key => $value) {
        $template = str_replace('{{' . $key . '}}', (string) $value, $template);
    }

    return preg_replace('/\{\{[^}]+\}\}/', '', $template) ?? $template;
}