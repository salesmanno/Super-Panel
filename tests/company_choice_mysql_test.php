<?php
declare(strict_types=1);
[$script,$root,$temp]=$argv;
if (!str_starts_with($temp,'/tmp/rental-unavailable-mysql.')) throw new RuntimeException('Disposable database required');
function check(bool $ok,string $message):void { if(!$ok) throw new RuntimeException($message); }
// The production server rejects FOR SHARE even though the isolated server accepts it.
$loginSource=file_get_contents($root.'/auth/company_auth.php');
check(!preg_match('/\bFOR\s+SHARE\b/i',$loginSource),'Login must not require unsupported production lock syntax');
check(substr_count($loginSource,'LOCK IN SHARE MODE')===2,'Employee and company locks use compatible shared-lock syntax');
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
foreach(['app','app/auth','app/api','app/includes','app/config','app/pages','app/assets'] as $dir)mkdir($temp.'/'.$dir);
foreach(['company_context.js','company_session.js'] as $asset)copy($root.'/assets/'.$asset,$temp.'/app/assets/'.$asset);
// Copy includes: symlinking functions.php would resolve its config in the real project.
foreach(glob($root.'/includes/*.php') as $f)copy($f,$temp.'/app/includes/'.basename($f));
foreach(['index.php','pages/company_login.php','auth/company_auth.php','auth/logout.php','api/company_choice.php','api/refresh_session.php','api/company_session.php','pages/login_transition.php'] as $f)copy($root.'/'.$f,$temp.'/app/'.$f);
// Use the actual dashboard authentication preamble, without loading business UI/data.
$dashboardSource=file_get_contents($root.'/pages/company_dashboard.php');
$dashboardBoundary=strpos($dashboardSource,'$auditVisible = false;');
check($dashboardBoundary!==false,'Dashboard authentication preamble found');
file_put_contents($temp.'/app/pages/company_dashboard.php',substr($dashboardSource,0,$dashboardBoundary).'echo "fixture dashboard";');
file_put_contents($temp.'/app/pages/protected.php','<?php session_start();require __DIR__."/../includes/functions.php";getDBConnection();echo "protected";');
// Session corruption is restricted to this disposable fixture, never shipped as a route.
file_put_contents($temp.'/app/auth/fixture_session.php','<?php session_start();foreach(["session_id","db_type","company_id"] as $key)if(isset($_POST[$key])){if($_POST[$key]==="__unset")unset($_SESSION[$key]);else $_SESSION[$key]=$_POST[$key];}echo "fixture";');
foreach(['sales','sales_legacy_disabled','add_to_cart','checkout_cart','process_service_sale','get_cart','get_purchase_history','get_invoices','get_renewal_stats'] as $endpoint) {
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
    foreach($http_response_header as $h){
        if(preg_match('/Set-Cookie: PHPSESSID=([^;]*)/i',$h,$m)){
            if($m[1]===''||$m[1]==='deleted'||stripos($h,'Max-Age=0')!==false)unset($cookies[$browser]);
            else $cookies[$browser]=$m[1];
        }
        if(str_starts_with($h,'Location:'))$location=trim(substr($h,9));
    }
    return [$status,json_decode($body,true),$location,$body,$http_response_header];
};
$follow=function(string $path,string $browser)use($request):array{
    $chain=[];
    for($hop=0;$hop<8;$hop++){
        $response=$request($path,$browser);
        $chain[]=[$path,$response[0],$response[2]];
        if($response[2]==='')return [$response,$chain];
        $path=str_starts_with($response[2],'/')?$response[2]:'/'.ltrim($response[2],'./');
    }
    throw new RuntimeException('Redirect chain did not terminate: '.json_encode($chain));
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
    check($request('/pages/protected.php')[2]==='/pages/login_transition.php','Valid partial HTML access returns to company choice');
    check($request('/pages/login_transition.php')[0]===200,'Company-choice page survives denied API and HTML routes');
    $list=$request('/api/company_choice.php')[1];
    check(array_column($list['companies'],'id')===['a','b'],'All and only active companies offered');
    check($request('/api/company_choice.php','one',['company_id'=>'b','csrf_token'=>'bad'],true)[0]===403,'CSRF enforced');
    check($login('free','other')[2]==='/pages/login_transition.php','Verified new login replaces an abandoned pending session');
    check($request('/api/company_choice.php','one')[0]===401,'Replaced pending browser loses access');
    check($login()[2]==='/pages/login_transition.php','Abandoned choice can restart in same browser');
    check($request('/api/company_choice.php','other')[0]===401,'Only the latest pending login is active');
    $pending=$request('/api/company_choice.php')[1];
    $pendingCookie=$cookies['one'];
    $db->prepare('UPDATE tenant_admin_sessions SET last_activity=DATE_SUB(NOW(),INTERVAL 5 MINUTE) WHERE session_id=?')->execute([$pendingCookie]);
    $pendingLast=$db->query("SELECT last_activity FROM tenant_admin_sessions WHERE session_id=".$db->quote($pendingCookie))->fetchColumn();
    check($request('/api/company_session.php','one',['action'=>'activity'],true,$pending['csrf_token'])[0]===200,'Pending company chooser can check session status');
    check($db->query("SELECT last_activity FROM tenant_admin_sessions WHERE session_id=".$db->quote($pendingCookie))->fetchColumn()===$pendingLast,'Company choice keeps its fixed deadline despite activity');
    check($choose('off')[0]===409,'Inactive company denied');
    check($choose('b')[0]===200,'Active company saved');
    $state=$request('/api/probe.php')[1];
    check($state['company']==='b'&&$state['role']==='owner'&&$state['name']==='free','Full identity follows selected company without old staff');
    check($state['modules']===['events'],'User rights intersect selected company modules');
    check($request('/api/get_rental_test.php')[0]===403,'Disabled company module rejected server side');
    check($request('/api/get_rental_test.php')[1]['error']==='access_denied','Module denial is not reported as expired login');
    check($request('/api/probe.php')[0]===200,'Module denial preserves valid authenticated session');
    foreach(['sales','add_to_cart','checkout_cart','process_service_sale','get_cart','get_purchase_history','get_invoices','get_renewal_stats'] as $endpoint) {
        $expectedStatus=in_array($endpoint,['add_to_cart','checkout_cart'],true)?410:403;
        check($request('/api/'.$endpoint.'.php','one',['output_product_id'=>'fixture','quantity'=>1],true,$state['context'])[0]===$expectedStatus,
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
    check($request('/api/probe.php')[0]===200,'Old context denial preserves the current authenticated session');
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
    $staffState=$request('/api/probe.php')[1];$staffCookie=$cookies['one'];
    $db->prepare('UPDATE tenant_admin_sessions SET last_activity=DATE_SUB(NOW(),INTERVAL 10 MINUTE) WHERE session_id=?')->execute([$staffCookie]);
    $beforeStatus=$db->query("SELECT last_activity FROM tenant_admin_sessions WHERE session_id=".$db->quote($staffCookie))->fetchColumn();
    $status=$request('/api/company_session.php','one',null,false,$staffState['context']);
    check($status[0]===200&&$status[1]['expires_in']>=1190&&$status[1]['expires_in']<=1200,'Browser receives remaining authoritative server lifetime');
    check($db->query("SELECT last_activity FROM tenant_admin_sessions WHERE session_id=".$db->quote($staffCookie))->fetchColumn()===$beforeStatus,'Status polling does not extend idle time');
    check($request('/api/company_session.php','one',['action'=>'activity'],true,'old-context')[0]===409,'Stale tab cannot keep a newer login alive');
    check($request('/api/company_session.php','one',['action'=>'logout'],true,'old-context')[0]===409,'Stale tab cannot end a newer login');
    $activity=$request('/api/company_session.php','one',['action'=>'activity'],true,$staffState['context']);
    check($activity[0]===200&&$activity[1]['expires_in']>=1798,'Real activity extends a still-valid login');
    check(str_contains($login('staff','one','wrong')[2],'invalid_credentials')&&$request('/api/probe.php')[0]===200,'Wrong password never revokes the active login');
    check($login('staff','replacement')[2]==='/pages/login_transition.php','Correct password replaces a full login in another browser');
    check((int)$db->query("SELECT COUNT(*) FROM tenant_admin_sessions WHERE tenant_admin_id='staff' AND is_active=1")->fetchColumn()===1,'Exactly one employee session remains active');
    $retired=$request('/api/company_session.php','one',null,false,$staffState['context']);
    check($retired[0]===401&&$retired[1]['error']==='session_expired','Old browser receives explicit session revocation');
    check(!array_filter($retired[4],fn($header)=>str_starts_with($header,'Set-Cookie:')&&str_contains($header,'Max-Age=0')),'Delayed revoked API response cannot delete a newer shared login cookie');
    check($request('/api/probe.php','replacement')[0]===200,'Cleanup of the replaced browser leaves the new login valid');
    check($login('staff')[2]==='/pages/login_transition.php','Original browser can sign in again without waiting');
    $lostCookie=$cookies['one'];
    unlink($temp.'/sessions/sess_'.$lostCookie);
    check($request('/api/company_session.php','one')[0]===401,'Lost PHP session is rejected consistently');
    check((int)$db->query("SELECT is_active FROM tenant_admin_sessions WHERE session_id=".$db->quote($lostCookie))->fetchColumn()===0,'Orphan registry entry is retired when PHP session data is lost');
    check($login('staff')[2]==='/pages/login_transition.php','Login works after PHP session storage disappears');
    $lostCookie=$cookies['one'];unset($cookies['one']);
    check($login('staff')[2]==='/pages/login_transition.php','Login works when browser lost its cookie entirely');
    check((int)$db->query("SELECT is_active FROM tenant_admin_sessions WHERE session_id=".$db->quote($lostCookie))->fetchColumn()===0,'Losing the browser cookie does not leave an active lock behind');
    $expiredState=$request('/api/probe.php')[1];$expiredCookie=$cookies['one'];
    $db->prepare('UPDATE tenant_admin_sessions SET last_activity=DATE_SUB(NOW(),INTERVAL 30 MINUTE) WHERE session_id=?')->execute([$expiredCookie]);
    check($request('/api/company_session.php','one',['action'=>'activity'],true,$expiredState['context'])[0]===401,'Activity cannot revive an expired session at the exact timeout boundary');
    check((int)$db->query("SELECT is_active FROM tenant_admin_sessions WHERE session_id=".$db->quote($expiredCookie))->fetchColumn()===0,'Expired server session is marked inactive');
    check(!file_exists($temp.'/sessions/sess_'.$expiredCookie),'Expired PHP session storage is removed');
    check($login('staff')[2]==='/pages/login_transition.php','Immediate new login works after idle timeout');
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
    // One independent valid browser must survive cleanup of another browser.
    check($login('free','keeper')[2]==='/pages/login_transition.php'&&$choose('a','keeper')[0]===200,'Independent browser authenticated');
    [$validRoot,$validChain]=$follow('/','keeper');
    check($validRoot[0]===200&&$validRoot[3]==='fixture dashboard'&&count($validChain)===2,'Valid root session still reaches dashboard');
    $keeperContext=$request('/api/probe.php','keeper')[1]['context'];
    $busyKey='company-login:'.substr(hash('sha256','free'),0,40);
    $db->prepare('SELECT GET_LOCK(?,5)')->execute([$busyKey]);
    try{
        $busyStatus=$request('/api/company_session.php','keeper',null,false,$keeperContext);
        check($busyStatus[0]===409&&$busyStatus[1]['error']==='session_unavailable','Busy session check does not invalidate or log out the current user');
        [$busyResponse,$busyChain]=$follow('/','keeper');
        check($busyResponse[0]===409&&$busyResponse[2]===''&&count($busyChain)===2
            &&str_contains($busyResponse[3],'Prøv igjen'),'Busy connection ends without redirect loop or forced logout');
    }finally{$db->prepare('SELECT RELEASE_LOCK(?)')->execute([$busyKey]);}
    check($request('/api/probe.php','keeper')[0]===200,'Busy request preserves the valid login');
    foreach(['expired','revoked','account-inactive','company-mismatch','session-mismatch','missing-session-id','wrong-database'] as $scenario){
        check($login('staff','redirect')[2]==='/pages/login_transition.php','Employee login before '.$scenario);
        $oldCookie=$cookies['redirect'];
        $foreignSession='unrelated-'.$scenario;
        $db->prepare('INSERT INTO tenant_admin_sessions(tenant_admin_id,session_id,is_active,login_at,last_activity) VALUES(?,?,1,NOW(),NOW())')
            ->execute(['staff',$foreignSession]);
        if($scenario==='expired')$db->prepare('UPDATE tenant_admin_sessions SET last_activity=DATE_SUB(NOW(),INTERVAL 31 MINUTE) WHERE session_id=?')->execute([$oldCookie]);
        elseif($scenario==='revoked')$db->prepare('UPDATE tenant_admin_sessions SET is_active=0 WHERE session_id=?')->execute([$oldCookie]);
        elseif($scenario==='account-inactive')$db->exec("UPDATE tenant_admins SET is_active=0 WHERE id='staff'");
        else{
            $changes=match($scenario){
                'company-mismatch'=>['company_id'=>'b'],
                'session-mismatch'=>['session_id'=>'not-the-browser-session'],
                'missing-session-id'=>['session_id'=>'__unset'],
                'wrong-database'=>['db_type'=>'pgsql'],
            };
            $request('/auth/fixture_session.php','redirect',$changes);
        }
        [$terminal,$chain]=$follow('/','redirect');
        check($terminal[0]===200&&str_contains($terminal[3],'Økten din har utløpt'),'Invalid root ends on explained login: '.$scenario);
        check(count($chain)===3&&$chain[0][2]==='pages/company_dashboard.php'
            &&$chain[1][2]==='/index.php?error=session_expired','Redirect chain terminates without a loop: '.$scenario);
        check(str_contains(implode("\n",$terminal[4]),'Cache-Control: no-store'),'Login recovery is not cached');
        $q=$db->prepare('SELECT is_active FROM tenant_admin_sessions WHERE session_id=?');$q->execute([$oldCookie]);
        check((int)$q->fetchColumn()===0,'Only invalid current session is retired: '.$scenario);
        $q->execute([$foreignSession]);check((int)$q->fetchColumn()===1,'Same-account unrelated session preserved: '.$scenario);
        check($request('/api/probe.php','keeper')[0]===200,'Other browser remains authenticated: '.$scenario);
        check($request('/pages/login_transition.php','redirect')[2]==='/index.php?error=session_expired','Cleared session cannot reuse transition: '.$scenario);
        $db->exec("UPDATE tenant_admins SET is_active=1 WHERE id='staff'");
        $db->prepare('DELETE FROM tenant_admin_sessions WHERE session_id=?')->execute([$foreignSession]);
    }
    $request('/auth/logout.php','keeper');
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
        file_put_contents($temp.'/login-worker.php','<?php file_get_contents($argv[1]."/auth/company_auth.php",false,stream_context_create(["http"=>["method"=>"POST","follow_location"=>0,"header"=>"Content-Type: application/x-www-form-urlencoded","content"=>"username=free&password=fixture-password"]])); foreach($http_response_header as $h)if(str_starts_with($h,"Location:")||str_starts_with($h,"Set-Cookie:"))echo $h."\n";');
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
        check(count(array_filter($locations,fn($s)=>str_contains($s,'login_transition')))===2,'Both correct logins serialize; the latest replaces the earlier one');
        $active=$db->query("SELECT session_id FROM tenant_admin_sessions WHERE tenant_admin_id='free' AND is_active=1")->fetchAll(PDO::FETCH_COLUMN);
        check(count($active)===1,'Exactly one simultaneous login remains active');
        $workerCookies=[];
        foreach($locations as $headers){
            preg_match_all('/Set-Cookie: PHPSESSID=([^;\\s]*)/i',$headers,$cookieMatches);
            $workerCookies[]=end($cookieMatches[1]);
        }
        check(in_array($active[0],$workerCookies,true),'Winner belongs to an actual verified browser session');
        $cookies['winner']=$active[0];
        $cookies['superseded']=current(array_diff($workerCookies,$active));
        check($request('/api/company_session.php','superseded')[0]===401,'Superseded concurrent browser is revoked');
        check($choose('a','winner')[0]===200&&$request('/api/probe.php','winner')[0]===200,'Winning concurrent browser can choose company and use protected routes');
    }finally {
        foreach($workers as $worker)if(is_resource($worker)){proc_terminate($worker);proc_close($worker);}
        proc_terminate($server2);proc_close($server2);
    }
    echo "Company choice: isolated HTTP/MySQL authentication, migration, context, modules, revocation and expiry passed.\n";
}catch(Throwable $e){fwrite(STDERR,file_get_contents($temp.'/http.log'));throw $e;}
finally{proc_terminate($server);proc_close($server);}
