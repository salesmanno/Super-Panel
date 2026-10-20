<?php
// In-memory queue doubles and loopback SMTP only. No application DB or recipients.
require_once __DIR__ . '/../includes/email_delivery_safety.php';
require_once __DIR__ . '/smtp_capture_fixture.php';

function safetyCheck(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}

$rendered = renderWebmailMessage(
    'Brev til {{cus_first_name}} – <høst>',
    '<h1>{{subject}}</h1><p>{{cus_first_name}}</p>{{unknown}}',
    ['first_name' => '<Alex>'], 'Eksempelfirma'
);
safetyCheck($rendered['subject'] === 'Brev til <Alex> – <høst>', 'plain-text subject is rendered');
safetyCheck($rendered['body'] === '<h1>Brev til &lt;Alex&gt; – &lt;høst&gt;</h1><p>&lt;Alex&gt;</p>{{unknown}}',
    'subject and recipient values are escaped in HTML; unknown tags remain');
safetyCheck(emailBodyWithSubject('Tekst {{ subject }}', 'Møte & fest') === 'Tekst Møte &amp; fest',
    'already queued subject tags are resolved');
safetyCheck(emailBodyWithSubject('Fast tekst', 'Møte') === 'Fast tekst', 'stored prose is not changed');
$nonrecursive = renderWebmailMessage('Hei', '{{cus_first_name}}', ['first_name' => '{{company_name}}'], 'Firma');
safetyCheck($nonrecursive['body'] === '{{company_name}}', 'replacement values are not recursively rendered');
try {
    emailBodyWithSubject('<h1>{{subject}}</h1>', '{{subject}}');
    throw new RuntimeException('unresolved subject must be rejected');
} catch (EmailDeliveryTemplateException $expected) {}

$email = [
    'id' => 'queue-1', 'company_id' => 'company-1', 'recipient_email' => ' Alex@Example.invalid ',
    'from_email' => 'sender@example.invalid', 'module_source' => 'webmail', 'source_id' => null,
    'template_id' => 'template-1', 'subject' => 'Møte', 'body' => '<p>Brev</p>',
];
safetyCheck(strlen(emailDeliveryLockName($email)) === 64, 'MySQL lock name fits the limit');
safetyCheck(emailDeliveryLockName($email) === emailDeliveryLockName(array_merge($email, [
    'id' => 'queue-2', 'recipient_email' => 'alex@example.invalid',
])), 'equivalent queue entries use the same lock');
foreach (['company_id', 'module_source', 'template_id', 'subject', 'body'] as $key) {
    safetyCheck(emailDeliveryLockName($email) !== emailDeliveryLockName(array_merge($email, [$key => 'different'])),
        'separate business identity must not be suppressed: ' . $key);
}
safetyCheck(emailDeliveryLockName($email) === emailDeliveryLockName(array_merge($email, ['source_id' => 'new-request'])),
    'rapid webmail duplicates share a lock even after a reload');
$businessEmail = array_merge($email, ['module_source' => 'events', 'source_id' => 'registration-1']);
safetyCheck(emailDeliveryLockName($businessEmail) !== emailDeliveryLockName(array_merge($businessEmail, ['source_id' => 'registration-2'])),
    'separate registrations are not collapsed');

class SafetyPDO extends PDO {
    public array $calls = [];
    public mixed $nextRow = false;
    public bool $processorMode = false;
    public bool $lockAvailable = true;
    public bool $due = true;
    public string $status = 'queued';
    public ?string $duplicateId = null;
    public bool $transaction = false;
    public function __construct() {}
    public function beginTransaction(): bool { $this->transaction = true; return true; }
    public function commit(): bool { $this->transaction = false; return true; }
    public function rollBack(): bool { $this->transaction = false; return true; }
    public function inTransaction(): bool { return $this->transaction; }
    public function prepare(string $query, array $options = []): PDOStatement|false {
        return new SafetyStatement($this, $query);
    }
}
class SafetyStatement extends PDOStatement {
    private int $affected = 0;
    public function __construct(private SafetyPDO $db, private string $query) {}
    public function execute(?array $params = null): bool {
        $this->db->calls[] = [$this->query, $params];
        if ($this->db->processorMode && str_contains($this->query, "SET status = 'sending'")) {
            $this->affected = $this->db->status === 'queued' && $this->db->due ? 1 : 0;
            if ($this->affected) $this->db->status = 'sending';
        }
        if ($this->db->processorMode && str_contains($this->query, "SET status = 'failed'")) {
            $this->db->status = 'failed';
        }
        return true;
    }
    public function fetch(int $mode = PDO::FETCH_DEFAULT, int $cursorOrientation = PDO::FETCH_ORI_NEXT, int $cursorOffset = 0): mixed {
        return $this->db->nextRow;
    }
    public function fetchColumn(int $column = 0): mixed {
        if ($this->db->processorMode) {
            if (str_contains($this->query, 'SELECT GET_LOCK')) return $this->db->lockAvailable ? 1 : 0;
            if (str_contains($this->query, 'SELECT RELEASE_LOCK')) return 1;
            if (str_contains($this->query, 'SELECT id FROM email_queue')) return $this->db->duplicateId ?? false;
            if (str_contains($this->query, 'SELECT name FROM tenant_companies')) return 'Fixture Company';
        }
        return $this->db->nextRow;
    }
    public function rowCount(): int { return $this->affected; }
}
$db = new SafetyPDO();
$db->nextRow = 'older-queue';
safetyCheck(findRecentIdenticalEmail($db, $email) === 'older-queue', 'duplicate result is returned');
[$sql, $params] = $db->calls[0];
safetyCheck(!str_contains($sql, 'source_id <=> ?') && str_contains($sql, 'INTERVAL 5 MINUTE')
    && str_contains($sql, "'sending','sent'") && !str_contains($sql, "'queued','sending'"),
    'delivery lookup is source-scoped and excludes unsent queued peers');
