<?php
declare(strict_types=1);
// Called before PDO owners are released and browser processes are spawned.
$retrySeed=new SalesService($db,'a','admin','retry-browser-seed',new SalesFixtureVipps());
$retryProduct=$retrySeed->saveProduct(['name'=>'Vipps retry customized','selling_price'=>'100','stock_quantity'=>100])['product'];
$setup->prepare("INSERT INTO dynamic_form_fields(id,company_id,entity_type,entity_id,field_name,field_label,field_type,is_required,is_visible,sort_order) VALUES(UUID(),'a','sales_product',?,'engraving','Gravering','text',1,1,1)")
    ->execute([$retryProduct['id']]);
unset($retrySeed,$retryProduct);
