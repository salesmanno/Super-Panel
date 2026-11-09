<?php
declare(strict_types=1);

if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/lib/EventBookingRecovery.php';

$args=getopt('',['database:','company:','event:','reference:','amount:','apply','confirm-capture']);
try {
    foreach(['database','company','event','reference','amount'] as $key) {
        if(!isset($args[$key]) || !is_string($args[$key]) || trim($args[$key])==='') {
            throw new RuntimeException('Påkrevd: --database --company --event --reference --amount. Standard er kun lesing.');
        }
    }
    $apply=isset($args['apply']);
    if($apply && !isset($args['confirm-capture']))throw new RuntimeException('Belastning krever --apply --confirm-capture og eksplisitt operatørgodkjenning.');
    $db=getDBConnection();
    if($db->query('SELECT DATABASE()')->fetchColumn()!==$args['database'])throw new RuntimeException('Feil måldatabase. Ingen endringer utført.');
    if(!$apply)$db->exec('SET SESSION TRANSACTION READ ONLY');
    $result=(new EventBookingRecovery($db,new SalesVipps()))->run($args['company'],$args['reference'],
        $args['event'],EventBookingRecovery::minor($args['amount']),$apply);
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE)."\n";
}catch(Throwable $e) {
    // Never expose SQL parameters, payment credentials or raw provider responses.
    $message=$e instanceof PDOException?'Databaseoperasjonen feilet. Kontroller lokal status før nytt forsøk.':$e->getMessage();
    fwrite(STDERR,"STOPPET: ".$message."\n");exit(1);
}
