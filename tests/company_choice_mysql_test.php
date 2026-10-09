<?php
declare(strict_types=1);
[$script,$root,$temp]=$argv;
if (!str_starts_with($temp,'/tmp/rental-unavailable-mysql.')) throw new RuntimeException('Disposable database required');
function check(bool $ok,string $message):void { if(!$ok) throw new RuntimeException($message); }
$dsn='mysql:unix_socket='.$temp.'/mysql.sock;dbname=choice';
$db=new PDO('mysql:unix_socket='.$temp.'/mysql.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE DATABASE choice');$db->exec('USE choice');
$schema=[
"tenant_companies(id CHAR(36) PRIMARY KEY,name TEXT,subdomain TEXT,logo_url TEXT,is_active INT)",
"tenant_staff(id CHAR(36) PRIMARY KEY,company_id CHAR(36),first_name TEXT,last_name TEXT,email TEXT,is_active INT)",
"tenant_admins(id CHAR(36) PRIMARY KEY,company_id CHAR(36) NOT NULL,tenant_staff_id CHAR(36),username VARCHAR(100),password_hash TEXT,role TEXT,is_active INT,last_login DATETIME)",
"tenant_admin_sessions(id INT AUTO_INCREMENT PRIMARY KEY,tenant_admin_id CHAR(36),session_id VARCHAR(255),is_active INT,ip_address TEXT,user_agent TEXT,login_at DATETIME,last_activity DATETIME,logout_at DATETIME)",
"system_modules(id CHAR(36),module_key TEXT,name TEXT,description TEXT,icon TEXT,is_active INT,created_at DATETIME)",
"tenant_admin_modules(tenant_admin_id CHAR(36),module_id CHAR(36))",
"tenant_company_modules(company_id CHAR(36),module_id CHAR(36),is_active INT)"
];
foreach($schema as $sql)$db->exec('CREATE TABLE '.$sql.' ENGINE=InnoDB');
$db->exec("INSERT INTO tenant_companies VALUES('a','Firma A','a',NULL,1),('b','Firma B','b','/logo.png',1),('off','Avslått','off',NULL,0)");
$db->exec("INSERT INTO tenant_staff VALUES('s','a','Ansatt','Test','staff@example.invalid',1),('bad','a','Inaktiv','Test',NULL,0)");
$q=$db->prepare('INSERT INTO tenant_admins(id,company_id,tenant_staff_id,username,password_hash,role,is_active) VALUES(?,?,?,?,?,?,?)');
foreach([['free','a',null,1],['staff','a','s',1],['bad','a','bad',1],['broken','a','missing',1],['inactive','a',null,0],['old','off',null,1]] as [$id,$c,$s,$active])
    $q->execute([$id,$c,$s,$id,password_hash('fixture-password',PASSWORD_DEFAULT),'owner',$active]);
