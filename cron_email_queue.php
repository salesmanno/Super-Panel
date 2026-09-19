#!/usr/bin/env php
<?php
/**
 * Email Queue Cron Job
 * 
 * Runs email queue processor every minute
 * Usage: Add to crontab or run via workflow
 */

// Include without auto-running; this script invokes the processor once below.
define('EMAIL_QUEUE_PROCESSOR_NO_AUTO_RUN', true);
require_once __DIR__ . '/api/process_email_queue.php';

echo "[" . date('Y-m-d H:i:s') . "] Email queue processor started\n";
processQueue();
echo "[" . date('Y-m-d H:i:s') . "] Email queue processor finished\n";
