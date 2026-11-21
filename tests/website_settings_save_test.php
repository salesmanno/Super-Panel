<?php
declare(strict_types=1);

// Run the actual endpoint with isolated data. Never connect to a project database.
if (isset($argv[1])) {
    final class WebsiteSaveDatabase {
        public array $writes = [];
        public bool $transaction = false;
        public array $general = [
            'id'=>'settings','company_id'=>'company','footer_mode'=>'static','header_mode'=>'static',
            'logo_url'=>'legacy-relative-logo.png','favicon_url'=>'favicon.ico','hero_title'=>'Keep this title',
            'hero_description'=>'Keep this text','contact_email'=>'preserve@example.test',
            'footer_text'=>'Existing footer','footer_links'=>'[]','maintenance_mode'=>1,'is_active'=>0,
            'header_bg_color'=>'#3366CC'
        ];
        public array $config = ['id'=>'config','is_enabled'=>1,'is_published'=>1,'seo_title'=>'Keep SEO'];
        public function beginTransaction(): void {$this->transaction=true;}
        public function commit(): void {$this->transaction=false;}
        public function rollBack(): void {$this->transaction=false;}
        public function inTransaction(): bool {return $this->transaction;}
        public function prepare(string $sql): WebsiteSaveStatement {
            if (preg_match('/\b(custom_css|custom_js|spacing_unit)\b/', $sql)) throw new RuntimeException('Removed column referenced');
            return new WebsiteSaveStatement($this,$sql);
        }
    }
    final class WebsiteSaveStatement {
        public function __construct(private WebsiteSaveDatabase $db,private string $sql) {}
        public function execute(array $params=[]): void {
            if (substr_count($this->sql,'?')!==count($params)) throw new RuntimeException('SQL parameter count mismatch');
            if (preg_match('/^(UPDATE|INSERT)/',trim($this->sql))) {
                if (!$this->db->inTransaction()) throw new RuntimeException('Write outside transaction');
                $this->db->writes[]=['sql'=>$this->sql,'params'=>$params];
            }
        }
        public function fetch(): array|false {
            if (str_contains($this->sql,'FROM tenant_website_general')) return $this->db->general ?: false;
            if (str_contains($this->sql,'FROM tenant_website_config')) return $this->db->config ?: false;
            if (str_contains($this->sql,'FROM tenant_companies')) return ['name'=>'Testfirma'];
            return false;
        }
    }
    function getDBConnection(): WebsiteSaveDatabase {return $GLOBALS['websiteTestDB'];}
    function companySettingsRequireAccess($db,string $company): void {
        if ($company!=='company'||$GLOBALS['websiteTestCase']==='denied') throw new DomainException('Ingen tilgang');
    }
    $GLOBALS['websiteTestCase']=$argv[1];
    $GLOBALS['websiteTestDB']=$db=new WebsiteSaveDatabase;
    $_SESSION=['tenant_admin_id'=>'admin','company_id'=>'company'];
    $payload=match($argv[1]){
        'unchanged'=>['general'=>['footer_mode'=>'static'],'config'=>[]],
        'invalid'=>['general'=>['footer_mode'=>'broken']],
        'insert'=>['general'=>['footer_mode'=>'dynamic'],'config'=>['seo_title'=>'New SEO']],
        default=>['general'=>['footer_mode'=>'dynamic'],'config'=>[]]
    };
    if ($argv[1]==='insert') {$db->general=[];$db->config=[];}
    if ($argv[1]==='create') {$db->general=[];$db->config=[];}
    $GLOBALS['websiteTestInput']=json_encode($payload);
    $source=file_get_contents(__DIR__.'/../api/'.($argv[1]==='create'?'create_website_settings':'update_webside_data').'.php');
    $source=str_replace(['<?php','session_start();'], '', $source);
    $source=preg_replace('/^\s*require_once __DIR__.*(?:database|company_settings_access)\.php.*;\s*$/m','',$source);
    $source=str_replace("file_get_contents('php://input')", "\$GLOBALS['websiteTestInput']", $source);
    ob_start();eval($source);$rawResponse=ob_get_clean();$response=json_decode($rawResponse,true,512,JSON_THROW_ON_ERROR);
    echo json_encode(['response'=>$response,'writes'=>$db->writes]);
    exit;
}
function websiteAssert(bool $passed,string $message): void {
    if (!$passed) throw new RuntimeException($message);
}
foreach (['change','unchanged','invalid','denied','insert','create'] as $case) {
    $command=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' '.escapeshellarg($case);
    exec($command,$lines,$status);
    websiteAssert($status===0,'Endpoint fixture failed: '.$case);
    $result=json_decode(implode("\n",$lines),true,512,JSON_THROW_ON_ERROR);$lines=[];
    if (in_array($case,['invalid','denied'])) {
        websiteAssert(!$result['response']['success']&&!$result['writes'],'Invalid/unauthorized write accepted');
    } else {
        websiteAssert($result['response']['success'],'Save failed: '.$case);
        $writes=$result['writes'];
        websiteAssert(count($writes)===match($case){'unchanged'=>0,'change'=>1,default=>2},'Unexpected number of writes: '.$case);
        if ($case==='change') {
            websiteAssert(str_contains($writes[0]['sql'],'`footer_mode` = ?'),'Footer mode not saved');
            websiteAssert($writes[0]['params']===['dynamic','admin','company'],'Wrong update or tenant scope');
            foreach (['logo_url','hero_title','contact_email','footer_text','maintenance_mode','is_active'] as $field) {
                websiteAssert(!str_contains($writes[0]['sql'],$field),'Removed field overwritten: '.$field);
            }
        }
    }
}
echo "PASS: footer save, removed columns, partial updates, no-op, validation, permissions and both creation paths\n";
