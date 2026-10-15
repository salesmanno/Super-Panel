<?php
declare(strict_types=1);
[$script,$root,$temp]=$argv;
if (!str_starts_with($temp,'/tmp/company-login-mysql.')) throw new RuntimeException('Disposable authentication database required');
function expect(bool $ok,string $message): void {if(!$ok)throw new RuntimeException($message);}
$db=new PDO('mysql:unix_socket='.$temp.'/mysql.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE DATABASE auth_lifecycle');$db->exec('USE auth_lifecycle');
$dsn='mysql:unix_socket='.$temp.'/mysql.sock;dbname=auth_lifecycle';
foreach([
    'tenant_companies(id CHAR(36) PRIMARY KEY,name TEXT,subdomain TEXT,logo_url TEXT,is_active INT)',
    'tenant_staff(id CHAR(36) PRIMARY KEY,company_id CHAR(36),first_name TEXT,last_name TEXT,email TEXT,is_active INT)',
    'tenant_admins(id CHAR(36) PRIMARY KEY,company_id CHAR(36),tenant_staff_id CHAR(36),username VARCHAR(100),password_hash TEXT,role TEXT,is_active INT,last_login DATETIME)',
    'tenant_admin_sessions(id INT AUTO_INCREMENT PRIMARY KEY,tenant_admin_id CHAR(36),session_id VARCHAR(255),is_active INT,ip_address TEXT,user_agent TEXT,login_at DATETIME,last_activity DATETIME,logout_at DATETIME)',
    'system_modules(id CHAR(36),module_key TEXT,name TEXT,description TEXT,icon TEXT,is_active INT,created_at DATETIME)',
    'tenant_admin_modules(tenant_admin_id CHAR(36),module_id CHAR(36))',
    'tenant_company_modules(company_id CHAR(36),module_id CHAR(36),is_active INT)',
] as $table)$db->exec('CREATE TABLE '.$table.' ENGINE=InnoDB');
$db->exec("INSERT INTO tenant_companies VALUES('firm','Testbedrift','fixture',NULL,1),('other','Annen bedrift','other',NULL,1)");
$db->exec("INSERT INTO tenant_staff VALUES('employee','firm','Test','Ansatt',NULL,1)");
$insert=$db->prepare('INSERT INTO tenant_admins VALUES(?,?,?,?,?,?,?,NULL)');
foreach([['employee-account','firm','employee'],['chooser','firm',null]] as [$id,$company,$staff])
    $insert->execute([$id,$company,$staff,$id,password_hash('fixture-password',PASSWORD_DEFAULT),'owner',1]);
foreach(['app','app/auth','app/api','app/includes','app/config','app/pages','app/assets'] as $dir)mkdir($temp.'/'.$dir);
foreach(glob($root.'/includes/*.php') as $file)copy($file,$temp.'/app/includes/'.basename($file));
foreach(['index.php','auth/company_auth.php','auth/logout.php','pages/login_transition.php','pages/company_login.php',
    'api/company_choice.php','api/company_session.php','api/refresh_session.php',
    'assets/company_context.js','assets/company_session.js'] as $file)copy($root.'/'.$file,$temp.'/app/'.$file);
$dashboard=file_get_contents($root.'/pages/company_dashboard.php');
$boundary=strpos($dashboard,'$auditVisible = false;');
expect($boundary!==false,'Real dashboard authentication boundary exists');
file_put_contents($temp.'/app/pages/company_dashboard.php',substr($dashboard,0,$boundary).'echo "Authenticated fixture dashboard";');
file_put_contents($temp.'/app/api/probe.php','<?php session_start();require __DIR__."/../includes/functions.php";getDBConnection();header("Content-Type: application/json");echo json_encode(["context"=>$_SESSION["company_context"],"company"=>$_SESSION["company_id"]]);');
file_put_contents($temp.'/app/config/database.php','<?php require_once __DIR__."/../includes/AuditedPDO.php";function getDBType(){return "mysql";}function getDBConnection(){if(is_file('.var_export($temp.'/connect-fault',true).'))throw new PDOException("fixture-private-driver-detail");static $db;return $db??=new AuditedPDO('.var_export($dsn,true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::MYSQL_ATTR_INIT_COMMAND=>"SET SESSION sql_mode=\'\'"]);}');
// Faults are exclusively in this disposable server's prepend, not production routes.
file_put_contents($temp.'/prepend.php','<?php class FixtureSessionHandler extends SessionHandler { public function write(string $id,string $data):bool {if(is_file('.var_export($temp.'/write-fault',true).'))return false;return parent::write($id,$data);}} session_set_save_handler(new FixtureSessionHandler(),true);');
$sock=stream_socket_server('tcp://127.0.0.1:0');$address=stream_socket_get_name($sock,false);fclose($sock);
$server=proc_open([PHP_BINARY,'-d','session.save_path='.$temp.'/sessions','-d','auto_prepend_file='.$temp.'/prepend.php','-S',$address,'-t',$temp.'/app'],
    [0=>['file','/dev/null','r'],1=>['file',$temp.'/http.log','a'],2=>['file',$temp.'/http.log','a']],$pipes);
