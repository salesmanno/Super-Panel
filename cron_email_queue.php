#!/usr/bin/env php
<?php
/**
 * Email Queue Cron Job
 * 
 * Runs email queue processor every minute
 * Usage: Add to crontab or run via workflow
 */

// Include the queue processor
require_once __DIR__ . '/api/process_email_queue.php';

echo "[" . date('Y-m-d H:i:s') . "] Email queue processor started\n";
processQueue();
echo "[" . date('Y-m-d H:i:s') . "] Email queue processor finished\n";
