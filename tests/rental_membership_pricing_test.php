<?php
require_once __DIR__.'/../includes/rental_membership_pricing.php';
if (!empty($argv[1])) {
    if (!file_exists($argv[1].'/mysql.sock')) throw new RuntimeException('Isolated MySQL socket is required.');
    $db = new PDO('mysql:unix_socket='.$argv[1].'/mysql.sock','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $db->exec('CREATE DATABASE rental_membership_test');
    $db->exec('USE rental_membership_test');
} else {
    $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
}
$db->exec("
CREATE TABLE membership_product_modifications(company_id TEXT,output_product_id TEXT,membership_plan_id TEXT,benefit_type TEXT,benefit_value TEXT,price_basis TEXT,is_active INT,public_display INT);
CREATE TABLE output_products(id TEXT,company_id TEXT,product_type TEXT,product_id TEXT);
CREATE TABLE membership_plans(id TEXT,company_id TEXT,name TEXT,is_active INT);
CREATE TABLE membership_members(company_id TEXT,customer_id TEXT,membership_plan_id TEXT,status TEXT,payment_status TEXT,join_date TEXT,expiration_date TEXT);
INSERT INTO output_products VALUES ('op','a','rental','venue'),('op','b','rental','venue');
INSERT INTO membership_plans VALUES ('p','a','Plan A',1),('p2','a','Plan B',1),('p','b','Foreign',1);
INSERT INTO membership_product_modifications VALUES
('a','op','p','amount_discount','50','rental_day',1,0),
('a','op','p2','percent_discount','15','booking_total',1,1),
('b','op','p','included',NULL,'booking_total',1,1);
INSERT INTO membership_members VALUES
('a','customer','p','active','paid','2026-01-01','2026-12-31'),
('a','customer','p2','active','paid','2026-01-01','2026-12-31'),
('a','expired','p','active','paid','2025-01-01','2026-01-01'),
('a','unpaid','p','active','unpaid','2026-01-01','2026-12-31'),
('a','future','p','active','paid','2026-12-01','2027-12-31'),
('a','inactive','p','inactive','paid','2026-01-01','2026-12-31'),
('b','foreign','p','active','paid','2026-01-01','2026-12-31');
");
$check = static function(bool $value, string $message): void {
    if (!$value) throw new RuntimeException($message);
};
$rules = rentalEligibleMembershipBenefits($db,'a','venue','customer','2026-11-14');
$check(count($rules) === 2,'Only same-company active benefits; public_display=0 still applies');
foreach (['expired','unpaid','future','inactive','foreign','unknown',null] as $customer) {
    $check(rentalEligibleMembershipBenefits($db,'a','venue',$customer,'2026-11-14') === [],'Reject ineligible membership: '.($customer ?? 'none'));
}
$check(rentalEligibleMembershipBenefits($db,'a','another-object','customer','2026-11-14') === [],'Scope rental object');
$price = rentalMembershipPrice(300000,'2026-11-14','2026-11-15',$rules);
$check($price['member_base_cents'] === 255000 && $price['benefit']['plan_id'] === 'p2','Use best eligible plan, never stack');
$daily = rentalMembershipPrice(300000,'2026-11-14','2026-11-15',[$rules[0]]);
$check($daily['member_base_cents'] === 290000 && $daily['rental_days'] === 2,'One weekend package, two discount days');
$check(rentalMembershipPrice(10000,'2026-10-24','2026-10-26',[$rules[0]])['rental_days'] === 3,'DST counts calendar days');
$check(rentalApplyMembershipBenefit(1000,['benefit_type'=>'fixed_price','price_basis'=>'unit','benefit_value'=>'20'],1) === 1000,'Benefit cannot increase price');
$check(rentalApplyMembershipBenefit(1000,['benefit_type'=>'included','price_basis'=>'booking_total'],1) === 0,'Included means zero base rent');
$check(rentalApplyMembershipBenefit(1000,['benefit_type'=>'access','price_basis'=>'unit'],1) === 1000,'Access leaves price intact');
$check(rentalApplyMembershipBenefit(101,['benefit_type'=>'percent_discount','price_basis'=>'booking_total','benefit_value'=>'50'],1) === 51,'Half-up cent rounding agrees with JS');
$db->exec("UPDATE membership_product_modifications SET is_active=0 WHERE company_id='a' AND membership_plan_id='p'");
$check(count(rentalEligibleMembershipBenefits($db,'a','venue','customer','2026-11-14')) === 1,'Removed benefit stays removed, no legacy fallback');
$db->exec("UPDATE membership_plans SET is_active=0 WHERE company_id='a' AND id='p2'");
$check(rentalEligibleMembershipBenefits($db,'a','venue','customer','2026-11-14') === [],'Inactive plan cannot grant benefit');
try {
    rentalMembershipPrice(1000,'2026-02-30','2026-03-04',[]);
    throw new RuntimeException('Invalid date accepted');
} catch (InvalidArgumentException $expected) {}
echo "Rental membership pricing tests passed.\n";
