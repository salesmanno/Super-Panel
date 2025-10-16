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

// Hent company logo fra database
$companyLogo = null;
try {
    $db = getDBConnection();
    if ($db && isset($_SESSION['subdomain'])) {
        // Prøv først tenant_companies
        $stmt = $db->prepare("SELECT company_logo_url FROM tenant_companies WHERE subdomain = ?");
        $stmt->execute([$_SESSION['subdomain']]);
        $logoData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Hvis ikke funnet, prøv companies-tabellen
        if (!$logoData || empty($logoData['company_logo_url'])) {
            $stmt = $db->prepare("SELECT logo_index FROM companies WHERE subdomain = ?");
            $stmt->execute([$_SESSION['subdomain']]);
            $logoData = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($logoData && !empty($logoData['logo_index'])) {
                $companyLogo = $logoData['logo_index'];
            }
        } else {
            $companyLogo = $logoData['company_logo_url'];
        }
    }
} catch (Exception $e) {
    $companyLogo = null;
}

$allUserModules = getTenantAdminModules($_SESSION['tenant_admin_id']);
$officialModules = array_filter($allUserModules, function($module) {
    return in_array($module['module_key'], ['dashboard', 'ditt_nettsted']);
});
$userModules = array_filter($allUserModules, function($module) {
    return !in_array($module['module_key'], ['dashboard', 'ditt_nettsted']);
});
$allModules = getCompanyModules($_SESSION['company_id']);

// Sjekk om email er konfigurert (med SMTP data)
$webmailConfigured = false;
$smtpConfigured = false;
try {
    $db = getDBConnection();
    if ($db) {
        // Sjekk om SMTP er konfigurert i email_configs
        $stmt = $db->prepare("SELECT smtp_host, smtp_port, smtp_username FROM email_configs WHERE company_id = ?");
        $stmt->execute([$_SESSION['company_id']]);
        $smtpData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($smtpData && !empty($smtpData['smtp_host']) && !empty($smtpData['smtp_username'])) {
            $smtpConfigured = true;
        }
        
        // Sjekk om email_accounts har data
        $stmt = $db->prepare("SELECT COUNT(*) FROM email_accounts WHERE company_id = ?");
        $stmt->execute([$_SESSION['company_id']]);
        $hasAccount = ($stmt->fetchColumn() > 0);
        
        // Email er kun konfigurert hvis både SMTP og konto finnes
        $webmailConfigured = $smtpConfigured && $hasAccount;
    }
} catch (Exception $e) {
    // Tabellene eksisterer ikke ennå
    $webmailConfigured = false;
    $smtpConfigured = false;
}

