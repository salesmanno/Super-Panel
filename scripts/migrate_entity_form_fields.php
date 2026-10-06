<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../includes/entity_form_migration.php';
try {
    $db=getDBConnection();
    $options=getopt('',['apply','expected:','conflicts:']);
    if(isset($options['apply'])) {
        if(empty($options['expected']))throw new RuntimeException('Kjør kontrollrapport og oppgi --expected før overføring');
        $result=entityFormMigrationApply($db,(string)$options['expected'],(string)($options['conflicts']??'abort'));
    }else {
        $plan=entityFormMigrationPlan($db);
        $result=$plan['report']+['fingerprint'=>$plan['fingerprint'],'mode'=>'read-only',
            'conflict_choices'=>['abort','keep-shared','use-legacy']];
    }
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR).PHP_EOL;
}catch(Throwable $e){
    fwrite(STDERR,($e instanceof PDOException?'Kontroll/overføring feilet i databasen':$e->getMessage()).PHP_EOL);exit(1);
}
