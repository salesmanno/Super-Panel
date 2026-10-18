<?php
declare(strict_types=1);
// Disposable socket DB + actual PHP endpoints. Never reads project DB settings.
[$script, $root, $tmp] = $argv;
require_once $root.'/includes/ledger_reporting.php';
require_once $root.'/includes/event_results_helpers.php';
function ledgerCheck(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException('FAIL: '.$message);
}
$socket = $tmp.'/mysql.sock';
$db = new PDO('mysql:unix_socket='.$socket, 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE DATABASE fixture; USE fixture');
$db->exec("CREATE TABLE unified_ledger (
    id VARCHAR(36) PRIMARY KEY,order_id VARCHAR(36),company_id VARCHAR(36),source_id VARCHAR(36),customer_id VARCHAR(36),
    module_source VARCHAR(30),item_name VARCHAR(100),item_type VARCHAR(30),category VARCHAR(30),transaction_type VARCHAR(30),
    selling_price DECIMAL(12,2),cost_price DECIMAL(12,2),quantity INT,payment_method VARCHAR(30),payment_status VARCHAR(30),
    status VARCHAR(30),transaction_date DATETIME,created_at DATETIME,metadata JSON)");
$db->exec("CREATE TABLE economy_configuration_data(company_id VARCHAR(36),gateway_type VARCHAR(20),fee_fixed DECIMAL(12,2),fee_percent DECIMAL(12,2));
    INSERT INTO economy_configuration_data VALUES('company-a','vipps',0,0);
    CREATE TABLE tenant_customers(id VARCHAR(36),company_id VARCHAR(36),first_name VARCHAR(30),last_name VARCHAR(30),email VARCHAR(100),company_name VARCHAR(30),phone VARCHAR(30),customer_code VARCHAR(30),address_street VARCHAR(100),address_city VARCHAR(40),date_of_birth DATE);
    INSERT INTO tenant_customers VALUES('customer-a','company-a','A','A','shared@example.test',NULL,NULL,NULL,NULL,NULL,NULL),('customer-b','company-a','B','B','shared@example.test',NULL,NULL,NULL,NULL,NULL,NULL),('customer-foreign','company-b','Foreign','Customer','shared@example.test',NULL,NULL,NULL,NULL,NULL,NULL);
    CREATE TABLE membership_plans(id VARCHAR(36),company_id VARCHAR(36),name VARCHAR(30),price DECIMAL(12,2),billing_cycle VARCHAR(20));
    INSERT INTO membership_plans VALUES('plan-a','company-a','Plan',100,'monthly');
    CREATE TABLE membership_members(id VARCHAR(36),company_id VARCHAR(36),customer_id VARCHAR(36),membership_plan_id VARCHAR(36),status VARCHAR(30),join_date DATE,expiration_date DATE,auto_renew INT);
    INSERT INTO membership_members VALUES('member-a','company-a','customer-a','plan-a','active',CONCAT(YEAR(CURRENT_DATE),'-01-01'),NULL,0),('member-b','company-a','customer-b','plan-a','active',CONCAT(YEAR(CURRENT_DATE),'-01-01'),NULL,0);
    CREATE TABLE events_eventdata(id VARCHAR(36),company_id VARCHAR(36),title VARCHAR(40),ticket_price DECIMAL(12,2),is_active INT);
    INSERT INTO events_eventdata VALUES('event-a','company-a','Arrangement',100,1);
    CREATE TABLE events_checkin(id VARCHAR(36),event_id VARCHAR(36));
    CREATE TABLE tenant_companies(id VARCHAR(36),name VARCHAR(40),is_active INT);
    INSERT INTO tenant_companies VALUES('company-a','Fixture',1),('company-b','Foreign',1);
    CREATE TABLE tenant_admins(id VARCHAR(36),company_id VARCHAR(36),username VARCHAR(30),role VARCHAR(20),is_active INT,tenant_staff_id VARCHAR(36));
    INSERT INTO tenant_admins VALUES('admin-a','company-a','Fixture','admin',1,NULL),('disabled','company-a','Disabled','admin',0,NULL);
    CREATE TABLE tenant_staff(id VARCHAR(36),company_id VARCHAR(36),first_name VARCHAR(30),last_name VARCHAR(30));
    CREATE TABLE system_modules(id VARCHAR(36),module_key VARCHAR(30),is_active INT);
    INSERT INTO system_modules VALUES('sales','sales',1),('rental','rental',1);
    CREATE TABLE tenant_admin_modules(tenant_admin_id VARCHAR(36),module_id VARCHAR(36));
    INSERT INTO tenant_admin_modules VALUES('admin-a','sales');");
$insert = $db->prepare("INSERT INTO unified_ledger VALUES(" . implode(',', array_fill(0, 16, '?')) . ",NOW(),NOW(),?)");
$rows = [
    ['s1','sales',120,3,'completed','active','income',30],
    ['s2','sales',80,2,'completed','active','income',20],
    ['original-return','sales',50,2,'completed','active','income',10],
    ['return','sales',-50,2,'completed','active','income',-10],
    ['event-paid','events',100,4,'completed','active','income',null],
    ['event-expense','events',0,1,'completed','active','expense',25],
    ['member-paid','membership',60,3,'completed','active','income',null],
    ['member-other','membership',20,1,'completed','active','income',null],
    ['rental-paid','rental',130,1,'completed','active','income',null],
];
foreach (['declined','cancelled','expired','failed','pending','authorized','unknown'] as $status) {
    $rows[] = ['attempt-'.$status,'sales',200,2,$status,'active','income',30];
}
$rows[] = ['member-pending','membership',900,4,'pending','active','income',null];
$rows[] = ['member-cancelled','membership',700,2,'completed','cancelled','income',null];
$rows[] = ['ledger-refunded','sales',600,2,'completed','refunded','income',null];
foreach ($rows as [$id,$module,$price,$qty,$payment,$status,$type,$cost]) {
    $customer = $id === 'member-other' ? 'customer-b' : 'customer-a';
    $source = $module === 'events' ? 'event-a' : $id;
    $insert->execute([$id,$id==='s2'?'s1':$id,'company-a',$source,$customer,$module,$id,'product',null,$type,$price,$cost,$qty,'cash',$payment,$status,'{}']);
}
$insert->execute(['foreign','foreign','company-b','foreign','customer-foreign','membership','Foreign','membership',null,'income',9999,null,1,'cash','completed','active','{}']);
$before = $db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn();
$beforeHash = hash('sha256', serialize($db->query('SELECT * FROM unified_ledger ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)));
foreach ($db->query('SELECT * FROM unified_ledger') as $row) {
    if ($row['module_source'] === 'events') {
        $general = ledger_reporting_amounts($row);
        $event = event_results_ledger_amounts($row);
        ledgerCheck($general['income_amount'] === $event['income'] && $general['expense_amount'] === $event['expense'], 'event and general semantics agree');
    }
}
foreach (['../../x','ul; DROP TABLE x'] as $alias) {
    try { ledger_reporting_paid_sql($alias); throw new LogicException('unsafe alias accepted'); } catch (InvalidArgumentException $e) {}
}

$app = $tmp.'/app';
foreach (['api','config','includes'] as $dir) mkdir($app.'/'.$dir,0700,true);
foreach (['get_economy_data.php','get_membership_income.php','get_membership_income_tracking.php','get_monthly_membership_payments.php','get_event_finances.php','get_event_transactions.php','image_upload.php'] as $api) copy($root.'/api/'.$api,$app.'/api/'.$api);
foreach (['ImageUpload.php','ledger_reporting.php','event_results_helpers.php','entity_form_fields.php'] as $file) symlink($root.'/includes/'.$file,$app.'/includes/'.$file);
file_put_contents($app.'/config/database.php', '<?php function getDBConnection(){return new PDO('.var_export('mysql:unix_socket='.$socket.';dbname=fixture',true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);}');
// Login fixture exists only in the temporary isolated server, never the app.
file_put_contents($app.'/session.php', '<?php session_start(); $_SESSION=[]; if(($_GET["mode"]??"")!=="anonymous"){$_SESSION=["tenant_admin_id"=>($_GET["mode"]??"")==="disabled"?"disabled":"admin-a","company_id"=>"company-a"];} echo "ok";');
file_put_contents($app.'/router.php', '<?php putenv("IMGBB_API_KEY=fixture-only-never-used-for-network"); if(str_starts_with($_SERVER["REQUEST_URI"],"/api/")) chdir(__DIR__."/api"); return false;');
$listener = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
$port = (int)substr(strrchr(stream_socket_get_name($listener,false),':'),1);
fclose($listener);
$log = $tmp.'/php.log';
$process = proc_open([PHP_BINARY,'-d','display_errors=0','-S','127.0.0.1:'.$port,'-t',$app,$app.'/router.php'],
    [0=>['pipe','r'],1=>['file',$log,'a'],2=>['file',$log,'a']],$pipes,$app);
fclose($pipes[0]);
$base = 'http://127.0.0.1:'.$port;
$cookie = '';
function requestFixture(string $path, ?array $post=null, ?string $multipart=null): array {
    global $base,$cookie;
    $ch = curl_init($base.$path);
    curl_setopt_array($ch,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_HEADER=>true,CURLOPT_TIMEOUT=>10,CURLOPT_COOKIE=>$cookie]);
    if ($post!==null) {
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$post);
    }
    $result = curl_exec($ch);
    if ($result===false) throw new RuntimeException(curl_error($ch));
    $headerSize = curl_getinfo($ch,CURLINFO_HEADER_SIZE);
    $status = (int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    $headers = substr($result,0,$headerSize);
    if(preg_match('/Set-Cookie: ([^;]+)/i',$headers,$m))$cookie=$m[1];
    $body = substr($result,$headerSize);
    curl_close($ch);
    return ['code'=>$status,'data'=>json_decode($body,true),'body'=>$body];
}
function apiFixture(string $path): array {
    $response = requestFixture($path);
    ledgerCheck($response['code']===200 && ($response['data']['success']??false)===true, $path.' '.$response['body']);
    return $response['data'];
}
try {
    for($i=0;$i<100;$i++){if(@fsockopen('127.0.0.1',$port,$e,$m,.1))break;usleep(50000);}
    requestFixture('/session.php');
    $overview = apiFixture('/api/get_economy_data.php')['data'];
    ledgerCheck((float)$overview['total_income']===560.0 && (float)$overview['total_expenses']===75.0 && (float)$overview['total_profit']===485.0,'completed-only totals, return reversal and positive-cost expense');
    ledgerCheck($overview['payment_filter']==='completed' && (int)$overview['total']===9 && (int)$overview['transaction_count']===9,'posted default counts');
    foreach (['completed'=>9,'attempts'=>10,'all'=>19] as $filter=>$count) {
        $seen=[];
        for($page=1;$page<=max(1,(int)ceil($count/3));$page++){
            $d=apiFixture('/api/get_economy_data.php?payment_filter='.$filter.'&per_page=3&page='.$page)['data'];
            ledgerCheck((int)$d['total']===$count && (float)$d['total_profit']===485.0,'filter pagination never changes totals');
            foreach($d['transactions'] as $row){
                $seen[]=$row['ledger_id'];
                if(!$row['counts_as_paid'])ledgerCheck($row['profit']===null && (float)$row['income_amount']===0.0 && (float)$row['expense_amount']===0.0,'unposted amount never a profit');
                ledgerCheck($row['ledger_id']!=='foreign','foreign company excluded');
            }
        }
        ledgerCheck(count(array_unique($seen))===$count,'no missing/duplicate entries across pages');
    }
    ledgerCheck(requestFixture('/api/get_economy_data.php?payment_filter=invalid')['code']===400,'invalid filter rejected');
    $membership = apiFixture('/api/get_membership_income.php');
    ledgerCheck((float)$membership['stats']['total_income']===80.0 && (int)$membership['stats']['total_transactions']===2,'membership no pending, inactive or quantity multiplication');
    ledgerCheck(count($membership['transactions'])===2 && (float)$membership['income_by_plan'][0]['plan_income']===60.0,'membership list and plan settled');
    $tracking = apiFixture('/api/get_membership_income_tracking.php');
    ledgerCheck((float)$tracking['summary']['total_paid']===80.0,'tracking only posted line totals');
    $members=array_column($tracking['members'],null,'member_id');
    ledgerCheck((float)$members['member-a']['total_paid']===60.0 && (float)$members['member-b']['total_paid']===20.0,'same email never merges different customers');
    $monthly = apiFixture('/api/get_monthly_membership_payments.php?month='.date('n'));
    ledgerCheck((float)$monthly['summary']['paid']===80.0,'monthly same settled totals');
    foreach(['get_event_finances.php','get_event_transactions.php'] as $endpoint){
        $event=apiFixture('/api/'.$endpoint.'?event_id=event-a');
        ledgerCheck((float)$event['summary']['total_income']===100.0 && (float)$event['summary']['total_expense']===25.0,'event read model preserved');
    }
    $income=apiFixture('/api/get_economy_data.php?view=income')['data'];
    $expenses=apiFixture('/api/get_economy_data.php?view=expense')['data'];
    ledgerCheck((float)$income['total']===560.0 && (float)$expenses['total']===75.0,'overview and income/expense agree');
    $module=apiFixture('/api/get_economy_data.php?view=by_module')['data']['breakdown'];
    ledgerCheck(array_sum(array_column($module,'total_profit'))===485.0,'module totals agree');
    // Actual upload endpoint access paths; never send a real image or call ImgBB.
    $upload=apiFixture('/api/image_upload.php?module=sales');
    ledgerCheck($upload['configured']===true && !isset($upload['key']),'key not exposed');
    ledgerCheck(requestFixture('/api/image_upload.php?module=rental')['code']===403,'module permission enforced');
    ledgerCheck(requestFixture('/api/image_upload.php?module=company')['code']===403,'company settings require unlock');
    $post=['module'=>'sales','company_id'=>'company-a','csrf_token'=>'wrong'];
    ledgerCheck(requestFixture('/api/image_upload.php',$post)['code']===403,'CSRF enforced');
    $post['csrf_token']=$upload['csrf_token'];$post['company_id']='company-b';
    ledgerCheck(requestFixture('/api/image_upload.php',$post)['code']===403,'company scope enforced');
    $post['company_id']='company-a';
    file_put_contents($tmp.'/fake.jpg','not an image');
    $post['image']=new CURLFile($tmp.'/fake.jpg','image/jpeg','fake.jpg');
    ledgerCheck(requestFixture('/api/image_upload.php',$post)['code']===422,'forged file rejected before external call');
    ledgerCheck(requestFixture('/api/image_upload.php',['padding'=>str_repeat('x',12*1024*1024)])['code']===413,'oversized request rejected clearly even if PHP discards multipart fields');
    requestFixture('/session.php?mode=disabled');
    ledgerCheck(requestFixture('/api/image_upload.php?module=sales')['code']===403,'inactive administrator rejected');
    requestFixture('/session.php?mode=anonymous');
    ledgerCheck(requestFixture('/api/image_upload.php?module=sales')['code']===401,'anonymous rejected');
    ledgerCheck($db->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn()===$before,'no history deleted or modified');
    ledgerCheck(hash('sha256', serialize($db->query('SELECT * FROM unified_ledger ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)))===$beforeHash,'every historical field remains unchanged');
    echo "Ledger MySQL/API tests passed: all statuses, line totals, refunds, expenses, retries, filters/pagination, company scope, membership and event consistency; image endpoint access/CSRF/file validation.\n";
} finally {
    proc_terminate($process);proc_close($process);
}
