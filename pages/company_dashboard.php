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

$userModules = getTenantAdminModules($_SESSION['tenant_admin_id']);
$allModules = getCompanyModules($_SESSION['company_id']);
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
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        
        .navbar-gradient {
            background: linear-gradient(135deg, var(--primary-start), var(--primary-end));
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .navbar-brand {
            color: white !important;
            font-weight: 600;
        }
        
        .navbar-text {
            color: rgba(255,255,255,0.95) !important;
            font-weight: 500;
        }
        
        .btn-nav {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            transition: all 0.2s ease;
            font-weight: 500;
        }
        
        .btn-nav:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            transform: translateY(-1px);
        }
        
        .dashboard-header {
            background: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .module-tabs .nav-link {
            color: #6b7280;
            font-weight: 500;
            padding: 1rem 1.5rem;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        
        .module-tabs .nav-link:hover {
            color: var(--primary-start);
            border-bottom-color: var(--primary-start);
        }
        
        .module-tabs .nav-link.active {
            color: var(--primary-start);
            background: transparent;
            border-bottom-color: var(--primary-start);
        }
        
        .tab-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-gradient navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="bi bi-building me-2"></i><?= htmlspecialchars($companyName) ?>
            </a>
            <div class="d-flex align-items-center gap-2">
                <span class="navbar-text">
                    <i class="bi bi-person-circle me-1"></i>
                    <span class="d-none d-sm-inline"><?= htmlspecialchars($adminName) ?></span>
                </span>
                <a href="ditt_nettsted.php" class="btn btn-nav btn-sm">
                    <i class="bi bi-globe me-1"></i>
                    <span class="d-none d-sm-inline">Ditt Nettsted</span>
                </a>
                <button class="btn btn-nav btn-sm" data-bs-toggle="modal" data-bs-target="#permissionsModal">
                    <i class="bi bi-shield-check me-1"></i>
                    <span class="d-none d-sm-inline">Permissions</span>
                </button>
                <a href="admin_sessions.php" class="btn btn-nav btn-sm">
                    <i class="bi bi-clock-history me-1"></i>
                    <span class="d-none d-sm-inline">Sessions</span>
                </a>
                <a href="/auth/logout.php" class="btn btn-nav btn-sm">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    <span class="d-none d-sm-inline">Logg ut</span>
                </a>
            </div>
        </div>
    </nav>
    
    <!-- Header -->
    <div class="dashboard-header">
        <div class="container">
            <h1 class="mb-2">Velkommen, <?= htmlspecialchars($adminName) ?>!</h1>
            <p class="text-muted mb-0">Du har tilgang til <?= count($userModules) ?> modul<?= count($userModules) != 1 ? 'er' : '' ?></p>
        </div>
    </div>
    
    <!-- Module Tabs -->
    <div class="container">
        <?php if (empty($userModules)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>Ingen moduler tilgjengelig</h3>
                <p class="mb-0">Kontakt administrator for å få tilgang til moduler</p>
            </div>
        <?php else: ?>
            <ul class="nav nav-tabs module-tabs mb-0" role="tablist">
                <?php foreach ($userModules as $index => $module): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" 
                                id="tab-<?= $module['id'] ?>" 
                                data-bs-toggle="tab" 
                                data-bs-target="#content-<?= $module['id'] ?>" 
                                type="button" 
                                role="tab">
                            <i class="bi bi-<?= htmlspecialchars($module['icon'] ?? 'grid') ?> me-2"></i>
                            <?= htmlspecialchars($module['name']) ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="tab-content">
                <?php foreach ($userModules as $index => $module): ?>
                    <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" 
                         id="content-<?= $module['id'] ?>" 
                         role="tabpanel">
                        <h3><?= htmlspecialchars($module['name']) ?></h3>
                        <p class="text-muted"><?= htmlspecialchars($module['description'] ?? 'Modul innhold kommer snart') ?></p>
                        <hr>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Innhold for <strong><?= htmlspecialchars($module['name']) ?></strong> er under utvikling.
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Permissions Modal -->
    <div class="modal fade" id="permissionsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bi bi-shield-check me-2"></i>Velg Permissions
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Velg hvilke moduler du vil ha tilgang til:</p>
                    <form id="permissionsForm">
                        <?php foreach ($allModules as $module): 
                            $hasPermission = false;
                            foreach ($userModules as $userMod) {
                                if ($userMod['id'] === $module['id']) {
                                    $hasPermission = true;
                                    break;
                                }
                            }
                        ?>
                            <div class="form-check mb-3">
                                <input class="form-check-input" 
                                       type="checkbox" 
                                       value="<?= $module['id'] ?>" 
                                       id="perm-<?= $module['id'] ?>"
                                       <?= $hasPermission ? 'checked' : '' ?>>
                                <label class="form-check-label" for="perm-<?= $module['id'] ?>">
                                    <i class="bi bi-<?= htmlspecialchars($module['icon'] ?? 'grid') ?> me-2"></i>
                                    <strong><?= htmlspecialchars($module['name']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($module['description'] ?? '') ?></small>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                    <button type="button" class="btn btn-primary" onclick="savePermissions()">
                        <i class="bi bi-check-circle me-1"></i>Lagre
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function savePermissions() {
            const form = document.getElementById('permissionsForm');
            const checkboxes = form.querySelectorAll('input[type="checkbox"]');
            const selectedModules = [];
            
            checkboxes.forEach(checkbox => {
                if (checkbox.checked) {
                    selectedModules.push(checkbox.value);
                }
            });
            
            fetch('/api/update_permissions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    modules: selectedModules
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Feil: ' + (data.error || 'Kunne ikke oppdatere permissions'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('En feil oppstod ved lagring av permissions');
            });
        }
    </script>
</body>
</html>