$cookies=[];
$request=function(string $path,string $browser='one',?array $body=null,?string $context=null)use($address,&$cookies):array{
    $headers='Cookie: PHPSESSID='.($cookies[$browser]??'')."\r\n";
    $json=$path==='/api/company_choice.php';
    if($body!==null)$headers.='Content-Type: '.($json?'application/json':'application/x-www-form-urlencoded')."\r\n";
    if($context!==null)$headers.="X-Company-Context: ".$context."\r\n";
    $result=file_get_contents('http://'.$address.$path,false,stream_context_create(['http'=>[
        'method'=>$body===null?'GET':'POST','header'=>$headers,'content'=>$body===null?'':($json?json_encode($body):http_build_query($body)),
        'ignore_errors'=>true,'follow_location'=>0,'timeout'=>15]]));
    $status=(int)explode(' ',$http_response_header[0])[1];$location='';
    foreach($http_response_header as $header){
        if(preg_match('/^Set-Cookie: PHPSESSID=([^;]*)/i',$header,$match)){
            if($match[1]===''||$match[1]==='deleted')unset($cookies[$browser]);else $cookies[$browser]=$match[1];
        }
        if(str_starts_with($header,'Location:'))$location=trim(substr($header,9));
    }
    return [$status,$location,$result,json_decode($result,true),$http_response_header];
};
$login=fn(string $browser='one',string $name='employee-account',string $password='fixture-password')=>
    $request('/auth/company_auth.php',$browser,['username'=>$name,'password'=>$password]);