$db->exec("INSERT INTO system_modules VALUES('r','rental','Rental',NULL,NULL,1,NOW()),('e','events','Events',NULL,NULL,1,NOW())");
$db->exec("INSERT INTO tenant_admin_modules VALUES('free','r'),('free','e'),('staff','r')");
$db->exec("INSERT INTO tenant_company_modules VALUES('a','r',1),('a','e',1),('b','r',0),('b','e',1)");
foreach(['app','app/auth','app/api','app/includes','app/config','app/pages'] as $dir)mkdir($temp.'/'.$dir);
// Copy includes: symlinking functions.php would resolve its config in the real project.
foreach(glob($root.'/includes/*.php') as $f)copy($f,$temp.'/app/includes/'.basename($f));
foreach(['auth/company_auth.php','auth/logout.php','api/company_choice.php','api/refresh_session.php','pages/login_transition.php'] as $f)copy($root.'/'.$f,$temp.'/app/'.$f);
foreach(['add_to_cart','checkout_cart','process_service_sale','get_cart','get_purchase_history','get_invoices','get_renewal_stats'] as $endpoint) {
    copy($root.'/api/'.$endpoint.'.php',$temp.'/app/api/'.$endpoint.'.php');
}
file_put_contents($temp.'/app/config/database.php','<?php require_once __DIR__."/../includes/AuditedPDO.php"; function getDBType(){return "mysql";} function getDBConnection(){static $db;return $db??=new AuditedPDO('.var_export($dsn,true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);}');
$probe='<?php session_start(); require __DIR__."/../includes/functions.php";$db=getDBConnection();if(empty($_SESSION["tenant_admin_id"])){http_response_code(401);exit;}header("Content-Type: application/json");echo json_encode(["company"=>$_SESSION["company_id"],"role"=>$_SESSION["admin_role"],"name"=>$_SESSION["admin_name"],"context"=>$_SESSION["company_context"],"modules"=>array_column(getTenantAdminModules($_SESSION["tenant_admin_id"]),"module_key")]);';
file_put_contents($temp.'/app/api/probe.php',$probe);file_put_contents($temp.'/app/api/get_rental_test.php',$probe);
file_put_contents($temp.'/app/api/get_reservations_test.php',$probe);
file_put_contents($temp.'/app/api/get_cart_probe.php',$probe);
$sock=stream_socket_server('tcp://127.0.0.1:0');$address=stream_socket_get_name($sock,false);fclose($sock);
$server=proc_open([PHP_BINARY,'-d','session.save_path='.$temp.'/sessions','-S',$address,'-t',$temp.'/app'],[0=>['file','/dev/null','r'],1=>['file',$temp.'/http.log','a'],2=>['file',$temp.'/http.log','a']],$pipes);
$cookies=[];
$request=function(string $path,string $browser='one',?array $data=null,bool $json=false,?string $context=null)use($address,&$cookies):array{
    $headers="Cookie: PHPSESSID=".($cookies[$browser]??'')."\r\n";
    if($data!==null)$headers.='Content-Type: '.($json?'application/json':'application/x-www-form-urlencoded')."\r\n";
    if($context!==null)$headers.="X-Company-Context: ".$context."\r\n";
    $body=file_get_contents('http://'.$address.$path,false,stream_context_create(['http'=>['method'=>$data===null?'GET':'POST','ignore_errors'=>true,'follow_location'=>0,'header'=>$headers,'content'=>$data===null?'':($json?json_encode($data):http_build_query($data))]]));
    $status=(int)explode(' ',$http_response_header[0])[1];$location='';
    foreach($http_response_header as $h){if(preg_match('/Set-Cookie: PHPSESSID=([^;]+)/i',$h,$m))$cookies[$browser]=$m[1];if(str_starts_with($h,'Location:'))$location=trim(substr($h,9));}
    return [$status,json_decode($body,true),$location,$body];
};
$login=fn($name='free',$browser='one',$password='fixture-password')=>$request('/auth/company_auth.php',$browser,['username'=>$name,'password'=>$password]);
$choose=function(string $company,string $browser='one')use($request):array{
    $list=$request('/api/company_choice.php',$browser);
    return $request('/api/company_choice.php',$browser,['company_id'=>$company,'csrf_token'=>$list[1]['csrf_token']??''],true);
};
try{
    for($i=0;$i<100;$i++){if(@fsockopen('127.0.0.1',(int)explode(':',$address)[1]))break;usleep(20000);}
    check(str_contains($login()[2],'setup_required'),'Schema prerequisite is explicit');
    check($db->query("SELECT company_id FROM tenant_admins WHERE id='free'")->fetchColumn()==='a','Missing migration does not mutate account');
    $migration=file_get_contents($root.'/db_migrations/tenant_admin_company_optional.sql');
    $db->exec($migration);$db->exec($migration);
    check(str_contains($login('free','one','wrong')[2],'invalid_credentials'),'Wrong password rejected');
    check($db->query("SELECT company_id FROM tenant_admins WHERE id='free'")->fetchColumn()==='a','Wrong password preserves company');
    check($login()[2]==='/pages/login_transition.php','Verified login enters choice');
    check($db->query("SELECT company_id FROM tenant_admins WHERE id='free'")->fetchColumn()===null,'Verified login clears stored company');
    check($request('/api/probe.php')[0]===401,'Partial session cannot query company API');
    $list=$request('/api/company_choice.php')[1];
    check(array_column($list['companies'],'id')===['a','b'],'All and only active companies offered');
    check($request('/api/company_choice.php','one',['company_id'=>'b','csrf_token'=>'bad'],true)[0]===403,'CSRF enforced');
    check(str_contains($login('free','other')[2],'already_logged_in'),'Pending session prevents parallel login');
    check($login()[2]==='/pages/login_transition.php','Abandoned choice can restart in same browser');
    check($choose('off')[0]===409,'Inactive company denied');
    check($choose('b')[0]===200,'Active company saved');
    $state=$request('/api/probe.php')[1];
    check($state['company']==='b'&&$state['role']==='owner'&&$state['name']==='free','Full identity follows selected company without old staff');
    check($state['modules']===['events'],'User rights intersect selected company modules');
    check($request('/api/get_rental_test.php')[0]===403,'Disabled company module rejected server side');
    foreach(['add_to_cart','checkout_cart','process_service_sale','get_cart','get_purchase_history','get_invoices','get_renewal_stats'] as $endpoint) {
        check($request('/api/'.$endpoint.'.php','one',['output_product_id'=>'fixture','quantity'=>1],true,$state['context'])[0]===403,
            'Real endpoint rejects unlicensed access: '.$endpoint);
    }
    $db->exec("INSERT INTO system_modules VALUES('sale','sales','Sales',NULL,NULL,1,NOW())");
    $db->exec("INSERT INTO tenant_company_modules VALUES('b','sale',1)");
    check($request('/api/get_cart_probe.php')[0]===403,'Company license alone does not grant sales');
    $db->exec("INSERT INTO tenant_admin_modules VALUES('free','sale')");
    check($request('/api/get_cart_probe.php')[0]===200,'Assigned user with company sales license is allowed');
    $db->exec("UPDATE tenant_company_modules SET is_active=0 WHERE company_id='b' AND module_id='sale'");
    check($request('/api/get_cart_probe.php')[0]===403,'Revoked sales license overrides user assignment');
    check($request('/api/get_reservations_test.php')[0]===403,'Unassigned reservation module denied');
    $db->exec("INSERT INTO system_modules VALUES('v','reservation','Reservations',NULL,NULL,1,NOW())");
    $db->exec("INSERT INTO tenant_admin_modules VALUES('free','v')");
    $db->exec("INSERT INTO tenant_company_modules VALUES('b','v',1)");
    check($request('/api/get_reservations_test.php')[0]===200,'Singular reservation module key grants licensed access');
    $db->exec("UPDATE tenant_company_modules SET is_active=0 WHERE company_id='b' AND module_id='v'");
    check($request('/api/get_reservations_test.php')[0]===403,'Revoked reservation license denied immediately');
    check($request('/api/refresh_session.php')[1]['success']===true,'Unlinked account can refresh session');
    $refreshed=$request('/api/probe.php')[1];
    check($refreshed['company']==='b' && $refreshed['name']==='free' && $refreshed['context']===$state['context'],'Refresh preserves chosen company and context');
    check($request('/api/probe.php','one',[],true,'old-context')[0]===409,'Old tab writes rejected');
    check($request('/api/probe.php','one',[],true,$state['context'])[0]===200,'Current context can write');
    check($request('/api/probe.php','one',[],true)[0]===409,'Missing context cannot write');
    $request('/auth/logout.php');
    check((int)$db->query("SELECT COUNT(*) FROM tenant_admin_sessions WHERE tenant_admin_id='free' AND is_active=1")->fetchColumn()===0,
        'Logout deactivates registry without optional admin last_logout column');
    check($login()[2]==='/pages/login_transition.php'&&$choose('a')[0]===200,'New login requires fresh choice');
    check($request('/api/probe.php','one',[],true,$state['context'])[0]===409,'Prior company context remains invalid after relogin');
    $request('/auth/logout.php');
    check($login('staff')[2]==='/pages/login_transition.php','Employee login remains direct');
    check($request('/api/probe.php')[1]['company']==='a','Employee retains binding');
    check($request('/api/company_choice.php')[0]===401,'Employee cannot list companies');
    $request('/auth/logout.php');
    foreach(['bad','broken','inactive'] as $name)check($login($name)[2]!=='/pages/login_transition.php','Invalid linked or inactive account denied');
    check($login('old')[2]==='/pages/login_transition.php','Inactive old company does not block unlinked account');
    $request('/auth/logout.php');
    $login();$db->exec("UPDATE tenant_admins SET tenant_staff_id='s' WHERE id='free'");
    check($choose('b')[0]===401,'New employee link invalidates pending choice');
    $request('/auth/logout.php');$db->exec("UPDATE tenant_admins SET tenant_staff_id=NULL WHERE id='free'");
    $login();$db->exec("UPDATE tenant_admins SET is_active=0 WHERE id='free'");
    check($request('/api/company_choice.php')[0]===401,'Deactivation invalidates partial session');
    $request('/auth/logout.php');$db->exec("UPDATE tenant_admins SET is_active=1 WHERE id='free'");
    $login();$db->exec("UPDATE tenant_admin_sessions SET last_activity=DATE_SUB(NOW(),INTERVAL 31 MINUTE)");
    check($request('/api/company_choice.php')[0]===401,'Expired session cannot choose');
    check($login()[2]==='/pages/login_transition.php','Expired partial login can restart');
    $db->exec("UPDATE tenant_companies SET is_active=0");
    check($request('/api/company_choice.php')[1]['companies']===[],'Empty list supported');
    $db->exec("UPDATE tenant_companies SET is_active=1 WHERE id='a'");
    check($choose('a')[0]===200,'Choice recovers when company available');
    $db->exec("UPDATE tenant_admin_sessions SET is_active=0");
    check($request('/api/probe.php')[0]===401,'Revoked full session blocked at legacy endpoint');
    // Independent servers/processes: PHP's per-session lock must not hide the
    // race between different browsers authenticating the same administrator.
    $socket2=stream_socket_server('tcp://127.0.0.1:0');$address2=stream_socket_get_name($socket2,false);fclose($socket2);
    $server2=proc_open([PHP_BINARY,'-d','session.save_path='.$temp.'/sessions','-S',$address2,'-t',$temp.'/app'],
        [0=>['file','/dev/null','r'],1=>['file',$temp.'/http2.log','a'],2=>['file',$temp.'/http2.log','a']],$unused);
    $workers=[];$wp=[];
    try {
        for($i=0;$i<100;$i++){if(@fsockopen('127.0.0.1',(int)explode(':',$address2)[1]))break;usleep(20000);}
        $key='company-login:'.substr(hash('sha256','free'),0,40);
        $lock=$db->prepare('SELECT GET_LOCK(?,5)');$lock->execute([$key]);check((int)$lock->fetchColumn()===1,'Hold request lock');
        file_put_contents($temp.'/login-worker.php','<?php file_get_contents($argv[1]."/auth/company_auth.php",false,stream_context_create(["http"=>["method"=>"POST","follow_location"=>0,"header"=>"Content-Type: application/x-www-form-urlencoded","content"=>"username=free&password=fixture-password"]])); foreach($http_response_header as $h)if(str_starts_with($h,"Location:"))echo $h;');
        foreach([$address,$address2] as $i=>$host)$workers[$i]=proc_open([PHP_BINARY,$temp.'/login-worker.php','http://'.$host],
            [0=>['file','/dev/null','r'],1=>['pipe','w'],2=>['file',$temp.'/worker.log','a']],$wp[$i]);
        $deadline=microtime(true)+3;
        do {
            $waiting=(int)$db->query("SELECT COUNT(*) FROM information_schema.PROCESSLIST WHERE INFO LIKE 'SELECT GET_LOCK(%' AND ID<>CONNECTION_ID()")->fetchColumn();
            if($waiting===2)break;usleep(10000);
        }while(microtime(true)<$deadline);
        check($waiting===2,'Both logins wait for prior request');
        check($db->query("SELECT company_id FROM tenant_admins WHERE id='free'")->fetchColumn()==='a','In-flight prior request prevents early company change');
        $db->prepare('SELECT RELEASE_LOCK(?)')->execute([$key]);
        $locations=[];
        foreach($workers as $i=>$worker){$locations[]=stream_get_contents($wp[$i][1]);fclose($wp[$i][1]);proc_close($worker);}
        $workers=[];
        check(count(array_filter($locations,fn($s)=>str_contains($s,'login_transition')))===1
            &&count(array_filter($locations,fn($s)=>str_contains($s,'already_logged_in')))===1,'Exactly one simultaneous login succeeds');
    }finally {
        foreach($workers as $worker)if(is_resource($worker)){proc_terminate($worker);proc_close($worker);}
        proc_terminate($server2);proc_close($server2);
    }
    echo "Company choice: isolated HTTP/MySQL authentication, migration, context, modules, revocation and expiry passed.\n";
}catch(Throwable $e){fwrite(STDERR,file_get_contents($temp.'/http.log'));throw $e;}
finally{proc_terminate($server);proc_close($server);}
