<?php
/**
 * Email Queue Processor
 * 
 * Prosesserer e-postkøen med rate limiting og retry-logikk
 * - Henter e-poster som er klare for sending
 * - Sender via SMTP (fra email_configs)
 * - Håndterer rate limiting per firma (100 e-poster per time)
 * - Retry-logikk med exponential backoff
 */

require_once __DIR__ . '/../config/database.php';

// Rate limiting: max emails per hour per company
define('MAX_EMAILS_PER_HOUR_PER_COMPANY', 100);

// Exponential backoff for retries (minutes)
$RETRY_DELAYS = [5, 15, 60]; // 5min, 15min, 1hour

function processQueue() {
    try {
        $db = getDBConnection();
        
        // 1. Get companies with queued emails ready to send
        $companies = getCompaniesWithQueuedEmails($db);
        
        foreach ($companies as $companyId) {
            // 2. Check rate limit for this company
            $canSend = checkRateLimit($db, $companyId);
            
            if (!$canSend) {
                echo "Rate limit reached for company {$companyId}, skipping...\n";
                continue;
            }
            
            // 3. Get batch of emails to send (respecting rate limit)
            $emailsToSend = getEmailsToSend($db, $companyId);
            
            echo "Processing " . count($emailsToSend) . " emails for company {$companyId}\n";
            
            // 4. Process each email
            foreach ($emailsToSend as $email) {
                processEmail($db, $email);
            }
        }
        
        echo "Queue processing completed\n";
        
    } catch (Exception $e) {
        error_log("Queue processor error: " . $e->getMessage());
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

/**
 * Get companies that have emails ready to send
 */
function getCompaniesWithQueuedEmails($db) {
    $stmt = $db->prepare("
        SELECT DISTINCT company_id 
        FROM email_queue
        WHERE status = 'queued'
        AND scheduled_at <= NOW()
        ORDER BY company_id
    ");
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/**
 * Check if company has not exceeded rate limit
 */
function checkRateLimit($db, $companyId) {
    // Count emails sent in the last hour
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM email_queue
        WHERE company_id = ?
        AND status = 'sent'
        AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$companyId]);
    $sentLastHour = $stmt->fetchColumn();
    
    return $sentLastHour < MAX_EMAILS_PER_HOUR_PER_COMPANY;
}

/**
 * Get emails to send for a company (respecting rate limit)
 */
function getEmailsToSend($db, $companyId) {
    // Calculate how many we can send
    $stmt = $db->prepare("
        SELECT COUNT(*) 
        FROM email_queue
        WHERE company_id = ?
        AND status = 'sent'
        AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
    ");
    $stmt->execute([$companyId]);
    $sentLastHour = $stmt->fetchColumn();
    
    $remaining = MAX_EMAILS_PER_HOUR_PER_COMPANY - $sentLastHour;
    
    if ($remaining <= 0) {
        return [];
    }
    
    // Get emails ready to send, ordered by priority and scheduled time
    $stmt = $db->prepare("
        SELECT * 
        FROM email_queue
        WHERE company_id = ?
        AND status = 'queued'
        AND scheduled_at <= NOW()
        ORDER BY priority ASC, scheduled_at ASC
        LIMIT ?
    ");
    $stmt->execute([$companyId, $remaining]);
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Process a single email
 */
function processEmail($db, $email) {
    global $RETRY_DELAYS;
    
    try {
        // Mark as sending
        updateEmailStatus($db, $email['id'], 'sending');
        
        // Get SMTP config for this company
        $smtpConfig = getSMTPConfig($db, $email['company_id']);
        
        if (!$smtpConfig) {
            throw new Exception("SMTP config not found for company {$email['company_id']}");
        }
        
        // Send email
        $result = sendEmailViaSMTP($smtpConfig, $email);
        
        if ($result['success']) {
            // Mark as sent
            $stmt = $db->prepare("
                UPDATE email_queue 
                SET status = 'sent',
                    sent_at = NOW(),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$email['id']]);
            
            echo "✓ Email sent: {$email['id']} to {$email['recipient_email']}\n";
            
        } else {
            throw new Exception($result['error']);
        }
        
    } catch (Exception $e) {
        // Handle failure with retry logic
        $attempts = $email['attempts'] + 1;
        $maxAttempts = $email['max_attempts'] ?? 3;
        
        if ($attempts < $maxAttempts) {
            // Calculate next retry time with exponential backoff
            $delayIndex = min($attempts - 1, count($RETRY_DELAYS) - 1);
            $delayMinutes = $RETRY_DELAYS[$delayIndex];
            
            $stmt = $db->prepare("
                UPDATE email_queue 
                SET status = 'queued',
                    attempts = ?,
                    next_retry_at = DATE_ADD(NOW(), INTERVAL ? MINUTE),
                    error_message = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$attempts, $delayMinutes, $e->getMessage(), $email['id']]);
            
            echo "✗ Email failed (attempt {$attempts}/{$maxAttempts}): {$email['id']} - {$e->getMessage()}\n";
            echo "  Retry scheduled in {$delayMinutes} minutes\n";
            
        } else {
            // Max attempts reached, mark as permanently failed
            $stmt = $db->prepare("
                UPDATE email_queue 
                SET status = 'failed',
                    attempts = ?,
                    error_message = ?,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$attempts, $e->getMessage(), $email['id']]);
            
            echo "✗✗ Email permanently failed: {$email['id']} - {$e->getMessage()}\n";
        }
        
        error_log("Email send error (ID: {$email['id']}): " . $e->getMessage());
    }
}

/**
 * Update email status
 */
function updateEmailStatus($db, $emailId, $status) {
    $stmt = $db->prepare("
        UPDATE email_queue 
        SET status = ?,
            updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$status, $emailId]);
}

/**
 * Get SMTP configuration for company
 */
function getSMTPConfig($db, $companyId) {
    $stmt = $db->prepare("
        SELECT * FROM email_configs 
        WHERE company_id = ? 
        LIMIT 1
    ");
    $stmt->execute([$companyId]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Send email via SMTP using PHP's mail() function
 */
function sendEmailViaSMTP($config, $email) {
    try {
        // Build headers
        $headers = [];
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-type: text/html; charset=utf-8";
        
        $fromName = $config['from_name'] ?? '';
        $fromEmail = $email['from_email'] ?? $config['from_email'];
        
        if ($fromName) {
            $headers[] = "From: {$fromName} <{$fromEmail}>";
        } else {
            $headers[] = "From: {$fromEmail}";
        }
        
        $headers[] = "Reply-To: {$fromEmail}";
        $headers[] = "X-Mailer: PHP/" . phpversion();
        
        // Send using PHP's mail() function
        $success = mail(
            $email['recipient_email'],
            $email['subject'],
            $email['body'],
            implode("\r\n", $headers)
        );
        
        if ($success) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => 'PHP mail() returned false'];
        }
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Run if called directly
if (php_sapi_name() === 'cli') {
    processQueue();
} else {
    // Called via HTTP - return JSON response
    header('Content-Type: application/json');
    processQueue();
    echo json_encode(['success' => true, 'message' => 'Queue processed']);
}
