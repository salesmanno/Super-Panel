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
    $news = [];
    
    try {
        $news = getLatestNews(5);
    } catch (PDOException $e) {
        // Table might not exist yet or database connection issue - log but continue
        error_log("News database error (table may not exist): " . $e->getMessage());
        $news = [];
    } catch (Exception $e) {
        error_log("Unexpected news error: " . $e->getMessage());
        $news = [];
    }
    
    // Format news items
    $formatted_news = [];
    if (!empty($news) && is_array($news)) {
        $formatted_news = array_map(function($item) {
            try {
                $date = new DateTime($item['publish_at'] ?? 'now');
                return [
                    'id' => $item['id'] ?? null,
                    'title' => $item['title'] ?? 'Ukjent tittel',
                    'content' => $item['content'] ?? '',
                    'news_type' => $item['news_type'] ?? 'general',
                    'severity' => $item['severity'] ?? 'low',
                    'priority_text' => getPriorityText($item['severity'] ?? 'low'),
                    'formatted_date' => $date->format('d.m.Y H:i')
                ];
            } catch (Exception $e) {
                error_log("Error formatting news item: " . $e->getMessage());
                return null;
            }
        }, $news);
        
        // Filter out null items
        $formatted_news = array_filter($formatted_news, fn($item) => $item !== null);
    }
    
    // Always return success with either news or empty array
    echo json_encode([
        'success' => true,
        'news' => array_values($formatted_news) // Reindex array
    ]);
    
} catch (Exception $e) {
    error_log("Critical news API error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Even on error, return success with empty news array
    // This prevents cascade errors on the frontend
    echo json_encode([
        'success' => true,
        'news' => []
    ]);
}
