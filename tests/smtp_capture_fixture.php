<?php

require_once __DIR__ . '/../includes/SMTPClient.php';

function runFixtureSmtpServer(): void {
    $server = stream_socket_server('tcp://127.0.0.1:0', $errno, $error);
    if (!$server) throw new RuntimeException('Could not create fixture SMTP server');
    $port = (int) substr(strrchr(stream_socket_get_name($server, false), ':'), 1);
    echo $port . "\n";
    fflush(STDOUT);
        pcntl_alarm(15);
        $client = stream_socket_accept($server, 10);
        if (!$client) exit(2);
        fwrite($client, "220 fixture\r\n");
        $authStep = 0;
        $data = '';
        while (($line = fgets($client)) !== false) {
            if ($authStep) {
                fwrite($client, $authStep === 1 ? "334 password\r\n" : "235 accepted\r\n");
                $authStep = $authStep === 1 ? 2 : 0;
            } elseif (str_starts_with($line, 'EHLO')) {
                fwrite($client, "250 fixture\r\n");
            } elseif (trim($line) === 'AUTH LOGIN') {
                $authStep = 1;
                fwrite($client, "334 username\r\n");
            } elseif (str_starts_with($line, 'MAIL FROM:') || str_starts_with($line, 'RCPT TO:')) {
                fwrite($client, "250 accepted\r\n");
            } elseif (trim($line) === 'DATA') {
                fwrite($client, "354 continue\r\n");
                while (($part = fgets($client)) !== false && $part !== ".\r\n") {
                    $data .= str_starts_with($part, '..') ? substr($part, 1) : $part;
                }
                fwrite($client, "250 fixture accepted\r\n");
            } elseif (trim($line) === 'QUIT') {
                fwrite($client, "221 bye\r\n");
                break;
            } else {
                fwrite($client, "500 unexpected command\r\n");
            }
        }
        fclose($client);
        fwrite(STDOUT, $data);
        fclose($server);
}

// A new PHP process avoids inheriting/closing the parent's live PDO sockets.
// Loopback only; never uses application SMTP settings or an external server.
function captureFixtureSmtp(string $body, string $subject = 'Godkjent leie – æøå'): string {
    $process = proc_open([PHP_BINARY, __FILE__, '--smtp-capture-worker'], [
        0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w'],
    ], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Could not start fixture SMTP server');
    try {
        stream_set_timeout($pipes[1], 15);
        $port = (int) fgets($pipes[1]);
        if ($port < 1024 || $port > 65535) throw new RuntimeException('Invalid fixture SMTP port');
        $result = (new SMTPClient('127.0.0.1', $port, 'fixture-user', 'fixture-password'))->send(
            'admin@example.invalid', 'Testbedrift æøå', 'admin@example.invalid', $subject, $body
        );
        $captured = stream_get_contents($pipes[1]);
        $errors = stream_get_contents($pipes[2]);
        if (!$result['success'] || $errors !== '') {
            throw new RuntimeException('Fixture SMTP failed: ' . ($result['error'] ?? $errors));
        }
        return $captured;
    } finally {
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_terminate($process);
        proc_close($process);
    }
}

function checkFixtureSmtp(string $message, string $expectedBody): void {
    if (preg_match('/(?<!\r)\n|\r(?!\n)/', $message)) {
        throw new RuntimeException('SMTP wire message contains bare line breaks');
    }
    [$headers, $encoded] = explode("\r\n\r\n", $message, 2);
    if (!str_contains($headers, 'Content-Type: text/html; charset=UTF-8')
        || !str_contains($headers, 'Content-Transfer-Encoding: base64')
        || base64_decode(preg_replace('/\s/', '', $encoded), true) !== $expectedBody) {
        throw new RuntimeException('MIME must preserve the exact queued body');
    }
    foreach (explode("\r\n", $message) as $line) {
        if (strlen($line) > 998) throw new RuntimeException('SMTP line exceeds RFC limit');
    }
    foreach (explode("\r\n", trim($encoded)) as $line) {
        if (strlen($line) > 76) throw new RuntimeException('MIME body line exceeds 76 characters');
    }
    preg_match_all('/=\?UTF-8\?B\?([^?]*)\?=/', $headers, $matches);
    foreach ($matches[0] as $index => $word) {
        if (strlen($word) > 75 || !mb_check_encoding(base64_decode($matches[1][$index]), 'UTF-8')) {
            throw new RuntimeException('Invalid or oversized MIME encoded word');
        }
    }
}

if (PHP_SAPI === 'cli' && realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__
    && ($argv[1] ?? '') === '--smtp-capture-worker') {
    runFixtureSmtpServer();
}