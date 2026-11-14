<?php
require_once __DIR__.'/../includes/source_catalog_links.php';
function catalogAssert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
if (isset($argv[2])) {
    $temp = $argv[2];
    if (!str_starts_with($temp, sys_get_temp_dir().'/rental-unavailable-mysql.') || !is_file($temp.'/mysql.pid')) {
        throw new RuntimeException('Only disposable MySQL harness allowed');
    }
    $db = new PDO('mysql:unix_socket='.$temp.'/mysql.sock', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $db->exec('CREATE DATABASE catalog_links_test');
    $db->exec('USE catalog_links_test');
} else {
    $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
}
$db->exec('CREATE TABLE system_modules(id VARCHAR(36) PRIMARY KEY,module_key VARCHAR(30))');
$db->exec("INSERT INTO system_modules VALUES('rm','rental'),('sm','sales')");
foreach (['rental_objects','sales_products'] as $table) {
    $db->exec("CREATE TABLE $table(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),output_product_id VARCHAR(36),is_active INT,visibility VARCHAR(20))");
}
$db->exec('CREATE TABLE output_products(id VARCHAR(36) PRIMARY KEY,company_id VARCHAR(36),module_id VARCHAR(36),
    product_type VARCHAR(30),product_id VARCHAR(36),is_visible INT,is_public INT,
    is_marketplace_featured INT,marketplace_priority INT,discount_percent DECIMAL(12,2),created_by VARCHAR(36))');
$db->exec("INSERT INTO rental_objects VALUES('r1','a',NULL,1,NULL),('r2','b',NULL,1,NULL),('off','a',NULL,0,NULL)");
$db->exec("INSERT INTO sales_products VALUES('s1','a',NULL,1,'kiosk'),('s2','a',NULL,1,'online')");
$before = $db->query('SELECT COUNT(*) FROM output_products')->fetchColumn();
$report = repairSourceCatalogLinks($db,'a');
catalogAssert(!$report['applied'] && $report['checked']===4 && $report['missing']===4, 'Dry-run reports only selected company');
catalogAssert($db->query('SELECT COUNT(*) FROM output_products')->fetchColumn()===$before, 'Dry-run creates no entries');
catalogAssert($db->query('SELECT COUNT(*) FROM rental_objects WHERE output_product_id IS NOT NULL')->fetchColumn()==0, 'Dry-run changes no source links');
$report = repairSourceCatalogLinks($db,'a',true,'admin');
catalogAssert($report['created']===4 && $report['linked']===4, 'Backfill creates and links missing catalogue rows');
catalogAssert($db->query("SELECT output_product_id FROM rental_objects WHERE id='r2'")->fetchColumn()===null, 'Other company untouched');
catalogAssert($db->query("SELECT is_public FROM output_products WHERE product_id='s1'")->fetchColumn()==0, 'Kiosk remains non-public');
catalogAssert($db->query("SELECT is_public FROM output_products WHERE product_id='s2'")->fetchColumn()==1, 'Online product can be public');
catalogAssert($db->query("SELECT is_visible FROM output_products WHERE product_id='off'")->fetchColumn()==0, 'Inactive source is hidden');
catalogAssert(repairSourceCatalogLinks($db,'a',true)['created']===0, 'Repeated repair is idempotent');
$db->exec('ALTER TABLE rental_objects ADD COLUMN is_available INT DEFAULT 1');
$db->exec("UPDATE rental_objects SET is_available=0 WHERE id='off'");
$db->beginTransaction();
ensureSourceCatalogLink($db,'a','rental','off',null,true);
$db->commit();
catalogAssert($db->query("SELECT is_public FROM output_products WHERE product_id='off'")->fetchColumn()==0,'Explicit source hiding keeps catalogue hidden');
$db->exec("UPDATE rental_objects SET is_available=1,is_active=1 WHERE id='off'");
$db->beginTransaction();
ensureSourceCatalogLink($db,'a','rental','off',null,true);
$db->commit();
catalogAssert($db->query("SELECT is_public FROM output_products WHERE product_id='off'")->fetchColumn()==1,'Explicit source showing updates catalogue');
$db->exec("INSERT INTO rental_objects(id,company_id,is_active,is_available) VALUES('hidden-missing','a',1,0)");
$db->beginTransaction();
ensureSourceCatalogLink($db,'a','rental','hidden-missing');
$db->commit();
catalogAssert($db->query("SELECT is_public FROM output_products WHERE product_id='hidden-missing'")->fetchColumn()==0,'Missing catalogue row for hidden source is not published');

$db->exec("UPDATE output_products SET is_visible=0,is_public=0 WHERE product_id='r1'");
$db->beginTransaction();
$result = ensureSourceCatalogLink($db,'a','rental','r1');
catalogAssert(!$result['created'] && !$result['linked'], 'Unchanged edit writes no link');
$db->commit();
catalogAssert($db->query("SELECT is_public FROM output_products WHERE id='".$result['id']."'")->fetchColumn()==0, 'Repair does not republish hidden entry');
$db->exec("INSERT INTO output_products VALUES('duplicate','a','rm','rental','r1',1,1,0,0,0,NULL)");
$db->beginTransaction();
$result2 = ensureSourceCatalogLink($db,'a','rental','r1');
$db->commit();
catalogAssert($result2['id']===$result['id'] && $result2['duplicates']===1, 'Valid linked duplicate ID preserved');
catalogAssert($db->query("SELECT COUNT(*) FROM output_products WHERE product_id='r1'")->fetchColumn()==2, 'Duplicates not deleted');

$db->exec("INSERT INTO output_products VALUES('foreign','b','rm','rental','r1',1,1,0,0,0,NULL)");
$db->exec("UPDATE rental_objects SET output_product_id='foreign' WHERE id='r1'");
$db->beginTransaction();
$fixed = ensureSourceCatalogLink($db,'a','rental','r1');
$db->commit();
catalogAssert($fixed['linked'] && $fixed['id']!=='foreign', 'Foreign pointer replaced with owned matching source');
$db->beginTransaction();
try {
    ensureSourceCatalogLink($db,'a','rental','r2');
    throw new RuntimeException('Expected ownership denial');
} catch (InvalidArgumentException $e) {
    $db->rollBack();
}

// Existing legacy Sales entries are reused, not duplicated.
$db->exec("UPDATE output_products SET product_type='sales_item' WHERE product_id='s1'");
$db->exec("UPDATE sales_products SET output_product_id=NULL WHERE id='s1'");
$db->beginTransaction();
$legacy = ensureSourceCatalogLink($db,'a','sales','s1');
$db->commit();
catalogAssert(!$legacy['created'] && $legacy['linked'], 'Legacy Sales type reused');

// A late failure rolls back catalogue AND source-pointer changes.
$db->exec("INSERT INTO rental_objects(id,company_id,output_product_id,is_active,visibility) VALUES('rollback','a',NULL,1,NULL)");
$db->exec("INSERT INTO sales_products VALUES('fail','a',NULL,1,'online')");
$db->exec("DELETE FROM system_modules WHERE module_key='sales'");
$before = $db->query('SELECT COUNT(*) FROM output_products')->fetchColumn();
try {
    repairSourceCatalogLinks($db,'a',true);
    throw new RuntimeException('Expected missing module failure');
} catch (RuntimeException $e) {
    catalogAssert(str_contains($e->getMessage(),'system_modules'), 'Expected module setup error');
}
catalogAssert(!$db->inTransaction(), 'Failed repair closes transaction');
catalogAssert($db->query('SELECT COUNT(*) FROM output_products')->fetchColumn()===$before, 'Late failure rolls back earlier inserts');
catalogAssert($db->query("SELECT output_product_id FROM rental_objects WHERE id='rollback'")->fetchColumn()===null, 'Late failure rolls back source links');
if ($db->getAttribute(PDO::ATTR_DRIVER_NAME)==='mysql') {
    $db->exec("INSERT INTO rental_objects(id,company_id,output_product_id,is_active,visibility) VALUES('race','a',NULL,1,NULL)");
    $worker = 'require $argv[1]; $db=new PDO($argv[2],"root","",[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);'
        .'$db->beginTransaction(); ensureSourceCatalogLink($db,"a","rental","race"); usleep(80000); $db->commit();';
    $workers=[];
    for($i=0;$i<2;$i++) {
        $p=proc_open([PHP_BINARY,'-r',$worker,__DIR__.'/../includes/source_catalog_links.php',
            'mysql:unix_socket='.$temp.'/mysql.sock;dbname=catalog_links_test'],
            [0=>['file','/dev/null','r'],1=>['file',$temp.'/catalog-worker.log','a'],2=>['file',$temp.'/catalog-worker.log','a']],$pipes);
        catalogAssert(is_resource($p),'Concurrent worker started');
        $workers[]=$p;
    }
    foreach($workers as $p) catalogAssert(proc_close($p)===0,'Concurrent repair completed');
    catalogAssert($db->query("SELECT COUNT(*) FROM output_products WHERE company_id='a' AND product_id='race'")->fetchColumn()==1,'Concurrent repairs create only one catalogue row');
}
echo "Source catalogue tests passed (".$db->getAttribute(PDO::ATTR_DRIVER_NAME).").\n";