safetyCheck($params[0] === 'company-1' && $params[2] === 'alex@example.invalid'
    && end($params) === hash('sha256', $email['body']), 'duplicate lookup is tenant-, recipient-, and payload-scoped');
findRecentIdenticalEmail($db, $businessEmail);
safetyCheck(str_contains($db->calls[1][0], 'source_id <=> ?'), 'automated emails remain business-source-scoped');
$request = array_merge($email, ['source_id' => 'e24da11d-8f07-4d89-b1d0-000000000001']);
$db->nextRow = ['id' => 'previous', 'subject' => $email['subject'], 'body' => $email['body'], 'template_id' => 'template-1'];
safetyCheck(existingWebmailRequest($db, $request) === 'previous', 'retry reuses committed queue entry');
safetyCheck(str_contains($db->calls[2][0], 'FOR UPDATE'), 'idempotent replay uses a current read');
try {
    existingWebmailRequest($db, array_merge($request, ['body' => 'Changed payload']));
    throw new RuntimeException('reusing a key for different content must fail');
} catch (InvalidArgumentException $expected) {}

define('EMAIL_QUEUE_PROCESSOR_NO_AUTO_RUN', true);
require_once __DIR__ . '/../api/process_email_queue.php';
foreach (['busy-lock', 'stale-retry', 'duplicate'] as $case) {
    $queue = new SafetyPDO();
    $queue->processorMode = true;
    $queue->lockAvailable = $case !== 'busy-lock';
    $queue->due = $case !== 'stale-retry';
    $queue->duplicateId = $case === 'duplicate' ? 'older-queue' : null;
    ob_start();
    processEmail($queue, $email);
    ob_end_clean();
    $queries = implode("\n", array_column($queue->calls, 0));
    safetyCheck(!str_contains($queries, 'FROM email_configs'), 'no SMTP configuration/transport for ' . $case);
    safetyCheck($queue->status === ($case === 'duplicate' ? 'failed' : 'queued'),
        'worker stops duplicate or defers locked/stale message');
    if ($case !== 'busy-lock') {
        safetyCheck(str_contains($queries, 'SELECT RELEASE_LOCK'), 'worker releases named lock');
    }
}
$processor = file_get_contents(__DIR__ . '/../api/process_email_queue.php');
safetyCheck(substr_count($processor, 'next_retry_at IS NULL OR next_retry_at <= NOW()') === 3,
    'company discovery, selection, and atomic claim all honor retry backoff');

foreach (['normal', 'reject-quit', 'disconnect-after-acceptance', 'disconnect-after-data', 'temporary-reject'] as $scenario) {
    $result = null;
    $wire = captureFixtureSmtp('<h1>Én melding</h1>', 'Test', $scenario, $result);
    safetyCheck(substr_count($wire, 'Message-ID:') === 1, 'one SMTP message submitted for ' . $scenario);
    if (in_array($scenario, ['normal', 'reject-quit', 'disconnect-after-acceptance'], true)) {
        safetyCheck($result['success'] === true, 'accepted delivery stays accepted despite teardown: ' . $scenario);
    } elseif ($scenario === 'disconnect-after-data') {
        safetyCheck(!$result['success'] && $result['delivery_uncertain'], 'lost DATA response requires manual review, not retry');
    } else {
        safetyCheck(!$result['success'] && !$result['delivery_uncertain'], 'explicit 451 rejection remains safely retryable');
    }
}
echo "Email delivery safety checks passed (no external email)\n";
