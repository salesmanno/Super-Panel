<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/portal_website_preview_data.php';
class PreviewDataStatement extends PDOStatement {
    public string $sql;
    public array $rows;
    public function execute(?array $params = null): bool {
        if ($params !== ['company-test']) throw new RuntimeException('Query is not company scoped');
        return true;
    }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array { return $this->rows; }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed { return $this->rows[0] ?? false; }
}
class PreviewDataDatabase extends PDO {
    public array $queries = [];
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false {
        if (!str_starts_with($query, 'SELECT')) throw new RuntimeException('Preview attempted a write');
        $this->queries[] = $query;
        $statement = new PreviewDataStatement();
        $statement->sql = $query;
        $statement->rows = match (true) {
            str_contains($query, 'FROM tenant_companies ') => [['name'=>'Test Company']],
            str_contains($query, 'FROM tenant_website_general ') => [['header_bg_color'=>'#234567']],
            str_contains($query, 'FROM tenant_announcements') => [['id'=>'news-test', 'title'=>'News']],
            str_contains($query, 'FROM rental_objects') => [
                ['id'=>'rental-test','name'=>'Rental','is_active'=>1,'key_box_code'=>'secret-code','rental_price'=>250],
            ],
            str_contains($query, 'FROM output_products') => [
                ['id'=>'output-rental','product_type'=>'rental','product_id'=>'rental-test','name'=>'Rental','is_visible'=>1,'is_public'=>1],
                ['id'=>'output-sales','product_type'=>'product','product_id'=>'sales-test','name'=>'Not delegated','is_visible'=>1,'is_public'=>1],
                ['id'=>'duplicate','product_type'=>'rental','product_id'=>'rental-test','name'=>'Duplicate','is_visible'=>1,'is_public'=>1],
            ],
            default => throw new RuntimeException('Unexpected query: '.$query),
        };
        return $statement;
    }
}
$db = new PreviewDataDatabase();
$data = portalWebsitePreviewData($db, 'company-test', [['module_key'=>'rental']], [['module_key'=>'rental'], ['module_key'=>'sales']]);
if ($data['errors']) throw new RuntimeException(implode('; ', $data['errors']));
if (count($data['products']) !== 1 || $data['products'][0]['name'] !== 'Rental') throw new RuntimeException('Permission filtering/deduplication failed');
if (str_contains(json_encode($data), 'secret-code')) throw new RuntimeException('Source access code leaked');
if (array_filter($db->queries, fn($query)=>str_contains($query, 'sales_products'))) throw new RuntimeException('Unpermitted source queried');
echo "Preview data: company scope, delegated modules, read-only projection and sensitive-field exclusion passed.\n";
