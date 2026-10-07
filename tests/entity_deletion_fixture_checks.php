<?php
// Runs only from the disposable forms DB fixture; never load application database.php.
$token=$get['csrf_token'];
$delete=['event_id'=>$id,'delete_mode'=>'hard','csrf_token'=>$token];
formsAssert(formsHttp('delete_event.php',array_diff_key($delete,['csrf_token'=>1]))['success']===false,'Event delete requires CSRF');
formsAssert(formsHttp('delete_event.php',['event_id'=>'foreign','delete_mode'=>'hard','csrf_token'=>$token])['success']===false,'Foreign event deletion denied');
$setup->exec("INSERT INTO event_registrations VALUES('attendee','a','$id')");
formsAssert(!formsHttp('delete_event.php',$delete)['success'],'Registration history blocks permanent event deletion');
$delete['delete_mode']='soft';
formsAssert(formsHttp('delete_event.php',$delete)['success'],'Event hiding succeeds');
formsAssert(formsHttp('delete_event.php',$delete)['success'],'Repeated hiding is idempotent');
formsAssert((int)$setup->query("SELECT COUNT(*) FROM event_registrations WHERE event_id='$id'")->fetchColumn()===1,'Hiding preserves participants');
formsAssert((int)$setup->query("SELECT is_visible FROM output_products WHERE product_id='$id'")->fetchColumn()===0,'Hiding updates catalog');

// Empty event with actual catalog->event CASCADE and shared field definitions.
$emptyInput=$ordinary;$emptyInput['title']='Tomt arrangement';
$empty=formsHttp('create_event.php',$emptyInput);
formsAssert($empty['success'],'Empty event fixture created');
$emptyId=$empty['event_id'];
formsAssert(formsHttp('delete_event.php',['event_id'=>$emptyId,'delete_mode'=>'hard','csrf_token'=>$token])['success'],'Empty event deletion respects cascade order');
formsAssert((int)$setup->query("SELECT COUNT(*) FROM output_products WHERE product_id='$emptyId'")->fetchColumn()===0,'Deleted event catalog cleaned');
formsAssert((int)$setup->query("SELECT COUNT(*) FROM dynamic_form_fields WHERE entity_id='$emptyId'")->fetchColumn()===0,'Deleted event definitions cleaned');

$ledgerEvent=formsHttp('create_event.php',$emptyInput)['event_id'];
$setup->exec("INSERT INTO unified_ledger VALUES('ledger-event','a','events','unrelated',JSON_OBJECT('event_id','$ledgerEvent'))");
formsAssert(!formsHttp('delete_event.php',['event_id'=>$ledgerEvent,'delete_mode'=>'hard','csrf_token'=>$token])['success'],'Metadata ledger reference protects financial history');
formsAssert((int)$setup->query("SELECT COUNT(*) FROM unified_ledger WHERE id='ledger-event'")->fetchColumn()===1,'Ledger is never deleted');

$setup->exec("INSERT INTO rental_objects(id,company_id,object_name) VALUES('active-contract','a','Kontraktobjekt'),('rejected-request','a','Avslag'),('rent-empty','a','Tomt'),('rent-rollback','a','Rollback'),('rent-foreign','b','Annen bedrift')");
$setup->exec("INSERT INTO rental_contracts VALUES('contract','a','active-contract','active')");
$setup->exec("INSERT INTO rental_schedule VALUES('declined','a','rejected-request','rejected')");
$reply=formsHttp('delete_rental_object.php',['id'=>'active-contract','csrf_token'=>$token]);
formsAssert(!$reply['success'] && str_contains($reply['message'],'aktive'),'Active contract is checked independently of schedule');
formsAssert((int)$setup->query("SELECT COUNT(*) FROM rental_contracts WHERE id='contract'")->fetchColumn()===1,'Active contract never cascades away');
$reply=formsHttp('delete_rental_object.php',['id'=>'rejected-request','csrf_token'=>$token]);
formsAssert(!$reply['success'] && str_contains($reply['message'],'historiske'),'Rejected requests are explained as history, not active bookings');
formsAssert(!formsHttp('delete_rental_object.php',['id'=>'rent-foreign','csrf_token'=>$token])['success'],'Rental tenant isolation');
formsAssert(!formsHttp('delete_rental_object.php',['id'=>'rent-empty'])['success'],'Rental deletion requires CSRF');
formsAssert(formsHttp('delete_rental_object.php',['id'=>'rent-empty','csrf_token'=>$token])['success'],'Empty rental deletion works');

// Every genuine category includes its real type child, not just a bare parent.
foreach(getRentalSchemas() as $category=>$schema) {
    $typedId='typed-'.$category;
    $q=$setup->prepare('INSERT INTO rental_objects(id,company_id,object_name,category) VALUES(?,?,?,?)');
    $q->execute([$typedId,'a',$schema['label'],$category]);
    $q=$setup->prepare('INSERT INTO `'.$schema['table'].'`(object_id) VALUES(?)');$q->execute([$typedId]);
    entityFormSave($db,'a','rental_object',$typedId,entityFormDefaults('rental_object'));
    $response=formsHttp('delete_rental_object.php',['id'=>$typedId,'csrf_token'=>$token]);
    formsAssert($response['success'],'History-free '.$category.' object with type child can be deleted: '.json_encode($response));
    $q=$setup->prepare('SELECT COUNT(*) FROM `'.$schema['table'].'` WHERE object_id=?');$q->execute([$typedId]);
    formsAssert((int)$q->fetchColumn()===0,'Type child cascades for '.$category);
    $q=$setup->prepare("SELECT COUNT(*) FROM dynamic_form_fields WHERE company_id='a' AND entity_type='rental_object' AND entity_id=?");$q->execute([$typedId]);
    formsAssert((int)$q->fetchColumn()===0,'Shared fields cleaned for typed '.$category);
}

