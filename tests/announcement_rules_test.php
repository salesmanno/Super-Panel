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
foreach ([1, 5, 10, 11, 12, 500] as $priority) check_rule(announcement_priority_for_write($priority) === $priority, 'positive integer priority accepted');
foreach ([0, -1, 5.5, '5', '5.0', 9007199254740992, null, false] as $priority) check_rule(announcement_priority_for_write($priority) === null, 'invalid numeric priority rejected');
check_rule(announcement_priority(11) === 11 && announcement_priority('12') === 12, 'priorities above ten survive reads');
$priorityRows = [
    ['id' => 'target', 'priority' => 8],
    ['id' => 'a', 'priority' => 1],
    ['id' => 'b', 'priority' => 2],
    ['id' => 'c', 'priority' => 3],
    ['id' => 'gap', 'priority' => 5],
    ['id' => 'other-tenant', 'priority' => 1],
];
$cascade = announcement_priority_assignment_plan(array_slice($priorityRows, 0, 5), 'target', 1);
check_rule($cascade === ['target' => 1, 'a' => 2, 'b' => 3, 'c' => 4], 'assignment cascades through only contiguous tenant conflicts');
check_rule(announcement_priority_assignment_plan($priorityRows, 'target', 8) === [], 'unchanged priority does not reorder');
$legacyTopDuplicates = announcement_priority_assignment_plan([
    ['id' => 'target', 'priority' => 7],
    ['id' => 'a', 'priority' => 1],
    ['id' => 'b', 'priority' => 1],
    ['id' => 'c', 'priority' => 2],
    ['id' => 'd', 'priority' => 3],
], 'target', 1);
check_rule($legacyTopDuplicates === ['target' => 1, 'a' => 2, 'b' => 2, 'c' => 3, 'd' => 4], 'duplicate legacy top ranks cascade deterministically');
check_rule(
    announcement_priority_assignment_plan(
        [['id' => 'target', 'priority' => 2], ['id' => 'last', 'priority' => ANNOUNCEMENT_PRIORITY_MAX]],
        'target',
        ANNOUNCEMENT_PRIORITY_MAX
    ) === null,
    'priority cascade rejects unsafe integer overflow'
);
check_rule(announcement_excerpt("Long\ntext with a very long final word", 10) === "Long\ntext…", 'word-boundary excerpt');
check_rule(announcement_excerpt("<script>alert(1)</script>") === "", 'unsafe markup is removed from excerpts');
$oneLineBreak = "a\nb";
$twoLineBreaks = "a\n\nb";
check_rule(announcement_content_to_plain_text(announcement_content_to_html($oneLineBreak)) === $oneLineBreak, 'single legacy newline round trips exactly');
check_rule(announcement_content_to_plain_text(announcement_content_to_html($twoLineBreaks)) === $twoLineBreaks, 'multiple legacy newlines round trip exactly');
check_rule(announcement_content_to_html($oneLineBreak) === 'a<br />b' && announcement_content_to_html($twoLineBreaks) === 'a<br /><br />b', 'legacy HTML projection does not retain source newlines');
$fixture = json_decode(file_get_contents(__DIR__ . '/fixtures/announcement_ranking.json'), true);
announcement_sort($fixture['items']);
check_rule(array_column($fixture['items'], 'id') === $fixture['expected'], 'cross-runtime ranking fixture');
$valid = announcement_validate_payload(['title'=>'  Tittel ','content'=>"a\r\nb",'priority'=>10,'is_published'=>true,'is_pinned'=>0,'image_url'=>'https://example.test/a.jpg','publish_at'=>'2025-01-01T10:00','expire_at'=>'2025-01-02 10:00:00','product'=>['output_product_id'=>'p','discount_percent'=>10,'max_quantity'=>2,'expiry_date'=>'2025-02-01']]);
check_rule($valid['ok'] && $valid['data']['content'] === "a\nb", 'valid payload normalization');
$rich = announcement_sanitize_content('<h3>Overskrift</h3><p>Første avsnitt</p><p><br></p><p><strong>Viktig</strong> <em>tekst</em> <a href="https://example.test/info" style="color:red">lenke</a></p><ul><li>Punkt</li></ul>');
check_rule(str_contains($rich, '<h3>Overskrift</h3>') && str_contains($rich, '<strong>Viktig</strong>') && str_contains($rich, '<a href="https://example.test/info"'), 'allowed rich formatting survives sanitization');
check_rule(str_contains($rich, '<p><br></p>') && !str_contains($rich, 'style='), 'blank paragraphs survive and styling is removed');
$unsafe = announcement_sanitize_content('<p>Trygt</p><script>alert(1)</script><img src=x onerror=alert(1)><a href="javascript:alert(1)">farlig</a><span onclick="evil()">tekst</span>');
check_rule(str_contains($unsafe, 'Trygt') && !str_contains($unsafe, 'script') && !str_contains($unsafe, 'onerror') && !str_contains($unsafe, 'javascript:') && str_contains($unsafe, 'tekst'), 'unsafe tags, attributes and URLs are removed');
$emptyRichContent = ['<script>alert(1)</script>', '<br>', '<p>   </p>', '<div><br></div>', ''];
foreach ($emptyRichContent as $emptyContent) {
    check_rule(!announcement_validate_payload(['title'=>'x', 'content'=>$emptyContent])['ok'], 'empty semantic content is rejected');
}
$legacy = "Linje én\n\nLinje tre";
check_rule(announcement_content_to_plain_text($legacy) === $legacy, 'plain text round trip preserves blank lines');
check_rule(announcement_content_to_plain_text($rich) === "Overskrift\nFørste avsnitt\n\nViktig tekst lenke\nPunkt\n", 'rich content round trip exposes readable text');
foreach ([['title'=>'','content'=>'x'],['title'=>str_repeat('x',256),'content'=>'x'],['title'=>'x','content'=>'x','image_url'=>'javascript:alert(1)'],['title'=>'x','content'=>'x','is_published'=>'1'],['title'=>'x','content'=>'x','publish_at'=>'bad'],['title'=>'x','content'=>'x','publish_at'=>'2025-01-02','expire_at'=>'2025-01-01'],['title'=>'x','content'=>'x','product'=>['output_product_id'=>'p','discount_percent'=>101]]] as $invalid) check_rule(!announcement_validate_payload($invalid)['ok'], 'invalid payload rejected');
foreach ([0, -1, 5.5, '5', '5.0', 9007199254740992] as $priority) check_rule(!announcement_validate_payload(['title'=>'x','content'=>'x','priority'=>$priority])['ok'], 'invalid payload priority rejected');
check_rule(announcement_validate_payload(['title'=>'x','content'=>'x','priority'=>12])['ok'], 'payload accepts priority above ten');
foreach (['2025-02-30 10:00:00','tomorrow','2025-01-01T10:00:00Z','2025-01-01T10:00:61'] as $badDate) check_rule(!announcement_validate_payload(['title'=>'x','content'=>'x','publish_at'=>$badDate])['ok'], 'strict date rejected');
check_rule(!announcement_validate_payload(['title'=>'x','content'=>'x','image_url'=>'https://example.test/'.str_repeat('a',250)])['ok'], 'long image URL rejected');
$priorityEndpoint = file_get_contents(__DIR__ . '/../api/update_announcement_priority.php');
check_rule(
    str_contains($priorityEndpoint, 'announcement_apply_priority_assignment')
    && str_contains($priorityEndpoint, 'beginTransaction()')
    && str_contains($priorityEndpoint, 'company_id')
    && !str_contains($priorityEndpoint, 'title ='),
    'priority endpoint uses the tenant-serialized priority assignment transaction'
);
$priorityHelpers = file_get_contents(__DIR__ . '/../includes/announcement_helpers.php');
check_rule(
    str_contains($priorityHelpers, 'SELECT id FROM tenant_companies WHERE id = ? FOR UPDATE')
    && str_contains($priorityHelpers, 'WHERE company_id = ? ORDER BY CAST(priority AS UNSIGNED), id FOR UPDATE'),
    'priority writes lock the tenant and all status rows before planning a cascade'
);
$createEndpoint = file_get_contents(__DIR__ . '/../api/create_announcement.php');
$updateEndpoint = file_get_contents(__DIR__ . '/../api/update_announcement.php');
$editorModule = file_get_contents(__DIR__ . '/../modules/announcements_content.php');
foreach ([$createEndpoint, $updateEndpoint] as $endpoint) {
    check_rule(
        str_contains($endpoint, "'content_html' => announcement_content_to_html")
        && str_contains($endpoint, "'content_text' => announcement_content_to_plain_text"),
        'save endpoints return server-sanitized content projections'
    );
    check_rule(
        str_contains($endpoint, 'beginTransaction()')
        && str_contains($endpoint, 'announcement_apply_priority_assignment')
        && str_contains($endpoint, 'announcement_priority_map'),
        'create and update serialize priority changes and return authoritative tenant ranks'
    );
    check_rule(
        strpos($endpoint, 'beginTransaction()') < strpos($endpoint, 'announcement_apply_priority_assignment'),
        'create and update apply their cascade inside the write transaction'
    );
}
check_rule(
    str_contains($editorModule, "content:x.content,content_html:x.content_html,content_text:x.content_text")
    && !str_contains($editorModule, "content_html:contentValue(),content_text:contentToText(contentValue())")
    && str_contains($editorModule, "excerpt(a.content_text||''")
    && str_contains($editorModule, "setContentHtml(a.content_html)")
    && !str_contains($editorModule, "excerpt(a.content,")
    && !str_contains($editorModule, "setContentHtml(a.content)")
    && str_contains($editorModule, "validateContentEditor")
    && str_contains($editorModule, "editor.focus()"),
    'editor optimistic state uses only server-returned content projections'
);
$priorityMigration = file_get_contents(__DIR__ . '/../db_migrations/tenant_announcement_priority_numeric.sql');
foreach (["WHEN 'urgent' THEN 1", "WHEN 'high' THEN 3", "WHEN 'normal' THEN 5", "WHEN 'low' THEN 8", 'BIGINT UNSIGNED NOT NULL DEFAULT 5', 'BETWEEN 1 AND 9007199254740991'] as $migrationRule) {
    check_rule(str_contains($priorityMigration, $migrationRule), 'priority migration contains canonical mapping and type');
}
$cascadeMigration = file_get_contents(__DIR__ . '/../db_migrations/tenant_announcement_priority_numeric_cascade.sql');
check_rule(
    str_contains($cascadeMigration, 'MIN(`id`) AS `keeper_id`')
    && str_contains($cascadeMigration, 'WITH RECURSIVE')
    && str_contains($cascadeMigration, 'SELECT DISTINCT announcement.`company_id`, announcement.`priority`')
    && str_contains($cascadeMigration, 'tmp_tenant_announcement_priority_chain_rows')
    && str_contains($cascadeMigration, 'SET loser.`priority` = 2')
    && str_contains($cascadeMigration, 'GENERATED ALWAYS AS')
    && str_contains($cascadeMigration, 'ADD UNIQUE KEY `uq_tenant_announcements_priority_one`'),
    'cascade migration reconciles duplicate top ranks and adds a database uniqueness guard'
);
check_rule(
    str_contains($editorModule, 'type="number" min="1"')
    && str_contains($editorModule, 'input.valueAsNumber')
    && str_contains($editorModule, 'AnnouncementRanking.isValidPriority(priority)')
    && !str_contains($editorModule, "priorityValue(val('announcement-priority'))")
    && str_contains($editorModule, 'planPriorityAssignment')
    && str_contains($editorModule, 'applyPriorityMap(x.priorities)'),
    'News editor previews cascades and applies authoritative returned ranks'
);
echo "Announcement rules passed\n";