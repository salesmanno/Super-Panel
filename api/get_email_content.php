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

require_once __DIR__ . '/../includes/db_config.php';

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
        echo json_encode(['success' => false, 'error' => 'Kunne ikke koble til IMAP-server']);
        exit;
    }
    
    $folderList = @imap_list($connection, $mailbox, '*');
    $availableFolders = [];
    if ($folderList) {
        foreach ($folderList as $f) {
            $availableFolders[] = str_replace($mailbox, '', $f);
        }
    }
    
    $folderMap = [
        'inbox' => 'INBOX',
        'sent' => null,
        'archive' => null,
        'trash' => null
    ];
    
    $sentVariants = ['Sent', 'INBOX.Sent', 'Sendt', 'INBOX.Sendt'];
    $trashVariants = ['Trash', 'INBOX.Trash', 'Papirkurv', 'INBOX.Papirkurv'];
    $archiveVariants = ['Archive', 'INBOX.Archive', 'Arkiv', 'INBOX.Arkiv'];
    
    foreach ($sentVariants as $v) {
        if (in_array($v, $availableFolders)) { $folderMap['sent'] = $v; break; }
    }
    foreach ($trashVariants as $v) {
        if (in_array($v, $availableFolders)) { $folderMap['trash'] = $v; break; }
    }
    foreach ($archiveVariants as $v) {
        if (in_array($v, $availableFolders)) { $folderMap['archive'] = $v; break; }
    }
    
    $targetFolder = $folderMap[strtolower($folder)] ?? 'INBOX';
    if (!$targetFolder) $targetFolder = 'INBOX';
    
    @imap_close($connection);
    $mailboxFull = $mailbox . $targetFolder;
    $connection = @imap_open($mailboxFull, $imapUser, $imapPass);
    
    if (!$connection) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Kunne ikke åpne mappe']);
        exit;
    }
    
    $msgno = @imap_msgno($connection, $uid);
    if (!$msgno) {
        $msgno = $uid;
    }
    
    $header = @imap_headerinfo($connection, $msgno);
    if (!$header) {
        @imap_close($connection);
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'E-post ikke funnet']);
        exit;
    }
    
    $structure = @imap_fetchstructure($connection, $msgno);
    
    $body = '';
    if (!$structure || !isset($structure->parts)) {
        $body = @imap_body($connection, $msgno);
        if ($structure) {
            if ($structure->encoding == 3) {
                $body = base64_decode($body);
            } elseif ($structure->encoding == 4) {
                $body = quoted_printable_decode($body);
            }
        }
        if (empty(strip_tags($body))) {
            $body = nl2br(htmlspecialchars($body));
        }
    } else {
        foreach ($structure->parts as $partNum => $part) {
            if (isset($part->subtype) && $part->subtype == 'HTML') {
                $body = @imap_fetchbody($connection, $msgno, $partNum + 1);
                if ($part->encoding == 3) {
                    $body = base64_decode($body);
                } elseif ($part->encoding == 4) {
                    $body = quoted_printable_decode($body);
                }
                break;
            } elseif (isset($part->subtype) && $part->subtype == 'PLAIN' && empty($body)) {
                $body = @imap_fetchbody($connection, $msgno, $partNum + 1);
                if ($part->encoding == 3) {
                    $body = base64_decode($body);
                } elseif ($part->encoding == 4) {
                    $body = quoted_printable_decode($body);
                }
                $body = nl2br(htmlspecialchars($body));
            }
        }
    }
    
    @imap_setflag_full($connection, (string)$msgno, "\\Seen");
    @imap_close($connection);
    
    $from = '';
    if (isset($header->from[0])) {
        $fromObj = $header->from[0];
        $fromName = isset($fromObj->personal) ? @imap_utf8($fromObj->personal) : '';
        $fromEmail = (isset($fromObj->mailbox) && isset($fromObj->host)) ? $fromObj->mailbox . '@' . $fromObj->host : '';
        $from = $fromName ? "$fromName <$fromEmail>" : $fromEmail;
    }
    
    $to = '';
    if (isset($header->to[0])) {
        $toObj = $header->to[0];
        $to = (isset($toObj->mailbox) && isset($toObj->host)) ? $toObj->mailbox . '@' . $toObj->host : '';
    }
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'email' => [
            'uid' => $uid,
            'from' => $from,
            'to' => $to,
            'subject' => isset($header->subject) ? @imap_utf8($header->subject) : '(Ingen emne)',
            'date' => isset($header->date) ? date('d.m.Y H:i', strtotime($header->date)) : '',
            'body' => $body
        ]
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
