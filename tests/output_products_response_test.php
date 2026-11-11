<?php
// Execute the real endpoint against an isolated in-memory database.
// Only its session/bootstrap statements are removed; its query and loops are unchanged.
$endpoint = file_get_contents(__DIR__ . '/../api/get_output_products.php');
foreach ([
    'session_start();',
    'session_write_close();',
    "require_once __DIR__ . '/../config/database.php';",
] as $bootstrap) {
    $endpoint = str_replace($bootstrap, '', $endpoint, $count);
    if ($count !== 1) {
        throw new RuntimeException('Endpoint bootstrap changed; update the isolated test adapter.');
    }
}
$endpoint = preg_replace('/^<\?php\s*/', '', $endpoint, 1);
$fixtureDb = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
function getDBConnection() {
    return $GLOBALS['fixtureDb'];
}
$fixtureDb->exec('
    CREATE TABLE system_modules (id TEXT PRIMARY KEY, module_key TEXT);
    CREATE TABLE output_products (
        id TEXT PRIMARY KEY, company_id TEXT, module_id TEXT, product_type TEXT,
        product_id TEXT, name TEXT, description TEXT, is_visible INTEGER,
        is_public INTEGER, created_at TEXT
    );
    CREATE TABLE membership_plans (id TEXT PRIMARY KEY, company_id TEXT, name TEXT);
    INSERT INTO system_modules VALUES ("1-events", "events"), ("9-membership", "membership");
    INSERT INTO membership_plans VALUES ("plan-member", "company-a", "Bergsøy Medlem");
');
$_SESSION = ['tenant_admin_id' => 'test-admin', 'company_id' => 'company-a'];
function check_product_response($condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function product_response(string $endpoint): array {
    ob_start();
    try {
        eval($endpoint);
        return json_decode(ob_get_contents(), true, 512, JSON_THROW_ON_ERROR);
    } finally {
        ob_end_clean();
    }
}
function seed_product(string $id, string $company, string $module, string $type, string $source, ?string $name): void {
    $stmt = getDBConnection()->prepare('INSERT INTO output_products VALUES (?, ?, ?, ?, ?, ?, "", 1, 1, "2026-11-11 12:00:00")');
    $stmt->execute([$id, $company, $module, $type, $source, $name]);
}

seed_product('event-product', 'company-a', '1-events', 'events', 'event-source', 'Et arrangement');
seed_product('member-product', 'company-a', '9-membership', 'membership', 'plan-member', 'Bergsøy Medlem');
seed_product('foreign-member', 'company-b', '9-membership', 'membership', 'foreign-plan', 'Annen bedrifts medlemskap');
$response = product_response($endpoint);
check_product_response($response['success'] === true, 'Endpoint returns a successful response');
check_product_response(array_column($response['products'], 'id') === ['event-product', 'member-product'],
    'The membership at the end survives processing, with its original product ID');
check_product_response($response['total_products'] === 2, 'Product total includes the membership');
check_product_response($response['count_by_module']['9-membership'] === 1, 'Membership module count is correct');
check_product_response(in_array('membership', $response['modules'], true), 'Membership is present in module metadata');

$fixtureDb->exec('UPDATE output_products SET name = NULL WHERE id = "member-product"');
$response = product_response($endpoint);
$member = array_values(array_filter($response['products'], static fn($p) => $p['id'] === 'member-product'))[0] ?? null;
check_product_response($member && $member['name'] === 'Bergsøy Medlem',
    'Membership name lookup also retains the last product');

$fixtureDb->exec('DELETE FROM output_products WHERE product_type = "events"');
$response = product_response($endpoint);
check_product_response(array_column($response['products'], 'id') === ['member-product'],
    'A catalog containing just one membership is returned correctly');
seed_product('duplicate-member', 'company-a', '9-membership', 'membership', 'plan-member', 'Bergsøy Medlem');
$response = product_response($endpoint);
check_product_response(count($response['products']) === 1, 'Genuine duplicate source products are still deduplicated');

$fixtureDb->exec('DELETE FROM output_products WHERE company_id = "company-a"');
$response = product_response($endpoint);
check_product_response($response['success'] === true && $response['products'] === [] && $response['total_products'] === 0,
    'An empty company catalog does not expose another company’s membership');
echo "Output products endpoint regression tests passed.\n";
