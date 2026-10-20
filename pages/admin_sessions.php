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
require_once __DIR__ . '/../includes/tenant_supervisor.php';
if (!tenantIsSupervisor(getDBConnection(), (string) ($_SESSION['company_id'] ?? ''))) {
    http_response_code(403);
    exit('Du har ikke tilgang til sesjonsoversikten.');
}

$adminName = $_SESSION['admin_name'] ?? 'Bruker';
$companyName = $_SESSION['company_name'] ?? 'Firma';

$activeSessions = getAllActiveSessions($_SESSION['company_id']);
$mySessionHistory = getTenantAdminSessionHistory($_SESSION['tenant_admin_id'], 20);

function formatTimeDifference($datetime) {
    if (!$datetime) return 'Ukjent';
    
    $time = strtotime($datetime);
    $diff = time() - $time;
    
    if ($diff < 60) return 'Nå';
    if ($diff < 3600) return floor($diff / 60) . ' min siden';
    if ($diff < 86400) return floor($diff / 3600) . ' t siden';
    if ($diff < 172800) return 'I går';
    return date('d.m.Y H:i', $time);
}

function formatSessionDuration($loginTime, $logoutTime) {
    if (!$loginTime) return 'Ukjent';
    
    $start = strtotime($loginTime);
    $end = $logoutTime ? strtotime($logoutTime) : time();
    $duration = $end - $start;
    
    $hours = floor($duration / 3600);
    $minutes = floor(($duration % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . 't ' . $minutes . 'm';
    }
    return $minutes . ' min';
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session Oversikt - <?= htmlspecialchars($companyName) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-start: #667eea;
            --primary-end: #764ba2;
        }
        
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .header-gradient {
            background: linear-gradient(135deg, var(--primary-start), var(--primary-end));
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }
        
        .session-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transition: transform 0.2s;
        }
        
        .session-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        }
        
        .session-active {
            border-left: 4px solid #10b981;
        }
        
        .session-ended {
            border-left: 4px solid #6b7280;
            opacity: 0.7;
        }
        
        .badge-active {
            background: #10b981;
        }
        
        .badge-ended {
            background: #6b7280;
        }
        
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-start), var(--primary-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>
</head>
<body>
    <div class="header-gradient">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="mb-1">Session Oversikt</h1>
                    <p class="mb-0 opacity-75"><?= htmlspecialchars($companyName) ?></p>
                </div>
                <a href="company_dashboard.php" class="btn btn-light">
                    <i class="bi bi-arrow-left me-2"></i>Tilbake til Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?= count($activeSessions) ?></div>
                    <div class="text-muted">Aktive sessions</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="stats-number"><?= count($mySessionHistory) ?></div>
                    <div class="text-muted">Mine sessions</div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <div class="stats-card">
                    <div class="stats-number">
                        <?php
                        $currentActive = array_filter($mySessionHistory, fn($s) => $s['is_active']);
                        echo count($currentActive);
                        ?>
                    </div>
                    <div class="text-muted">Mine aktive</div>
                </div>
            </div>
        </div>

        <!-- Active Sessions -->
        <div class="mb-5">
            <h3 class="mb-3"><i class="bi bi-people-fill text-primary me-2"></i>Aktive Sessions (<?= htmlspecialchars($companyName) ?>)</h3>
            <?php if (empty($activeSessions)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>Ingen aktive sessions funnet.
                </div>
            <?php else: ?>
                <?php foreach ($activeSessions as $session): ?>
                    <div class="session-card session-active">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">
                                    <i class="bi bi-person-circle me-2"></i><?= htmlspecialchars($session['name']) ?>
                                    <?php if ($session['tenant_admin_id'] === $_SESSION['tenant_admin_id']): ?>
                                        <span class="badge bg-primary ms-2">Du</span>
                                    <?php endif; ?>
                                </h5>
                                <p class="text-muted mb-2"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($session['email']) ?></p>
                                <div class="row">
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="bi bi-clock me-1"></i>Innlogget: <?= formatTimeDifference($session['login_at']) ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6">
                                        <small class="text-muted">
                                            <i class="bi bi-activity me-1"></i>Sist aktiv: <?= formatTimeDifference($session['last_activity']) ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-laptop me-1"></i>IP: <?= htmlspecialchars($session['ip_address'] ?? 'Ukjent') ?>
                                    </small>
                                </div>
                            </div>
                            <span class="badge badge-active">Aktiv</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Session History -->
        <div class="mb-5">
            <h3 class="mb-3"><i class="bi bi-clock-history text-primary me-2"></i>Min Session Historikk</h3>
            <?php if (empty($mySessionHistory)): ?>
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>Ingen session-historikk funnet.
                </div>
            <?php else: ?>
                <?php foreach ($mySessionHistory as $session): ?>
                    <div class="session-card <?= $session['is_active'] ? 'session-active' : 'session-ended' ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="bi bi-calendar-check me-2"></i>
                                    <strong><?= date('d.m.Y H:i', strtotime($session['login_at'])) ?></strong>
                                    <?php if ($session['session_id'] === $_SESSION['session_id']): ?>
                                        <span class="badge bg-info ms-2">Nåværende session</span>
                                    <?php endif; ?>
                                </div>
                                <div class="row mt-2">
                                    <div class="col-md-4">
                                        <small class="text-muted">
                                            <i class="bi bi-hourglass me-1"></i>Varighet: <?= formatSessionDuration($session['login_at'], $session['logout_at']) ?>
                                        </small>
                                    </div>
                                    <div class="col-md-4">
                                        <small class="text-muted">
                                            <i class="bi bi-activity me-1"></i>Sist aktiv: <?= formatTimeDifference($session['last_activity']) ?>
                                        </small>
                                    </div>
                                    <div class="col-md-4">
                                        <?php if ($session['logout_at']): ?>
                                            <small class="text-muted">
                                                <i class="bi bi-box-arrow-right me-1"></i>Utlogget: <?= date('d.m.Y H:i', strtotime($session['logout_at'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="bi bi-laptop me-1"></i>IP: <?= htmlspecialchars($session['ip_address'] ?? 'Ukjent') ?>
                                    </small>
                                </div>
                            </div>
                            <span class="badge <?= $session['is_active'] ? 'badge-active' : 'badge-ended' ?>">
                                <?= $session['is_active'] ? 'Aktiv' : 'Avsluttet' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
