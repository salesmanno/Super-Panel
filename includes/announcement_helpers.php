<?php
/**
 * Shared tenant-news rules. Keep this deliberately framework-free so API and tests
 * can use exactly the same validation and ranking rules.
 */
const ANNOUNCEMENT_TYPES = ['event', 'info', 'news', 'promotion', 'urgent'];
const ANNOUNCEMENT_PRIORITY_MIN = 1;
const ANNOUNCEMENT_PRIORITY_MAX = 10;
const ANNOUNCEMENT_PRIORITY_DEFAULT = 5;
const ANNOUNCEMENT_LEGACY_PRIORITIES = ['urgent' => 1, 'high' => 3, 'normal' => 5, 'low' => 8];
const ANNOUNCEMENT_CONTENT_MAX = 100000;
const ANNOUNCEMENT_ALLOWED_CONTENT_TAGS = [
    'div', 'p', 'br', 'h2', 'h3', 'h4', 'h5', 'ul', 'ol', 'li',
    'strong', 'em', 'u', 's', 'blockquote', 'pre', 'code', 'a'
];

function announcement_type($value): string {
    return in_array($value, ANNOUNCEMENT_TYPES, true) ? $value : 'info';
}
/** Normalize values read during the legacy text-to-number transition. */
function announcement_priority($value): int {
    if (is_int($value) && $value >= ANNOUNCEMENT_PRIORITY_MIN && $value <= ANNOUNCEMENT_PRIORITY_MAX) return $value;
    if (is_string($value) && preg_match('/^(?:[1-9]|10)$/', trim($value))) return (int)$value;
    return ANNOUNCEMENT_LEGACY_PRIORITIES[(string)$value] ?? ANNOUNCEMENT_PRIORITY_DEFAULT;
}
function announcement_priority_for_write($value): ?int {
    if (is_int($value) && $value >= ANNOUNCEMENT_PRIORITY_MIN && $value <= ANNOUNCEMENT_PRIORITY_MAX) return $value;
    return null;
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

/**
 * Sanitize editor HTML while keeping legacy plain text untouched.
 *
 * The content column intentionally remains the single storage field. Rich
 * content is stored as a small allowlisted HTML fragment; legacy text remains
 * text and gets projected to HTML only when it is rendered.
 */
function announcement_content_is_html(string $content): bool {
    return preg_match('/<\s*\/?\s*[a-z][^>]*>/i', $content) === 1;
}

function announcement_sanitize_href(string $href): ?string {
    $href = trim(html_entity_decode($href, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    if ($href === '') return null;
    if (str_starts_with($href, '//')) return null;
    $scheme = strtolower((string) parse_url($href, PHP_URL_SCHEME));
    if ($scheme !== '' && !in_array($scheme, ['http', 'https', 'mailto'], true)) return null;
    if (preg_match('/[\x00-\x1f\x7f]/', $href)) return null;
    return $href;
}

function announcement_sanitize_content(string $content): string {
    $content = preg_replace("/\r\n?/", "\n", $content) ?? $content;
    if (!announcement_content_is_html($content)) return $content;

    $document = new DOMDocument('1.0', 'UTF-8');
    $previous = libxml_use_internal_errors(true);
    $loaded = $document->loadHTML(
        '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"></head><body><div id="announcement-content-root">'
        . $content
        . '</div></body></html>',
        LIBXML_HTML_NOIMPLIED | LIBXML_NONET
    );
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded) return htmlspecialchars($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

    $root = $document->getElementById('announcement-content-root');
    if (!$root) return '';

    $sanitizeNode = static function (DOMNode $node) use (&$sanitizeNode): void {
        if ($node->nodeType === XML_COMMENT_NODE) {
            $node->parentNode?->removeChild($node);
            return;
        }
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            foreach (iterator_to_array($node->childNodes ?? []) as $child) {
                $sanitizeNode($child);
            }
            return;
        }

        $tag = strtolower($node->nodeName);
        if (!in_array($tag, ANNOUNCEMENT_ALLOWED_CONTENT_TAGS, true)) {
            if (in_array($tag, ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'noscript'], true)) {
                $node->parentNode?->removeChild($node);
                return;
            }
            foreach (iterator_to_array($node->childNodes) as $child) $sanitizeNode($child);
            $parent = $node->parentNode;
            if ($parent) {
                while ($node->firstChild) $parent->insertBefore($node->firstChild, $node);
                $parent->removeChild($node);
            }
            return;
        }

        if ($node->hasAttributes()) {
            foreach (iterator_to_array($node->attributes) as $attribute) {
                $name = strtolower($attribute->name);
                if ($tag === 'a' && $name === 'href') {
                    $href = announcement_sanitize_href($attribute->value);
                    if ($href === null) $node->removeAttributeNode($attribute);
                    else $node->setAttribute('href', $href);
                } else {
                    $node->removeAttributeNode($attribute);
                }
            }
        }
        if ($tag === 'a' && $node->hasAttribute('href')) {
            $node->setAttribute('target', '_blank');
            $node->setAttribute('rel', 'noopener noreferrer');
        }
        foreach (iterator_to_array($node->childNodes) as $child) $sanitizeNode($child);
    };

    foreach (iterator_to_array($root->childNodes) as $child) $sanitizeNode($child);
    $html = '';
    foreach ($root->childNodes as $child) $html .= $document->saveHTML($child);
    return trim($html);
}

function announcement_content_to_html(string $content): string {
    if (announcement_content_is_html($content)) return announcement_sanitize_content($content);
    $normalized = preg_replace("/\r\n?/", "\n", $content) ?? $content;
    return str_replace("\n", '<br />', htmlspecialchars($normalized, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}

function announcement_content_to_plain_text(string $content): string {
    if (!announcement_content_is_html($content)) {
        return preg_replace("/\r\n?/", "\n", $content) ?? $content;
    }
    $html = announcement_content_to_html($content);
    $html = preg_replace('/<(?:div|p|h[2-5]|blockquote|pre)>\s*<br\s*\/?>\s*<\/(?:div|p|h[2-5]|blockquote|pre)>/i', "\n", $html) ?? $html;
    $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
    $html = preg_replace('/<\/(?:div|p|h[2-5]|li|blockquote|pre)>/i', "\n", $html) ?? $html;
    $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $text;
}

function announcement_validate_payload($data): array {
    if (!is_array($data)) return ['ok'=>false, 'message'=>'Ugyldig JSON-data'];
    $errors = [];
    foreach (['title'=>'Tittel', 'content'=>'Innhold'] as $key=>$label) {
        if (!isset($data[$key]) || !is_string($data[$key]) || trim($data[$key]) === '') $errors[] = "{$label} er påkrevd";
    }
    $title = is_string($data['title'] ?? null) ? trim($data['title']) : '';
    $content = is_string($data['content'] ?? null) ? announcement_sanitize_content($data['content']) : '';
    $content = trim($content);
    if (trim(announcement_content_to_plain_text($content)) === '') {
        $errors[] = 'Innhold kan ikke være tomt';
    }
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
    $priority = array_key_exists('priority', $data) ? announcement_priority_for_write($data['priority']) : ANNOUNCEMENT_PRIORITY_DEFAULT;
    if ($priority === null) $errors[] = 'Prioritet må være et heltall mellom 1 og 10';
    if ($errors) return ['ok'=>false, 'message'=>implode('. ', $errors)];
    return ['ok'=>true, 'data'=>['title'=>$title,'content'=>$content,'image_url'=>$image,'is_published'=>(bool)($data['is_published'] ?? false),'is_pinned'=>(bool)($data['is_pinned'] ?? false),'announcement_type'=>announcement_type($data['announcement_type'] ?? null),'priority'=>$priority,'publish_at'=>$publish,'expire_at'=>$expire,'product'=>$product]];
}
function announcement_is_active(array $a, ?DateTimeImmutable $now = null): bool {
    $now = $now ?: new DateTimeImmutable('now');
    if (!(bool)($a['is_published'] ?? false)) return false;
    if (!empty($a['publish_at']) && new DateTimeImmutable($a['publish_at']) > $now) return false;
    return empty($a['expire_at']) || new DateTimeImmutable($a['expire_at']) > $now;
}
function announcement_rank(array $a): array {
    $date = !empty($a['publish_at']) ? strtotime($a['publish_at']) : 0;
    return [(int)!empty($a['is_pinned']) ? 0 : 1, announcement_priority($a['priority'] ?? null), -$date, (string)($a['id'] ?? '')];
}
function announcement_sort(array &$items): void {
    usort($items, static function ($a, $b) {
        $ra = announcement_rank($a); $rb = announcement_rank($b);
        for ($i=0; $i<3; $i++) if ($ra[$i] !== $rb[$i]) return $ra[$i] <=> $rb[$i];
        if ($ra[3] === $rb[3]) return 0;
        return strcmp($ra[3], $rb[3]);
    });
}
function announcement_excerpt(string $text, int $length = 150): string {
    $text = announcement_content_to_plain_text($text);
    $text = preg_replace("/\r\n?/", "\n", trim($text));
    if (mb_strlen($text) <= $length) return $text;
    $cut = mb_substr($text, 0, $length);
    $cut = preg_replace('/\s+\S*$/u', '', $cut);
    return rtrim($cut) . '…';
}