// A genuine type row does not make its customer's history disposable.
$setup->exec("INSERT INTO rental_objects(id,company_id,object_name,category) VALUES('typed-history','a','Boenhet med historikk','residence')");
$setup->exec("INSERT INTO rental_residences(object_id) VALUES('typed-history')");
$setup->exec("INSERT INTO rental_contracts VALUES('typed-contract','a','typed-history','completed')");
formsAssert(!formsHttp('delete_rental_object.php',['id'=>'typed-history','csrf_token'=>$token])['success'],'Typed object with contract history still protected');
formsAssert((int)$setup->query("SELECT COUNT(*) FROM rental_residences WHERE object_id='typed-history'")->fetchColumn()===1,'Type child preserved on rejected deletion');
formsAssert((int)$setup->query("SELECT COUNT(*) FROM rental_contracts WHERE id='typed-contract'")->fetchColumn()===1,'Contract preserved on rejected typed deletion');

// Independent process, not pcntl_fork with live PDO: force a real FK insert to race deletion.
$setup->exec("INSERT INTO rental_objects(id,company_id,object_name) VALUES('rent-concurrent','a','Samtidig')");
$setup->exec("CREATE TRIGGER pause_rental_delete BEFORE DELETE ON rental_objects FOR EACH ROW
 BEGIN IF OLD.id='rent-concurrent' THEN DO SLEEP(0.5); END IF; END");
$raceSource='<?php $db=new PDO('.var_export($dsn,true).',"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);'
    .'for($i=0;$i<200;$i++){foreach($db->query("SHOW PROCESSLIST")->fetchAll(PDO::FETCH_ASSOC) as $p){'
    .'if(stripos($p["State"]??"","sleep")!==false && ($p["Command"]??"")==="Query"){'
    .'try{$db->exec("INSERT INTO rental_schedule VALUES(\'racing-request\',\'a\',\'rent-concurrent\',\'pending\')");echo "inserted";}'
    .'catch(PDOException $e){echo $e->getCode()==="23000"?"protected":"unexpected";}exit;}}usleep(10000);}echo "timeout";';
file_put_contents($temp.'/deletion-race.php',$raceSource);
$raceProcess=proc_open([PHP_BINARY,$temp.'/deletion-race.php'],[
    0=>['file','/dev/null','r'],1=>['file',$temp.'/deletion-race.out','w'],2=>['file',$temp.'/deletion-race.err','w']],$racePipes);
formsAssert(formsHttp('delete_rental_object.php',['id'=>'rent-concurrent','csrf_token'=>$token])['success'],'Parent locking permits empty deletion');
proc_close($raceProcess);
formsAssert(file_get_contents($temp.'/deletion-race.out')==='protected','Actual FK blocks racing request until parent deletion commits: '
    .file_get_contents($temp.'/deletion-race.out').' '.file_get_contents($temp.'/deletion-race.err'));
$setup->exec('DROP TRIGGER pause_rental_delete');

// A late catalog failure rolls back the base object, definitions and audit together.
$setup->exec("INSERT INTO output_products(id,company_id,product_id,product_type,is_visible,is_public) VALUES('rollback-product','a','rent-rollback','rental',1,1)");
$setup->exec("UPDATE rental_objects SET output_product_id='rollback-product' WHERE id='rent-rollback'");
entityFormSave($db,'a','rental_object','rent-rollback',entityFormDefaults('rental_object'));
$setup->exec("CREATE TRIGGER block_catalog_delete BEFORE DELETE ON output_products FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Fixture deletion failure'");
formsAssert(!formsHttp('delete_rental_object.php',['id'=>'rent-rollback','csrf_token'=>$token])['success'],'Catalog failure is returned, not success');
formsAssert((int)$setup->query("SELECT COUNT(*) FROM rental_objects WHERE id='rent-rollback'")->fetchColumn()===1,'Rental base row rolls back');
formsAssert((int)$setup->query("SELECT COUNT(*) FROM dynamic_form_fields WHERE entity_id='rent-rollback'")->fetchColumn()>0,'Rental field cleanup rolls back');
$setup->exec('DROP TRIGGER block_catalog_delete');
formsAssert(formsHttp('delete_rental_object.php',['id'=>'rent-rollback','csrf_token'=>$token])['success'],'Rental catalog deletion can be retried');
$setup->exec("DELETE FROM tenant_admin_modules WHERE module_id='events-module'");
formsAssert(!formsHttp('delete_event.php',['event_id'=>$id,'delete_mode'=>'soft','csrf_token'=>$token])['success'],'Soft delete enforces module permission too');
$setup->exec("INSERT INTO tenant_admin_modules VALUES('admin','events-module')");
