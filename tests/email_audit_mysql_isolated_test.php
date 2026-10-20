<?php
declare(strict_types=1);

$root = $argv[1] ?? '';
$temp = $argv[2] ?? '';
if (!str_starts_with($temp, sys_get_temp_dir() . '/rental-unavailable-mysql.')
    || !file_exists($temp . '/mysql.sock')) {
    throw new RuntimeException('Use the disposable socket-only MySQL harness.');
}

require_once $root . '/includes/AuditedPDO.php';

function emailAuditAssert(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$setup = new PDO('mysql:unix_socket=' . $temp . '/mysql.sock', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$setup->exec('CREATE DATABASE email_audit_fixture');
$setup->exec('USE email_audit_fixture');
$setup->exec("CREATE TABLE tenant_companies (
    id CHAR(36) PRIMARY KEY, name VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL DEFAULT 1
)");
$setup->exec("CREATE TABLE tenant_staff (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36), first_name VARCHAR(100), last_name VARCHAR(100)
)");
$setup->exec("CREATE TABLE tenant_admins (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36), tenant_staff_id CHAR(36),
    username VARCHAR(100), role VARCHAR(50), is_active TINYINT
)");
$setup->exec("CREATE TABLE audit_logs (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36), user_id CHAR(36), user_type VARCHAR(20),
    user_email VARCHAR(255), action VARCHAR(50), resource_type VARCHAR(100), resource_id CHAR(36),
    resource_name VARCHAR(255), old_values TEXT, new_values TEXT, severity VARCHAR(20),
    performed_at DATETIME, module VARCHAR(50)
) ENGINE=InnoDB");
$setup->exec("CREATE TABLE email_queue (
    id CHAR(36) PRIMARY KEY, company_id CHAR(36), recipient_email VARCHAR(255), from_email VARCHAR(255),
    subject TEXT, body LONGTEXT, template_id CHAR(36), module_source VARCHAR(50), source_id VARCHAR(100),
    status VARCHAR(30), priority INT, attempts INT DEFAULT 0, max_attempts INT DEFAULT 3,
    scheduled_at DATETIME, next_retry_at DATETIME NULL, sent_at DATETIME NULL,
    created_at DATETIME NULL, updated_at DATETIME NULL
) ENGINE=InnoDB");
$setup->exec("INSERT INTO tenant_companies (id,name,is_active) VALUES ('company-a','Auditfirma',1)");
$setup->exec("INSERT INTO tenant_staff (id,company_id,first_name,last_name) VALUES ('staff-a','company-a','Ada','Ansatt')");
$setup->exec("INSERT INTO tenant_admins (id,company_id,tenant_staff_id,username,role,is_active)
    VALUES ('admin-a','company-a','staff-a','ada','admin',1)");

$_SESSION = ['company_id' => 'company-a', 'tenant_admin_id' => 'admin-a'];
$dsn = 'mysql:unix_socket=' . $temp . '/mysql.sock;dbname=email_audit_fixture;charset=utf8mb4';
$db = new AuditedPDO($dsn, 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

// Execute the real INSERT statement from the webmail queue endpoint.
$queueEndpoint = file_get_contents($root . '/api/queue_email.php');
if (!preg_match('/\\$insertStmt\\s*=\\s*\\$db->prepare\\(\"([\\s\\S]*?)\"\\);/', $queueEndpoint, $insertMatch)) {
    throw new RuntimeException('Could not locate the webmail email_queue INSERT.');
}
emailAuditAssert(str_contains($queueEndpoint, 'message_body_sha256')
    && str_contains($queueEndpoint, 'withAuditLinks'),
    'Webmail queue writes attach safe message fingerprints.');

$requestId = '123e4567-e89b-12d3-a456-426614174001';
$privateSubject = 'PRIVATE subject fixture';
$privateBody = '<p>PRIVATE body fixture</p>';
$subjectHash = hash('sha256', $privateSubject);
$bodyHash = hash('sha256', $privateBody);
$_SERVER['SCRIPT_FILENAME'] = 'api/queue_email.php';
$insert = $db->prepare($insertMatch[1]);
$db->beginTransaction();
$db->withAuditLinks([
    'correlation_id' => $requestId,
    'message_subject_sha256' => $subjectHash,
    'message_body_sha256' => $bodyHash,
], static fn() => $insert->execute([
    'company-a', 'private@example.invalid', 'sender@example.invalid', $privateSubject,
    $privateBody, 'template-a', $requestId,
]));
$db->commit();
$queueId = (string) $setup->query('SELECT id FROM email_queue LIMIT 1')->fetchColumn();
emailAuditAssert($queueId !== '', 'The endpoint-generated queue identifier is present.');

$queued = $setup->query("SELECT * FROM audit_logs WHERE resource_type='email_queue'")->fetchAll();
emailAuditAssert(count($queued) === 1 && $queued[0]['action'] === 'email.insert',
    'A queue insert creates exactly one automatic audit event.');
$queuedPublic = tenantAuditPublicRow($queued[0]);
emailAuditAssert($queuedPublic['actor_name'] === 'Ada Ansatt'
    && $queuedPublic['actor_role'] === 'admin'
    && $queuedPublic['after']['status'] === 'queued'
    && $queuedPublic['after']['template_id'] === 'template-a'
    && $queuedPublic['links']['correlation_id'] === $requestId
    && $queuedPublic['links']['message_subject_sha256'] === $subjectHash
    && $queuedPublic['links']['message_body_sha256'] === $bodyHash,
    'Queue audit exposes actor, safe status, source and message fingerprints.');
$auditJson = $queued[0]['old_values'] . $queued[0]['new_values'];
foreach ([$privateSubject, $privateBody, 'private@example.invalid', 'sender@example.invalid'] as $privateValue) {
    emailAuditAssert(!str_contains($auditJson, $privateValue), 'Private email data is absent from audit values.');
}

// The processor suppresses automatic UPDATE auditing and records a single explicit send-start event.
$_SERVER['SCRIPT_FILENAME'] = 'api/process_email_queue.php';
$db->beginTransaction();
$claim = $db->prepare("
    UPDATE email_queue SET status='sending', updated_at=NOW()
    WHERE id=? AND company_id=? AND status='queued'
");
$claim->execute([$queueId, 'company-a']);
emailAuditAssert($claim->rowCount() === 1, 'Processor claims the queued message.');
$systemActor = tenantAuditSystemActor($db, 'company-a', 'email_queue');
tenantAuditRecord(
    $db, 'company-a', 'email', 'email.processing_started', 'email_queue', $queueId, 'E-postbehandling startet',
    ['status' => 'queued', 'attempts' => 0],
    ['status' => 'sending', 'attempts' => 0, 'attempt_number' => 1, 'max_attempts' => 3],
    'success',
    ['queue_id' => $queueId, 'correlation_id' => $requestId,
        'message_subject_sha256' => $subjectHash, 'message_body_sha256' => $bodyHash],
    $systemActor
);
$db->commit();
emailAuditAssert((int) $setup->query("SELECT COUNT(*) FROM audit_logs WHERE action='email.update'")->fetchColumn() === 0
    && (int) $setup->query("SELECT COUNT(*) FROM audit_logs WHERE action='email.processing_started'")->fetchColumn() === 1,
    'Send start is journaled once without a duplicate automatic UPDATE event.');

$db->beginTransaction();
tenantAuditRecord(
    $db, 'company-a', 'email', 'email.smtp_accepted', 'email_queue', $queueId,
    'SMTP akseptert – ikke bekreftet mottakerlevering',
    ['status' => 'sending', 'attempts' => 0],
    ['status' => 'sent', 'attempts' => 0, 'max_attempts' => 3,
        'next_retry_at' => null, 'sent_at' => '2026-10-21 12:00:00'],
    'smtp_accepted',
    ['queue_id' => $queueId, 'correlation_id' => $requestId,
        'message_subject_sha256' => $subjectHash, 'message_body_sha256' => $bodyHash],
    $systemActor
);
$db->commit();
tenantAuditRecord(
    $db, 'company-a', 'email', 'email.smtp_failed', 'email_queue', $queueId, 'SMTP-forsøket feilet',
    [],
    ['status' => 'queued', 'attempts' => 1, 'max_attempts' => 3,
        'next_retry_at' => '2026-10-21 12:05:00'],
    'failed',
    ['queue_id' => $queueId, 'correlation_id' => $requestId,
        'message_subject_sha256' => $subjectHash, 'message_body_sha256' => $bodyHash],
    $systemActor
);

$accepted = $setup->query("SELECT new_values FROM audit_logs WHERE action='email.smtp_accepted'")->fetchColumn();
$acceptedValues = json_decode((string) $accepted, true);
emailAuditAssert(($acceptedValues['sent_at'] ?? null) === '2026-10-21 12:00:00'
    && ($acceptedValues['status'] ?? null) === 'sent',
    'SMTP accepted event records a safe final status and timestamp.');
$failed = $setup->query("SELECT new_values FROM audit_logs WHERE action='email.smtp_failed'")->fetchColumn();
$failedValues = json_decode((string) $failed, true);
emailAuditAssert(($failedValues['attempts'] ?? null) === 1
    && ($failedValues['next_retry_at'] ?? null) === '2026-10-21 12:05:00',
    'SMTP failure event records attempt and retry time without raw error text.');

echo "Isolated email audit MySQL checks passed\n";
