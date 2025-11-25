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
    
    $formatted_news = [];
    if ($news && is_array($news)) {
        $formatted_news = array_map(function($item) {
            if (!isset($item['publish_at'])) {
                return null;
            }
            try {
                $date = new DateTime($item['publish_at']);
                return [
                    'id' => $item['id'] ?? null,
                    'title' => $item['title'] ?? '',
                    'content' => $item['content'] ?? '',
                    'news_type' => $item['news_type'] ?? 'info',
                    'severity' => $item['severity'] ?? 'low',
                    'priority_text' => getPriorityText($item['severity'] ?? 'low'),
                    'formatted_date' => $date->format('d.m.Y H:i')
                ];
            } catch (Exception $e) {
                return null;
            }
        }, $news);
        $formatted_news = array_filter($formatted_news);
    }
    
    echo json_encode([
        'success' => true,
        'news' => $formatted_news
    ]);
} catch (Exception $e) {
    error_log("News API error: " . $e->getMessage());
    echo json_encode([
        'success' => true,
        'news' => []
    ]);
}
