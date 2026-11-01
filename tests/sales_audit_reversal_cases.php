<?php
declare(strict_types=1);
// Included by the isolated Sales fixture; never connects to application data.
$setup->exec('ALTER TABLE tenant_admins ADD COLUMN tenant_supervisor TINYINT NOT NULL DEFAULT 0');
$setup->exec("UPDATE tenant_admins SET tenant_supervisor=1 WHERE id='admin'");
$p=$sales->saveProduct(['name'=>'Audit test product','selling_price'=>'25','cost_price'=>'5'])['product'];
$id=$p['id'];
$sales->adjustStock(['product_id'=>$id,'quantity'=>12,'expected_quantity'=>0,'reason'=>'Test restock']);
$audit=$setup->query("SELECT * FROM audit_logs WHERE resource_id=".$setup->quote($id)." AND action='sales.stock_adjusted'")->fetch(PDO::FETCH_ASSOC);
salesAssert((bool)$audit,'Explicit stock change recorded');
$setup->exec("UPDATE audit_logs SET performed_at=DATE_SUB(NOW(),INTERVAL 10 SECOND) WHERE resource_id=".$setup->quote($id)." AND id<>".$setup->quote($audit['id']));
$public=tenantAuditPublicRow($audit);
salesAssert(str_contains($public['description'],'admin Kasserer fylte på') && str_contains($public['description'],'×12')
    && str_contains($public['description'],'60,00 NOK'),'Restock description records role, name, quantity and unit/total cost');
$preview=$sales->reverseStockAdjustment($audit['id']);
salesAssert($preview['from']===12 && $preview['to']===0,'Preview shows exact stock compensation');
salesReject(fn()=>$sales->reverseStockAdjustment($audit['id'],true,'wrong'),'Stale or invented preview cannot apply');
$ledgerBefore=(int)$setup->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn();
$reversed=$sales->reverseStockAdjustment($audit['id'],true,$preview['revision']);
salesAssert((int)$setup->query("SELECT stock_quantity FROM sales_products WHERE id=".$setup->quote($id))->fetchColumn()===0,'Confirmed reversal restores stock');
$linked=$setup->query('SELECT new_values FROM audit_logs WHERE id='.$setup->quote($reversed['audit_id']))->fetchColumn();
salesAssert(json_decode($linked,true)['_audit']['links']['reverses_audit_id']===$audit['id'],'Compensation links to original audit without deleting it');
salesAssert((int)$setup->query('SELECT COUNT(*) FROM unified_ledger')->fetchColumn()===$ledgerBefore,'Reversal does not delete or modify financial entries');
salesReject(fn()=>$sales->reverseStockAdjustment($audit['id'],true,$preview['revision']),'Repeated reversal cannot run twice');
salesReject(fn()=>$other->reverseStockAdjustment($audit['id']),'Another company cannot reverse the change');
$sales->adjustStock(['product_id'=>$id,'quantity'=>5,'expected_quantity'=>0,'reason'=>'New test change']);
$next=$setup->query("SELECT id FROM audit_logs WHERE resource_id=".$setup->quote($id)." AND action='sales.stock_adjusted' AND id<>".$setup->quote($audit['id'])." ORDER BY performed_at DESC LIMIT 1")->fetchColumn();
salesReject(fn()=>$sales->reverseStockAdjustment($next),'Ambiguous same-second changes block reversal conservatively');
$setup->exec("UPDATE tenant_admins SET tenant_supervisor=0 WHERE id='admin'");
salesReject(fn()=>$sales->reverseStockAdjustment($next),'Non-supervisor cannot reverse');
$setup->exec("UPDATE tenant_admins SET tenant_supervisor=1 WHERE id='admin'");
$raceProduct=$sales->saveProduct(['name'=>'Concurrent reversal fixture','selling_price'=>'10'])['product'];
$raceId=$raceProduct['id'];
$sales->adjustStock(['product_id'=>$raceId,'quantity'=>8,'expected_quantity'=>0,'reason'=>'Race fixture']);
$raceLog=$setup->query("SELECT id FROM audit_logs WHERE resource_id=".$setup->quote($raceId)." AND action='sales.stock_adjusted'")->fetchColumn();
$setup->exec("UPDATE audit_logs SET performed_at=DATE_SUB(NOW(),INTERVAL 20 SECOND) WHERE resource_id=".$setup->quote($raceId)." AND id<>".$setup->quote($raceLog));
$racePreview=$sales->reverseStockAdjustment($raceLog);
$workerSource='<?php require '.var_export($temp.'/sales-db.php',true).'; require '.var_export($root.'/includes/SalesService.php',true).';'
    .'$_SESSION=["tenant_admin_id"=>"admin","company_id"=>"a"];'
    .'$service=new SalesService(getDBConnection(),"a","admin","fixture-worker");'
    .'while(!file_exists($argv[1]))usleep(1000);'
    .'try{$service->reverseStockAdjustment($argv[2],true,$argv[3]);echo "applied";}'
    .'catch(Throwable $e){echo "blocked";}';
file_put_contents($temp.'/audit-reversal-worker.php',$workerSource);
$gate=$temp.'/audit-reversal-go';
$workers=[];
for($i=0;$i<2;$i++) {
    $proc=proc_open([PHP_BINARY,$temp.'/audit-reversal-worker.php',$gate,$raceLog,$racePreview['revision']],
        [0=>['file','/dev/null','r'],1=>['file',$temp."/reversal-$i.out",'w'],2=>['file',$temp."/reversal-$i.err",'w']],$pipes);
    if(!is_resource($proc))throw new RuntimeException('Could not start isolated reversal worker');
    $workers[]=$proc;
}
touch($gate);
foreach($workers as $worker) salesAssert(proc_close($worker)===0,'Reversal worker exits normally');
$outcomes=[file_get_contents($temp.'/reversal-0.out'),file_get_contents($temp.'/reversal-1.out')];
sort($outcomes);
salesAssert($outcomes===['applied','blocked'],'Two independent database connections can apply the same reversal only once');
salesAssert((int)$setup->query("SELECT stock_quantity FROM sales_products WHERE id=".$setup->quote($raceId))->fetchColumn()===0,
    'Concurrent reversal produces one stock compensation');
echo "Sales audit narratives, permissions, preview, retained history and concurrent reversal checks passed\n";
