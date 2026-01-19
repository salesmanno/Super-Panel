<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['tenant_admin_id']) || !isset($_SESSION['company_id'])) {
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
        echo json_encode(['success' => false, 'error' => 'IMAP ikke konfigurert']);
        exit;
    }
    
    $folderMap = [
        'inbox' => 'INBOX',
        'sent' => 'Sent',
        'archive' => 'Archive',
        'trash' => 'Trash'
    ];
    
    $imapFolder = $folderMap[strtolower($folder)] ?? 'INBOX';
    $mailbox = '{' . $config['imap_host'] . ':' . ($config['imap_port'] ?? 993) . '/imap/ssl}' . $imapFolder;
    
    $connection = @imap_open($mailbox, $config['imap_username'], $config['imap_password_encrypted']);
    
    if (!$connection) {
        echo json_encode(['success' => false, 'error' => 'Kunne ikke koble til e-postserver']);
        exit;
    }
    
    $msgno = imap_msgno($connection, $uid);
    if (!$msgno) {
        imap_close($connection);
        echo json_encode(['success' => false, 'error' => 'E-post ikke funnet']);
        exit;
    }
    
    $header = imap_headerinfo($connection, $msgno);
    $structure = imap_fetchstructure($connection, $msgno);
    
    $body = '';
    if (!isset($structure->parts)) {
        $body = imap_body($connection, $msgno);
        if ($structure->encoding == 3) {
            $body = base64_decode($body);
        } elseif ($structure->encoding == 4) {
            $body = quoted_printable_decode($body);
        }
    } else {
        foreach ($structure->parts as $partNum => $part) {
            if ($part->subtype == 'HTML') {
                $body = imap_fetchbody($connection, $msgno, $partNum + 1);
                if ($part->encoding == 3) {
                    $body = base64_decode($body);
                } elseif ($part->encoding == 4) {
                    $body = quoted_printable_decode($body);
                }
                break;
            } elseif ($part->subtype == 'PLAIN' && empty($body)) {
                $body = imap_fetchbody($connection, $msgno, $partNum + 1);
                if ($part->encoding == 3) {
                    $body = base64_decode($body);
                } elseif ($part->encoding == 4) {
                    $body = quoted_printable_decode($body);
                }
                $body = nl2br(htmlspecialchars($body));
            }
        }
    }
    
    imap_setflag_full($connection, $msgno, "\\Seen");
    imap_close($connection);
    
    $from = isset($header->from[0]) ? (isset($header->from[0]->personal) ? imap_utf8($header->from[0]->personal) : '') . ' <' . $header->from[0]->mailbox . '@' . $header->from[0]->host . '>' : '';
    $to = isset($header->to[0]) ? $header->to[0]->mailbox . '@' . $header->to[0]->host : '';
    
    echo json_encode([
        'success' => true,
        'email' => [
            'uid' => $uid,
            'from' => $from,
            'to' => $to,
            'subject' => isset($header->subject) ? imap_utf8($header->subject) : '(Ingen emne)',
            'date' => date('d.m.Y H:i', strtotime($header->date)),
            'body' => $body
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
