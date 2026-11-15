<?php
require_once __DIR__.'/../includes/rental_schedule_editing.php';
require_once __DIR__.'/../includes/rental_membership_pricing.php';
function weekAssert(bool $ok,string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function weekReject(callable $fn,string $message): void {
    try { $fn(); } catch (RentalWeekPriceException $e) { return; }
    throw new RuntimeException($message);
}
$daily = array_combine(rentalWeekDays(),['500.00','500.00','600.00','600.00','800.00','1800.00','1600.00']);
$groups = [
    ['slot'=>1,'start_day'=>2,'end_day'=>3,'price'=>'900.00'],
    ['slot'=>2,'start_day'=>6,'end_day'=>7,'price'=>'3000.00']
];
$object = ['pricing_model'=>'weekly','daily_prices'=>$daily,'price_groups'=>$groups,
    'rental_price'=>999999,'weekday_prices'=>'{invalid','weekend_price'=>888888];
$cases = [];
foreach (['2026-11-16','2026-12-28','2027-03-26','2027-10-29'] as $date) {
    for ($i=0;$i<16;$i++) {
        $start = rentalWeekDate($date)->modify("+$i days")->format('Y-m-d');
        $end = rentalWeekDate($start)->modify('+'.($i%11).' days')->format('Y-m-d');
        foreach ([$groups,[],[['slot'=>1,'start_day'=>7,'end_day'=>2,'price'=>'750.00']]] as $gs) {
            $rates = array_replace($object,['price_groups'=>$gs]);
            $q = rentalWeekQuote($rates,$start,$end);
            $cases[] = ['rates'=>$rates,'startDate'=>$start,'endDate'=>$end,'expected'=>$q];
        }
    }
}
if (in_array('--json',$argv,true)) { echo json_encode($cases,JSON_THROW_ON_ERROR); exit; }
weekAssert(rentalPeriodPriceCents($object,'2026-11-16','2026-11-22')===580000,'Full week packages and singles');
weekAssert(rentalPeriodPriceCents($object,'2026-11-17','2026-11-17')===90000,'Partial touch uses full coupled price');
weekAssert(rentalPeriodPriceCents($object,'2026-11-21','2026-11-22')===300000,'Weekend only once, not both daily prices');
weekAssert(rentalPeriodPriceCents($object,'2026-11-22','2026-11-22')===300000,'Historical Sunday touch semantics');
weekAssert(rentalPeriodPriceCents($object,'2026-11-16','2026-11-29')===1160000,'Repeated groups per occurrence');
$wrap = array_replace($object,['price_groups'=>[['slot'=>1,'start_day'=>7,'end_day'=>1,'price'=>'700.00']]]);
weekAssert(rentalPeriodPriceCents($wrap,'2026-11-22','2026-11-23')===70000,'Sunday-Monday belongs to same anchored group');
$free = array_replace($object,['daily_prices'=>array_fill_keys(rentalWeekDays(),'0.00'),
    'price_groups'=>[['slot'=>1,'start_day'=>2,'end_day'=>3,'price'=>'0.00']]]);
weekAssert(rentalPeriodPriceCents($free,'2026-11-16','2026-11-22')===0,'Zero does not fall back to old rates');
weekAssert(rentalPeriodPriceCents(array_replace($object,['pricing_model'=>'legacy','weekday_prices_enabled'=>0,
    'rental_price'=>100,'weekend_price'=>300]),'2026-11-21','2026-11-22')===30000,'Legacy ignores new fields');
foreach ([
    ['daily_prices'=>array_slice($daily,0,6,true)],['daily_prices'=>'{broken'],
    ['daily_prices'=>array_replace($daily,['monday'=>-1])],['price_groups'=>null],
    ['price_groups'=>[$groups[0],array_replace($groups[1],['start_day'=>3])]],
    ['price_groups'=>[$groups[0],$groups[0]]],['price_groups'=>[$groups[0],$groups[1],$groups[0]]],
    ['price_groups'=>[['slot'=>1,'start_day'=>1,'end_day'=>1,'price'=>100]]],
    ['price_groups'=>[['slot'=>1,'start_day'=>1,'end_day'=>2,'price'=>'1.111']]],
    ['pricing_model'=>'unknown']
] as $patch) weekReject(fn()=>rentalPeriodPriceCents(array_replace($object,$patch),'2026-11-16','2026-11-22'),'Invalid tariff accepted');
weekReject(fn()=>rentalPeriodPriceCents($object,'2026-02-30','2026-03-01'),'Invalid calendar date accepted');
weekReject(fn()=>rentalPeriodPriceCents($object,'2026-11-22','2026-11-21'),'Reversed date accepted');
weekReject(fn()=>rentalPeriodPriceCents($object,'2020-01-01','2040-01-01'),'Excessive period accepted');
$member = rentalMembershipPrice(580000,'2026-11-16','2026-11-22',[
    ['benefit_type'=>'amount_discount','benefit_value'=>50,'price_basis'=>'rental_day']]);
weekAssert($member['member_base_cents']===545000,'Day discount uses seven calendar days, not five price lines');

// Disposable in-memory database only. Never loads config/database.php.
$db = new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec("CREATE TABLE rental_objects(id TEXT PRIMARY KEY,company_id TEXT,pricing_model TEXT DEFAULT 'legacy',
    daily_prices TEXT,rental_price TEXT DEFAULT '100.00')");
$db->exec('CREATE TABLE rental_price_groups(id TEXT PRIMARY KEY,company_id TEXT,rental_object_id TEXT,
    slot INT,start_day INT,end_day INT,price TEXT,UNIQUE(company_id,rental_object_id,slot))');
$db->exec("INSERT INTO rental_objects(id,company_id) VALUES('one','a'),('two','b')");
$get = fn($id)=>$db->query('SELECT * FROM rental_objects WHERE id='.$db->quote($id))->fetch(PDO::FETCH_ASSOC);
$input = $object + ['pricing_version'=>rentalWeekAttach($db,$get('one'))['pricing_version']];
$db->beginTransaction(); rentalWeekSave($db,'a','one',$input,$get('one')); $db->commit();
$read = rentalWeekAttach($db,$get('one'));
weekAssert($read['daily_prices']===$daily && $read['price_groups']===$groups,'Roundtrip preserves all prices');
weekAssert($get('one')['rental_price']==='100.00','Old values not overwritten');
weekAssert($get('two')['pricing_model']==='legacy','Other company unchanged');
$input['pricing_version']=$read['pricing_version'];
$changes = (int)$db->query('SELECT total_changes()')->fetchColumn();
$db->beginTransaction(); rentalWeekSave($db,'a','one',$input,$get('one')); $db->commit();
weekAssert((int)$db->query('SELECT total_changes()')->fetchColumn()===$changes,'Unchanged save performs no writes');
$stale = array_replace($input,['pricing_version'=>'stale']);
$db->beginTransaction();
weekReject(fn()=>rentalWeekSave($db,'a','one',$stale,$get('one')),'Stale price save accepted');
$db->rollBack();
$db->beginTransaction();
weekReject(fn()=>rentalWeekSave($db,'a','two',$input,$get('two')),'Cross-company tariff accepted');
$db->rollBack();
$input['price_groups']=[$groups[1]];
$db->beginTransaction(); rentalWeekSave($db,'a','one',$input,$get('one')); $db->commit();
$read=rentalWeekAttach($db,$get('one'));
weekAssert($read['price_groups']===[$groups[1]] && $read['daily_prices']===$daily,'Remove one group without changing singles');
$db->beginTransaction();
weekReject(fn()=>rentalWeekSave($db,'a','one',['rental_price'=>500],$get('one')),'Old client overwrote weekly tariff');
$db->rollBack();
$old = new PDO('sqlite::memory:');
$old->exec('CREATE TABLE rental_objects(id TEXT,company_id TEXT)');
weekReject(function()use($old,$input){$old->beginTransaction();try{rentalWeekSave($old,'a','one',$input,null);}finally{$old->rollBack();}},'Missing migration silently accepted');
weekAssert(count($old->query('PRAGMA table_info(rental_objects)')->fetchAll())===2,'No runtime schema creation');
echo "Rental weekly pricing, persistence, concurrency and membership tests passed\n";
