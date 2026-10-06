<?php
declare(strict_types=1);
$root=$argv[1]??'';$temp=$argv[2]??'';
if(!str_starts_with($temp,sys_get_temp_dir().'/rental-unavailable-mysql.') || !file_exists($temp.'/mysql.sock'))throw new RuntimeException('Use disposable harness');
require_once $root.'/includes/AuditedPDO.php';
require_once $root.'/includes/entity_form_fields.php';
require_once $root.'/includes/entity_form_migration.php';
require_once $root.'/includes/entity_form_input.php';
require_once $root.'/includes/rental_request.php';
require_once $root.'/includes/event_form_submission.php';
function formsAssert(bool $ok,string $message):void {if(!$ok)throw new RuntimeException($message);}
$base='mysql:unix_socket='.$temp.'/mysql.sock';
$setup=new PDO($base,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$setup->exec('CREATE DATABASE forms_fixture');
$setup->exec('USE forms_fixture');
$setup->exec("CREATE TABLE tenant_companies(id CHAR(36) PRIMARY KEY,name VARCHAR(255),is_active INT,cash_payment INT)");
$setup->exec("CREATE TABLE tenant_staff(id CHAR(36),company_id CHAR(36),first_name VARCHAR(255),last_name VARCHAR(255))");
$setup->exec("CREATE TABLE tenant_admins(id CHAR(36),company_id CHAR(36),tenant_staff_id CHAR(36),username VARCHAR(255),role VARCHAR(50),is_active INT)");
$setup->exec("CREATE TABLE system_modules(id CHAR(36),module_key VARCHAR(50),is_active INT)");
$setup->exec("CREATE TABLE tenant_admin_modules(tenant_admin_id CHAR(36),module_id CHAR(36))");
$setup->exec("CREATE TABLE events_eventdata(id CHAR(36) PRIMARY KEY,company_id CHAR(36),title VARCHAR(255),registration_type VARCHAR(30))");
$setup->exec("CREATE TABLE rental_objects(id CHAR(36) PRIMARY KEY,company_id CHAR(36),object_name VARCHAR(255))");
$setup->exec("CREATE TABLE event_registration_fields(id CHAR(36),event_id CHAR(36),field_name VARCHAR(100),field_label VARCHAR(255),field_type VARCHAR(50),is_required INT,is_visible INT,display_order INT)");
$setup->exec("CREATE TABLE rental_customer_field_settings(id CHAR(36),company_id CHAR(36),rental_object_id CHAR(36),field_key VARCHAR(100),selected INT,required INT,display_order INT)");
$setup->exec("CREATE TABLE dynamic_form_fields(id CHAR(36) PRIMARY KEY,company_id CHAR(36),entity_type VARCHAR(50),entity_id CHAR(36),field_name VARCHAR(100),field_label VARCHAR(255),field_type VARCHAR(50),is_required INT DEFAULT 0,is_visible INT DEFAULT 1,default_value TEXT,options TEXT,validation_rules TEXT,sort_order INT DEFAULT 0,created_at DATETIME DEFAULT CURRENT_TIMESTAMP,updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,UNIQUE(company_id,entity_type,entity_id,field_name))");
$setup->exec("CREATE TABLE rental_request_field_values(id CHAR(36),company_id CHAR(36),rental_object_id CHAR(36),rental_request_id CHAR(36),field_key VARCHAR(100),field_value TEXT)");
$setup->exec("CREATE TABLE audit_logs(id CHAR(36) PRIMARY KEY,company_id CHAR(36),user_id CHAR(36),user_type VARCHAR(20),user_email VARCHAR(255),action VARCHAR(50),resource_type VARCHAR(100),resource_id CHAR(36),resource_name VARCHAR(255),old_values TEXT,new_values TEXT,severity VARCHAR(20),performed_at DATETIME,module VARCHAR(50))");
$setup->exec("INSERT INTO tenant_companies VALUES('a','Bedrift A',1,1),('b','Bedrift B',1,1)");
$setup->exec("INSERT INTO tenant_staff VALUES('person','a','Virkelig','Ansatt')");
$setup->exec("INSERT INTO tenant_admins VALUES('admin','a','person','employee','admin',1)");
$setup->exec("INSERT INTO system_modules VALUES('events-module','events',1),('rental-module','rental',1)");
$setup->exec("INSERT INTO tenant_admin_modules VALUES('admin','events-module'),('admin','rental-module')");
$setup->exec("INSERT INTO events_eventdata VALUES('e','a','Arrangement','signup'),('foreign','b','Annet','signup'),('none','a','Uten påmelding','none')");
$setup->exec("INSERT INTO rental_objects VALUES('r','a','Hytte'),('other','a','Bil')");
$setup->exec("INSERT INTO event_registration_fields VALUES('f1','e','first_name','Fornavn','text',1,1,1),('f2','e','last_name','Etternavn','text',1,1,2),('f3','e','email','E-postadresse','email',0,1,3)");
$setup->exec("INSERT INTO rental_customer_field_settings VALUES('legacy','a','r','email',1,0,2)");
$setup->exec("INSERT INTO dynamic_form_fields(id,company_id,entity_type,entity_id,field_name,field_label,field_type,is_required,is_visible,sort_order) VALUES('shared','a','rental_object','r','email','E-post','email',1,1,2),('member','a','membership_plan','plan','email','Medlemskontakt','email',1,1,0)");
$setup->exec("INSERT INTO rental_request_field_values VALUES('answer','a','r','request','email','historical@example.test')");
$_SESSION=['company_id'=>'a','tenant_admin_id'=>'admin','admin_name'=>'FORGED'];
$dsn=$base.';dbname=forms_fixture;charset=utf8mb4';
$db=new AuditedPDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
$plan=entityFormMigrationPlan($db);
formsAssert($plan['report']['conflicting_entities']===1,'Reports shared/legacy conflict');
formsAssert($setup->query('SELECT COUNT(*) FROM dynamic_form_fields')->fetchColumn()==2,'Dry run does not write');
$blocked=false;
try{entityFormMigrationApply($db,$plan['fingerprint'],'abort');}catch(RuntimeException $e){$blocked=true;}
formsAssert($blocked && $setup->query('SELECT COUNT(*) FROM dynamic_form_fields')->fetchColumn()==2,'Conflict abort is atomic');
entityFormMigrationApply($db,$plan['fingerprint'],'keep-shared');
formsAssert($setup->query("SELECT is_required FROM dynamic_form_fields WHERE id='shared'")->fetchColumn()==1,'Shared conflict choice preserved');
formsAssert($setup->query("SELECT COUNT(*) FROM event_registration_fields")->fetchColumn()==3,'Legacy source retained');
$next=entityFormMigrationPlan($db);
formsAssert(entityFormMigrationApply($db,$next['fingerprint'],'abort')['converted_entities']===0,'Migration idempotent');
formsAssert(entityFormLoad($db,'a','event','e')['source']==='dynamic','Migrated reads use shared storage');
$fields=entityFormLoad($db,'a','event','e')['fields'];
$fields[2]['field_label']='Kontaktadresse';
entityFormSave($db,'a','event','e',$fields);
formsAssert($setup->query("SELECT COUNT(*) FROM event_registration_fields")->fetchColumn()==3,'Editing never writes dedicated fields');
formsAssert($setup->query("SELECT field_label FROM dynamic_form_fields WHERE entity_type='event' AND field_name='email'")->fetchColumn()==='Kontaktadresse','Field edit persisted');
$logs=$setup->query("SELECT new_values FROM audit_logs WHERE user_id='admin'")->fetchAll(PDO::FETCH_COLUMN);
formsAssert(str_contains(implode('',$logs),'Virkelig Ansatt') && !str_contains(implode('',$logs),'FORGED'),'Real actor recorded');
$blocked=false;try{entityFormSave($db,'a','event','foreign',$fields);}catch(OutOfBoundsException $e){$blocked=true;}
formsAssert($blocked,'Cross-tenant write denied');
$blocked=false;try{entityFormLoad($db,'a','event','foreign');}catch(OutOfBoundsException $e){$blocked=true;}
formsAssert($blocked,'Cross-tenant read denied');
$setup->exec("DELETE FROM tenant_admin_modules WHERE module_id='events-module'");
$blocked=false;try{entityFormSave($db,'a','event','e',$fields);}catch(TenantAuditException $e){$blocked=true;}
formsAssert($blocked,'Missing module permission denied');
$setup->exec("INSERT INTO tenant_admin_modules VALUES('admin','events-module')");
entityFormSave($db,'a','rental_object','r',[]);
formsAssert(entityFormLoad($db,'a','rental_object','r')['fields']===[],'Intentionally empty does not restore defaults');
formsAssert($setup->query("SELECT field_value FROM rental_request_field_values WHERE id='answer'")->fetchColumn()==='historical@example.test','Historical answers retained');
formsAssert($setup->query("SELECT field_label FROM dynamic_form_fields WHERE id='member'")->fetchColumn()==='Medlemskontakt','Membership fields untouched');
entityFormSave($db,'a','event','none',[]);
$blocked=false;try{entityFormSave($db,'a','event','none',entityFormDefaults('event'));}catch(InvalidArgumentException $e){$blocked=true;}
formsAssert($blocked,'No-registration rule enforced');
$custom=['field_name'=>'choice','field_label'=>'<script>alert(1)</script>','field_type'=>'select','is_required'=>1,'is_visible'=>1,'sort_order'=>0,'options'=>['<img src=x onerror=alert(1)>','B'],'validation_rules'=>[],'default_value'=>null];
entityFormSave($db,'a','rental_object','other',[$custom]);
$settings=getRentalCustomerFieldSettings($db,'a','other');
$html=renderRentalRequestField($settings[0]);
formsAssert(str_contains($html,'&lt;script&gt;') && !str_contains($html,'<script>'),'Labels/options escaped');
formsAssert(validateEntityFormValues([$custom],['choice'=>'B'])['choice']==='B','Supported choices validated');
$blocked=false;try{validateEntityFormValues([$custom],['choice'=>'C']);}catch(InvalidArgumentException $e){$blocked=true;}
formsAssert($blocked,'Forged choice rejected');
$legacyRule=$custom;$legacyRule['field_type']='text';$legacyRule['validation_rules']=['maxLength'=>3,'pattern'=>'^[A-Z]+$'];
$normal=entityFormNormalize('rental_object',[$legacyRule])[0];
formsAssert($normal['validation_rules']['max_length']===3,'Legacy rule keys normalized');
$blocked=false;try{validateEntityFormValues([$normal],['choice'=>'aa']);}catch(InvalidArgumentException $e){$blocked=true;}
formsAssert($blocked,'Legacy pattern enforced');
$guest=eventGuestFormNotes($db,'a','e',[], 'Kari','Test','kari@example.test');
$snapshot=eventGuestFormSnapshot($guest."\nNo-show");
formsAssert($snapshot['guest']['first_name']==='Kari' && count($snapshot['form_fields'])===3,'Event answers retain configured labels after no-show append');
formsAssert(eventGuestFormSnapshot('Historical free text')===[],'Historic event notes are untouched');
// Invalid existing config is reported, never silently repaired by a read.
$setup->exec("INSERT INTO rental_objects VALUES('broken','a','Gammelt oppsett')");
$setup->exec("INSERT INTO rental_customer_field_settings VALUES('broken1','a','broken','street_address',1,0,0),('broken2','a','broken','email',1,0,1)");
$setup->prepare("INSERT INTO dynamic_form_fields(id,company_id,entity_type,entity_id,field_name,field_label,field_type,is_required,is_visible,sort_order,validation_rules) VALUES('alias','a','rental_object','broken','address_street','Gateadresse','text',0,1,0,NULL),('invalid','a','rental_object','broken','email','E-post','email',0,1,1,?)")
    ->execute(['{"pattern":"\\s"}']);
$invalidPlan=entityFormMigrationPlan($db);
formsAssert($invalidPlan['report']['invalid_shared_fields']===1,'Invalid legacy shared JSON reported');
$blocked=false;try{entityFormMigrationApply($db,$invalidPlan['fingerprint'],'keep-shared');}catch(RuntimeException $e){$blocked=true;}
formsAssert($blocked,'Preserve cannot silently discard invalid rules');
entityFormMigrationApply($db,$invalidPlan['fingerprint'],'use-legacy');
formsAssert($setup->query("SELECT field_name FROM dynamic_form_fields WHERE id='alias'")->fetchColumn()==='address_street','Existing storage alias preserved');
formsAssert(entityFormLoad($db,'a','rental_object','broken')['fields'][0]['field_name']==='street_address','Current answer key stays compatible');
formsAssert(entityFormLoad($db,'a','rental_object','broken')['source']==='dynamic','Approved legacy config becomes shared');
$db->beginTransaction();
$custom['field_label']='Temporary';
entityFormSave($db,'a','rental_object','other',[$custom]);
$db->rollBack();
formsAssert($setup->query("SELECT field_label FROM dynamic_form_fields WHERE entity_id='other' AND field_name='choice'")->fetchColumn()!=='Temporary','Caller rollback covers fields and audit');
$setup->exec("CREATE TRIGGER fail_form_audit BEFORE INSERT ON audit_logs FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='fixture audit outage'");
$before=$setup->query("SELECT field_label FROM dynamic_form_fields WHERE entity_id='other' AND field_name='choice'")->fetchColumn();
$custom['field_label']='Must roll back';
$blocked=false;try{entityFormSave($db,'a','rental_object','other',[$custom]);}catch(TenantAuditException $e){$blocked=true;}
formsAssert($blocked && $setup->query("SELECT field_label FROM dynamic_form_fields WHERE entity_id='other' AND field_name='choice'")->fetchColumn()===$before,'Audit failure rolls fields back');
$setup->exec('DROP TRIGGER fail_form_audit');
// Run real HTTP endpoints against disposable DB, with actual sessions and CSRF.
$setup->exec("ALTER TABLE tenant_companies ADD subdomain VARCHAR(100)");
$setup->exec("ALTER TABLE events_eventdata ADD description TEXT,ADD start_date DATE,ADD start_time TIME,
 ADD end_date DATE,ADD end_time TIME,ADD venue_name TEXT,ADD event_address TEXT,ADD event_city TEXT,ADD event_postal TEXT,
 ADD max_attendees INT,ADD min_attendees INT,ADD registration_deadline DATE,ADD age_limit INT,
 ADD is_member_exclusive INT DEFAULT 0,ADD memberverification INT DEFAULT 0,ADD ticket_price DECIMAL(12,2),
 ADD event_image_url TEXT,ADD is_active INT DEFAULT 1,ADD created_by CHAR(36),ADD created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
 ADD updated_at DATETIME,ADD default_payment_method VARCHAR(50),ADD payment_timing VARCHAR(50),
 ADD payment_methods_json TEXT,ADD share_code VARCHAR(20),ADD output_product_id CHAR(36),ADD cost_price DECIMAL(12,2)");
$setup->exec("CREATE TABLE economy_payment_gateways(company_id CHAR(36),gateway_name VARCHAR(50),is_active INT)");
$setup->exec("INSERT INTO economy_payment_gateways VALUES('a','vipps',1),('b','stripe',1),('a','inactive',0)");
$setup->exec("CREATE TABLE event_membership_plans(event_id CHAR(36),membership_plan_id CHAR(36),ticket_price DECIMAL(12,2),public_display INT)");
$setup->exec("CREATE TABLE membership_plans(id CHAR(36),name VARCHAR(255))");
$setup->exec("CREATE TABLE output_products(id CHAR(36) PRIMARY KEY,company_id CHAR(36),module_id CHAR(36),
 product_type VARCHAR(50),product_id CHAR(36),is_visible INT,is_public INT,is_marketplace_featured INT,
 marketplace_priority INT,discount_percent DECIMAL(12,2),created_by CHAR(36))");
$serverDir=$temp.'/forms-http';mkdir($serverDir);
$bootstrap='require_once '.var_export($root.'/includes/AuditedPDO.php',true).';function getDBConnection(){return new AuditedPDO('.var_export($dsn,true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);}';
foreach(['get_entity_form_fields.php','save_entity_form_fields.php'] as $file) {
    $source=file_get_contents($root.'/api/'.$file);
    $source=str_replace('session_start();','session_save_path('.var_export($temp.'/sessions',true).');session_start();$_SESSION["company_id"]="a";$_SESSION["tenant_admin_id"]="admin";',$source);
    $source=str_replace("require_once __DIR__.'/../config/database.php';",$bootstrap,$source);
    $source=str_replace("__DIR__.'/../includes/entity_form_fields.php'",var_export($root.'/includes/entity_form_fields.php',true),$source);
    file_put_contents($serverDir.'/'.$file,$source);
}
foreach(['create_event.php','update_event.php','get_event.php'] as $file) {
    $source=file_get_contents($root.'/api/'.$file);
    $source=str_replace('session_start();','session_save_path('.var_export($temp.'/sessions',true).');session_start();$_SESSION["company_id"]="a";$_SESSION["tenant_admin_id"]="admin";',$source);
    $source=str_replace("require_once __DIR__ . '/../config/database.php';",$bootstrap,$source);
    $source=preg_replace_callback("~__DIR__ \\. '/\\.\\./includes/([^']+)'~",
        static fn($m)=>var_export($root.'/includes/'.$m[1],true),$source);
    file_put_contents($serverDir.'/'.$file,$source);
}
$socket=stream_socket_server('tcp://127.0.0.1:0');$port=(int)substr(strrchr(stream_socket_get_name($socket,false),':'),1);fclose($socket);
$worker=proc_open([PHP_BINARY,'-S','127.0.0.1:'.$port,'-t',$serverDir],[0=>['file','/dev/null','r'],1=>['file',$temp.'/forms-http.log','a'],2=>['file',$temp.'/forms-http.log','a']],$pipes);
function formsHttp(string $path,?array $data=null):array {
    global $port,$cookie;
    $headers="Cookie: ".($cookie??'')."\r\nContent-Type: application/json\r\n";
    $body=file_get_contents('http://127.0.0.1:'.$port.'/'.$path,false,stream_context_create(['http'=>[
        'method'=>$data===null?'GET':'POST','header'=>$headers,'content'=>$data===null?'':json_encode($data),'ignore_errors'=>true]]));
    foreach($http_response_header as $header)if(preg_match('/^Set-Cookie: ([^;]+)/i',$header,$match))$cookie=$match[1];
    return json_decode($body,true,512,JSON_THROW_ON_ERROR);
}
try {
    for($i=0;$i<60;$i++){if($s=@fsockopen('127.0.0.1',$port)){fclose($s);break;}usleep(100000);}
    $get=formsHttp('get_entity_form_fields.php?entity_type=rental_object&entity_id=other');
    formsAssert($get['success'] && $get['csrf_token']!=='' && count($get['presets'])===2,'Actual GET returns scoped fields,presets,token');
    $post=['entity_type'=>'rental_object','entity_id'=>'other','fields'=>[]];
    formsAssert(formsHttp('save_entity_form_fields.php',$post)['success']===false,'Missing CSRF rejected');
    $post['csrf_token']=$get['csrf_token'];
    formsAssert(formsHttp('save_entity_form_fields.php',$post)['success']===true,'Actual save endpoint accepts valid CSRF');
    formsAssert(formsHttp('get_entity_form_fields.php?entity_type=event&entity_id=foreign')['success']===false,'Actual HTTP foreign read denied');
    $eventInput=['title'=>'Betalt medlemsarrangement','start_date'=>'2070-06-01','start_time'=>'13:00',
        'registration_type'=>'binding','allowed_payment_methods'=>['vipps'],'default_payment_method'=>'vipps',
        'ticket_price'=>100,'memberverification'=>1,'is_member_exclusive'=>1,'csrf_token'=>$get['csrf_token'],
        'form_fields'=>entityFormDefaults('event')];
    $before=(int)$setup->query('SELECT COUNT(*) FROM events_eventdata')->fetchColumn();
    $bad=$eventInput;$bad['allowed_payment_methods']=['cash','vipps'];
    formsAssert(formsHttp('create_event.php',$bad)['success']===false,'Binding create rejects cash even when company permits cash');
    $bad['allowed_payment_methods']=['stripe'];$bad['default_payment_method']='stripe';
    formsAssert(formsHttp('create_event.php',$bad)['success']===false,'Foreign payment gateway rejected');
    $bad['allowed_payment_methods']=['inactive'];$bad['default_payment_method']='inactive';
    formsAssert(formsHttp('create_event.php',$bad)['success']===false,'Inactive payment gateway rejected');
    formsAssert((int)$setup->query('SELECT COUNT(*) FROM events_eventdata')->fetchColumn()===$before,'Invalid payment settings roll back creation');
    $created=formsHttp('create_event.php',$eventInput);
    formsAssert($created['success'],'Actual binding event creation: '.json_encode($created));
    $id=$created['event_id'];
    $read=formsHttp('get_event.php?id='.$id)['event'];
    formsAssert((int)$read['memberverification']===1&&(int)$read['is_member_exclusive']===1
        &&$read['registration_type']==='binding'&&json_decode($read['payment_methods_json'],true)===['vipps'],
        'Create/read round trip preserves both membership flags and digital payment');
    $edit=$eventInput;unset($edit['form_fields'],$edit['registration_type'],$edit['allowed_payment_methods'],
        $edit['default_payment_method'],$edit['memberverification'],$edit['is_member_exclusive']);
    $edit['event_id']=$id;$edit['title']='Endret tittel';
    formsAssert(formsHttp('update_event.php',$edit)['success'],'Basic edit with omitted settings succeeds');
    $read=formsHttp('get_event.php?id='.$id)['event'];
    formsAssert((int)$read['memberverification']===1&&(int)$read['is_member_exclusive']===1
        &&$read['registration_type']==='binding'&&json_decode($read['payment_methods_json'],true)===['vipps'],
        'Omitted or unavailable membership controls and disabled registration radios do not erase stored settings');
    $bad=$edit;$bad['allowed_payment_methods']=['cash'];$bad['default_payment_method']='cash';
    formsAssert(formsHttp('update_event.php',$bad)['success']===false,'Binding edit rejects cash');
    $edit['memberverification']=0;$edit['is_member_exclusive']=0;
    formsAssert(formsHttp('update_event.php',$edit)['success'],'Explicit unchecked membership flags saved');
    $read=formsHttp('get_event.php?id='.$id)['event'];
    formsAssert((int)$read['memberverification']===0&&(int)$read['is_member_exclusive']===0,'Both flags can be explicitly cleared');
}finally{proc_terminate($worker);proc_close($worker);}
echo "Isolated shared form storage, migration, audit, rendering, validation and HTTP tests passed\n";
