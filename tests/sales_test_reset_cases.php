<?php
declare(strict_types=1);
// Loaded exclusively by the disposable socket-MySQL harness.
require_once $root.'/scripts/lib/SalesTestReset.php';
$resetCompany = '11111111-2222-4333-a444-555555555555';
foreach (['tenant_companies', 'tenant_admins', 'tenant_staff', 'tenant_company_modules',
    'email_configs', 'economy_configuration_data', 'economy_payment_gateways'] as $table) {
    $column = $table === 'tenant_companies' ? 'id' : 'company_id';
    $s = $setup->prepare("UPDATE `$table` SET `$column`=? WHERE `$column`='a'");
    $s->execute([$resetCompany]);
}
salesInstallRegisterFixture($setup, $root);
$_SESSION = ['company_id' => $resetCompany, 'tenant_admin_id' => 'admin'];
class SalesResetVippsFixture extends SalesFixtureVipps {
    public function configured(array $c, string $company): bool { return $c['configuration_id'] === 'cfg-a'; }
}
$provider = new SalesResetVippsFixture();
$sales = new SalesService($db, $resetCompany, 'admin', 'reset-fixture', $provider);
salesFixtureRegister($sales);
$product = $sales->saveProduct(['name'=>'Reset test product','selling_price'=>'100','cost_price'=>'10',
    'stock_quantity'=>20,'tax_class'=>'standard','tax_rate'=>'25','visibility'=>'kiosk'])['product'];
$productId = $product['id'];
$checkoutCash = function(int $quantity) use (&$sales, $productId): array {
    $cart = $sales->cartResult()['cart'];
    $sales->mutateCart('add', ['product_id'=>$productId,'quantity'=>$quantity,
        'revision'=>$cart['revision'],'mutation_id'=>SalesRegisterStore::id()]);
    $cart = $sales->cartResult()['cart'];
    return $sales->checkout(['revision'=>$cart['revision'],'mutation_id'=>SalesRegisterStore::id(),
        'payment_method'=>'cash','expected_total_minor'=>$cart['total_minor'],'received_minor'=>100000])['order'];
};
$paid = $checkoutCash(2);
$sales->nextCustomer();
$toReturn = $checkoutCash(1);
$sales->registerReturn(['order_id'=>$toReturn['id'],'reason'=>'Test return',
    'idempotency_key'=>'reset-fixture-return-id']);
$sales->nextCustomer();
$cart = $sales->cartResult()['cart'];
$sales->mutateCart('add', ['product_id'=>$productId,'quantity'=>1,
    'revision'=>$cart['revision'],'mutation_id'=>SalesRegisterStore::id()]);
$cart = $sales->cartResult()['cart'];
$pending = $sales->checkout(['revision'=>$cart['revision'],'mutation_id'=>SalesRegisterStore::id(),
    'payment_method'=>'vipps','expected_total_minor'=>$cart['total_minor'],'gateway_id'=>'gateway-a','phone'=>'90000000'])['order'];
$reset = new SalesTestReset($setup, $resetCompany);
salesReject(fn() => $reset->snapshot(), 'Pending payment must block test reset');
$provider->payments['sales-'.$pending['id']]['state'] = 'ABORTED';
$sales->reconcile($pending['id']);
// Leave the restored original cart in place; resetting it must not restore stock twice.
$s = $setup->prepare("INSERT INTO unified_ledger(id,company_id,module_source,order_id,metadata) VALUES(?,?,?,?,?)");
$s->execute(['reset-event-keep', $resetCompany, 'events', 'event-order', '{}']);
$s->execute(['other-ledger-keep', 'b', 'sales', 'other-order', '{}']);
$s = $setup->prepare("INSERT INTO email_queue(id,company_id,module_source,source_id,status) VALUES(?,?,?,?,?)");
$s->execute(['reset-mail', $resetCompany, 'sales', $paid['id'], 'pending']);
$s->execute(['reset-event-mail-keep', $resetCompany, 'events', 'event-order', 'pending']);
$s->execute(['other-mail-keep', 'b', 'sales', 'other-order', 'pending']);
$_SESSION = ['company_id'=>'b','tenant_admin_id'=>'admin-b'];
$other = new SalesService($db, 'b', 'admin-b', 'reset-other', $provider);
salesFixtureRegister($other);
$_SESSION = ['company_id'=>$resetCompany,'tenant_admin_id'=>'admin'];
$unaffected = function() use ($setup, $resetCompany): string {
    $out = [];
    foreach (['sales_registers','sales_register_periods','sales_register_journal','sales_register_documents',
        'sales_cart','unified_ledger','email_queue','dynamic_form_fields','output_products','audit_logs'] as $t) {
        $s = $setup->prepare("SELECT * FROM `$t` WHERE company_id<>? OR ".(
            in_array($t, ['unified_ledger','email_queue'], true) ? "module_source<>'sales'" : '0=1')." ORDER BY id");
        $s->execute([$resetCompany]); $out[$t] = $s->fetchAll(PDO::FETCH_ASSOC);
    }
    return hash('sha256', json_encode($out, JSON_THROW_ON_ERROR));
};
$untouchedBefore = $unaffected();
$before = $reset->snapshot();
salesAssert($reset->snapshot() === $before, 'Read-only preflight must not change any rows or guards');
salesAssert($before['stock_restore'] === [$productId=>2], 'Only paid, unreturned stock must be restored');
salesAssert(SalesTestReset::report($before)['counts']['sales_orders'] === 3, 'Preview covers paid, refunded, aborted orders');
$backupRoot = $temp.'/private-reset-backups'; mkdir($backupRoot, 0700);
salesReject(fn() => $reset->apply($before['fingerprint'], 'WRONG', true, $backupRoot), 'Exact confirmation is mandatory');
salesReject(fn() => $reset->apply($before['fingerprint'], 'RESET TEST SALES '.$resetCompany, false, $backupRoot), 'Offline confirmation is mandatory');
salesReject(fn() => $reset->apply(str_repeat('0',64), 'RESET TEST SALES '.$resetCompany, true, $backupRoot), 'Stale fingerprint must block');
salesReject(fn() => $reset->apply($before['fingerprint'], 'RESET TEST SALES '.$resetCompany, true, $root), 'Webroot backup must be blocked');
salesAssert($reset->snapshot() === $before, 'Rejected operations must not mutate rows/triggers');

