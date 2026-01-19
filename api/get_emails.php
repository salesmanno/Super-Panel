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
    
    $stmt = $db->prepare("
        SELECT imap_host, imap_port, imap_username, imap_password_encrypted 
        FROM email_configs 
        WHERE company_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$config || empty($config['imap_host']) || empty($config['imap_username'])) {
        echo json_encode(['success' => false, 'error' => 'IMAP ikke konfigurert']);
        exit;
    }
    
    $folder = $_GET['folder'] ?? 'INBOX';
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    $offset = (int)($_GET['offset'] ?? 0);
    
    $folderMap = [
        'inbox' => 'INBOX',
        'sent' => 'Sent',
        'starred' => 'INBOX',
        'archive' => 'Archive',
        'trash' => 'Trash'
    ];
    
    $imapFolder = $folderMap[strtolower($folder)] ?? 'INBOX';
    $mailbox = '{' . $config['imap_host'] . ':' . ($config['imap_port'] ?? 993) . '/imap/ssl}' . $imapFolder;
    
    $connection = @imap_open($mailbox, $config['imap_username'], $config['imap_password_encrypted']);
    
    if (!$connection) {
        echo json_encode(['success' => false, 'error' => 'Kunne ikke koble til e-postserver: ' . imap_last_error()]);
        exit;
    }
    
    $emails = [];
    $check = imap_check($connection);
    $totalMessages = $check->Nmsgs;
    
    if ($totalMessages > 0) {
        $start = max(1, $totalMessages - $offset - $limit + 1);
        $end = max(1, $totalMessages - $offset);
        
        if ($start <= $end) {
            $sequence = "$start:$end";
            $overview = imap_fetch_overview($connection, $sequence, 0);
            
            if ($overview) {
                $overview = array_reverse($overview);
                
                foreach ($overview as $msg) {
                    $from = isset($msg->from) ? imap_utf8($msg->from) : '';
                    $subject = isset($msg->subject) ? imap_utf8($msg->subject) : '(Ingen emne)';
                    
                    preg_match('/<([^>]+)>/', $from, $emailMatch);
                    $fromEmail = $emailMatch[1] ?? $from;
                    $fromName = trim(preg_replace('/<[^>]+>/', '', $from));
                    if (empty($fromName)) $fromName = $fromEmail;
                    
                    $emails[] = [
                        'uid' => $msg->uid,
                        'msgno' => $msg->msgno,
                        'from_name' => $fromName,
                        'from_email' => $fromEmail,
                        'subject' => $subject,
                        'date' => date('Y-m-d H:i', strtotime($msg->date)),
                        'seen' => (bool)$msg->seen,
                        'flagged' => (bool)($msg->flagged ?? false),
                        'size' => $msg->size ?? 0
                    ];
                }
            }
        }
    }
    
    $unreadCount = 0;
    $unread = imap_search($connection, 'UNSEEN');
    if ($unread) {
        $unreadCount = count($unread);
    }
    
    imap_close($connection);
    
    echo json_encode([
        'success' => true,
        'folder' => $folder,
        'emails' => $emails,
        'total' => $totalMessages,
        'unread' => $unreadCount,
        'offset' => $offset,
        'limit' => $limit
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
