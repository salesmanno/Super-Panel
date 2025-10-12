<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

set_time_limit(0);

while (ob_get_level() > 0) {
    ob_end_flush();
}

ob_implicit_flush(true);

function sendSSE($id, $data) {
    echo "id: $id\n";
    echo "data: " . json_encode($data) . "\n\n";
    if (ob_get_level() > 0) {
        ob_flush();
    }
    flush();
}

try {
    $lastId = isset($_GET['lastEventId']) ? (int)$_GET['lastEventId'] : 0;
    
    $news = getLatestNews(10);
    
    if (!empty($news)) {
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
        
        sendSSE(time(), [
            'success' => true,
            'news' => $formattedNews,
            'count' => count($formattedNews)
        ]);
    } else {
        sendSSE(time(), [
            'success' => true,
            'news' => [],
            'count' => 0
        ]);
    }
    
    $lastUpdate = time();
    
    while (true) {
        sleep(5);
        
        if (time() - $lastUpdate >= 30) {
            $lastUpdate = time();
            $news = getLatestNews(10);
            
            if (!empty($news)) {
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
                
                sendSSE(time(), [
                    'success' => true,
                    'news' => $formattedNews,
                    'count' => count($formattedNews),
                    'updated' => true
                ]);
            }
        } else {
            echo ": heartbeat\n\n";
            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();
        }
        
        if (connection_aborted()) {
            break;
        }
    }
    
} catch (Exception $e) {
    error_log("SSE Error: " . $e->getMessage());
    sendSSE(time(), [
        'success' => false,
        'error' => 'Kunne ikke hente nyheter'
    ]);
}
