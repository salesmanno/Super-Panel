<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

try {
    // Get company ID from query string if provided (for logged-in users)
    $companyId = isset($_GET['company_id']) ? $_GET['company_id'] : null;
    
    // Fetch core_sys_news filtered by target_audience
    // On login page: shows 'all' audience only
    // For logged-in users: shows 'all' + company-specific news
    $news = getLatestNews(10, $companyId);
    
    if (!$news) {
        echo json_encode([
            'success' => true,
            'news' => []
        ]);
        exit;
    }
    
    $formattedNews = [];
    
    foreach ($news as $item) {
        // Map priority to text
        $priorityMap = [
            'critical' => 'Kritisk',
            'high' => 'Høy',
            'medium' => 'Medium',
            'low' => 'Lav'
        ];
        
        $newsTypeMap = [
            'announcement' => 'Kunngjøring',
            'maintenance' => 'Vedlikehold',
            'update' => 'Oppdatering',
            'alert' => 'Varsel',
            'info' => 'Info'
        ];
        
        $severity = isset($item['priority']) ? strtolower($item['priority']) : 'medium';
        $newsType = isset($item['news_type']) ? strtolower($item['news_type']) : 'info';
        
        $publishDate = new DateTime($item['publish_at']);
        $now = new DateTime();
        $diff = $publishDate->diff($now);
        
        // Format date
        if ($diff->days == 0) {
            if ($diff->h == 0) {
                $formattedDate = $diff->i . ' min siden';
            } else {
                $formattedDate = $diff->h . ' t siden';
            }
        } elseif ($diff->days == 1) {
            $formattedDate = 'I går';
        } else {
            $formattedDate = $publishDate->format('d. M');
        }
        
        $formattedNews[] = [
            'id' => $item['id'] ?? uniqid(),
            'title' => $item['title'] ?? '',
            'content' => $item['content'] ?? '',
            'priority_text' => $priorityMap[$severity] ?? 'Info',
            'severity' => $severity,
            'news_type' => $newsType,
            'type_label' => $newsTypeMap[$newsType] ?? 'Info',
            'formatted_date' => $formattedDate,
            'publish_at' => $item['publish_at'] ?? null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'news' => $formattedNews
    ]);
    
} catch (Exception $e) {
    error_log('Error fetching news: ' . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => 'Kunne ikke hente nyheter',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
