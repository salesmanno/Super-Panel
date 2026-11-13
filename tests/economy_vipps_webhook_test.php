<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/EconomyVippsWebhookSetup.php';
require_once __DIR__.'/../includes/economy_vipps_access.php';

function whCheck(bool $value, string $message): void {
    if (!$value) throw new RuntimeException($message);
}
class WebhookTestDb extends PDO {
    public bool $lockAvailable = true;
    public function __construct() {
        parent::__construct('sqlite::memory:');
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->sqliteCreateFunction('GET_LOCK', fn()=> $this->lockAvailable ? 1 : 0, 2);
        $this->sqliteCreateFunction('RELEASE_LOCK', fn()=>1, 1);
    }
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return parent::prepare(str_replace(' FOR UPDATE', '', $query), $options);
    }
}
class WebhookTestClient extends VippsWebhookClient {
    public array $webhooks = [];
    public int $posts = 0;
    public bool $ready = true;
    public bool $timeout = false;
    public function webhookRequest(array $config, string $company, string $method, ?array $body = null): array {
        $this->selected($config, $company);
        if ($method === 'GET') return ['code'=>200,'data'=>['webhooks'=>$this->webhooks]];
        whCheck($method === 'POST', 'No deletion, capture or payment initiation allowed');
        ++$this->posts;
        $this->webhooks[] = ['id'=>'registered-1'] + $body;
        if ($this->timeout) throw new RuntimeException('Fixture timeout');
        return ['code'=>201,'data'=>['id'=>'registered-1','secret'=>'fixture-webhook-secret']];
    }
    public function receiverReady(array $config, string $company, string $url, array $record): bool {
        return $this->ready;
    }
}
function fixture(): array {
    $db = new WebhookTestDb();
    $db->exec("CREATE TABLE tenant_companies(id TEXT PRIMARY KEY, subdomain TEXT,is_active INT);
        INSERT INTO tenant_companies VALUES('company-a','fixture',1),('company-b','other',1);
        CREATE TABLE economy_configuration_data(id TEXT PRIMARY KEY,company_id TEXT,gateway_type TEXT,
        test_mode INT,is_active INT,client_id TEXT,client_secret_encrypted TEXT,subscription_key TEXT,
        merchant_serial_number TEXT,test_client_id TEXT,test_client_secret TEXT,test_subscription_key TEXT,
        test_merchant_serial_number TEXT,config_data TEXT,updated_at TEXT);
        INSERT INTO economy_configuration_data VALUES('config-a','company-a','vipps',0,0,'prod-id','prod-secret',
        'prod-sub','prod-msn','test-id','test-secret','test-sub','test-msn','{\"unrelated\":{\"keep\":true}}',NULL);
        CREATE TABLE economy_payment_gateways(id TEXT,company_id TEXT,configuration_id TEXT,gateway_name TEXT,
        is_active INT,updated_at TEXT);
        INSERT INTO economy_payment_gateways VALUES('gateway-a','company-a','config-a','Vipps',0,NULL)");
    $client = new WebhookTestClient();
    return [$db,$client,new EconomyVippsWebhookSetup($db,$client)];
}
[$db,$client,$service] = fixture();
$before = $db->query('SELECT config_data FROM economy_configuration_data')->fetchColumn();
$status = $service->status('company-a','config-a');
whCheck($status['state']==='not_registered' && $client->posts===0, 'GET never registers');
whCheck($before===$db->query('SELECT config_data FROM economy_configuration_data')->fetchColumn(), 'GET does not write storage');
$client->ready = false;
$state = $service->activate('company-a','config-a');
whCheck($state['state']==='attention' && !$state['is_active'] && $client->posts===0, 'Receiver prerequisite fails closed');
$client->ready = true;
$state = $service->activate('company-a','config-a');
whCheck($state['state']==='registered' && $state['is_active'] && !$state['delivery_verified'], 'Activation distinguishes registration and delivery');
whCheck((int)$db->query('SELECT is_active FROM economy_payment_gateways')->fetchColumn()===1, 'Both activation records updated');
$config = $service->load('company-a','config-a');
$doc = VippsWebhookRegistry::document($config);
whCheck($doc['unrelated']['keep']===true, 'Unrelated JSON is preserved');
whCheck($doc[VippsWebhookRegistry::KEY]['production']['secret']==='fixture-webhook-secret', 'Secret stored only server side');
whCheck(!str_contains(json_encode($state), 'fixture-webhook-secret') && !str_contains(json_encode($state), 'prod-secret'), 'Public status is secret-free');
$service->activate('company-a','config-a');
whCheck($client->posts===1, 'Repeated activation cannot duplicate the webhook');
$service->deactivate('company-a','config-a');
whCheck((int)$db->query('SELECT is_active FROM economy_configuration_data')->fetchColumn()===0
    && count($client->webhooks)===1, 'Off retains subscriptions and does not call provider');
whCheck(VippsWebhookRegistry::record($service->load('company-a','config-a'))['secret']==='fixture-webhook-secret', 'Off retains signing secret');
try {$service->load('company-b','config-a'); throw new LogicException('Should reject other company');}
catch(RuntimeException $e) {whCheck(!$e instanceof LogicException, 'Company isolation');}
$db->lockAvailable = false;
try {$service->activate('company-a','config-a'); throw new LogicException('Should reject concurrent activation');}
catch(RuntimeException $e) {whCheck(!$e instanceof LogicException, 'Concurrent activation rejected');}
$db->lockAvailable = true;

[$uncertainDb,$uncertainClient,$uncertain] = fixture();
$uncertainClient->timeout = true;
try {$uncertain->activate('company-a','config-a');} catch(RuntimeException $e) {}
$state = $uncertain->activate('company-a','config-a');
whCheck($uncertainClient->posts===1 && $state['state']==='attention', 'Timeout never triggers a second registration');
whCheck(!(bool)$uncertainDb->query('SELECT is_active FROM economy_configuration_data')->fetchColumn(), 'Uncertain registration remains inactive');

[$lostDb,$lostClient,$lost] = fixture();
$lostClient->webhooks = [['id'=>'unknown','url'=>'https://fixture.salesman.no/api/vipps_callback.php','events'=>VippsWebhookRegistry::EVENTS]];
whCheck($lost->activate('company-a','config-a')['state']==='attention' && $lostClient->posts===0, 'Existing webhook without secret is not duplicated');

[$testDb,$testClient,$testService] = fixture();
$testDb->exec('UPDATE economy_configuration_data SET test_mode=1');
$testConfig = $testService->load('company-a','config-a');
$selected = $testClient->selected($testConfig,'company-a');
whCheck($selected['client_id']==='test-id' && $selected['merchant_serial_number']==='test-msn', 'Test uses only test credentials');
$invalid = $testConfig; $invalid['test_client_secret']='';
try {$testClient->selected($invalid,'company-a'); throw new LogicException('Should not fall back to prod');}
catch(RuntimeException $e) {whCheck(!$e instanceof LogicException, 'Missing test keys never fall back to production');}
whCheck(VippsWebhookRegistry::record($testConfig)===[], 'No production secret fallback in test');
foreach ([null, '', 'wrong'] as $token) {
    try {economyVippsCsrf(['economy_bank_csrf'=>'fixture-csrf'],$token);throw new LogicException('CSRF should fail');}
    catch(RuntimeException $e) {whCheck(!$e instanceof LogicException, 'Missing/wrong CSRF rejected');}
}
economyVippsCsrf(['economy_bank_csrf'=>'fixture-csrf'],'fixture-csrf');
try {economyVippsAccess($db,[]); throw new LogicException('Missing authentication should fail');}
catch(RuntimeException $e) {whCheck($e->getCode()===401,'Missing authentication rejected');}

// The real shared receiver helper validates body hash, signature, timestamp, company and environment.
$db->exec('UPDATE economy_configuration_data SET is_active=1');
$config = $service->load('company-a','config-a');
$challenge = str_repeat('a',48);
$url = VippsWebhookRegistry::callback($config);
$server = ['REQUEST_METHOD'=>'GET','HTTP_HOST'=>'fixture.salesman.no','REQUEST_URI'=>'/api/vipps_callback.php',
    'HTTP_X_SALESMAN_SETUP_AUTHORIZATION'=>VippsWebhookRegistry::proof('request',$challenge,$config['id'],'production',$url,'','prod-secret')];
$query = ['salesman_webhook_probe'=>'1','configuration_id'=>'config-a','environment'=>'production','challenge'=>$challenge];
$proof = VippsWebhookRegistry::receiverProbe($db,$config,$query,$server);
whCheck(hash_equals($proof['proof'],VippsWebhookRegistry::proof('response',$challenge,'config-a','production',$url,
    VippsWebhookRegistry::digest(VippsWebhookRegistry::record($config)),'prod-secret')), 'Receiver proves shared storage read');
$body = '{"reference":"fixture-reference","msn":"prod-msn"}';
$date = gmdate('D, d M Y H:i:s').' GMT';
$hash = base64_encode(hash('sha256',$body,true));
$post = ['REQUEST_METHOD'=>'POST','HTTP_HOST'=>'fixture.salesman.no','REQUEST_URI'=>'/api/vipps_callback.php',
    'HTTP_X_MS_DATE'=>$date,'HTTP_X_MS_CONTENT_SHA256'=>$hash,
    'HTTP_AUTHORIZATION'=>'HMAC-SHA256 SignedHeaders=x-ms-date;host;x-ms-content-sha256&Signature='.
        base64_encode(hash_hmac('sha256',"POST\n/api/vipps_callback.php\n$date;fixture.salesman.no;$hash",'fixture-webhook-secret',true))];
whCheck(VippsWebhookRegistry::verifyDelivery($config,'company-a',$body,$post), 'Shared signing secret validates genuine delivery');
whCheck(!VippsWebhookRegistry::verifyDelivery($config,'company-b',$body,$post), 'Other company rejected');
whCheck(!VippsWebhookRegistry::verifyDelivery($config,'company-a',$body.' ',$post), 'Tampered body rejected');
$post['HTTP_AUTHORIZATION']='invalid';
whCheck(!VippsWebhookRegistry::verifyDelivery($config,'company-a',$body,$post), 'Invalid signature rejected');
echo "Economy Vipps activation, shared storage, isolation, CSRF, signing and uncertain-retry checks passed\n";
