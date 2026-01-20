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

try {
    $db = getDBConnection();
    $companyId = $_SESSION['company_id'];
    
    $stmt = $db->prepare("
        SELECT imap_host, imap_port, imap_username, imap_password_encrypted 
        FROM email_configs 
        WHERE company_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config || empty($config['imap_host']) || empty($config['imap_username'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'IMAP ikke konfigurert']);
        exit;
    }
    
    $folder = $_GET['folder'] ?? 'INBOX';
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $imapHost = $config['imap_host'];
    $imapPort = $config['imap_port'] ?? 993;
    $imapUser = $config['imap_username'];
    $imapPass = $config['imap_password_encrypted'];
    
    $mailbox = '{' . $imapHost . ':' . $imapPort . '/imap/ssl/novalidate-cert}';
    
    $connection = @imap_open($mailbox, $imapUser, $imapPass);
    
    if (!$connection) {
        $imapError = imap_last_error();
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'IMAP-feil: ' . ($imapError ?: 'Ukjent feil')]);
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
        'starred' => 'INBOX',
        'archive' => null,
        'trash' => null
    ];
    
    $sentVariants = ['Sent', 'INBOX.Sent', 'Sendt', 'INBOX.Sendt', 'Sent Messages', 'Sent Items'];
    $trashVariants = ['Trash', 'INBOX.Trash', 'Papirkurv', 'INBOX.Papirkurv', 'Deleted Messages', 'Deleted Items'];
    $archiveVariants = ['Archive', 'INBOX.Archive', 'Arkiv', 'INBOX.Arkiv', 'Archives'];
    
    foreach ($sentVariants as $v) {
        if (in_array($v, $availableFolders)) {
            $folderMap['sent'] = $v;
            break;
        }
    }
    foreach ($trashVariants as $v) {
        if (in_array($v, $availableFolders)) {
            $folderMap['trash'] = $v;
            break;
        }
    }
    foreach ($archiveVariants as $v) {
        if (in_array($v, $availableFolders)) {
            $folderMap['archive'] = $v;
            break;
        }
    }
    
    $targetFolder = $folderMap[strtolower($folder)] ?? 'INBOX';
    if (!$targetFolder) $targetFolder = 'INBOX';
    
    imap_close($connection);
    $mailboxFull = $mailbox . $targetFolder;
    $connection = @imap_open($mailboxFull, $imapUser, $imapPass);
    
    if (!$connection) {
        ob_end_clean();
        echo json_encode(['success' => false, 'error' => 'Kunne ikke åpne mappe: ' . $targetFolder]);
        exit;
    }
    
    $emails = [];
    $check = @imap_check($connection);
    $totalMessages = $check ? $check->Nmsgs : 0;
    
    if ($totalMessages > 0) {
        $start = max(1, $totalMessages - $offset - $limit + 1);
        $end = max(1, $totalMessages - $offset);
        
        if ($start <= $end) {
            $sequence = "$start:$end";
            $overview = @imap_fetch_overview($connection, $sequence, 0);
            
            if ($overview && is_array($overview)) {
                $overview = array_reverse($overview);
                
                foreach ($overview as $msg) {
                    $from = isset($msg->from) ? @imap_utf8($msg->from) : '';
                    $subject = isset($msg->subject) ? @imap_utf8($msg->subject) : '(Ingen emne)';
                    
                    preg_match('/<([^>]+)>/', $from, $emailMatch);
                    $fromEmail = $emailMatch[1] ?? $from;
                    $fromName = trim(preg_replace('/<[^>]+>/', '', $from));
                    if (empty($fromName)) $fromName = $fromEmail;
                    
                    $emails[] = [
                        'uid' => $msg->uid ?? $msg->msgno,
                        'msgno' => $msg->msgno,
                        'from_name' => $fromName,
                        'from_email' => $fromEmail,
                        'subject' => $subject,
                        'date' => isset($msg->date) ? date('Y-m-d H:i', strtotime($msg->date)) : '',
                        'seen' => isset($msg->seen) ? (bool)$msg->seen : true,
                        'flagged' => isset($msg->flagged) ? (bool)$msg->flagged : false,
                        'size' => $msg->size ?? 0
                    ];
                }
            }
        }
    }
    
    $unreadCount = 0;
    $unread = @imap_search($connection, 'UNSEEN');
    if ($unread && is_array($unread)) {
        $unreadCount = count($unread);
    }
    
    @imap_close($connection);
    
    ob_end_clean();
    echo json_encode([
        'success' => true,
        'folder' => $folder,
        'emails' => $emails,
        'total' => $totalMessages,
        'unread' => $unreadCount,
        'offset' => $offset,
        'limit' => $limit,
        'available_folders' => $availableFolders
    ]);
    
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
