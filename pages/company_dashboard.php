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

$allUserModules = getTenantAdminModules($_SESSION['tenant_admin_id']);
$officialModules = array_filter($allUserModules, function($module) {
    return in_array($module['slug'], ['dashboard', 'ditt_nettsted']);
});
$userModules = array_filter($allUserModules, function($module) {
    return !in_array($module['slug'], ['dashboard', 'ditt_nettsted']);
});
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
        
        .module-tabs {
            padding: 0.5rem 0;
        }
        
        .module-tabs .nav-link {
            color: #6b7280;
            font-weight: 600;
            padding: 0.875rem 1.75rem;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
            background: transparent;
            border-radius: 0;
        }
        
        .module-tabs .nav-link:hover {
            color: var(--primary-start);
            border-bottom-color: rgba(102, 126, 234, 0.3);
            background: rgba(102, 126, 234, 0.05);
        }
        
        .module-tabs .nav-link.active {
            color: var(--primary-start);
            background: linear-gradient(to bottom, rgba(102, 126, 234, 0.1), transparent);
            border-bottom-color: var(--primary-start);
        }
        
        .module-tabs .nav-link i {
            font-size: 1.1rem;
        }
        
        .subtabs .nav-link {
            color: #6b7280;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: all 0.2s;
            font-size: 0.9rem;
        }
        
        .subtabs .nav-link:hover {
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary-start);
        }
        
        .subtabs .nav-link.active {
            background: var(--primary-start);
            color: white;
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
            <p class="text-muted mb-0">Du har tilgang til <?= count($allUserModules) ?> modul<?= count($allUserModules) != 1 ? 'er' : '' ?></p>
        </div>
    </div>
    
    <?php if (!empty($officialModules)): ?>
    <!-- Official Modules Navigation -->
    <div class="bg-light border-bottom">
        <div class="container">
            <ul class="nav nav-tabs module-tabs border-0 mb-0" role="tablist">
                <?php foreach ($officialModules as $index => $module): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" 
                                id="tab-<?= $module['id'] ?>" 
                                data-bs-toggle="tab" 
                                data-bs-target="#content-<?= $module['id'] ?>" 
                                type="button" 
                                role="tab"
                                onclick="showSubTabs('<?= $module['slug'] ?>')">
                            <i class="bi bi-<?= htmlspecialchars($module['icon'] ?? 'grid') ?> me-2"></i>
                            <?= htmlspecialchars($module['name']) ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($userModules)): ?>
    <!-- Module Navigation Bar -->
    <div class="bg-white border-bottom">
        <div class="container">
            <ul class="nav nav-tabs module-tabs border-0 mb-0" role="tablist">
                <?php foreach ($userModules as $index => $module): ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $index === 0 && empty($officialModules) ? 'active' : '' ?>" 
                                id="tab-<?= $module['id'] ?>" 
                                data-bs-toggle="tab" 
                                data-bs-target="#content-<?= $module['id'] ?>" 
                                type="button" 
                                role="tab"
                                onclick="showSubTabs('<?= $module['slug'] ?>')">
                            <i class="bi bi-<?= htmlspecialchars($module['icon'] ?? 'grid') ?> me-2"></i>
                            <?= htmlspecialchars($module['name']) ?>
                        </button>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- SubTabs Container -->
    <div class="bg-light border-bottom mb-4" id="subtabs-container" style="display: none;">
        <div class="container">
            <!-- Events subtabs -->
            <ul class="nav nav-pills py-2 subtabs" id="subtabs-events" style="display: none;">
                <li class="nav-item">
                    <a class="nav-link active" href="#events-my">Dine arrangement</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#events-create">Opprett arrangement</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#events-expenses">Arrangement utgifter</a>
                </li>
            </ul>
            
            <!-- Membership subtabs -->
            <ul class="nav nav-pills py-2 subtabs" id="subtabs-membership" style="display: none;">
                <li class="nav-item">
                    <a class="nav-link active" href="#membership-plans">Dine medlemskapsplaner</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#membership-members">Medlemmer</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#membership-income">Medlemsinntekter</a>
                </li>
            </ul>
            
            <!-- Reservations subtabs -->
            <ul class="nav nav-pills py-2 subtabs" id="subtabs-reservations" style="display: none;">
                <li class="nav-item">
                    <a class="nav-link active" href="#reservations-incoming">Reservasjonsmottak</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#reservations-confirmed">Bekreftede Reservasjoner</a>
                </li>
            </ul>
            
            <!-- Rental subtabs -->
            <ul class="nav nav-pills py-2 subtabs" id="subtabs-rental" style="display: none;">
                <li class="nav-item">
                    <a class="nav-link active" href="#rental-objects">Se og opprett utleieobjekt</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#rental-contracts">Kontrakt & betingelser</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#rental-upcoming">Kommende utleie</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#rental-results">Deres utleie resultat</a>
                </li>
            </ul>
            
            <!-- Webmail subtabs -->
            <ul class="nav nav-pills py-2 subtabs" id="subtabs-webmail" style="display: none;">
                <li class="nav-item">
                    <a class="nav-link active" href="#webmail-client">E-post klient</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#webmail-history">E-post historikk</a>
                </li>
            </ul>
            
            <!-- Economy subtabs -->
            <ul class="nav nav-pills py-2 subtabs" id="subtabs-economy" style="display: none;">
                <li class="nav-item">
                    <a class="nav-link active" href="#economy-results">Deres resultat</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#economy-payment">Se og Aktiver nettbetaling</a>
                </li>
            </ul>
        </div>
    </div>
    
    <!-- Module Content -->
    <div class="container">
        <?php if (empty($allUserModules)): ?>
            <div class="empty-state">
                <i class="bi bi-inbox"></i>
                <h3>Ingen moduler tilgjengelig</h3>
                <p class="mb-0">Kontakt administrator for å få tilgang til moduler</p>
            </div>
        <?php else: ?>
            <div class="tab-content">
                <?php 
                $allModulesForDisplay = array_merge($officialModules, $userModules);
                foreach ($allModulesForDisplay as $index => $module): 
                ?>
                    <div class="tab-pane fade <?= $index === 0 ? 'show active' : '' ?>" 
                         id="content-<?= $module['id'] ?>" 
                         role="tabpanel">
                        <?php 
                        switch ($module['slug']) {
                            case 'dashboard':
                                echo '<h3>Dashboard</h3>';
                                echo '<p class="text-muted">Oversikt over systemet</p>';
                                echo '<div class="alert alert-info">Dashboard innhold kommer snart</div>';
                                break;
                            case 'ditt_nettsted':
                                echo '<iframe src="ditt_nettsted.php" style="width: 100%; height: 80vh; border: none; border-radius: 8px;" frameborder="0"></iframe>';
                                break;
                            case 'webmail':
                                include __DIR__ . '/../modules/webmail_content.php';
                                break;
                            case 'reservations':
                                include __DIR__ . '/../modules/reservations_content.php';
                                break;
                            case 'economy':
                                include __DIR__ . '/../modules/economy_content.php';
                                break;
                            case 'membership':
                                include __DIR__ . '/../modules/membership_content.php';
                                break;
                            case 'rental':
                                include __DIR__ . '/../modules/rental_content.php';
                                break;
                            case 'events':
                                include __DIR__ . '/../modules/events_content.php';
                                break;
                            case 'marketplace':
                                include __DIR__ . '/../modules/marketplace_content.php';
                                break;
                            default:
                                echo '<div class="alert alert-warning">Modul under utvikling</div>';
                        }
                        ?>
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
        
        function showSubTabs(moduleSlug) {
            const container = document.getElementById('subtabs-container');
            const allSubtabs = document.querySelectorAll('.subtabs');
            
            allSubtabs.forEach(subtab => subtab.style.display = 'none');
            
            const moduleSubtabs = document.getElementById('subtabs-' + moduleSlug);
            if (moduleSubtabs) {
                container.style.display = 'block';
                moduleSubtabs.style.display = 'flex';
                
                const firstSubtabLink = moduleSubtabs.querySelector('.nav-link');
                if (firstSubtabLink) {
                    const targetId = firstSubtabLink.getAttribute('href').substring(1);
                    showSubTabContent(moduleSlug, targetId);
                }
            } else {
                container.style.display = 'none';
            }
        }
        
        function showSubTabContent(moduleSlug, targetId) {
            const moduleContent = document.getElementById('content-' + moduleSlug);
            if (!moduleContent) return;
            
            const allSubtabContents = moduleContent.querySelectorAll('.subtab-content');
            allSubtabContents.forEach(content => {
                content.style.display = 'none';
            });
            
            const targetContent = moduleContent.querySelector('#' + targetId);
            if (targetContent) {
                targetContent.style.display = 'block';
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.subtabs .nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    const targetId = this.getAttribute('href').substring(1);
                    const subtabsContainer = this.closest('.subtabs');
                    const moduleSlug = subtabsContainer.id.replace('subtabs-', '');
                    
                    subtabsContainer.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                    
                    showSubTabContent(moduleSlug, targetId);
                });
            });
            
            <?php if (!empty($officialModules)): ?>
                showSubTabs('<?= $officialModules[0]['slug'] ?? '' ?>');
            <?php elseif (!empty($userModules)): ?>
                showSubTabs('<?= $userModules[0]['slug'] ?? '' ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>
