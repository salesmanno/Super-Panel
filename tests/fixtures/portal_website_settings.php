<?php
// Isolated page data: never connect this fixture to a project or production database.
$_SESSION=['tenant_admin_id'=>'fixture-admin','company_id'=>'fixture-company'];
$adminName='Test Administrator';
$previewData=['company'=>['name'=>'Testbedrift']];
class WebsiteFixtureStatement {
    public function __construct(private array $rows) {}
    public function execute(array $parameters=[]): void {}
    public function fetch(mixed $mode=null): array|false { return $this->rows[0] ?? false; }
    public function fetchAll(mixed $mode=null): array { return $this->rows; }
    public function fetchColumn(): int { return 0; }
}
class WebsiteFixtureDatabase {
    public function prepare(string $sql): WebsiteFixtureStatement {
        $row = match (true) {
            str_contains($sql,'FROM tenant_companies ') => ['name'=>'Testbedrift'],
            str_contains($sql,'FROM tenant_website_general ') => [
                'header_bg_color'=>'#244d83','body_bg_color'=>'#e0f0fc','font_family'=>'Inter, sans-serif',
                'header_height'=>'90px','logo_height'=>'50px','hero_enabled'=>1,'header_mode'=>'static','footer_mode'=>'dynamic',
                'custom_js'=>'/* existing script */','custom_css'=>'.existing { color: blue; }','contact_phone'=>'12345678',
                'google_analytics_id'=>'G-EXISTING'
            ],
            str_contains($sql,'FROM tenant_website_config ') => ['is_enabled'=>1,'is_published'=>1,'seo_title'=>'Eksisterende søketittel'],
            default => null
        };
        return new WebsiteFixtureStatement($row ? [$row] : []);
    }
}
function getDBConnection(): WebsiteFixtureDatabase { return new WebsiteFixtureDatabase(); }
ob_start(); include __DIR__.'/../../modules/deres_firma_content.php'; $module = ob_get_clean();
ob_start(); include __DIR__.'/../../includes/portal_preview_account.php'; $account = ob_get_clean();
ob_start(); include __DIR__.'/../../includes/portal_preview_control_room.php'; $control = ob_get_clean();
echo json_encode(['module'=>$module,'account'=>$account,'control'=>$control], JSON_THROW_ON_ERROR);
