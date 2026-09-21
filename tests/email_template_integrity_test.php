<?php

require_once __DIR__ . '/../includes/email_template_tags.php';

function email_template_integrity_check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
}

$plainText = 'Fast kontakt: Ola, telefon 99887766.';
$template = $plainText . ' {{known_tag}} {{unknown_tag}}';
email_template_integrity_check(
    replaceKnownEmailTemplateTags($template, ['known_tag' => 'ERSTATTET'])
        === $plainText . ' ERSTATTET {{unknown_tag}}',
    'only known explicit tags are replaced'
);
email_template_integrity_check(
    replaceKnownEmailTemplateTags(
        '{{first_value}}|{{second_value}}',
        [
            'first_value' => '{{second_value}}',
            'second_value' => 'skal ikke erstatte verdien',
        ]
    ) === '{{second_value}}|skal ikke erstatte verdien',
    'replacement values cannot create recursively rendered tags'
);

$emailServiceSource = file_get_contents(__DIR__ . '/../includes/EmailService.php');
email_template_integrity_check(
    str_contains($emailServiceSource, 'replaceKnownEmailTemplateTags($template, $data)')
        && !str_contains($emailServiceSource, "preg_replace('/\\{\\{[^}]+\\}\\}/'"),
    'EmailService preserves unknown tags'
);

define('EMAIL_QUEUE_PROCESSOR_NO_AUTO_RUN', true);
require_once __DIR__ . '/../api/process_email_queue.php';

$templateBody = '<section>Lagret body {{ukjent_tag}}</section>';
email_template_integrity_check(
    emailQueueBodyForDelivery(
        ['body' => $templateBody, 'template_id' => 'template-1'],
        'Firma',
        '/logo.png',
        '#123456'
    ) === $templateBody,
    'template queue body is delivered without a wrapper'
);

$manualBody = 'Manuelt skrevet innhold';
$wrappedManualBody = emailQueueBodyForDelivery(
    ['body' => $manualBody, 'template_id' => null],
    'Firma',
    '/logo.png',
    '#123456'
);
email_template_integrity_check(
    $wrappedManualBody !== $manualBody
        && str_contains($wrappedManualBody, $manualBody),
    'manual queue body still receives the standard wrapper'
);

$fullManualHtml = '<!DOCTYPE html><html><body>Manuell full HTML</body></html>';
email_template_integrity_check(
    emailQueueBodyForDelivery(
        ['body' => $fullManualHtml, 'template_id' => null],
        'Firma',
        '/logo.png',
        '#123456'
    ) === $fullManualHtml,
    'manual full HTML remains unchanged'
);

foreach ([
    __DIR__ . '/../cron_membership_check.php',
    __DIR__ . '/../cron_membership_reminder.php',
] as $membershipCronPath) {
    $membershipSource = file_get_contents($membershipCronPath);
    email_template_integrity_check(
        str_contains($membershipSource, 'SELECT id, subject_template, body_template')
            && str_contains($membershipSource, 'template_id, module_source')
            && str_contains($membershipSource, 'replaceKnownEmailTemplatePlaceholders')
            && !str_contains($membershipSource, 'preg_replace'),
        basename($membershipCronPath) . ' preserves template text and identifies the template'
    );
}

$webmailSource = file_get_contents(__DIR__ . '/../modules/webmail_content.php');
$queueApiSource = file_get_contents(__DIR__ . '/../api/queue_email.php');
email_template_integrity_check(
    str_contains($webmailSource, 'template_id: wmActiveTemplateId')
        && str_contains($webmailSource, 'wmActiveRenderedTemplateBody')
        && str_contains($webmailSource, 'wmTemplateEditorDirty')
        && str_contains($webmailSource, 'wmTemplateLoadSequence')
        && str_contains($webmailSource, 'wmActiveTemplateId !== id')
        && str_contains($webmailSource, 'wmClearTemplate();')
        && str_contains($queueApiSource, '(company_id = ? OR company_id IS NULL)')
        && str_contains($queueApiSource, 'template_id, module_source'),
    'webmail template sends retain a validated template id'
);
email_template_integrity_check(
    str_contains($queueApiSource, "? (string) \$data['subject']")
        && str_contains($queueApiSource, "? (string) \$data['body']"),
    'webmail template subject and body are not trimmed'
);

echo "Email template integrity checks passed\n";