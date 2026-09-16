<?php
require_once __DIR__ . '/../includes/announcement_helpers.php';

function check_rule(bool $value, string $message): void {
    if (!$value) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
}
$now = new DateTimeImmutable('2025-01-10 12:00:00');
$items = [
    ['id'=>'z','is_published'=>true,'is_pinned'=>false,'priority'=>'normal','publish_at'=>'2025-01-09 10:00:00'],
    ['id'=>'a','is_published'=>true,'is_pinned'=>true,'priority'=>'low','publish_at'=>'2025-01-01 10:00:00'],
    ['id'=>'b','is_published'=>true,'is_pinned'=>false,'priority'=>'urgent','publish_at'=>'2025-01-01 10:00:00'],
    ['id'=>'c','is_published'=>true,'is_pinned'=>false,'priority'=>'urgent','publish_at'=>'2025-01-01 10:00:00'],
    ['id'=>'future','is_published'=>true,'publish_at'=>'2025-01-11 00:00:00'],
    ['id'=>'expired','is_published'=>true,'publish_at'=>'2025-01-01','expire_at'=>'2025-01-09'],
    ['id'=>'draft','is_published'=>false],
];
$active = array_values(array_filter($items, fn($a) => announcement_is_active($a, $now)));
announcement_sort($active);
check_rule(array_column($active, 'id') === ['a','b','c','z'], 'pin, priority, date and id tie ordering');
check_rule(announcement_type('unknown') === 'info', 'safe type default');
check_rule(announcement_priority('unknown') === 'normal', 'safe priority default');
check_rule(announcement_excerpt("Long\ntext with a very long final word", 10) === "Long\ntext…", 'word-boundary excerpt');
check_rule(announcement_excerpt("<script>alert(1)</script>") === "<script>alert(1)</script>", 'helper preserves text for caller escaping');
$fixture = json_decode(file_get_contents(__DIR__ . '/fixtures/announcement_ranking.json'), true);
announcement_sort($fixture['items']);
check_rule(array_column($fixture['items'], 'id') === $fixture['expected'], 'cross-runtime ranking fixture');
$valid = announcement_validate_payload(['title'=>'  Tittel ','content'=>"a\r\nb",'is_published'=>true,'is_pinned'=>0,'image_url'=>'https://example.test/a.jpg','publish_at'=>'2025-01-01T10:00','expire_at'=>'2025-01-02 10:00:00','product'=>['output_product_id'=>'p','discount_percent'=>10,'max_quantity'=>2,'expiry_date'=>'2025-02-01']]);
check_rule($valid['ok'] && $valid['data']['content'] === "a\nb", 'valid payload normalization');
foreach ([['title'=>'','content'=>'x'],['title'=>str_repeat('x',256),'content'=>'x'],['title'=>'x','content'=>'x','image_url'=>'javascript:alert(1)'],['title'=>'x','content'=>'x','is_published'=>'1'],['title'=>'x','content'=>'x','publish_at'=>'bad'],['title'=>'x','content'=>'x','publish_at'=>'2025-01-02','expire_at'=>'2025-01-01'],['title'=>'x','content'=>'x','product'=>['output_product_id'=>'p','discount_percent'=>101]]] as $invalid) check_rule(!announcement_validate_payload($invalid)['ok'], 'invalid payload rejected');
foreach (['2025-02-30 10:00:00','tomorrow','2025-01-01T10:00:00Z','2025-01-01T10:00:61'] as $badDate) check_rule(!announcement_validate_payload(['title'=>'x','content'=>'x','publish_at'=>$badDate])['ok'], 'strict date rejected');
check_rule(!announcement_validate_payload(['title'=>'x','content'=>'x','image_url'=>'https://example.test/'.str_repeat('a',250)])['ok'], 'long image URL rejected');
echo "Announcement rules passed\n";