// Trigger an actual late DML failure after some rows were deleted. No production hook.
class SalesResetFailStatement extends PDOStatement {
    protected function __construct(private SalesResetFailPDO $owner) {}
    public function execute(?array $params = null): bool {
        if ($this->owner->failReset && str_starts_with($this->queryString, 'DELETE FROM') &&
            str_contains($this->queryString, '`unified_ledger`')) {
            $this->owner->failReset = false;
            throw new RuntimeException('Injected test DML failure');
        }
        return parent::execute($params);
    }
}
class SalesResetFailPDO extends PDO {
    public bool $failReset = false;
}
$failing = new SalesResetFailPDO($dsn, 'root', '', [
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>false,
]);
$failing->setAttribute(PDO::ATTR_STATEMENT_CLASS, [SalesResetFailStatement::class, [$failing]]);
$failingReset = new SalesTestReset($failing, $resetCompany);
$failing->failReset = true;
salesReject(fn() => $failingReset->apply($before['fingerprint'], 'RESET TEST SALES '.$resetCompany, true, $backupRoot),
    'A late DML failure must roll back');
salesAssert($reset->snapshot() === $before, 'Late failure restores all target rows and original trigger definitions');
salesAssert($unaffected() === $untouchedBefore, 'Rollback does not touch other modules or companies');
$result = $reset->apply($before['fingerprint'], 'RESET TEST SALES '.$resetCompany, true, $backupRoot);
$after = $reset->snapshot();
foreach ($after['rows'] as $table => $rows) {
    if (!in_array($table, ['sales_products','sales_registers'], true)) salesAssert(!$rows, "History emptied: $table");
}
salesAssert((int)$after['rows']['sales_products'][0]['stock_quantity'] === 20, 'Net test sales stock restored exactly once');
salesAssert($after['triggers'] === $before['triggers'], 'All twelve original triggers restored exactly');
salesAssert($unaffected() === $untouchedBefore, 'Other firms, other modules and shared histories unchanged');
salesAssert($after['rows']['sales_registers'][0]['status'] === 'draft' &&
    (int)$after['rows']['sales_registers'][0]['last_journal_number'] === 0, 'Register identity retained, counters reset to draft');
$private = $result['backup_directory'];
salesAssert((fileperms($private.'/snapshot.json') & 0777) === 0600, 'Private row backup permissions');
salesAssert(json_decode(file_get_contents($private.'/snapshot.json'), true, 512, JSON_THROW_ON_ERROR) === $before, 'Backup contains exact snapshot');
salesAssert(json_decode(file_get_contents($private.'/status.json'), true)['phase'] === 'committed', 'Successful completion recorded');
salesAssert(str_contains(file_get_contents($private.'/restore-delete-guards.sql'), 'CREATE'), 'Original guard recovery script exists');
salesReject(fn() => new SalesTestReset($setup, 'bad-id'), 'Reject invalid company');
salesReject(fn() => (new SalesTestReset($setup, '99999999-2222-4333-a444-555555555555'))->snapshot(), 'Reject missing company');
$again = $reset->apply($after['fingerprint'], 'RESET TEST SALES '.$resetCompany, true, $backupRoot);
salesAssert($reset->snapshot() === $after, 'Second reset does not add stock again or alter identity');
// Real application can activate/open the retained register and journal a new sale.
$sales = new SalesService($db, $resetCompany, 'admin', 'fresh-after-reset', $provider);
$reg = $sales->registerState()['registers'][0];
$sales->registerAction('register_activate',['register_id'=>$reg['id'],'idempotency_key'=>'reset-after-activate']);
$sales->registerAction('register_select',['register_id'=>$reg['id']]);
$sales->registerAction('register_open',['register_id'=>$reg['id'],'opening_float_minor'=>0,'idempotency_key'=>'reset-after-opening']);
$next = $checkoutCash(1);
salesAssert((int)$next['register_document']['receipt_number'] === 1, 'Fresh test receipt starts correctly');
$store = new SalesRegisterStore($setup, $resetCompany, 'admin'); $store->verify($reg['id']);
salesReject(fn() => $setup->exec("DELETE FROM sales_register_journal WHERE company_id='$resetCompany'"), 'Journal delete protection active after reset');
salesReject(fn() => $setup->exec("DELETE FROM sales_register_documents WHERE company_id='$resetCompany'"), 'Document delete protection active after reset');
echo "Offline Sales test reset passed: preflight, guards, backups, stock, refund/abort, rollback, isolation, fresh sale.\n";
