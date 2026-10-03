<?php

// MIME encoding belongs at the transport boundary. Never modify stored HTML.
function smtpEncodedHeader(string $value): string
{
    $value = str_replace(["\r", "\n"], '', $value);
    if ($value === '') {
        return '';
    }
    $characters = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
    if ($characters === false) {
        throw new InvalidArgumentException('E-postheaderen inneholder ugyldig UTF-8.');
    }
    $words = [];
    $chunk = '';
    foreach ($characters as $character) {
        if (strlen($chunk . $character) > 42) {
            $words[] = '=?UTF-8?B?' . base64_encode($chunk) . '?=';
            $chunk = '';
        }
        $chunk .= $character;
    }
    $words[] = '=?UTF-8?B?' . base64_encode($chunk) . '?=';
    return implode("\r\n ", $words);
}

function buildSmtpHtmlMessage(
    string $from,
    string $fromName,
    string $to,
    string $subject,
    string $body,
    string $messageId
): string {
    if (!filter_var($from, FILTER_VALIDATE_EMAIL)
        || !filter_var($to, FILTER_VALIDATE_EMAIL)
        || !preg_match('/^<[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+>$/D', $messageId)) {
        throw new InvalidArgumentException('Ugyldig e-postheader.');
    }
    $headers = [
        'From: ' . smtpEncodedHeader($fromName) . " <$from>",
        "To: $to",
        'Subject: ' . smtpEncodedHeader($subject),
        "Message-ID: $messageId",
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        'Date: ' . date('r'),
        'X-Mailer: Salesman.no Mailer',
    ];
    // 76-character ASCII lines, CRLF on the wire, byte-exact HTML on decoding.
    return implode("\r\n", $headers) . "\r\n\r\n"
        . chunk_split(base64_encode($body), 76, "\r\n");
}