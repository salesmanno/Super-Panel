<?php
require_once __DIR__ . '/../includes/announcement_helpers.php';
require_once __DIR__ . '/../includes/announcement_elements.php';
require_once __DIR__ . '/../includes/announcement_editor.php';

function editor_check(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function editor_rejects(callable $operation, string $message): void {
    try { $operation(); } catch (InvalidArgumentException $e) { return; }
    throw new RuntimeException($message);
}

$canonical = [
    ['element_type'=>'image_carousel','content'=>['images'=>[
        ['url'=>'https://images.example.test/a.jpg','alt'=>'Første bilde'],
        ['url'=>'https://images.example.test/b.jpg','alt'=>'Andre bilde'],
    ]]],
    ['element_type'=>'youtube_video','content'=>['video_id'=>'abcdefghijk','caption'=>'Møtet']],
];
$first = announcement_validate_elements($canonical);
$loaded = array_map(static fn($row) => [
    'element_type'=>$row['element_type'],
    'content'=>announcement_element_editor_content($row['element_type'], json_decode($row['content_json'], true)),
], $first);
$second = announcement_validate_elements($loaded);
editor_check($first === $second, 'Canonical media must survive load and save without data loss');
editor_check($loaded[0]['content']['urls'][1] === 'https://images.example.test/b.jpg', 'Carousel URL order survives');
editor_check($loaded[1]['content']['url'] === 'https://www.youtube.com/watch?v=abcdefghijk', 'Stored video ID reopens as an editable URL');
editor_rejects(static fn() => announcement_validate_elements([
    ['element_type'=>'youtube_video','content'=>['url'=>'https://evil.example/watch?v=abcdefghijk','video_id'=>'abcdefghijk']],
]), 'A valid ID must not disguise an untrusted video URL');

$payload = ['title'=>'Ny sak','content'=>'Innhold','priority'=>5,'is_published'=>true,'scheduled_publish'=>false,'publish_at'=>'ignored-client-value'];
$result = announcement_validate_payload($payload);
editor_check($result['ok'] && $result['data']['publish_at'] === null, 'Publish now must ignore client timestamps');
editor_check($result['data']['announcement_type'] === 'news', 'New articles default to news without a type selection');
$clean = $result['data'];
$now = '2026-11-09 18:40:01';
editor_check(announcement_editor_publish_at($clean, null, $now) === $now, 'First publication uses database time');
editor_check(announcement_editor_publish_at($clean, ['is_published'=>true,'publish_at'=>'2026-10-02 09:30:00'], $now) === '2026-10-02 09:30:00', 'Published story edits preserve publication timestamp');
$draft = array_replace($clean, ['is_published'=>false]);
editor_check(announcement_editor_publish_at($draft, null, $now) === null, 'A new unscheduled draft has no publication timestamp');
editor_check(announcement_editor_publish_at($clean, ['is_published'=>false,'publish_at'=>null], $now) === $now, 'Publishing a saved draft uses current database time');
$planned = array_replace($clean, ['scheduled_publish'=>true,'publish_at'=>'2026-12-01 19:00:00']);
editor_check(announcement_editor_publish_at($planned, null, $now) === $planned['publish_at'], 'Scheduled publication retains selected time');
editor_check(announcement_editor_publish_at($clean, ['is_published'=>true,'publish_at'=>$planned['publish_at']], $now) === $now, 'Turning off future scheduling publishes now');
editor_rejects(static fn() => announcement_editor_publish_at(array_replace($planned, ['publish_at'=>'2026-11-08 12:00:00']), null, $now), 'New scheduling in the past is rejected');
editor_rejects(static fn() => announcement_editor_publish_at(array_replace($planned, ['expire_at'=>'2026-11-30 12:00:00']), null, $now), 'Expiry before planned publication is rejected');
editor_rejects(static fn() => announcement_editor_publish_at(array_replace($clean, ['expire_at'=>'2026-11-08 12:00:00']), null, $now), 'Expiry before publish now is rejected');
editor_check(!announcement_validate_payload(array_replace($payload, ['scheduled_publish'=>'false']))['ok'], 'Schedule toggle must be a boolean');
editor_check(!announcement_validate_payload(array_replace($payload, ['scheduled_publish'=>true,'publish_at'=>null]))['ok'], 'Scheduling requires date and time');
$legacy = announcement_validate_payload(array_diff_key($payload, ['scheduled_publish'=>true,'publish_at'=>true]))['data'];
editor_check(announcement_editor_publish_at($legacy, null, $now) === null, 'Legacy callers retain their existing nullable scheduling contract');

$rich = '<h3>Overskrift</h3><p><strong>Viktig</strong> tekst</p><ul><li>Et punkt</li></ul><p>Avslutning</p>';
$text = announcement_validate_elements([['element_type'=>'text','content'=>['html'=>$rich]]]);
editor_check(json_decode($text[0]['content_json'], true)['html'] === $rich, 'Mixed rich text remains intact after server validation');
$expiryPayload = announcement_validate_payload([
    'title' => 'Utløpsdato',
    'content' => 'En sak med utløpsdato.',
    'expire_at' => '2039-12-31',
    'scheduled_publish' => false,
]);
editor_check($expiryPayload['ok'] && $expiryPayload['data']['expire_at'] === '2039-12-31 08:00:00',
    'Date-only expiry defaults to 08:00 on the server');
$legacyExpiry = announcement_validate_payload([
    'title' => 'Eksisterende utløp',
    'content' => 'Behold det opprinnelige tidspunktet.',
    'expire_at' => '2039-12-31 17:45:00',
]);
editor_check($legacyExpiry['ok'] && $legacyExpiry['data']['expire_at'] === '2039-12-31 17:45:00',
    'Existing explicit expiry times are not overwritten');
$invalidExpiry = announcement_validate_payload([
    'title' => 'Ugyldig dato',
    'content' => 'Dato må valideres.',
    'expire_at' => '2039-02-31',
]);
editor_check(!$invalidExpiry['ok'], 'Date-only expiry rejects nonexistent dates');
echo "News editor contracts passed: media round-trip, scheduling, expiry defaults, safe rich text\n";
