<?php
require_once __DIR__ . '/../includes/announcement_helpers.php';

function check_rule(bool $value, string $message): void {
    if (!$value) { fwrite(STDERR, "FAIL: {$message}\n"); exit(1); }
}
$now = new DateTimeImmutable('2025-01-10 12:00:00');
$items = [
    ['id'=>'z','is_published'=>true,'is_pinned'=>false,'priority'=>5,'publish_at'=>'2025-01-09 10:00:00'],
    ['id'=>'a','is_published'=>true,'is_pinned'=>true,'priority'=>8,'publish_at'=>'2025-01-01 10:00:00'],
    ['id'=>'b','is_published'=>true,'is_pinned'=>false,'priority'=>1,'publish_at'=>'2025-01-01 10:00:00'],
    ['id'=>'c','is_published'=>true,'is_pinned'=>false,'priority'=>1,'publish_at'=>'2025-01-01 10:00:00'],
    ['id'=>'future','is_published'=>true,'publish_at'=>'2025-01-11 00:00:00'],
    ['id'=>'expired','is_published'=>true,'publish_at'=>'2025-01-01','expire_at'=>'2025-01-09'],
    ['id'=>'draft','is_published'=>false],
];
$active = array_values(array_filter($items, fn($a) => announcement_is_active($a, $now)));
announcement_sort($active);
check_rule(array_column($active, 'id') === ['a','b','c','z'], 'pin, priority, date and id tie ordering');
check_rule(announcement_type('unknown') === 'info', 'safe type default');
check_rule(announcement_priority('urgent') === 1 && announcement_priority('high') === 3 && announcement_priority('normal') === 5 && announcement_priority('low') === 8, 'legacy priority mapping');
check_rule(announcement_priority('unknown') === 5, 'safe priority default');
check_rule(announcement_priority('1') === 1 && announcement_priority('10') === 10, 'numeric strings normalize on legacy reads');
foreach (range(1, 10) as $priority) check_rule(announcement_priority_for_write($priority) === $priority, 'numeric priority accepted');
foreach ([0, 11, 5.5, '5', '5.0', null, false] as $priority) check_rule(announcement_priority_for_write($priority) === null, 'invalid numeric priority rejected');
check_rule(announcement_excerpt("Long\ntext with a very long final word", 10) === "Long\ntext…", 'word-boundary excerpt');
check_rule(announcement_excerpt("<script>alert(1)</script>") === "<script>alert(1)</script>", 'helper preserves text for caller escaping');
$fixture = json_decode(file_get_contents(__DIR__ . '/fixtures/announcement_ranking.json'), true);
announcement_sort($fixture['items']);
check_rule(array_column($fixture['items'], 'id') === $fixture['expected'], 'cross-runtime ranking fixture');
$valid = announcement_validate_payload(['title'=>'  Tittel ','content'=>"a\r\nb",'priority'=>10,'is_published'=>true,'is_pinned'=>0,'image_url'=>'https://example.test/a.jpg','publish_at'=>'2025-01-01T10:00','expire_at'=>'2025-01-02 10:00:00','product'=>['output_product_id'=>'p','discount_percent'=>10,'max_quantity'=>2,'expiry_date'=>'2025-02-01']]);
check_rule($valid['ok'] && $valid['data']['content'] === "a\nb", 'valid payload normalization');
foreach ([['title'=>'','content'=>'x'],['title'=>str_repeat('x',256),'content'=>'x'],['title'=>'x','content'=>'x','image_url'=>'javascript:alert(1)'],['title'=>'x','content'=>'x','is_published'=>'1'],['title'=>'x','content'=>'x','publish_at'=>'bad'],['title'=>'x','content'=>'x','publish_at'=>'2025-01-02','expire_at'=>'2025-01-01'],['title'=>'x','content'=>'x','product'=>['output_product_id'=>'p','discount_percent'=>101]]] as $invalid) check_rule(!announcement_validate_payload($invalid)['ok'], 'invalid payload rejected');
foreach ([0, 11, 5.5, '5', '5.0'] as $priority) check_rule(!announcement_validate_payload(['title'=>'x','content'=>'x','priority'=>$priority])['ok'], 'invalid payload priority rejected');
foreach (['2025-02-30 10:00:00','tomorrow','2025-01-01T10:00:00Z','2025-01-01T10:00:61'] as $badDate) check_rule(!announcement_validate_payload(['title'=>'x','content'=>'x','publish_at'=>$badDate])['ok'], 'strict date rejected');
check_rule(!announcement_validate_payload(['title'=>'x','content'=>'x','image_url'=>'https://example.test/'.str_repeat('a',250)])['ok'], 'long image URL rejected');
$priorityEndpoint = file_get_contents(__DIR__ . '/../api/update_announcement_priority.php');
check_rule(str_contains($priorityEndpoint, 'company_id = ?') && !str_contains($priorityEndpoint, 'title ='), 'priority endpoint is tenant scoped and only updates priority');
$priorityMigration = file_get_contents(__DIR__ . '/../db_migrations/tenant_announcement_priority_numeric.sql');
foreach (["WHEN 'urgent' THEN 1", "WHEN 'high' THEN 3", "WHEN 'normal' THEN 5", "WHEN 'low' THEN 8", 'TINYINT UNSIGNED NOT NULL DEFAULT 5'] as $migrationRule) {
    check_rule(str_contains($priorityMigration, $migrationRule), 'priority migration contains canonical mapping and type');
}
echo "Announcement rules passed\n";