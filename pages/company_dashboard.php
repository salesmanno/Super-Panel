<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['tenant_admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/session_manager.php';

$adminName = $_SESSION['admin_name'] ?? 'Bruker';
$companyName = $_SESSION['company_name'] ?? 'Firma';
$adminRole = $_SESSION['admin_role'] ?? 'admin';

$modules = getCompanyModules($_SESSION['company_id']);

$lastLoginText = 'Ukjent';
if (isset($_SESSION['logged_in_at'])) {
    $loginTime = $_SESSION['logged_in_at'];
    $diff = time() - $loginTime;
    
    if ($diff < 60) {
        $lastLoginText = 'Nå';
    } elseif ($diff < 3600) {
        $minutes = floor($diff / 60);
        $lastLoginText = $minutes . ' min siden';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        $lastLoginText = $hours . ' t siden';
    } else {
        $days = floor($diff / 86400);
        if ($days == 1) {
            $lastLoginText = 'I går';
        } else {
            $lastLoginText = date('d.m.Y H:i', $loginTime);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($companyName) ?> - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-start: #667eea;
            --primary-end: #764ba2;
            --shadow-light: rgba(102, 126, 234, 0.15);
        }
        
        body {
            background: #f5f7fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            min-height: 100vh;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
            box-shadow: 0 2px 12px rgba(0,0,0,0.15);
            padding: 1rem 0;
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
            font-size: 20px;
        }
        
        .navbar-text {
            color: rgba(255,255,255,0.95) !important;
            font-weight: 500;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            transform: translateY(-1px);
        }
        
        .dashboard-header {
            background: white;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        }
        
        .dashboard-title {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-start), var(--primary-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 8px;
        }
        
        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .stat-card:hover {
            box-shadow: 0 8px 24px var(--shadow-light);
            transform: translateY(-2px);
        }
        
        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }
        
        .stat-icon.purple { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .stat-icon.blue { background: linear-gradient(135deg, #3b82f6, #2563eb); color: white; }
        .stat-icon.green { background: linear-gradient(135deg, #10b981, #059669); color: white; }
        .stat-icon.orange { background: linear-gradient(135deg, #f59e0b, #d97706); color: white; }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 14px;
            font-weight: 500;
        }
        
        .module-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
            text-decoration: none;
            color: inherit;
            display: block;
        }
        
        .module-card:hover {
            border-color: var(--primary-start);
            transform: translateY(-4px);
            box-shadow: 0 12px 32px var(--shadow-light);
            color: inherit;
        }
        
        .module-icon {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, var(--primary-start), var(--primary-end));
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            margin-bottom: 16px;
        }
        
        .module-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #1f2937;
        }
        
        .module-description {
            color: #6b7280;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .news-widget {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e5e7eb;
            max-height: 500px;
            overflow-y: auto;
        }
        
        .news-widget h5 {
            font-weight: 700;
            margin-bottom: 20px;
            color: #1f2937;
        }
        
        .news-item-small {
            padding: 16px;
            background: #f9fafb;
            border-radius: 12px;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }
        
        .news-item-small:hover {
            background: #f3f4f6;
            transform: translateX(4px);
        }
        
        .news-item-small .title {
            font-weight: 600;
            font-size: 14px;
            color: #1f2937;
            margin-bottom: 4px;
        }
        
        .news-item-small .content {
            font-size: 13px;
            color: #6b7280;
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .news-item-small .meta {
            font-size: 11px;
            color: #9ca3af;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 24px;
        }
        
        @media (max-width: 768px) {
            .dashboard-title {
                font-size: 24px;
            }
            .stat-value {
                font-size: 24px;
            }
            .navbar-text .d-none {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-building me-2"></i><?= htmlspecialchars($companyName) ?>
            </a>
            <div class="d-flex align-items-center">
                <span class="navbar-text me-3">
                    <i class="bi bi-person-circle me-1"></i>
                    <span class="d-none d-sm-inline"><?= htmlspecialchars($adminName) ?></span>
                </span>
                <a href="admin_sessions.php" class="btn btn-logout btn-sm me-2">
                    <i class="bi bi-clock-history me-1"></i>
                    <span class="d-none d-sm-inline">Sessions</span>
                </a>
                <a href="/auth/logout.php" class="btn btn-logout btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    <span class="d-none d-sm-inline">Logg ut</span>
                </a>
            </div>
        </div>
    </nav>
    
    <div class="dashboard-header">
        <div class="container">
            <h1 class="dashboard-title">Velkommen tilbake, <?= htmlspecialchars(explode(' ', $adminName)[0]) ?>!</h1>
            <p class="text-muted mb-0">Her er en oversikt over bedriftsportalen din</p>
        </div>
    </div>
    
    <div class="container pb-5">
        <!-- Statistikk -->
        <div class="row mb-4">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon purple">
                        <i class="bi bi-grid-3x3-gap"></i>
                    </div>
                    <div class="stat-value"><?= count($modules) ?></div>
                    <div class="stat-label">Tilgjengelige moduler</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon blue">
                        <i class="bi bi-person-check"></i>
                    </div>
                    <div class="stat-value">Aktiv</div>
                    <div class="stat-label">Kontostatus</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon green">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <div class="stat-value"><?= ucfirst($adminRole) ?></div>
                    <div class="stat-label">Din rolle</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="stat-card">
                    <div class="stat-icon orange">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="stat-value"><?= htmlspecialchars($lastLoginText) ?></div>
                    <div class="stat-label">Siste innlogging</div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Moduler -->
            <div class="col-lg-8 mb-4">
                <h2 class="section-title"><i class="bi bi-grid-3x3-gap me-2"></i>Dine moduler</h2>
                
                <?php if (empty($modules)): ?>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Ingen moduler er tilgjengelige for øyeblikket. Kontakt administrator for tilgang.
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($modules as $module): ?>
                            <div class="col-md-6 mb-3">
                                <a href="#" class="module-card" onclick="alert('Modul: <?= htmlspecialchars($module['name']) ?> kommer snart!'); return false;">
                                    <div class="module-icon">
                                        <i class="bi bi-<?= htmlspecialchars($module['icon'] ?? 'grid') ?>"></i>
                                    </div>
                                    <div class="module-name"><?= htmlspecialchars($module['name']) ?></div>
                                    <div class="module-description">
                                        <?= htmlspecialchars($module['description'] ?? 'Klikk for å åpne modulen') ?>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Nyheter -->
            <div class="col-lg-4 mb-4">
                <div class="news-widget">
                    <h5><i class="bi bi-newspaper me-2"></i>Siste nyheter</h5>
                    <div id="news-container">
                        <div class="text-center py-3">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Laster...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let eventSource = null;
        
        function connectToNewsStream() {
            if (eventSource) {
                eventSource.close();
            }
            
            eventSource = new EventSource('/api/news_stream.php');
            
            eventSource.onmessage = function(event) {
                try {
                    const data = JSON.parse(event.data);
                    updateNewsDisplay(data);
                } catch (error) {
                    console.error('Error parsing SSE data:', error);
                }
            };
            
            eventSource.onerror = function(error) {
                console.error('SSE error:', error);
                eventSource.close();
                document.getElementById('news-container').innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <p class="mb-0 mt-2 small">Kunne ikke laste nyheter</p>
                    </div>
                `;
                setTimeout(connectToNewsStream, 5000);
            };
        }
        
        function updateNewsDisplay(data) {
            const container = document.getElementById('news-container');
            
            if (data.success && data.news && data.news.length > 0) {
                let html = '';
                data.news.slice(0, 5).forEach(item => {
                    html += `
                        <div class="news-item-small">
                            <div class="title">${escapeHtml(item.title)}</div>
                            <div class="content">${escapeHtml(item.content.substring(0, 80))}${item.content.length > 80 ? '...' : ''}</div>
                            <div class="meta">
                                <i class="bi bi-calendar3"></i> ${item.formatted_date}
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-info-circle"></i>
                        <p class="mb-0 mt-2 small">Ingen nyheter tilgjengelig</p>
                    </div>
                `;
            }
        }
        
        function escapeHtml(unsafe) {
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            connectToNewsStream();
        });
        
        window.addEventListener('beforeunload', function() {
            if (eventSource) {
                eventSource.close();
            }
        });
    </script>
</body>
</html>
