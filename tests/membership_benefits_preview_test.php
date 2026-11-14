<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/membership_benefits_preview.php';

function benefitsCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec("
CREATE TABLE tenant_admins (id TEXT,company_id TEXT,is_active INT);
CREATE TABLE tenant_admin_modules (tenant_admin_id TEXT,module_id TEXT);
CREATE TABLE system_modules (id TEXT,module_key TEXT,is_active INT);
CREATE TABLE tenant_company_modules (company_id TEXT,module_id TEXT,is_active INT);
INSERT INTO tenant_admins VALUES ('admin-a','a',1),('admin-b','b',1),('disabled','a',0),('no-right','a',1);
INSERT INTO system_modules VALUES ('module','membership',1);
INSERT INTO tenant_admin_modules VALUES ('admin-a','module'),('admin-b','module'),('disabled','module');
INSERT INTO tenant_company_modules VALUES ('a','module',1),('b','module',0);
CREATE TABLE tenant_companies (id TEXT,name TEXT,img_url TEXT);
CREATE TABLE tenant_website_general (company_id TEXT,header_height TEXT,logo_height TEXT,header_bg_color TEXT,logo_position TEXT);
CREATE TABLE membership_plans (id TEXT,company_id TEXT,name TEXT,description TEXT,price NUMERIC,image_url TEXT,is_active INT,for_sale INT);
CREATE TABLE output_products (id TEXT,company_id TEXT,product_type TEXT,product_id TEXT,name TEXT,description TEXT,is_visible INT,is_public INT,created_at TEXT);
CREATE TABLE events_eventdata (id TEXT,company_id TEXT,title TEXT,description TEXT,ticket_price NUMERIC,event_image_url TEXT,start_date TEXT,start_time TEXT,is_active INT,is_member_exclusive INT);
CREATE TABLE event_membership_plans (event_id TEXT,membership_plan_id TEXT,ticket_price NUMERIC,public_display INT);
CREATE TABLE membership_product_modifications (company_id TEXT,output_product_id TEXT,membership_plan_id TEXT,benefit_type TEXT,benefit_value NUMERIC,price_basis TEXT,public_display INT,is_active INT);
CREATE TABLE rental_objects (id TEXT,company_id TEXT,object_name TEXT,rental_price NUMERIC,images TEXT,key_box_code TEXT,is_active INT,member_exclusive INT);
CREATE TABLE sales_products (id TEXT,company_id TEXT,name TEXT,selling_price NUMERIC,img_product TEXT,cost_price NUMERIC,is_active INT);
INSERT INTO tenant_companies VALUES ('a','Testklubb','https://example.org/logo.png'),('b','Annen bedrift','');
INSERT INTO tenant_website_general VALUES ('a','110px','60px','#123456','right');
INSERT INTO membership_plans VALUES ('m','a','Medlem','Medlemskap',250,'',1,1),('other','b','Fremmed','',900,'',1,1);
INSERT INTO events_eventdata VALUES ('e','a','Årsfest','Velkommen',500,'', '2026-12-05','19:00',1,1),('eb','b','Privat','',1000,'','2026-12-01','19:00',1,0);
INSERT INTO event_membership_plans VALUES ('e','m',0,1),('e','other',1,1),('eb','m',1,1);
INSERT INTO rental_objects VALUES ('r','a','Lokale',400,'[{\"url\":\"https://example.org/venue.jpg\"}]','SECRET-CODE',1,1);
INSERT INTO sales_products VALUES ('s','a','Kaffe',25,'https://example.org/coffee.jpg',3,1);
INSERT INTO output_products VALUES
 ('cm','a','membership','m','Katalog','',1,1,'2026-01-01'),
 ('ce','a','events','e','','',1,1,'2026-01-03'),
 ('ce-old','a','events','e','','',1,1,'2026-01-01'),
 ('cr','a','rental','r','','',1,1,'2026-01-01'),
 ('cs','a','product','s','','',0,1,'2026-01-01'),
 ('bad','a','events','eb','','',1,1,'2026-01-01'),
 ('other','b','events','eb','','',1,1,'2026-01-01');
");
$db->exec("INSERT INTO membership_product_modifications VALUES
 ('a','cr','m','percent_discount',15,'booking_total',0,1),
 ('a','cs','m','included',NULL,'unit',1,0),
 ('b','cr','m','fixed_price',1,'unit',1,1),
 ('a','cr','other','fixed_price',2,'unit',1,1),
 ('a','ce','m','fixed_price',99,'unit',1,1),
 ('a','cm','m','included',NULL,'unit',1,1)");
$before = $db->query('SELECT total_changes()')->fetchColumn();
benefitsCheck(membershipBenefitsPreviewCanAccess($db,'admin-a','a'), 'Active delegated Membership access is allowed');
benefitsCheck(!membershipBenefitsPreviewCanAccess($db,'admin-a','b'), 'Session company must match administrator company');
benefitsCheck(!membershipBenefitsPreviewCanAccess($db,'disabled','a'), 'Inactive administrator is denied');
benefitsCheck(!membershipBenefitsPreviewCanAccess($db,'no-right','a'), 'Missing delegated module is denied');
benefitsCheck(!membershipBenefitsPreviewCanAccess($db,'admin-b','b'), 'Inactive company module is denied');
$data = membershipBenefitsPreviewLoad($db, 'a');
benefitsCheck(count($data['plans']) === 1 && $data['plans'][0]['id'] === 'm', 'Plans are company scoped');
benefitsCheck(count($data['products']) === 4, 'Deduplicated catalog excludes another company source');
$products = array_column($data['products'], null, 'type');
benefitsCheck($products['events']['name'] === 'Årsfest' && $products['events']['exclusive'], 'Event source and membership requirement are retained');
benefitsCheck($products['events']['membership_prices'] === [['plan_id'=>'m','price'=>0.0,'public_display'=>true]], 'Free member price retained and cross-company links excluded');
benefitsCheck($products['rental']['name'] === 'Lokale' && $products['rental']['price'] === 400.0, 'Rental uses actual object name and rental_price');
benefitsCheck($products['rental']['exclusive'], 'User-added rental member_exclusive is represented in the simulator');
benefitsCheck($products['rental']['assigned_benefits'] === [['plan_id'=>'m','benefit_type'=>'percent_discount','benefit_value'=>15.0,'price_basis'=>'booking_total','public_display'=>false]], 'Only active company-scoped saved rules returned, with basis and private visibility');
benefitsCheck($products['events']['assigned_benefits'] === [] && $products['membership']['assigned_benefits'] === [], 'Events keep their authoritative model and membership cannot receive benefits');
benefitsCheck($products['product']['assigned_benefits'] === [], 'Inactive benefit rules are not presented as assigned');
benefitsCheck($products['rental']['image_url'] === 'https://example.org/venue.jpg', 'Rental JSON image normalized');
benefitsCheck(!$products['product']['is_visible'], 'Hidden catalog flag is preserved');
benefitsCheck($data['company']['header_height'] === '110px' && $data['company']['logo_position'] === 'right', 'News company theme reused');
benefitsCheck(!str_contains(json_encode($data), 'SECRET-CODE') && !str_contains(json_encode($data), 'cost_price'), 'Private access codes and purchase costs never leave endpoint projection');
benefitsCheck(count($data['warnings']) === 1, 'Unavailable source explicitly reported');
benefitsCheck($before === $db->query('SELECT total_changes()')->fetchColumn(), 'Catalog preview never writes data');
$other = membershipBenefitsPreviewLoad($db, 'b');
benefitsCheck(count($other['products']) === 1 && $other['products'][0]['source_id'] === 'eb', 'Second company cannot see first company catalog');
$db->exec("UPDATE output_products SET product_type='sales_item' WHERE id='cs'");
$legacyBefore=$db->query('SELECT total_changes()')->fetchColumn();
$legacyPreview=membershipBenefitsPreviewLoad($db,'a');
$legacyProducts=array_column($legacyPreview['products'],null,'type');
benefitsCheck(isset($legacyProducts['product']) && $legacyProducts['product']['source_id']==='s','Legacy Sales catalogue type is included');
benefitsCheck($legacyBefore===$db->query('SELECT total_changes()')->fetchColumn(),'Legacy type normalization does not write to database');
$db->exec('ALTER TABLE rental_objects ADD COLUMN is_available INT DEFAULT 1');
$db->exec("UPDATE rental_objects SET is_available=0 WHERE id='r'");
$hiddenRental=membershipBenefitsPreviewLoad($db,'a');
$hiddenProducts=array_column($hiddenRental['products'],null,'type');
benefitsCheck(!$hiddenProducts['rental']['is_visible'] && !$hiddenProducts['rental']['is_public'],'Source-hidden rental is not displayed even with a stale visible catalogue row');
$db->exec("ALTER TABLE events_eventdata ADD COLUMN registration_type TEXT DEFAULT 'signup'");
$db->exec("ALTER TABLE events_eventdata ADD COLUMN end_date TEXT");
$db->exec("UPDATE events_eventdata SET end_date='2000-01-01' WHERE id='e'");
$expired=membershipBenefitsPreviewLoad($db,'a');
benefitsCheck(!in_array('events',array_column($expired['products'],'type'),true),'Expired events are excluded by end_date');
$today=(new DateTimeImmutable('now',new DateTimeZone('Europe/Oslo')))->format('Y-m-d');
$db->prepare("UPDATE events_eventdata SET end_date=?,registration_type='none' WHERE id='e'")->execute([$today]);
$current=membershipBenefitsPreviewLoad($db,'a');
$currentProducts=array_column($current['products'],null,'type');
benefitsCheck(isset($currentProducts['events']) && !$currentProducts['events']['benefit_eligible'],'Final-day event remains, without registration it is ineligible');
benefitsCheck(!$currentProducts['membership']['benefit_eligible'],'Membership is retained but cannot receive a benefit');
$db->exec("UPDATE events_eventdata SET registration_type='binding' WHERE id='e'");
$binding=membershipBenefitsPreviewLoad($db,'a');
$bindingProducts=array_column($binding['products'],null,'type');
benefitsCheck($bindingProducts['events']['benefit_eligible'],'Binding registration can receive benefits');
$db->exec('DROP TABLE membership_product_modifications');
$unavailable=membershipBenefitsPreviewLoad($db,'a');
benefitsCheck(in_array('Tildelte produktfordeler kunne ikke hentes. Fordelsindikatorene er ufullstendige.',$unavailable['warnings'],true), 'Missing rule table is explicitly reported rather than silently claiming no assigned benefits');
echo "Membership benefits read-only projection tests passed\n";
