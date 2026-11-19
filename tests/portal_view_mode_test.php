<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/portal_view_mode.php';

class PreviewModeStatement extends PDOStatement {
    public mixed $value;
    public array $bound = [];
    public function execute(?array $params = null): bool { $this->bound = $params ?? []; return true; }
    public function fetchColumn(int $column = 0): mixed { return $this->value; }
}
class PreviewModeDatabase extends PDO {
    public PreviewModeStatement $statement;
    public ?PDOException $failure = null;
    public function __construct(mixed $value) { $this->statement = new PreviewModeStatement(); $this->statement->value = $value; }
    public function prepare(string $query, array $options = []): PDOStatement|false {
        if ($this->failure) throw $this->failure;
        if ($query !== 'SELECT view_mode FROM tenant_admins WHERE id = ? LIMIT 1') throw new RuntimeException('Unexpected query');
        return $this->statement;
    }
}
function expectPreview(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
foreach (['wpreview'=>'wpreview', 'legacy'=>'legacy', 'WPREVIEW'=>'legacy', ''=>'legacy', 'other'=>'legacy'] as $value=>$expected) {
    $db = new PreviewModeDatabase($value);
    expectPreview(portalViewMode($db, 'test-admin') === $expected, 'Incorrect opt-in behaviour');
    expectPreview($db->statement->bound === ['test-admin'], 'Must read the authenticated user');
}
$db = new PreviewModeDatabase(false);
expectPreview(portalViewMode($db, 'test-admin') === 'legacy', 'Missing user must not opt in');
$error = new PDOException('Missing column');
$error->errorInfo = ['42S22', 1054, 'Missing column'];
$db->failure = $error;
expectPreview(portalViewMode($db, 'test-admin') === 'legacy', 'Unmigrated exports must keep working');
$db->failure = new PDOException('Database unavailable');
try { portalViewMode($db, 'test-admin'); throw new RuntimeException('Database failure was hidden'); }
catch (PDOException $expected) {}
$user = [['module_key'=>'events'], ['module_key'=>'rental'], ['module_key'=>'sales']];
$company = [['module_key'=>'events'], ['module_key'=>'membership']];
expectPreview(portalPreviewModules($user, $company) === [['module_key'=>'events']], 'Permissions must intersect');
expectPreview(portalPreviewModuleForType('sales_item') === 'sales', 'Sales alias must resolve');
expectPreview(portalPreviewModuleForType('unknown') === null, 'Unknown sources must not be offered');
echo "Portal view mode: opt-in, legacy compatibility and module intersection passed.\n";
