<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/membership_product_benefits.php';
function benefitAssert(bool $ok,string $why): void { if (!$ok) throw new RuntimeException($why); }
$db = new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec("CREATE TABLE output_products(id TEXT PRIMARY KEY,company_id TEXT,product_type TEXT,product_id TEXT);
CREATE TABLE rental_objects(id TEXT PRIMARY KEY,company_id TEXT,object_name TEXT,is_active INT,rental_price NUMERIC);
CREATE TABLE events_eventdata(id TEXT PRIMARY KEY,company_id TEXT,title TEXT,is_active INT,registration_type TEXT,end_date TEXT);
CREATE TABLE membership_plans(id TEXT PRIMARY KEY,company_id TEXT,is_active INT);
CREATE TABLE membership_product_modifications(id TEXT PRIMARY KEY,company_id TEXT,output_product_id TEXT,membership_plan_id TEXT,benefit_type TEXT,benefit_value NUMERIC,price_basis TEXT,public_display INT,is_active INT,updated_at TEXT, UNIQUE(company_id,output_product_id,membership_plan_id));
CREATE TABLE event_membership_plans(event_id TEXT,ticket_price NUMERIC);
INSERT INTO output_products VALUES ('r','a','rental','rs'),('foreign','b','rental','rb'),('cross','a','rental','rb'),('e','a','events','es'),('m','a','membership','plan');
INSERT INTO rental_objects VALUES('rs','a','Lokale',1,1150),('rb','b','Privat',1,900);
INSERT INTO events_eventdata VALUES('es','a','Uten påmelding',1,'none','2099-01-01');
INSERT INTO membership_plans VALUES('plan','a',1),('plan2','a',1),('other','b',1),('disabled','a',0);
INSERT INTO event_membership_plans VALUES('es',500);");
$save = function(array $input) use($db) {
    $db->beginTransaction();
    try {$result=membershipProductBenefitSave($db,'a',$input);$db->commit();return $result;}
    catch(Throwable $e){$db->rollBack();throw $e;}
};
$input=['product_id'=>'r','plan_id'=>'plan','benefit'=>['benefit_type'=>'amount_discount','benefit_value'=>50,'price_basis'=>'rental_day']];
$r=$save($input);
benefitAssert($r['benefit']['benefit_value']===50.0 && $r['benefit']['price_basis']==='rental_day','Save daily discount');
$save($input);
benefitAssert((int)$db->query('SELECT COUNT(*) FROM membership_product_modifications')->fetchColumn()===1,'Repeated save is idempotent');
$input['benefit']=['benefit_type'=>'percent_discount','benefit_value'=>15.5,'price_basis'=>'booking_total'];
$save($input);
benefitAssert((float)$db->query('SELECT benefit_value FROM membership_product_modifications')->fetchColumn()===15.5,'Replace rule rather than stack');
$save(array_replace($input,['plan_id'=>'plan2']));
benefitAssert((int)$db->query('SELECT COUNT(*) FROM membership_product_modifications')->fetchColumn()===2,'Separate plan rules');
$save(array_replace($input,['benefit'=>null]));
benefitAssert((int)$db->query("SELECT is_active FROM membership_product_modifications WHERE membership_plan_id='plan'")->fetchColumn()===0,'Removal deactivates new rule');
$r=$save($input);
benefitAssert($r['benefit']['benefit_value']===15.5,'Reactivation works');
foreach([
    array_replace($input,['product_id'=>'foreign']),array_replace($input,['product_id'=>'cross']),
    array_replace($input,['plan_id'=>'other']),array_replace($input,['plan_id'=>'disabled']),
    array_replace($input,['product_id'=>'m']),array_replace($input,['product_id'=>'e']),
    array_replace($input,['benefit'=>['benefit_type'=>'percent_discount','benefit_value'=>101,'price_basis'=>'booking_total']]),
    array_replace($input,['benefit'=>['benefit_type'=>'amount_discount','benefit_value'=>-1,'price_basis'=>'rental_day']]),
    array_replace($input,['benefit'=>['benefit_type'=>'access','price_basis'=>'member_exclusive']]),
] as $bad) {
    $rejected=false;try{$save($bad);}catch(InvalidArgumentException $e){$rejected=true;}
    benefitAssert($rejected,'Invalid or cross-company rule rejected');
}
benefitAssert((int)$db->query('SELECT COUNT(*) FROM membership_product_modifications')->fetchColumn()===2,'Rejections leave saved rules intact');
benefitAssert((float)$db->query("SELECT rental_price FROM rental_objects WHERE id='rs'")->fetchColumn()===1150.0,'Benefit writes do not touch ordinary prices');
benefitAssert((float)$db->query('SELECT ticket_price FROM event_membership_plans')->fetchColumn()===500.0,'Legacy table untouched');
echo "Persistent membership benefit isolation and validation tests passed\n";
