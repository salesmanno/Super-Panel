<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    $news = getLatestNews(10);
    
    $formattedNews = array_map(function($item) {
        $priority = $item['severity'] ?? 'low';
        $priorityClass = '';
        $priorityText = '';
        
        switch($priority) {
            case 'critical':
                $priorityClass = 'text-danger fw-bold';
                $priorityText = 'Kritisk';
                break;
            case 'high':
                $priorityClass = 'text-warning fw-semibold';
                $priorityText = 'Høy';
                break;
            case 'medium':
                $priorityClass = 'text-info';
                $priorityText = 'Middels';
                break;
            default:
                $priorityClass = 'text-muted';
                $priorityText = 'Lav';
        }
        
        return [
            'id' => $item['id'],
            'title' => $item['title'],
            'content' => $item['content'],
            'news_type' => $item['news_type'],
            'severity' => $priority,
            'priority' => $priority,
            'priority_class' => $priorityClass,
            'priority_text' => $priorityText,
            'formatted_date' => date('d.m.Y', strtotime($item['publish_at'])),
            'publish_at' => $item['publish_at']
        ];
    }, $news);
    
    echo json_encode([
        'success' => true,
        'news' => $formattedNews,
        'count' => count($formattedNews)
    ]);
    
} catch (Exception $e) {
    error_log("Error fetching news: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Kunne ikke hente nyheter',
        'news' => []
    ]);
}
