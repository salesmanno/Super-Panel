<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/config/database.php';
require_once __DIR__.'/includes/SalesService.php';
$db=getDBConnection();$provider=new SalesVipps();
// Bound the work. Only this implementation's durable pending attempts qualify.
$q=$db->query("SELECT o.id FROM sales_orders o JOIN unified_ledger l ON l.order_id=o.id AND l.company_id=o.company_id AND l.module_source='sales'
    WHERE o.status='pending' AND JSON_VALID(l.metadata) AND JSON_EXTRACT(l.metadata,'$.sales.version')=1
    GROUP BY o.id ORDER BY MIN(COALESCE(JSON_UNQUOTE(JSON_EXTRACT(l.metadata,'$.sales.checked_at')),'1970-01-01')) LIMIT 20");
$count=0;
foreach($q->fetchAll(PDO::FETCH_COLUMN) as $id) {
    try {
        $auth=SalesWebhookAuth::forCron($db,$id);
        $service=new SalesService($db,$auth->company,'','',$provider,$auth);
        $service->reconcile($id);$count++;
    } catch(Throwable $e) {fwrite(STDERR,"Sales-avstemming krever manuell kontroll.\n");}
}
echo "Sales: $count betalinger kontrollert.\n";
