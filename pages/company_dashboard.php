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

$adminName = $_SESSION['admin_name'] ?? 'Bruker';
$companyName = $_SESSION['company_name'] ?? 'Firma';
$adminRole = $_SESSION['admin_role'] ?? 'admin';

$modules = getCompanyModules($_SESSION['company_id']);
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
        }
        
        body {
            background: #f8f9fa;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
        }
        
        .navbar {
            background: linear-gradient(135deg, var(--primary-start) 0%, var(--primary-end) 100%);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            font-weight: 700;
            color: white !important;
        }
        
        .navbar-text {
            color: rgba(255,255,255,0.9) !important;
        }
        
        .dashboard-header {
            background: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .dashboard-title {
            font-size: 32px;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-start), var(--primary-end));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .module-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            border: 2px solid #e5e7eb;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .module-card:hover {
            border-color: var(--primary-start);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.2);
        }
        
        .module-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--primary-start), var(--primary-end));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            margin-bottom: 16px;
        }
        
        .module-name {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .module-description {
            color: #6b7280;
            font-size: 14px;
        }
        
        .btn-logout {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            transition: all 0.2s ease;
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
            color: white;
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
                    <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($adminName) ?>
                </span>
                <a href="/auth/logout.php" class="btn btn-logout btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>Logg ut
                </a>
            </div>
        </div>
    </nav>
    
    <div class="dashboard-header">
        <div class="container">
            <h1 class="dashboard-title">Velkommen tilbake, <?= htmlspecialchars(explode(' ', $adminName)[0]) ?>!</h1>
            <p class="text-muted">Velg en modul for å komme i gang</p>
        </div>
    </div>
    
    <div class="container">
        <?php if (empty($modules)): ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                Ingen moduler er tilgjengelige for øyeblikket. Kontakt administrator for tilgang.
            </div>
        <?php else: ?>
            <div class="row">
                <?php foreach ($modules as $module): ?>
                    <div class="col-md-4">
                        <div class="module-card">
                            <div class="module-icon">
                                <i class="bi bi-<?= htmlspecialchars($module['icon'] ?? 'grid') ?>"></i>
                            </div>
                            <div class="module-name"><?= htmlspecialchars($module['name']) ?></div>
                            <div class="module-description">
                                <?= htmlspecialchars($module['description'] ?? 'Klikk for å åpne modulen') ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
