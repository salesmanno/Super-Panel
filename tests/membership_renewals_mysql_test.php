<?php
declare(strict_types=1);
$root=$argv[1]; $temp=$argv[2];
if (!str_starts_with($temp,sys_get_temp_dir().'/rental-unavailable-mysql.') || !file_exists($temp.'/mysql.sock')) throw new RuntimeException('Disposable database required');
$base='mysql:unix_socket='.$temp.'/mysql.sock';
$db=new PDO($base,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE DATABASE renewal_fixture CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci');
$dsn=$base.';dbname=renewal_fixture;charset=utf8mb4';
$db=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
foreach ([
    'CREATE TABLE membership_members(id VARCHAR(36), company_id VARCHAR(36), customer_id VARCHAR(36),membership_plan_id VARCHAR(36),member_code VARCHAR(30), payment_status VARCHAR(30),expiration_date DATE,kontrollert INT,status VARCHAR(30))',
    'CREATE TABLE membership_members_renew(id VARCHAR(36),company_id VARCHAR(36),membership_member_id VARCHAR(36),membership_plan_id VARCHAR(36),order_id VARCHAR(40),amount DECIMAL(12,2),status VARCHAR(30),created_at DATETIME,completed_at DATETIME,payment_method VARCHAR(36))',
    'CREATE TABLE tenant_customers(id VARCHAR(36),company_id VARCHAR(36),first_name VARCHAR(100),last_name VARCHAR(100))',
    'CREATE TABLE membership_plans(id VARCHAR(36),company_id VARCHAR(36),name VARCHAR(100))',
    "INSERT INTO tenant_customers VALUES('c','a','Anna','Nord'),('c','b','Other','Tenant')",
    "INSERT INTO membership_plans VALUES('p','a','Årsmedlem'),('p','b','Wrong plan')",
    "INSERT INTO membership_members VALUES('m','a','c','p','MEM-A','paid','2099-01-01',1,'active'),('m','b','c','p','MEM-B','paid','2099-01-01',0,'active')",
] as $sql) $db->exec($sql);
$insert=$db->prepare("INSERT INTO membership_members_renew VALUES(?,'a','m','p',?,100,'completed','2026-01-01',NULL,'cash')");
for ($i=1;$i<=13;$i++) $insert->execute([sprintf('r%02d',$i),sprintf('ORDER-%02d',$i)]);
$db->exec("INSERT INTO membership_members_renew VALUES('foreign','b','m','p','FOREIGN',1000,'completed','2026-01-01',NULL,'cash')");
$dir=$temp.'/renewal-http';mkdir($dir);
foreach (['get_membership_renewals','get_renewal_stats'] as $endpoint) {
    $source=file_get_contents("$root/api/$endpoint.php");
    $source=str_replace('session_start();','session_save_path('.var_export($temp.'/sessions',true).'); session_start(); $_SESSION["tenant_admin_id"]="admin"; $_SESSION["company_id"]="a";',$source);
    $source=str_replace("require_once __DIR__ . '/../config/database.php';",
        'function getDBConnection(){return new PDO('.var_export($dsn,true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);}',$source);
    file_put_contents("$dir/$endpoint.php",$source);
}
$sock=stream_socket_server('tcp://127.0.0.1:0');$port=(int)substr(strrchr(stream_socket_get_name($sock,false),':'),1);fclose($sock);
$worker=proc_open([PHP_BINARY,'-S',"127.0.0.1:$port",'-t',$dir],[0=>['file','/dev/null','r'],1=>['file',$temp.'/renewals.log','a'],2=>['file',$temp.'/renewals.log','a']],$pipes);
function renewalAssert(bool $ok,string $label): void {if (!$ok) throw new RuntimeException($label); echo "PASS $label\n";}
function renewalGet(string $endpoint): array {
    global $port;
    return json_decode(file_get_contents("http://127.0.0.1:$port/$endpoint"),true,512,JSON_THROW_ON_ERROR);
}
try {
    for ($i=0;$i<50;$i++) {if($s=@fsockopen('127.0.0.1',$port)){fclose($s);break;}usleep(50000);}
    $list=renewalGet('get_membership_renewals.php');
    renewalAssert($list['success'] && $list['total']===13 && count($list['rows'])===10,'Real renewal endpoint loads first page with native prepares');
    renewalAssert(count(array_unique(array_column($list['rows'],'member_name')))===1 && $list['rows'][0]['member_name']==='Anna Nord','Customer and member joins cannot leak another company');
    renewalAssert($list['rows'][0]['plan_name']==='Årsmedlem','Plan belongs to correct company');
    $page=renewalGet('get_membership_renewals.php?page=2');
    renewalAssert(count($page['rows'])===3 && !array_intersect(array_column($list['rows'],'id'),array_column($page['rows'],'id')),'Stable ordering paginates equal timestamps without repeats');
    $search=renewalGet('get_membership_renewals.php?search=ORDER-13');
    renewalAssert($search['total']===1 && $search['rows'][0]['order_id']==='ORDER-13','Order search works');
    $lookup=renewalGet('get_membership_renewals.php?lookup=MEM-A');
    renewalAssert($lookup['success'] && $lookup['row']['lookup_type']==='member','Exact membership-code lookup preserved');
    $stats=renewalGet('get_renewal_stats.php');
    renewalAssert($stats['success'] && $stats['sum_completed']==1300 && $stats['count_renewed']===13 && $stats['count_valid']===13,'Statistics isolated from other company with same relation IDs');
    $db->exec("DELETE FROM membership_members_renew WHERE company_id='a'");
    $empty=renewalGet('get_membership_renewals.php');
    renewalAssert($empty['success'] && $empty['total']===0 && $empty['rows']===[],'Empty list is a successful empty state');
} finally {proc_terminate($worker);proc_close($worker);}
