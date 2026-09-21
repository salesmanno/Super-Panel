<?php

require_once __DIR__ . '/email_template_tags.php';

/**
 * EmailService - Håndterer automatiske e-poster med templates
 * 
 * Brukes av alle moduler for å sende bekreftelser/kvitteringer
 * - Sales: Salgskvittering
 * - Events: Arrangement-bekreftelse
 * - Membership: Medlemskap-bekreftelse
 * - Rental: Utleie-bekreftelse
 * - Service: Service-booking bekreftelse
 */
class EmailService {
    private $db;
    private $companyId;
    
    public function __construct($db, $companyId) {
        $this->db = $db;
        $this->companyId = $companyId;
    }
    
    /**
     * Send automated email using template
     * Adds email to queue for processing instead of sending directly
     * 
     * @param array $params {
     *   template: string (template_key from email_templates)
     *   to: string (recipient email)
     *   data: array (placeholder values)
     *   module_source: string ('sales', 'events', 'membership', 'rental', 'service')
     *   source_id: string (ID from original module table)
     *   priority: int (optional, 1=highest, 10=lowest, default=5)
     *   scheduled_at: string (optional, timestamp when to send, default=NOW)
     * }
     * @return array ['success' => bool, 'message' => string, 'queue_id' => string]
     */
    public function send($params) {
        try {
            // Validate required params
            if (empty($params['template']) || empty($params['to']) || empty($params['module_source']) || empty($params['source_id'])) {
                throw new Exception('Mangler påkrevde parametere (template, to, module_source, source_id)');
            }
            
            // 1. Get email template
            $template = $this->getTemplate($params['template']);
            if (!$template) {
                throw new Exception("Email template '{$params['template']}' ikke funnet");
            }
            
            // 2. Get SMTP config to verify it exists
            $smtpConfig = $this->getSMTPConfig();
            if (!$smtpConfig) {
                throw new Exception('SMTP-konfigurasjon ikke funnet. Sett opp e-postkonfigurasjon først.');
            }
            
            // 3. Render template with data
            $subject = $this->renderTemplate($template['subject_template'], $params['data'] ?? []);
            $body = $this->renderTemplate($template['body_template'], $params['data'] ?? []);
            
            // 4. Add to email queue
            $queueId = $this->queueEmail([
                'template_id' => $template['id'],
                'module_source' => $params['module_source'],
                'source_id' => $params['source_id'],
                'recipient_email' => $params['to'],
                'from_email' => $smtpConfig['from_email'],
                'subject' => $subject,
                'body' => $body,
                'priority' => $params['priority'] ?? 5,
                'scheduled_at' => $params['scheduled_at'] ?? date('Y-m-d H:i:s')
            ]);
            
            return [
                'success' => true,
                'message' => 'E-post lagt i kø for sending',
                'queue_id' => $queueId
            ];
            
        } catch (Exception $e) {
            error_log("EmailService error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get email template by key
     */
    private function getTemplate($templateKey) {
        $stmt = $this->db->prepare("
            SELECT * FROM email_templates 
            WHERE company_id = ? AND template_key = ? AND is_active = TRUE
            LIMIT 1
        ");
        $stmt->execute([$this->companyId, $templateKey]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get SMTP configuration for company
     */
    private function getSMTPConfig() {
        $stmt = $this->db->prepare("
            SELECT * FROM email_configs 
            WHERE company_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$this->companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get IMAP configuration for company
     */
    public function getIMAPConfig() {
        $stmt = $this->db->prepare("
            SELECT imap_host, imap_port, imap_username, imap_password_encrypted 
            FROM email_configs 
            WHERE company_id = ? 
            LIMIT 1
        ");
        $stmt->execute([$this->companyId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Check for unread emails via IMAP and update alertNew flag
     */
    public function checkUnreadEmails() {
        $config = $this->getIMAPConfig();
        if (!$config || empty($config['imap_host']) || empty($config['imap_username'])) {
            return ['success' => false, 'error' => 'IMAP ikke konfigurert'];
        }
        
        try {
            $mailbox = '{' . $config['imap_host'] . ':' . ($config['imap_port'] ?? 993) . '/imap/ssl}INBOX';
            $connection = @imap_open($mailbox, $config['imap_username'], $config['imap_password_encrypted']);
            
            if (!$connection) {
                return ['success' => false, 'error' => imap_last_error()];
            }
            
            // Count unread emails
            $unread = imap_search($connection, 'UNSEEN');
            $unreadCount = $unread ? count($unread) : 0;
            
            imap_close($connection);
            
            // Update alertNew flag
            $alertNew = $unreadCount > 0 ? 1 : 0;
            $stmt = $this->db->prepare("UPDATE email_configs SET alertNew = ? WHERE company_id = ?");
            $stmt->execute([$alertNew, $this->companyId]);
            
            return [
                'success' => true,
                'unread_count' => $unreadCount,
                'alertNew' => $alertNew
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get alertNew status
     */
    public function getAlertStatus() {
        $stmt = $this->db->prepare("SELECT alertNew FROM email_configs WHERE company_id = ? LIMIT 1");
        $stmt->execute([$this->companyId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['alertNew'] : 0;
    }
    
    /**
     * Reset alertNew flag (called when user opens email module)
     */
    public function resetAlert() {
        $stmt = $this->db->prepare("UPDATE email_configs SET alertNew = 0 WHERE company_id = ?");
        $stmt->execute([$this->companyId]);
        return true;
    }
    
    /**
     * Render template by replacing {{placeholders}} with data
     * 
     * @param string $template - Template string with {{placeholders}}
     * @param array $data - Key-value pairs for replacement
     * @return string Rendered template
     */
    private function renderTemplate($template, $data) {
        // Add company info to data (always available)
        $stmt = $this->db->prepare("SELECT name FROM tenant_companies WHERE id = ?");
        $stmt->execute([$this->companyId]);
        $company = $stmt->fetch(PDO::FETCH_ASSOC);
        $data['company_name'] = $company['name'] ?? 'Firmaet';
        
        return replaceKnownEmailTemplateTags($template, $data);
    }
    
    /**
     * Create email log entry
     */
    private function createEmailLog($params) {
        $logId = bin2hex(random_bytes(16));
        $currentTimestamp = date('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare("
            INSERT INTO email_logs (
                id,
                company_id,
                template_id,
                module_source,
                source_id,
                recipient_email,
                subject,
                status,
                created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $logId,
            $this->companyId,
            $params['template_id'],
            $params['module_source'],
            $params['source_id'],
            $params['recipient_email'],
            $params['subject'],
            $params['status'],
            $currentTimestamp
        ]);
        
        return $logId;
    }
    
    /**
     * Update email log status
     */
    private function updateEmailLog($logId, $status, $errorMessage = null) {
        $currentTimestamp = date('Y-m-d H:i:s');
        
        $stmt = $this->db->prepare("
            UPDATE email_logs 
            SET status = ?, 
                sent_at = ?, 
                error_message = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $status,
            $status === 'sent' ? $currentTimestamp : null,
            $errorMessage,
            $logId
        ]);
    }
    
    /**
     * Send email via SMTP
     * 
     * Uses PHP's mail() function with proper headers
     */
    private function sendViaSMTP($config, $to, $subject, $body) {
        try {
            // Use PHP's built-in mail() function with proper SMTP headers
            $headers = [];
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-type: text/html; charset=utf-8";
            $headers[] = "From: {$config['from_name']} <{$config['from_email']}>";
            $headers[] = "Reply-To: {$config['from_email']}";
            $headers[] = "X-Mailer: PHP/" . phpversion();
            
            $success = mail($to, $subject, $body, implode("\r\n", $headers));
            
            if ($success) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'PHP mail() returnerte false'];
            }
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get email sending statistics for a module
     */
    public function getStats($moduleSource = null, $days = 30) {
        $query = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'sent' THEN 1 ELSE 0 END) as sent,
                SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) as failed,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending
            FROM email_logs
            WHERE company_id = ?
        ";
        
        $params = [$this->companyId];
        
        if ($moduleSource) {
            $query .= " AND module_source = ?";
            $params[] = $moduleSource;
        }
        
        $query .= " AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
        $params[] = $days;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get recent email logs
     */
    public function getRecentLogs($limit = 20, $moduleSource = null) {
        $query = "
            SELECT 
                el.*,
                et.template_key,
                et.name as template_name
            FROM email_logs el
            LEFT JOIN email_templates et ON el.template_id = et.id
            WHERE el.company_id = ?
        ";
        
        $params = [$this->companyId];
        
        if ($moduleSource) {
            $query .= " AND el.module_source = ?";
            $params[] = $moduleSource;
        }
        
        $query .= " ORDER BY el.created_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->db->prepare($query);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Add email to queue for processing
     * 
     * @param array $params
     * @return string Queue ID
     */
    private function queueEmail($params) {
        $queueId = bin2hex(random_bytes(18));
        
        $stmt = $this->db->prepare("
            INSERT INTO email_queue (
                id,
                company_id,
                recipient_email,
                from_email,
                subject,
                body,
                template_id,
                module_source,
                source_id,
                status,
                priority,
                scheduled_at,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'queued', ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $queueId,
            $this->companyId,
            $params['recipient_email'],
            $params['from_email'],
            $params['subject'],
            $params['body'],
            $params['template_id'],
            $params['module_source'],
            $params['source_id'],
            $params['priority'],
            $params['scheduled_at']
        ]);
        
        return $queueId;
    }
}
