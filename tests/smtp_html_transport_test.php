<?php
require_once __DIR__ . '/smtp_capture_fixture.php';
require_once __DIR__ . '/../includes/rental_confirmation_email.php';

function checkTransport(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
$emptyHeaders = buildSmtpHtmlMessage(
    'admin@example.invalid', '', 'customer@example.invalid', '', '', '<fixture@example.invalid>'
);
checkTransport(!str_contains($emptyHeaders, '=?UTF-8?B??='), 'Empty headers do not create invalid encoded words');
checkFixtureSmtp($emptyHeaders, '');
$template = '<h1>{{company_name}}</h1>{{#if (eq status "Godkjent")}}'
    . '<p>Viktig: Betalingen må være registrert.</p>'
    . '<table role="presentation" width="100%"><tr><td>{{customer_name}}</td>'
    . '<td>{{date_from}} – {{date_to}}</td></tr></table>'
    . '{{else}}<p>Leieforespørselen din er avslått.</p>{{/if}}';
$variables = buildRentalConfirmationVariables([
    'company_name' => 'Testbedrift æøå', 'customer_name' => '<img src=x onerror=alert(1)> & "Kunde"',
    'status' => 'approved', 'start_date' => '2030-01-01', 'end_date' => '2030-01-02',
]);
$valid = renderRentalConfirmationBody($template, $variables);
checkTransport(str_contains($valid, '&lt;img') && !str_contains($valid, '<img'), 'Customer values must remain text');
checkTransport(str_contains($valid, '<table role="presentation"'), 'Template markup stays intact');
$rejected = renderRentalConfirmationBody($template, array_replace($variables, ['status' => 'Avslått']));
checkTransport(!str_contains($rejected, '<table') && !str_contains($rejected, 'Betalingen'), 'Rejections omit payment blocks');

foreach ([
    str_replace('<table role=', '< table role=', $template),
    str_replace('<table role=', "<\n table role=", $template),
    str_replace('<table role="presentation" width="100%">', '&lt;table role="presentation" width="100%"&gt;', $template),
    str_replace('<table role="presentation" width="100%">', '&amp;lt;table role="presentation" width="100%"&amp;gt;', $template),
] as $bad) {
    try {
        renderRentalConfirmationBody($bad, $variables);
        throw new RuntimeException('Invalid template was not rejected');
    } catch (RentalConfirmationEmailException $expected) {
        checkTransport(str_contains($expected->getMessage(), 'tabellkode'), 'Actionable malformed-template error');
    }
}
$document = new DOMDocument();
libxml_use_internal_errors(true);
$document->loadHTML(str_replace('<table role=', '< table role=', $valid));
checkTransport(str_contains($document->textContent, '< table role='), 'Malformed source reproduces screenshot symptom locally');
libxml_clear_errors();
$longHtml = '<h1>Viktig æøå</h1>' . str_repeat(' ', 18000)
    . '<table role="presentation"><tr><td>Leieinformasjon</td></tr></table>';
$legacyWire = "Content-Type: text/html; charset=UTF-8\r\n\r\n" . str_repeat($valid . "\n", 8);
checkTransport(preg_match('/(?<!\r)\n/', $legacyWire) === 1
    && max(array_map('strlen', explode("\r\n", $legacyWire))) > 998,
    'Legacy transport violates CRLF/SMTP line limits with valid multi-line HTML');
foreach ([$valid . "\n.\næøå", $longHtml, str_replace("\n", "\r\n", $valid), '', $rejected] as $body) {
    checkFixtureSmtp(captureFixtureSmtp($body, str_repeat('Bekreftet leie æøå ', 90)), $body);
}
echo "SMTP HTML transport and malformed-template checks passed (loopback only)\n";