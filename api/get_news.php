<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

function getPriorityText($severity) {
    $priorities = [
        'critical' => 'Kritisk',
        'high' => 'Høy',
        'medium' => 'Middels',
        'low' => 'Lav'
    ];
    return $priorities[$severity] ?? 'Normal';
}

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
        
        echo json_encode([
            'success' => true,
            'news' => $formatted_news
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'news' => []
        ]);
    }
} catch (Exception $e) {
    error_log("News API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error',
        'news' => []
    ]);
}
