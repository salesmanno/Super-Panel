<?php
require_once __DIR__.'/functions.php';
require_once __DIR__.'/company_login.php';
try {
    companySessionStart();
    $db=companyLoginDatabase();
    if (!$db) throw new RuntimeException('Database unavailable');
    companyLoginRequire($db);
    companyLoginTouch($db);
} catch (Throwable $e) {
    companySessionRespondFailure($e,$db??null,'session_guard');
}
