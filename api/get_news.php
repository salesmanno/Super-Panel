<?php
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

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
    // Get news from database
    $news = getLatestNews(5);
    
    // Format news items for display
    $formatted_news = [];
    
    if (is_array($news) && !empty($news)) {
        foreach ($news as $item) {
            try {
                // Parse publish_at date
                $date = DateTime::createFromFormat('Y-m-d H:i:s', $item['publish_at']) 
                    ?: DateTime::createFromFormat('Y-m-d H:i:s.u', $item['publish_at'])
                    ?: new DateTime($item['publish_at']);
                
                $formatted_news[] = [
                    'id' => intval($item['id']),
                    'title' => htmlspecialchars($item['title'] ?? 'Ukjent tittel'),
                    'content' => htmlspecialchars($item['content'] ?? ''),
                    'news_type' => $item['news_type'] ?? 'info',
                    'severity' => $item['severity'] ?? 'low',
                    'priority_text' => getPriorityText($item['severity'] ?? 'low'),
                    'formatted_date' => $date->format('d.m.Y H:i')
                ];
            } catch (Exception $e) {
                error_log("Error formatting news item ID {$item['id']}: " . $e->getMessage());
                // Skip malformed items but continue with others
                continue;
            }
        }
    }
    
    // Always return success
    echo json_encode([
        'success' => true,
        'news' => $formatted_news
    ]);
    
} catch (Exception $e) {
    error_log("News API critical error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Return success with empty array to prevent cascade errors
    echo json_encode([
        'success' => true,
        'news' => []
    ]);
}