$active=fn(string $id)=>(int)$db->query("SELECT COUNT(*) FROM tenant_admin_sessions WHERE tenant_admin_id=".$db->quote($id)." AND is_active=1")->fetchColumn();
try{
    for($i=0;$i<100;$i++){if(@fsockopen('127.0.0.1',(int)explode(':',$address)[1]))break;usleep(20000);}
    expect($login()[1]==='/pages/login_transition.php','Correct employee login succeeds');
    expect($request('/pages/login_transition.php')[0]===200,'Employee loader succeeds');
    expect($request('/pages/company_dashboard.php')[2]==='Authenticated fixture dashboard','Employee reaches protected dashboard');
    $current=$cookies['one'];$context=$request('/api/probe.php')[3]['context'];
    expect(is_file($temp.'/sessions/sess_'.$current),'PHP login was stored before success');
    expect($active('employee-account')===1,'Only one employee session exists');
    expect(str_contains($login('one','employee-account','wrong')[1],'invalid_credentials'),'Incorrect password is rejected');
    expect($cookies['one']===$current && $active('employee-account')===1,'Wrong password preserves the valid login');
    // Both direct-page connection failures and failures inside the connection guard.
    file_put_contents($temp.'/connect-fault','1');
    foreach(['/pages/login_transition.php','/pages/company_dashboard.php','/api/company_session.php','/api/refresh_session.php'] as $path){
        $response=$request($path,'one',null,$context);
        expect($response[0]===503 && $response[1]==='','Database failure is not an expired redirect: '.$path);
        expect(!str_contains($response[2],'fixture-private-driver-detail'),'No private driver message reaches the browser');
        expect($cookies['one']===$current && $active('employee-account')===1,'Database outage preserves cookie and registry');
    }
    unlink($temp.'/connect-fault');
    $db->exec('RENAME TABLE tenant_staff TO unavailable_staff');
    expect($request('/pages/login_transition.php')[0]===503,'PDO error inside connection guard fails closed');
    expect($cookies['one']===$current && $active('employee-account')===1,'Guard failure preserves login');
    $db->exec('RENAME TABLE unavailable_staff TO tenant_staff');
    expect($request('/pages/company_dashboard.php')[0]===200,'Existing login recovers after the outage');
    $lock='company-login:'.substr(hash('sha256','employee-account'),0,40);
    $db->prepare('SELECT GET_LOCK(?,5)')->execute([$lock]);
    try{
        expect($request('/pages/login_transition.php')[0]===409,'Busy lock does not pretend to be expiry');
        expect($cookies['one']===$current && $active('employee-account')===1,'Busy lock preserves login');
    }finally{$db->prepare('SELECT RELEASE_LOCK(?)')->execute([$lock]);}
    // A failing PHP write must roll back revocation, not commit an unusable new login.
    file_put_contents($temp.'/write-fault','1');
    $failed=$login('write-failure');
    expect(str_contains($failed[1],'session_error'),'Unwritable PHP storage is reported explicitly');
    expect($active('employee-account')===1,'Failed storage does not revoke the previous good database login');
    expect((int)$db->query("SELECT COUNT(*) FROM tenant_admin_sessions WHERE session_id=".$db->quote($cookies['write-failure']??''))->fetchColumn()===0,'Failed storage leaves no new registry row');
    unlink($temp.'/write-fault');
    expect($request('/pages/company_dashboard.php')[0]===200,'Old valid browser survives another browser storage failure');
    expect($login('two')[1]==='/pages/login_transition.php','New correct login replaces old');
    $new=$cookies['two'];
    $old=$request('/pages/login_transition.php','one');
    expect($old[1]==='/index.php?error=session_expired','Revoked browser is redirected');
    expect(!array_filter($old[4],fn($header)=>str_starts_with($header,'Set-Cookie:')),'Delayed OLD page cannot overwrite NEW shared cookie');
    expect($active('employee-account')===1 && $cookies['two']===$new,'New login remains valid after old-page cleanup');
    expect($request('/pages/company_dashboard.php','two')[0]===200,'New login reaches dashboard');
    $oldLogout=$request('/auth/logout.php','one');
    expect(!array_filter($oldLogout[4],fn($header)=>str_starts_with($header,'Set-Cookie:')),'Delayed OLD logout also cannot overwrite NEW cookie');
    expect($active('employee-account')===1,'Old logout leaves new database login active');
    $context2=$request('/api/probe.php','two')[3]['context'];
    $db->prepare('UPDATE tenant_admin_sessions SET last_activity=DATE_SUB(NOW(),INTERVAL 10 MINUTE) WHERE session_id=?')->execute([$new]);
    $before=$db->query("SELECT last_activity FROM tenant_admin_sessions WHERE session_id=".$db->quote($new))->fetchColumn();
    expect($request('/api/company_session.php','two',null,$context2)[3]['expires_in']<=1200,'Server remaining lifetime is authoritative');
    expect($request('/api/refresh_session.php','two')[0]===200,'Refresh can read identity');
    expect($db->query("SELECT last_activity FROM tenant_admin_sessions WHERE session_id=".$db->quote($new))->fetchColumn()===$before,'Status and refresh do not extend idle time');
    expect($request('/api/company_session.php','two',['action'=>'activity'],'old-context')[0]===409,'Old context cannot touch session');
    $db->prepare('UPDATE tenant_admin_sessions SET last_activity=DATE_SUB(NOW(),INTERVAL 30 MINUTE) WHERE session_id=?')->execute([$new]);
    expect($request('/api/company_session.php','two',['action'=>'activity'],$context2)[0]===401,'Idle session cannot be resurrected');
    expect($active('employee-account')===0 && !is_file($temp.'/sessions/sess_'.$new),'Expiry clears only matching registry and PHP storage');
    expect($login('two')[1]==='/pages/login_transition.php','Login immediately works after expiry');
    unlink($temp.'/sessions/sess_'.$cookies['two']);
    $lost=$request('/pages/login_transition.php','two');
    expect($lost[1]==='/index.php?error=session_missing','Missing PHP storage has a distinct reason');
    expect($active('employee-account')===0,'Orphan matching registry row is retired');
    expect($login('chooser-browser','chooser')[1]==='/pages/login_transition.php','Unlinked account enters company choice');
    expect(str_contains($request('/pages/login_transition.php','chooser-browser')[2],'Velg bedrift'),'Company choice remains rendered');
    $choice=$request('/api/company_choice.php','chooser-browser')[3];
    expect(count($choice['companies'])===2,'Active companies remain selectable');
    $chosen=$request('/api/company_choice.php','chooser-browser',['company_id'=>'other','csrf_token'=>$choice['csrf_token']]);
    expect($chosen[0]===200,'Company choice accepted');
    expect($request('/pages/company_dashboard.php','chooser-browser')[0]===200,'Selected company reaches dashboard');
    // Real Chromium posts the form, receives the cookie and follows the loader JS.
    foreach(['check_database_status','get_news','get_terms_conditions','get_activity_summary'] as $name)
        file_put_contents($temp.'/app/api/'.$name.'.php','<?php header("Content-Type: application/json");echo \'{"status":"connected","success":true,"news":[]}\';');
    file_put_contents($temp.'/app/browser-login.php','<?php include __DIR__."/pages/company_login.php";?><script>document.getElementById("username").value="employee-account";document.getElementById("password").value="fixture-password";document.getElementById("login-form").submit();</script>');
    $chrome=trim((string)shell_exec('command -v chromium'));
    expect($chrome!=='','Chromium is required for browser-cookie verification');
    $command=['timeout','45',$chrome,'--headless','--no-sandbox','--disable-dev-shm-usage','--disable-background-networking',
        '--no-proxy-server','--host-resolver-rules=MAP * ~NOTFOUND, EXCLUDE 127.0.0.1',
        '--user-data-dir='.$temp.'/browser-profile','--virtual-time-budget=10000','--dump-dom','http://'.$address.'/browser-login.php'];
    $browser=proc_open($command,[0=>['file','/dev/null','r'],1=>['file',$temp.'/browser.html','w'],2=>['file',$temp.'/browser.log','w']],$browserPipes);
    expect(proc_close($browser)===0,'Headless browser exits cleanly');
    expect(str_contains(file_get_contents($temp.'/browser.html'),'Authenticated fixture dashboard'),'Real browser reaches authenticated dashboard through POST/cookie/loader');
    expect($login('deny')[1]==='/pages/login_transition.php','Login before account revocation');
    $db->exec("UPDATE tenant_admins SET is_active=0 WHERE id='employee-account'");
    expect($request('/pages/login_transition.php','deny')[1]==='/index.php?error=access_changed','Account revocation is distinct from idle timeout');
    $db->exec("UPDATE tenant_admins SET is_active=1 WHERE id='employee-account'");
    expect($login('logout-browser')[1]==='/pages/login_transition.php','Correct login before explicit logout');
    $logoutCookie=$cookies['logout-browser'];
    expect($request('/auth/logout.php','logout-browser')[1]==='/index.php?message=logged_out','Explicit logout returns to login');
    expect($active('employee-account')===0 && !is_file($temp.'/sessions/sess_'.$logoutCookie),'Explicit logout retires registry and destroys PHP session without cookie races');
    // Older non-strict hosts may silently truncate the stored session identifier.
    $db->exec("SET SESSION sql_mode=''");
    $db->exec('ALTER TABLE tenant_admin_sessions MODIFY session_id VARCHAR(8)');
    $countBefore=(int)$db->query('SELECT COUNT(*) FROM tenant_admin_sessions')->fetchColumn();
    expect(str_contains($login('short-registry')[1],'session_error'),'Truncated registry session cannot return successful login');
    expect((int)$db->query('SELECT COUNT(*) FROM tenant_admin_sessions')->fetchColumn()===$countBefore,'Registry mismatch rolls back the attempted login');
    expect(!str_contains(file_get_contents($temp.'/http.log'),'fixture-private-driver-detail'),'Private exception text is not logged');
    echo "Authentication lifecycle passed: employee login, company choice, storage failure rollback, outages, lock, cookies, retries, context, idle and revocation.\n";
} finally {
    proc_terminate($server);proc_close($server);
}
