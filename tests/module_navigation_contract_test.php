<?php
$dashboard = file_get_contents(__DIR__ . '/../pages/company_dashboard.php');
$start = strpos($dashboard, '<ul class="nav nav-tabs module-tabs border-0"');
$end = strpos($dashboard, '</ul>', $start);
if ($start === false || $end === false) throw new RuntimeException('Main navigation markup not found');
$navigation = substr($dashboard, $start, $end + strlen('</ul>') - $start);
$visibleModules = array_map(static fn($key) => [
    'module_key' => $key,
    'name' => $key,
    'icon' => 'bi-grid',
], ['events', 'reservation', 'reservations', 'news', 'membership', 'rental', 'webmail']);
ob_start();
eval('?>' . $navigation);
$html = ob_get_clean();
$document = new DOMDocument();
@$document->loadHTML($html);
$xpath = new DOMXPath($document);
foreach (['events', 'reservation', 'reservations', 'news', 'announcements'] as $key) {
    $button = $xpath->query('//button[@id="tab-' . $key . '"]')->item(0);
    if (!$button || $xpath->query('.//i', $button)->length) {
        throw new RuntimeException("Unexpected icon or missing navigation for $key");
    }
}
foreach (['membership', 'rental', 'webmail'] as $key) {
    $button = $xpath->query('//button[@id="tab-' . $key . '"]')->item(0);
    if (!$button || $xpath->query('.//i', $button)->length !== 1) {
        throw new RuntimeException("Unrelated module icon changed: $key");
    }
}
$news = file_get_contents(__DIR__ . '/../modules/announcements_content.php');
foreach (['.nav-link {', '.nav-link:hover {', '.nav-link.active {'] as $state) {
    $firstSelector = substr($state, 0, -2);
    if (!str_contains($dashboard, ".subtabs $firstSelector,\n        .news-subtabs $state")) {
        throw new RuntimeException('News must share the same subtab style rules: ' . $state);
    }
}
if (!str_contains($news, 'class="nav nav-pills py-2 news-subtabs"') ||
    !str_contains($news, 'class="nav-link news-subtab active"') ||
    str_contains($news, '.news-subtab.active{')) {
    throw new RuntimeException('News must use standard nav pills without its old custom active style');
}
echo "Module navigation tests passed: requested icons removed, unrelated icons retained, shared News subtab styling.\n";
