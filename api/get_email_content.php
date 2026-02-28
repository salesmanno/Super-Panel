<?php
ob_start();
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Server-feil: ' . $error['message']]);
        exit;
    }
});

session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => 'Ikke innlogget']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

function decodeMailBody(string $body, int $encoding): string {
    switch ($encoding) {
        case 3: return base64_decode($body);
        case 4: return quoted_printable_decode($body);
        default: return $body;
    }
}

function convertToUtf8(string $text, $part): string {
    $charset = 'UTF-8';
    if (!empty($part->parameters)) {
        foreach ($part->parameters as $p) {
            if (strtoupper($p->attribute) === 'CHARSET') {
                $charset = strtoupper(trim($p->value));
                break;
            }
        }
    }
    if ($charset !== 'UTF-8' && $charset !== 'US-ASCII') {
        $converted = @iconv($charset, 'UTF-8//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        } else {
            $text = mb_convert_encoding($text, 'UTF-8', $charset);
        }
    }
    return $text;
}

function extractBody($connection, int $msgno, $part, string $partNum, string &$html, string &$plain): void {
    $subtype = strtoupper($part->subtype ?? '');
    $maintype = $part->type ?? 0;

    if ($maintype === 0) {
        $raw = @imap_fetchbody($connection, $msgno, $partNum);
        $decoded = decodeMailBody($raw ?? '', $part->encoding ?? 0);
        $utf8 = convertToUtf8($decoded, $part);

        if ($subtype === 'HTML' && empty($html)) {
            $html = $utf8;
        } elseif ($subtype === 'PLAIN' && empty($plain)) {
            $plain = $utf8;
        }
    }

    if (!empty($part->parts)) {
        foreach ($part->parts as $i => $subpart) {
            $subNum = $partNum === '0' ? (string)($i + 1) : $partNum . '.' . ($i + 1);
            extractBody($connection, $msgno, $subpart, $subNum, $html, $plain);
        }
    }
}

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];

    $uid = (int)($_GET['uid'] ?? 0);
    $folder = $_GET['folder'] ?? 'INBOX';

    if (!$uid) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Ugyldig e-post ID']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT imap_host, imap_port, imap_username, imap_password_encrypted
        FROM email_configs
        WHERE company_id = ?
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config || empty($config['imap_host'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'IMAP ikke konfigurert']);
        exit;
    }

    $imapHost = $config['imap_host'];
    $imapPort = $config['imap_port'] ?? 993;
    $imapUser = $config['imap_username'];
    $imapPass = $config['imap_password_encrypted'];

    $mailbox = '{' . $imapHost . ':' . $imapPort . '/imap/ssl/novalidate-cert}';

    $connection = @imap_open($mailbox, $imapUser, $imapPass);
    if (!$connection) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Kunne ikke koble til IMAP-server: ' . imap_last_error()]);
        exit;
    }

    $folderList = @imap_list($connection, $mailbox, '*');
    $availableFolders = [];
    if ($folderList) {
        foreach ($folderList as $f) {
            $availableFolders[] = str_replace($mailbox, '', $f);
        }
    }

    $sentVariants  = ['Sent', 'INBOX.Sent', 'Sendt', 'INBOX.Sendt', 'Sent Messages', 'Sent Items'];
    $trashVariants = ['Trash', 'INBOX.Trash', 'Papirkurv', 'INBOX.Papirkurv', 'Deleted Messages', 'Deleted Items'];
    $folderMap = ['inbox' => 'INBOX', 'sent' => null, 'trash' => null];
    foreach ($sentVariants  as $v) { if (in_array($v, $availableFolders)) { $folderMap['sent']  = $v; break; } }
    foreach ($trashVariants as $v) { if (in_array($v, $availableFolders)) { $folderMap['trash'] = $v; break; } }

    $targetFolder = $folderMap[strtolower($folder)] ?? 'INBOX';
    if (!$targetFolder) $targetFolder = 'INBOX';

    @imap_close($connection);
    $connection = @imap_open($mailbox . $targetFolder, $imapUser, $imapPass);

    if (!$connection) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Kunne ikke åpne mappe: ' . $targetFolder]);
        exit;
    }

    $msgno = @imap_msgno($connection, $uid);
    if (!$msgno) $msgno = $uid;

    $header = @imap_headerinfo($connection, $msgno);
    if (!$header) {
        @imap_close($connection);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'E-post ikke funnet']);
        exit;
    }

    $structure = @imap_fetchstructure($connection, $msgno);
    $html  = '';
    $plain = '';

    if (!$structure) {
        $raw = @imap_body($connection, $msgno) ?? '';
        $plain = convertToUtf8($raw, (object)[]);
    } elseif (!isset($structure->parts)) {
        $raw = @imap_body($connection, $msgno) ?? '';
        $decoded = decodeMailBody($raw, $structure->encoding ?? 0);
        $utf8 = convertToUtf8($decoded, $structure);
        $subtype = strtoupper($structure->subtype ?? '');
        if ($subtype === 'HTML') {
            $html = $utf8;
        } else {
            $plain = $utf8;
        }
    } else {
        foreach ($structure->parts as $i => $part) {
            extractBody($connection, $msgno, $part, (string)($i + 1), $html, $plain);
        }
    }

    $body = '';
    if (!empty($html)) {
        $body = $html;
    } elseif (!empty($plain)) {
        $body = '<pre style="white-space:pre-wrap;font-family:inherit;font-size:0.9rem;line-height:1.6;">'
              . htmlspecialchars($plain, ENT_QUOTES, 'UTF-8')
              . '</pre>';
    } else {
        $body = '<p class="text-muted">(Tomt innhold)</p>';
    }

    @imap_setflag_full($connection, (string)$msgno, '\\Seen');
    @imap_close($connection);

    $from = '';
    if (isset($header->from[0])) {
        $fromObj  = $header->from[0];
        $fromName = isset($fromObj->personal) ? @imap_utf8($fromObj->personal) : '';
        $fromEmail = (isset($fromObj->mailbox) && isset($fromObj->host))
            ? $fromObj->mailbox . '@' . $fromObj->host : '';
        $from = $fromName ? "$fromName <$fromEmail>" : $fromEmail;
    }

    $to = '';
    if (isset($header->to[0])) {
        $toObj = $header->to[0];
        $to = (isset($toObj->mailbox) && isset($toObj->host))
            ? $toObj->mailbox . '@' . $toObj->host : '';
    }

    $subject = isset($header->subject) ? @imap_utf8($header->subject) : '(Ingen emne)';
    $date    = isset($header->date) ? date('d.m.Y H:i', strtotime($header->date)) : '';

    ob_end_clean();
    echo json_encode([
        'success' => true,
        'email'   => [
            'uid'     => $uid,
            'from'    => $from,
            'to'      => $to,
            'subject' => $subject,
            'date'    => $date,
            'body'    => $body
        ]
    ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
