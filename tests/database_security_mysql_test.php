<?php
declare(strict_types=1);
$root=$argv[1] ?? '';
$temp=$argv[2] ?? '';
if (!str_starts_with($temp,sys_get_temp_dir().'/rental-unavailable-mysql.')
    || !file_exists($temp.'/mysql.sock')) {
    throw new RuntimeException('Disposable socket-only database required.');
}
require_once $root.'/includes/AuditedPDO.php';
function databaseSecurityAssert(bool $ok,string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$options=databaseSecureOptions('mysql:example',[
    PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true,
    PDO::MYSQL_ATTR_LOCAL_INFILE=>true,PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION
]);
foreach ([PDO::ATTR_EMULATE_PREPARES,PDO::MYSQL_ATTR_MULTI_STATEMENTS,PDO::MYSQL_ATTR_LOCAL_INFILE] as $key) {
    databaseSecurityAssert($options[$key]===false,'Unsafe option was not overridden');
}
databaseSecurityAssert($options[PDO::ATTR_ERRMODE]===PDO::ERRMODE_EXCEPTION,'Unrelated option changed');
databaseSecurityAssert(databaseSecureOptions('sqlite::memory:',[PDO::ATTR_TIMEOUT=>2])===[PDO::ATTR_TIMEOUT=>2],
    'Non-MySQL options changed');
// No application configuration, network ports, real customers, or production credentials.
$db=new AuditedPDO('mysql:unix_socket='.$temp.'/mysql.sock;charset=utf8mb4','root','',[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES=>true,PDO::MYSQL_ATTR_MULTI_STATEMENTS=>true,PDO::MYSQL_ATTR_LOCAL_INFILE=>true
]);
$db->exec('CREATE DATABASE input_security_fixture');
$db->exec('USE input_security_fixture');
$db->exec('CREATE TABLE input_fixture(id INT PRIMARY KEY AUTO_INCREMENT,company_id VARCHAR(20),body TEXT)');
$payloads=[
    'sudo rm -rf /',
    '$(whoami); `id` && echo test',
    "' OR 1=1 -- ",
    "x'); UPDATE input_fixture SET body='changed'; --",
    '<script>alert("test")</script>',
    "O'Connor & Sønner – blåbær % _ \\ \nlinje to"
];
$insert=$db->prepare('INSERT INTO input_fixture(company_id,body) VALUES (?,?)');
foreach ($payloads as $body) $insert->execute(['a',$body]);
$rows=$db->query('SELECT body FROM input_fixture ORDER BY id')->fetchAll(PDO::FETCH_COLUMN);
databaseSecurityAssert($rows===$payloads,'Bound text was altered or executed');
databaseSecurityAssert(!(bool)$db->getAttribute(PDO::ATTR_EMULATE_PREPARES),'Native prepares disabled');
$lookup=$db->prepare('SELECT COUNT(*) FROM input_fixture WHERE company_id=? AND body=?');
$lookup->execute(["a' OR 1=1 -- ",$payloads[0]]);
databaseSecurityAssert((int)$lookup->fetchColumn()===0,'Tenant parameter affected SQL structure');
$lookup->execute(['a',$payloads[0]]);
databaseSecurityAssert((int)$lookup->fetchColumn()===1,'Legitimate bound search failed');
foreach (['query','exec','prepare'] as $method) {
    $blocked=false;
    try {
        $result=$db->$method('SELECT 1; SELECT 2');
        if ($result instanceof PDOStatement && $method==='prepare') $result->execute();
    } catch (PDOException $expected) {$blocked=true;}
    databaseSecurityAssert($blocked,'Multiple SQL statements accepted by '.$method);
}
// Check LOCAL INFILE against a harmless fixture file, not any project or user file.
$fixture=$temp.'/input-fixture.txt';
file_put_contents($fixture,"999\ta\tlocal-file-content\n");
$blocked=false;
try {
    $db->exec("LOAD DATA LOCAL INFILE ".$db->quote($fixture)." INTO TABLE input_fixture");
} catch (PDOException $expected) {$blocked=true;}
databaseSecurityAssert($blocked,'Local file loading was accepted');
databaseSecurityAssert((int)$db->query('SELECT COUNT(*) FROM input_fixture')->fetchColumn()===count($payloads),
    'SQL or file-loading test changed rows');
echo "PASS: shared connection protections, literal command/SQL text, tenant parameter binding, blocked multi-statements and local files\n";
