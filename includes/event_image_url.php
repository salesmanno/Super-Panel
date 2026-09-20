<?php

/**
 * Return a normalized HTTP(S) image URL, null for an empty value, or throw for
 * unsupported/invalid input. Event images are remote URLs entered by admins.
 */
function normalizeEventImageUrl($value): ?string {
    $url = trim((string) ($value ?? ''));
    if ($url === '') {
        return null;
    }

    if (filter_var($url, FILTER_VALIDATE_URL) === false) {
        throw new InvalidArgumentException('Bildeadressen er ugyldig');
    }

    $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        throw new InvalidArgumentException('Bildeadressen må bruke http eller https');
    }

    return $url;
}
