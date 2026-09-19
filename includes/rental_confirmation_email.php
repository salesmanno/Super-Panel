<?php

class RentalConfirmationEmailException extends RuntimeException {}

function formatRentalConfirmationDate(?string $date): string {
    if (!$date) {
        return '';
    }

    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', substr($date, 0, 10));
    return $parsed ? $parsed->format('d.m.Y') : $date;
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
    $totalPrice = number_format((float) ($rental['rental_fee'] ?? 0), 2, ',', ' ');

    return [
        'customer_name' => $customerName,
        'cus_first_name' => $firstName,
        'cus_last_name' => $lastName,
        'object_name' => (string) ($rental['object_name'] ?? ''),
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'rental_start' => $dateFrom,
        'rental_end' => $dateTo,
        'total_price' => $totalPrice,
        'rental_fee' => $totalPrice,
        'payment_method' => (string) ($rental['payment_method'] ?? ''),
        'pickup_time' => (string) ($rental['pickup_time'] ?? ''),
        'end_time' => (string) ($rental['end_time'] ?? ''),
        'logistics' => (string) ($rental['logistics'] ?? ''),
        'notes' => (string) ($rental['notes'] ?? ''),
        'company_name' => (string) ($rental['company_name'] ?? ''),
    ];
}

function renderRentalConfirmationTemplate(string $template, array $variables): string {
    foreach ($variables as $key => $value) {
        $template = str_replace('{{' . $key . '}}', (string) $value, $template);
    }

    return preg_replace('/\{\{[^}]+\}\}/', '', $template) ?? $template;
}