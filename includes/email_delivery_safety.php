<?php

require_once __DIR__ . '/email_template_tags.php';

class EmailDeliveryTemplateException extends InvalidArgumentException {}
class EmailDeliveryDuplicateException extends RuntimeException {}

/**
 * Only the explicit subject tag is resolved here. Stored prose and unknown tags
 * remain authoritative; neither templates nor historical messages are rewritten.
 */
function emailBodyWithSubject(string $body, string $subject): string
{
    if (preg_match('/\{\{\s*subject\s*\}\}/i', $subject)) {
        throw new EmailDeliveryTemplateException(
            'Emnet inneholder {{subject}}. Skriv inn et konkret emne før sending.'
        );
    }
    return preg_replace_callback(
        '/\{\{\s*subject\s*\}\}/i',
        static fn(): string => htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        $body
    ) ?? $body;
}

function renderWebmailMessage(string $subject, string $body, array $customer, string $companyName): array
{
    $values = [
        'cus_first_name' => (string) ($customer['first_name'] ?? ''),
        'cus_last_name' => (string) ($customer['last_name'] ?? ''),
        'company_name' => $companyName,
    ];
    $subject = replaceKnownEmailTemplateTags($subject, $values);
    emailBodyWithSubject('', $subject);
    $safeValues = array_map(
        static fn(string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
        $values
    );
    $safeValues['subject'] = htmlspecialchars($subject, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    $body = preg_replace_callback(
        '/\{\{\s*([a-zA-Z_][a-zA-Z0-9_]*)\s*\}\}/',
        static fn(array $match): string => $safeValues[$match[1]] ?? $match[0],
        $body
    ) ?? $body;
    return ['subject' => $subject, 'body' => $body];
}

function emailDeliveryLockName(array $email): string
{
    $identity = [
        (string) $email['company_id'],
        strtolower(trim((string) $email['recipient_email'])),
        strtolower(trim((string) ($email['from_email'] ?? ''))),
        (string) ($email['module_source'] ?? ''),
        // Webmail source_id is a request key, not a distinct business object.
        ($email['module_source'] ?? '') === 'webmail' ? null : ($email['source_id'] ?? null),
        $email['template_id'] ?? null,
        (string) $email['subject'],
        (string) $email['body'],
    ];
    return 'sm-mail-' . substr(hash('sha256', serialize($identity)), 0, 56);
}

/**
 * Suppress rapid duplicate queue entries for the SAME business source only.
 * This is not a permanent ban on legitimate reminders or future newsletters.
 * Named locks serialize equivalent sends across independent cron processes.
 */
function findRecentIdenticalEmail(PDO $db, array $email, bool $includeQueued = false): ?string
{
    $states = $includeQueued ? "'queued','sending','sent'" : "'sending','sent'";
    $matchSource = ($email['module_source'] ?? '') !== 'webmail';
    $sourceClause = $matchSource ? 'AND source_id <=> ?' : '';
    $stmt = $db->prepare("
        SELECT id FROM email_queue
        WHERE company_id = ? AND id <> ?
          AND LOWER(TRIM(recipient_email)) = ?
          AND LOWER(TRIM(COALESCE(from_email, ''))) = ?
          AND COALESCE(module_source, '') = ?
          $sourceClause AND template_id <=> ?
          AND BINARY subject = BINARY ? AND SHA2(body, 256) = ?
          AND (
              status IN ($states)
              OR (status = 'failed' AND error_message LIKE 'SMTP-levering er uavklart%')
          )
          AND COALESCE(sent_at, updated_at, created_at) >= NOW() - INTERVAL 5 MINUTE
        ORDER BY created_at ASC, id ASC LIMIT 1
    ");
    $arguments = [
        $email['company_id'], $email['id'] ?? '',
        strtolower(trim((string) $email['recipient_email'])),
        strtolower(trim((string) ($email['from_email'] ?? ''))),
        (string) ($email['module_source'] ?? ''),
    ];
    if ($matchSource) $arguments[] = $email['source_id'] ?? null;
    array_push($arguments, $email['template_id'] ?? null,
        $email['subject'], hash('sha256', (string) $email['body']),
    );
    $stmt->execute($arguments);
    $id = $stmt->fetchColumn();
    return $id === false ? null : (string) $id;
}

/** Called under the company's transaction lock, including retries after commit. */
function existingWebmailRequest(PDO $db, array $email): ?string
{
    if (empty($email['source_id'])) {
        return findRecentIdenticalEmail($db, $email, true);
    }
    $stmt = $db->prepare("
        SELECT id, subject, body, template_id FROM email_queue
        WHERE company_id = ? AND module_source = 'webmail' AND source_id = ?
          AND LOWER(TRIM(recipient_email)) = ?
        LIMIT 1 FOR UPDATE
    ");
    $stmt->execute([
        $email['company_id'], $email['source_id'],
        strtolower(trim((string) $email['recipient_email'])),
    ]);
    $previous = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$previous) return findRecentIdenticalEmail($db, $email, true);
    if ((string) $previous['subject'] !== $email['subject']
        || (string) $previous['body'] !== $email['body']
        || ($previous['template_id'] ?? null) !== ($email['template_id'] ?? null)) {
        throw new InvalidArgumentException('Utsendingsreferansen er allerede brukt til et annet innhold.');
    }
    return (string) $previous['id'];
}
