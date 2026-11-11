<?php
require_once __DIR__ . '/../includes/announcement_preview.php';

class PreviewStatement extends PDOStatement {
    public function __construct(private array $row) {}
    public function execute(?array $params = null): bool { return true; }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed { return $this->row; }
}
class PreviewDatabase extends PDO {
    public array $queries = [];
    public function __construct(private array $theme, private bool $failTheme = false) {}
    public function prepare(string $query, array $options = []): PDOStatement|false {
        $this->queries[] = $query;
        if (str_contains($query, 'tenant_website_general')) {
            if ($this->failTheme) throw new PDOException('Fixture: website table missing');
            return new PreviewStatement($this->theme);
        }
        return new PreviewStatement(['name'=>'Testfirma', 'img_url'=>'https://example.org/logo.png']);
    }
}
function preview_check(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$db = new PreviewDatabase(['header_bg_color'=>'#112233','logo_position'=>'right','nav_position'=>'left','header_layout'=>'standard']);
$result = announcement_preview_company($db, 'fixture-company');
preview_check($result['company']['logo_position'] === 'right', 'Saved right alignment is preserved');
preview_check($result['company']['nav_position'] === 'left', 'Saved navigation position is preserved');
preview_check($result['company']['header_bg_color'] === '#112233', 'Saved color is preserved');
preview_check(str_contains($db->queries[1], 'WHERE company_id = ?'), 'Theme lookup uses company_id');
$minimal = announcement_preview_company(new PreviewDatabase([]), 'fixture-company');
preview_check($minimal['company']['name'] === 'Testfirma' && !$minimal['warnings'], 'Optional columns are not required');
$failed = announcement_preview_company(new PreviewDatabase([], true), 'fixture-company');
preview_check($failed['company']['name'] === 'Testfirma' && count($failed['warnings']) === 1, 'Theme failure remains explicit without blocking company data');
$centered = announcement_preview_company(new PreviewDatabase(['header_layout'=>'centered']), 'fixture-company');
preview_check($centered['company']['logo_position'] === 'center', 'Centered layout supplies center position for legacy settings');
$unsafe = announcement_preview_company(new PreviewDatabase(['logo_position'=>'right;invalid','header_bg_color'=>'red;invalid']), 'fixture-company');
preview_check($unsafe['company']['logo_position'] === 'left' && $unsafe['company']['header_bg_color'] === '#176b69', 'Untrusted styles are constrained');
echo "News preview contract tests passed\n";
