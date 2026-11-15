<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/member_page_activation.php';

final class MemberPageTestDB extends PDO
{
    public array $rows = ['company-a:member-a' => 0, 'company-b:member-b' => 0];
    public int $writes = 0;
    public function __construct() {}
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        return new MemberPageTestStatement($this, $query);
    }
}
final class MemberPageTestStatement extends PDOStatement
{
    private array $params = [];
    public function __construct(private MemberPageTestDB $db, private string $sql) {}
    public function execute(?array $params = null): bool
    {
        $this->params = $params ?? [];
        if (str_starts_with($this->sql, 'UPDATE')) {
            $key = $params[1] . ':' . $params[0];
            if (array_key_exists($key, $this->db->rows)) {
                $this->db->rows[$key] = 1;
                $this->db->writes++;
            }
        }
        return true;
    }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed
    {
        $key = $this->params[1] . ':' . $this->params[0];
        return array_key_exists($key, $this->db->rows)
            ? ['my_membership_page_active' => $this->db->rows[$key]] : false;
    }
}
function checkMemberPage(bool $ok, string $message): void
{
    if (!$ok) throw new RuntimeException($message);
}
$db = new MemberPageTestDB();
activateMemberPage($db, 'company-a', 'member-a');
checkMemberPage($db->rows['company-a:member-a'] === 1 && $db->writes === 1, 'Inactive page activated');
activateMemberPage($db, 'company-a', 'member-a');
checkMemberPage($db->rows['company-a:member-a'] === 1 && $db->writes === 1, 'Active page unchanged, no duplicate write');
try {
    activateMemberPage($db, 'company-a', 'member-b');
    throw new RuntimeException('Cross-company activation accepted');
} catch (OutOfBoundsException $e) {}
checkMemberPage($db->rows['company-b:member-b'] === 0 && $db->writes === 1, 'Other company unchanged');
$db->rows['company-a:member-a'] = 0; // Customer closed the page.
activateMemberPage($db, 'company-a', 'member-a');
checkMemberPage($db->rows['company-a:member-a'] === 1 && $db->writes === 2, 'Closed page can be reactivated');
echo "Member page activation: inactive, active, tenant isolation, reactivation passed\n";
