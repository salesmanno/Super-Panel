<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/portal_service_payments.php';

// SQL contract fixture: no connection to development or production databases.
class ServicePaymentPDO extends PDO {
    public array $rows = [];
    public array $writes = [];
    public array $audits = [];
    public bool $transaction = false;
    public bool $authorized = true;
    public bool $auditFails = false;
    public function __construct() {}
    public function inTransaction(): bool { return $this->transaction; }
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return new ServicePaymentStatement($this, $query);
    }
}
class ServicePaymentStatement extends PDOStatement {
    private array $result = [];
    public function __construct(private ServicePaymentPDO $db, private string $sql) {}
    public function execute(?array $params = null): bool {
        $sql = $this->sql;
        if (str_contains($sql, 'FROM tenant_admins ta')) {
            $this->result = [['id'=>'admin','username'=>'Test','role'=>'Administrator','company_name'=>'Testfirma']];
        } elseif (str_contains($sql, 'JOIN tenant_admin_modules')) {
            $this->result = $this->db->authorized ? [['id'=>'module']] : [];
        } elseif (preg_match('/SELECT id,(?:title|object_name|name) AS name,payment_methods_json.*?FROM (\w+)/s', $sql, $match)) {
            $this->result = isset($this->db->rows[$match[1]]) && $params === ['object','company']
                ? [$this->db->rows[$match[1]]] : [];
        } elseif (str_contains($sql, 'SELECT id FROM tenant_companies')) {
            $this->result = [['id'=>'company']];
        } elseif (str_contains($sql, 'SELECT cash_payment')) {
            $this->result = [['cash_payment'=>1]];
        } elseif (str_contains($sql, 'FROM economy_configuration_data')) {
            $this->result = [
                ['method_type'=>'vipps','config_name'=>'Vipps','test_mode'=>0,'is_configured'=>1],
                ['method_type'=>'stripe','config_name'=>'Kort','test_mode'=>0,'is_configured'=>1],
                ['method_type'=>'paypal','config_name'=>'PayPal','test_mode'=>0,'is_configured'=>0],
            ];
        } elseif (preg_match('/UPDATE (\w+) SET (.+) WHERE id=\? AND company_id=\?/', $sql, $match)) {
            if (!$this->db->transaction || array_slice($params, -2) !== ['object','company']) throw new RuntimeException('Unscoped write');
            foreach (explode(',', $match[2]) as $index => $column) {
                $this->db->rows[$match[1]][substr($column, 0, -2)] = $params[$index];
            }
            $this->db->writes[] = $params;
        } elseif (str_contains($sql, 'INSERT INTO audit_logs')) {
            if ($this->db->auditFails) throw new RuntimeException('Simulated audit failure');
            $this->db->audits[] = $params;
        } else {
            throw new RuntimeException('Unexpected fixture SQL');
        }
        return true;
    }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed {
        return $this->result[0] ?? false;
    }
    public function fetchColumn(int $column = 0): mixed { return array_values($this->result[0] ?? [])[$column] ?? false; }
    public function fetchAll(int $mode = PDO::FETCH_DEFAULT, mixed ...$args): array { return $this->result; }
}
function paymentAssert(bool $value, string $message): void {
    if (!$value) throw new RuntimeException($message);
}
function paymentReject(callable $run, string $message): void {
    try { $run(); } catch (InvalidArgumentException|DomainException|OutOfBoundsException|TenantAuditException|JsonException $e) { return; }
    throw new RuntimeException($message);
}
$_SESSION = ['tenant_admin_id'=>'admin','company_id'=>'company', 'entity_form_csrf'=>'token'];
$_SERVER['REQUEST_METHOD'] = 'POST';
paymentReject(fn()=>entityFormRequireCsrf(['csrf_token'=>'wrong']), 'CSRF accepted');
entityFormRequireCsrf(['csrf_token'=>'token']);
foreach (['events'=>'events_eventdata','rental'=>'rental_objects','membership'=>'membership_plans'] as $module=>$table) {
    $db = new ServicePaymentPDO;
    $db->rows[$table] = ['id'=>'object','name'=>'Testtjeneste','payment_methods_json'=>'["vipps"]'];
    if ($module === 'events') $db->rows[$table] += ['registration_type'=>'signup', 'default_payment_method'=>'vipps','payment_timing'=>'both'];
    $initial = portalServicePayments($db,'company',$module,'object');
    paymentAssert(count($initial['methods']) === 3, 'Unconfigured method offered');
    paymentAssert(!$db->writes, 'GET wrote data');
    paymentReject(fn()=>portalServicePayments($db,'company',$module,'other'), 'Other tenant/object accepted');
    $db->authorized = false;
    paymentReject(fn()=>portalServicePayments($db,'company',$module,'object'), 'Missing module access accepted');
    $db->authorized = true;
    $db->transaction = true;
    $input = ['version'=>$initial['version'],'methods'=>['vipps','stripe']];
    $result = portalServicePayments($db,'company',$module,'object',$input);
    paymentAssert($result['changed'] && count($db->audits) === 1 && count($db->writes) === 1, 'Change/audit missing');
    paymentAssert(json_decode($db->rows[$table]['payment_methods_json'],true) === ['vipps','stripe'], 'Wrong JSON');
    $input['version'] = $result['version'];
    $input['methods'] = ['stripe','vipps'];
    $result = portalServicePayments($db,'company',$module,'object',$input);
    paymentAssert(!$result['changed'] && count($db->audits) === 1 && count($db->writes) === 1, 'No-op wrote audit/data');
    paymentReject(fn()=>portalServicePayments($db,'company',$module,'object',['version'=>'stale','methods'=>['cash']]), 'Stale write accepted');
    paymentReject(fn()=>portalServicePayments($db,'company',$module,'object',['version'=>$result['version'],'methods'=>['paypal']]), 'Unconfigured method accepted');
    $input = ['version'=>$result['version'], 'methods'=>['cash']];
    portalServicePayments($db,'company',$module,'object',$input);
    if ($module === 'events') paymentAssert($db->rows[$table]['default_payment_method']==='cash' && $db->rows[$table]['payment_timing']==='on_arrival', 'Event default inconsistent');
    $input = ['version'=>portalServicePaymentSnapshot($db->rows[$table]), 'methods'=>[]];
    $cleared = portalServicePayments($db,'company',$module,'object',$input);
    paymentAssert($cleared['selected'] === [], 'Could not clear selection');
    $db->rows[$table]['payment_methods_json'] = '{broken';
    paymentReject(fn()=>portalServicePayments($db,'company',$module,'object'), 'Malformed JSON was silently replaced');
}
paymentReject(fn()=>portalServicePaymentValidate(['cash'],['cash'],'binding'), 'Binding accepted cash');
paymentReject(fn()=>portalServicePaymentValidate([],['vipps'],'binding'), 'Binding accepted no methods');
paymentReject(fn()=>portalServicePaymentValidate(['vipps'],['vipps'],'none'), 'No-signup event accepted payment');
paymentReject(fn()=>portalServicePaymentValidate(['first'=>'vipps'],['vipps']), 'Object accepted instead of list');
paymentReject(fn()=>portalServicePaymentTarget('sales'), 'Unsupported table accepted');
paymentAssert(portalServicePaymentValidate(['Vipps','vipps','kontant'],['vipps','cash']) === ['vipps','cash'], 'Canonical names/duplicates incorrect');
foreach (['vipps', "'vipps'", "['vipps']", '"vipps"'] as $legacy) {
    paymentAssert(portalServicePaymentSelection($legacy) === ['vipps'], 'Legacy Vipps value failed');
    $db = new ServicePaymentPDO;
    $db->rows['membership_plans'] = ['id'=>'object','name'=>'Testplan','payment_methods_json'=>$legacy];
    $loaded = portalServicePayments($db,'company','membership','object');
    paymentAssert(!$db->writes && $loaded['selected'] === ['vipps'], 'Legacy read wrote or lost selection');
    $db->transaction = true;
    $saved = portalServicePayments($db,'company','membership','object',['version'=>$loaded['version'],'methods'=>['vipps']]);
    paymentAssert($saved['changed'] && $db->rows['membership_plans']['payment_methods_json'] === '["vipps"]', 'Explicit save did not repair legacy JSON');
    $again = portalServicePayments($db,'company','membership','object',['version'=>$saved['version'],'methods'=>['vipps']]);
    paymentAssert(!$again['changed'] && count($db->audits) === 1, 'Repeated save wrote audit');
}
paymentAssert(portalServicePaymentSelection("['vipps', 'cash']") === ['vipps','cash'], 'Legacy list lost methods');
paymentAssert(portalServicePaymentSelection('') === [], 'Empty historical selection failed');
paymentReject(fn()=>portalServicePaymentSelection("['vipps', broken]"), 'Broken list accepted');
echo "PASS: service payment selection, permissions, scoping, CSRF, JSON, stale writes, event rules, audit and no-op protection\n";