// Sjekk om betalingsmetoder er konfigurert
$paymentConfigured = false;
try {
    $db = getDBConnection();
    if ($db) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM payment_integrations WHERE company_id = ? AND is_active = TRUE");
        $stmt->execute([$_SESSION['company_id']]);
        $paymentConfigured = ($stmt->fetchColumn() > 0);
    }
} catch (Exception $e) {
    // Tabellen eksisterer ikke ennå
    $paymentConfigured = false;
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
        
        .company-logo {
            max-height: 36px;
            max-width: 120px;
            width: auto;
            height: auto;
            margin-right: 10px;
            object-fit: contain;
        }
        
        .header-logo {
            max-height: 80px;
            max-width: 200px;
            width: auto;
            height: auto;
            margin-bottom: 16px;
            object-fit: contain;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-gradient navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand d-flex align-items-center" href="#">
                <?php if ($companyLogo): ?>
                    <img src="<?= htmlspecialchars($companyLogo) ?>" alt="<?= htmlspecialchars($companyName) ?>" class="company-logo">
                <?php else: ?>
                    <i class="bi bi-building me-2"></i>
                <?php endif; ?>
                <?= htmlspecialchars($companyName) ?>
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
            <?php if ($companyLogo): ?>
                <img src="<?= htmlspecialchars($companyLogo) ?>" alt="<?= htmlspecialchars($companyName) ?>" class="header-logo">
            <?php endif; ?>
            <h1 class="mb-2">Velkommen, <?= htmlspecialchars($adminName) ?>!</h1>
            <p class="text-muted mb-0">Du har tilgang til <?= count($allUserModules) ?> modul<?= count($allUserModules) != 1 ? 'er' : '' ?></p>
        </div>
    </div>
    
    <?php if (!empty($allUserModules)): ?>
    <!-- Module Navigation Bar -->
    <div class="bg-light border-bottom">
        <div class="container">
            <ul class="nav nav-tabs module-tabs border-0 mb-0" role="tablist">
                <?php 
                $allModulesForNav = array_merge($officialModules, $userModules);
                foreach ($allModulesForNav as $index => $module): 
                ?>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link <?= $index === 0 ? 'active' : '' ?>" 
                                id="tab-<?= $module['module_key'] ?>" 
                                data-bs-toggle="tab" 
                                data-bs-target="#content-<?= $module['module_key'] ?>" 
                                type="button" 
                                role="tab">
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
                    <a class="nav-link" href="#events-checkin">Innsjekking</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#events-expenses">Arrangement resultat</a>
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
                    <a class="nav-link" href="#membership-requests">Medlemsforespørsler</a>
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
            
            <!-- Email/Webmail subtabs -->
            <ul class="nav nav-pills py-2 subtabs" id="subtabs-email" style="display: none;">
                <li class="nav-item">
                    <a class="nav-link active" href="#webmail-client">E-post klient</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#webmail-history">E-post historikk</a>
                </li>
                <?php if ($webmailConfigured): ?>
                <li class="nav-item">
                    <a class="nav-link" href="#webmail-automailer">AutoMailer</a>
                </li>
                <?php endif; ?>
            </ul>
            
            <!-- Economy subtabs -->
            <ul class="nav nav-pills py-2 subtabs" id="subtabs-economy" style="display: none;">
                <li class="nav-item">
                    <a class="nav-link active" href="#economy-results">Deres resultat</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#economy-income">Se inntekt</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#economy-expense">Se utgift</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#economy-payment">Betalingsløsninger</a>
                </li>
            </ul>
            
            <!-- Sales subtabs -->
            <ul class="nav nav-pills py-2 subtabs" id="subtabs-sales" style="display: none;">
                <li class="nav-item">
                    <a class="nav-link active" href="#sales-items">Produkter</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#sales-orders">Ordrer</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#sales-statistics">Salgsstatistikk</a>
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
                         id="content-<?= $module['module_key'] ?>" 
                         role="tabpanel">
                        <?php 
                        switch ($module['module_key']) {
                            case 'dashboard':
                                ?>
                                <div class="row">
                                    <div class="col-12">
                                        <h3><i class="bi bi-speedometer2 me-2"></i>Dashboard</h3>
                                        <p class="text-muted">Oversikt og innstillinger for bedriften</p>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="card-body p-2">
                                                <?php
                                                $publicModules = [
                                                    ['id' => 7, 'name' => 'Arrangement', 'slug' => 'events', 'icon' => 'bi-calendar-event'],
                                                    ['id' => 5, 'name' => 'Medlemskap', 'slug' => 'membership', 'icon' => 'bi-people'],
                                                    ['id' => 3, 'name' => 'Reservasjoner', 'slug' => 'reservations', 'icon' => 'bi-calendar-check'],
                                                    ['id' => 12, 'name' => 'Salg', 'slug' => 'sales', 'icon' => 'bi-cart'],
                                                    ['id' => 6, 'name' => 'Utleie', 'slug' => 'rental', 'icon' => 'bi-key']
                                                ];
                                                
                                                try {
                                                    $stmt = $db->prepare("SELECT module_id, is_public FROM tenant_company_modules WHERE company_id = ?");
                                                    $stmt->execute([$_SESSION['company_id']]);
                                                    $publicSettings = [];
                                                    while ($row = $stmt->fetch()) {
                                                        $publicSettings[$row['module_id']] = $row['is_public'];
                                                    }
                                                } catch (Exception $e) {
                                                    $publicSettings = [];
                                                }
                                                ?>
                                                
                                                <div class="d-flex align-items-center gap-3 flex-wrap">
                                                    <small class="text-muted fw-bold"><i class="bi bi-globe me-1"></i>Offentlig:</small>
                                                    <?php foreach ($publicModules as $module): ?>
                                                    <div class="d-flex align-items-center gap-1">
                                                        <i class="bi <?= $module['icon'] ?> text-primary" style="font-size: 14px;"></i>
                                                        <small><?= $module['name'] ?></small>
                                                        <div class="form-check form-switch mb-0" style="padding-left: 2rem;">
                                                            <input class="form-check-input public-module-toggle" 
                                                                   type="checkbox" 
                                                                   id="public-<?= $module['module_key'] ?>"
                                                                   data-module-id="<?= $module['id'] ?>"
                                                                   style="margin-left: -1.5rem;"
                                                                   <?= isset($publicSettings[$module['id']]) && $publicSettings[$module['id']] ? 'checked' : '' ?>>
                                                        </div>
                                                    </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <script>
                                // Toggle public module visibility
                                document.querySelectorAll('.public-module-toggle').forEach(toggle => {
                                    toggle.addEventListener('change', function() {
                                        const moduleId = this.dataset.moduleId;
                                        const isPublic = this.checked;
                                        
                                        fetch('../api/toggle_public_module.php', {
                                            method: 'POST',
                                            headers: { 'Content-Type': 'application/json' },
                                            body: JSON.stringify({ module_id: moduleId, is_public: isPublic })
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                console.log('Module visibility updated');
                                            }
                                        });
                                    });
                                });
                                </script>
                                <?php
                                break;
                            case 'ditt_nettsted':
                                echo '<iframe src="ditt_nettsted.php" style="width: 100%; height: 80vh; border: none; border-radius: 8px;" frameborder="0"></iframe>';
                                break;
                            case 'email':
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
                                // Check if user has membership module access
                                $hasMembershipAccess = false;
                                foreach ($allUserModules as $userMod) {
                                    if ($userMod['module_key'] === 'membership') {
                                        $hasMembershipAccess = true;
                                        break;
                                    }
                                }
                                include __DIR__ . '/../modules/events_content.php';
                                break;
                            case 'marketplace':
                                include __DIR__ . '/../modules/marketplace_content.php';
                                break;
                            case 'sales':
                                include __DIR__ . '/../modules/sales_content.php';
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
        function refreshSession() {
            fetch('/api/refresh_session.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Session oppdatert! Navn: ' + data.admin_name);
                        location.reload();
                    } else {
                        alert('Feil: ' + (data.error || 'Kunne ikke oppdatere session'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('En feil oppstod ved refresh av session');
                });
        }
        
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
            // Håndtere subtabs - La Bootstrap håndtere tab-switching
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
            // Lytt til Bootstrap's tab shown event
            document.querySelectorAll('[data-bs-toggle="tab"]').forEach(function(tabButton) {
                tabButton.addEventListener('shown.bs.tab', function(event) {
                    const targetId = event.target.getAttribute('data-bs-target');
                    const moduleSlug = targetId.replace('#content-', '');
                    showSubTabs(moduleSlug);
                });
            });
            
            // Håndter subtab clicks
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
            
            // Initialiser første modul
            <?php if (!empty($officialModules)): ?>
                showSubTabs('<?= $officialModules[0]['module_key'] ?? '' ?>');
            <?php elseif (!empty($userModules)): ?>
                showSubTabs('<?= $userModules[0]['module_key'] ?? '' ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>
