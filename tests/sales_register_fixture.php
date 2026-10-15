<?php
declare(strict_types=1);
function salesInstallRegisterFixture(PDO $db,string $root): array {
    $delimiter=';';$buffer='';$statements=[];
    foreach(explode("\n",file_get_contents($root.'/sql/sales_register_setup.sql')) as $line) {
        if(preg_match('/^\s*--/',$line))continue;
        if(preg_match('/^DELIMITER (.+)$/',$line,$m)) {$delimiter=trim($m[1]);continue;}
        $buffer.=$line."\n";
        if(str_ends_with(trim($buffer),$delimiter)) {
            $sql=substr(trim($buffer),0,-strlen($delimiter));
            if(trim($sql)!==''){$db->query($sql)->closeCursor();$statements[]=$sql;}
            $buffer='';
        }
    }
    return $statements;
}
/** Explicit synthetic register setup, never imported by application code. */
function salesFixtureRegister(SalesService $service): void {
    $state=$service->registerState();
    if($state['readiness_error'])throw new RuntimeException($state['readiness_error']);
    if($state['selected_register_id'])return;
    $register=$state['registers'][0]??null;
    if(!$register) {
        $state=$service->registerAction('register_create',['name'=>'Fixture POS','register_code'=>'FIXTURE','idempotency_key'=>'fixture-default-register']);
        $register=$state['registers'][0];
        $service->registerAction('register_activate',['register_id'=>$register['id'],'idempotency_key'=>'fixture-default-activate']);
    }
    $state=$service->registerAction('register_select',['register_id'=>$register['id']]);
    if(!$state['period'])$service->registerAction('register_open',['register_id'=>$register['id'],'opening_float_minor'=>0,'idempotency_key'=>'fixture-default-opening']);
}
