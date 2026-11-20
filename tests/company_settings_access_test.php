<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/company_settings_access.php';
$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->exec('CREATE TABLE tenant_companies(id TEXT,is_active INT);
CREATE TABLE tenant_admins(id TEXT,company_id TEXT,is_active INT,tenant_supervisor INT);
CREATE TABLE system_modules(id TEXT,module_key TEXT,is_active INT);
CREATE TABLE tenant_admin_modules(tenant_admin_id TEXT,module_id TEXT);
CREATE TABLE tenant_company_modules(company_id TEXT,module_id TEXT,is_active INT);
INSERT INTO tenant_companies VALUES("a",1),("b",1);
INSERT INTO tenant_admins VALUES("supervisor","a",1,1),("website","a",1,0),("other","b",1,1),("inactive","a",0,1);
INSERT INTO system_modules VALUES("web","ditt_nettsted",1);
INSERT INTO tenant_company_modules VALUES("a","web",1);
INSERT INTO tenant_admin_modules VALUES("website","web");');
function denied(PDO $db, string $admin, string $company): void {
    $_SESSION = ['tenant_admin_id'=>$admin,'company_id'=>'a'];
    try { companySettingsRequireAccess($db,$company); } catch (DomainException $e) { return; }
    throw new RuntimeException('Unauthorized company settings access');
}
$_SESSION=['tenant_admin_id'=>'supervisor','company_id'=>'a'];
companySettingsRequireAccess($db,'a');
$_SESSION=['tenant_admin_id'=>'website','company_id'=>'a'];
companySettingsRequireAccess($db,'a');
// Old unlock flags are not required; active tenant, role and module remain required.
denied($db,'other','a'); denied($db,'inactive','a'); denied($db,'','a'); denied($db,'website','b');
$db->exec('UPDATE tenant_company_modules SET is_active=0');
denied($db,'website','a');
$db->exec('UPDATE tenant_company_modules SET is_active=1; DELETE FROM tenant_admin_modules');
denied($db,'website','a');
$db->exec('DELETE FROM tenant_admin_modules; DELETE FROM tenant_company_modules');
$_SESSION=['tenant_admin_id'=>'supervisor','company_id'=>'a'];
companySettingsRequireAccess($db,'a');
$db->exec('UPDATE tenant_companies SET is_active=0 WHERE id="a"');
denied($db,'supervisor','a');
echo "Company settings: no extra password; supervisor, delegated module, active account and tenant isolation passed.\n";
