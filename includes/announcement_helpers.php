<?php
/**
 * Shared tenant-news rules. Keep this deliberately framework-free so API and tests
 * can use exactly the same validation and ranking rules.
 */
const ANNOUNCEMENT_TYPES = ['event', 'info', 'news', 'promotion', 'urgent'];
const ANNOUNCEMENT_PRIORITIES = ['urgent', 'high', 'normal', 'low'];
const ANNOUNCEMENT_PRIORITY_RANK = ['urgent' => 0, 'high' => 1, 'normal' => 2, 'low' => 3];
const ANNOUNCEMENT_CONTENT_MAX = 100000;

function announcement_type($value): string {
    return in_array($value, ANNOUNCEMENT_TYPES, true) ? $value : 'info';
}
function announcement_priority($value): string {
    return in_array($value, ANNOUNCEMENT_PRIORITIES, true) ? $value : 'normal';
}
function announcement_parse_datetime($value, string $field, array &$errors): ?string {
    if ($value === null || $value === '') return null;
    if (!is_string($value)) { $errors[] = "{$field} må være en gyldig dato"; return null; }
    $formats = [['!Y-m-d\TH:i', 'Y-m-d\TH:i'], ['!Y-m-d\TH:i:s', 'Y-m-d\TH:i:s'], ['!Y-m-d H:i:s', 'Y-m-d H:i:s']];
    foreach ($formats as [$parser, $roundTrip]) {
        $date = DateTimeImmutable::createFromFormat($parser, $value);
        $parseErrors = DateTimeImmutable::getLastErrors();
        $hasErrors = is_array($parseErrors) && ($parseErrors['warning_count'] > 0 || $parseErrors['error_count'] > 0);
        if ($date && !$hasErrors && $date->format($roundTrip) === $value) return $date->format('Y-m-d H:i:s');
    }
    $errors[] = "{$field} har ugyldig format";
    return null;
}
function announcement_validate_payload($data): array {
    if (!is_array($data)) return ['ok'=>false, 'message'=>'Ugyldig JSON-data'];
    $errors = [];
    foreach (['title'=>'Tittel', 'content'=>'Innhold'] as $key=>$label) {
        if (!isset($data[$key]) || !is_string($data[$key]) || trim($data[$key]) === '') $errors[] = "{$label} er påkrevd";
    }
    $title = is_string($data['title'] ?? null) ? trim($data['title']) : '';
    $content = is_string($data['content'] ?? null) ? preg_replace("/\r\n?/", "\n", trim($data['content'])) : '';
    if (mb_strlen($title) > 255) $errors[] = 'Tittel kan ikke være lengre enn 255 tegn';
    if (mb_strlen($content) > ANNOUNCEMENT_CONTENT_MAX) $errors[] = 'Innholdet er for langt';
    foreach (['is_published'=>'publisering', 'is_pinned'=>'festing'] as $key=>$label) {
        if (array_key_exists($key, $data) && !is_bool($data[$key]) && !($data[$key] === 0 || $data[$key] === 1)) $errors[] = "{$label} må være true eller false";
    }
    $image = $data['image_url'] ?? null;
    if ($image !== null && $image !== '') {
        if (!is_string($image) || !filter_var($image, FILTER_VALIDATE_URL) || !in_array(strtolower(parse_url($image, PHP_URL_SCHEME)), ['http','https'], true)) $errors[] = 'Bilde-URL må være en http- eller https-adresse';
        elseif (strlen($image) > 255) $errors[] = 'Bilde-URL kan ikke være lengre enn 255 tegn';
    } else $image = null;
    $publish = announcement_parse_datetime($data['publish_at'] ?? null, 'Publiseringstidspunkt', $errors);
    $expire = announcement_parse_datetime($data['expire_at'] ?? null, 'Utløpstidspunkt', $errors);
    if ($publish && $expire && $expire <= $publish) $errors[] = 'Utløpstidspunkt må være etter publiseringstidspunktet';
    $product = $data['product'] ?? null;
    if ($product !== null) {
        if (!is_array($product) || !isset($product['output_product_id']) || !is_scalar($product['output_product_id']) || trim((string)$product['output_product_id']) === '') $errors[] = 'Produktkoblingen er ugyldig';
        else {
            $discount = $product['discount_percent'] ?? 0; $max = $product['max_quantity'] ?? null; $expiry = $product['expiry_date'] ?? null;
            if (filter_var($discount, FILTER_VALIDATE_INT) === false || (int)$discount < 0 || (int)$discount > 100) $errors[] = 'Rabatt må være et heltall mellom 0 og 100';
            if ($max !== null && ($max === '' || filter_var($max, FILTER_VALIDATE_INT) === false || (int)$max < 1 || (int)$max > 1000000)) $errors[] = 'Maks antall må være et positivt heltall';
            if ($expiry !== null && $expiry !== '') { $parsed = DateTime::createFromFormat('!Y-m-d', (string)$expiry); if (!$parsed || $parsed->format('Y-m-d') !== $expiry) $errors[] = 'Produktets utløpsdato er ugyldig'; }
            $product = ['output_product_id'=>(string)$product['output_product_id'], 'discount_percent'=>(int)$discount, 'max_quantity'=>$max === null || $max === '' ? null : (int)$max, 'expiry_date'=>$expiry ?: null];
        }
    }
    if ($errors) return ['ok'=>false, 'message'=>implode('. ', $errors)];
    return ['ok'=>true, 'data'=>['title'=>$title,'content'=>$content,'image_url'=>$image,'is_published'=>(bool)($data['is_published'] ?? false),'is_pinned'=>(bool)($data['is_pinned'] ?? false),'announcement_type'=>announcement_type($data['announcement_type'] ?? null),'priority'=>announcement_priority($data['priority'] ?? null),'publish_at'=>$publish,'expire_at'=>$expire,'product'=>$product]];
}
function announcement_is_active(array $a, ?DateTimeImmutable $now = null): bool {
    $now = $now ?: new DateTimeImmutable('now');
    if (!(bool)($a['is_published'] ?? false)) return false;
    if (!empty($a['publish_at']) && new DateTimeImmutable($a['publish_at']) > $now) return false;
    return empty($a['expire_at']) || new DateTimeImmutable($a['expire_at']) > $now;
}
function announcement_rank(array $a): array {
    $date = !empty($a['publish_at']) ? strtotime($a['publish_at']) : 0;
    return [(int)!empty($a['is_pinned']) ? 0 : 1, ANNOUNCEMENT_PRIORITY_RANK[announcement_priority($a['priority'] ?? null)], -$date, (string)($a['id'] ?? '')];
}
function announcement_sort(array &$items): void {
    usort($items, static function ($a, $b) {
        $ra = announcement_rank($a); $rb = announcement_rank($b);
        for ($i=0; $i<3; $i++) if ($ra[$i] !== $rb[$i]) return $ra[$i] <=> $rb[$i];
        if ($ra[3] === $rb[3]) return 0;
        return strcmp($ra[3], $rb[3]);
        return 0;
    });
}
function announcement_excerpt(string $text, int $length = 150): string {
    $text = preg_replace("/\r\n?/", "\n", trim($text));
    if (mb_strlen($text) <= $length) return $text;
    $cut = mb_substr($text, 0, $length);
    $cut = preg_replace('/\s+\S*$/u', '', $cut);
    return rtrim($cut) . '…';
}