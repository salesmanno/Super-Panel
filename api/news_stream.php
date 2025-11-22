<?php
session_start();
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

function streamNews() {
    try {
        $news = getLatestNews(5);
        
        if ($news) {
            $formatted_news = array_map(function($item) {
                $date = new DateTime($item['publish_at']);
                return [
                    'id' => $item['id'],
                    'title' => $item['title'],
                    'content' => $item['content'],
                    'news_type' => $item['news_type'],
                    'severity' => $item['severity'],
                    'priority_text' => getPriorityText($item['severity']),
                    'formatted_date' => $date->format('d.m.Y H:i')
                ];
            }, $news);
            
            echo "data: " . json_encode([
                'success' => true,
                'news' => $formatted_news
            ]) . "\n\n";
        } else {
            echo "data: " . json_encode([
                'success' => false,
                'news' => []
            ]) . "\n\n";
        }
    } catch (Exception $e) {
        error_log("News stream error: " . $e->getMessage());
        echo "data: " . json_encode([
            'success' => false,
            'error' => 'Database error',
            'news' => []
        ]) . "\n\n";
    }
    
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

function getPriorityText($severity) {
    $priorities = [
        'critical' => 'Kritisk',
        'high' => 'Høy',
        'medium' => 'Middels',
        'low' => 'Lav'
    ];
    return $priorities[$severity] ?? 'Normal';
}

// Send news once
streamNews();

// Disable infinite loop on PHP built-in server (single-threaded)
if (php_sapi_name() !== 'cli-server') {
    while (true) {
        sleep(30);
        streamNews();
    }
}
