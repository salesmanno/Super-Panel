<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['tenant_admin_id'])) {
    header('Location: ../index.php');
    exit;
}

require_once __DIR__ . '/../config/database.php';

$companyId = $_SESSION['company_id'];
$companyName = $_SESSION['company_name'] ?? 'Firma';

$db = getDBConnection();

$templates = [];
$currentSettings = null;
$isLocked = false;
$privilegeGrantedAt = null;

// Check if unlocked via admin password (expires after 30 minutes)
$isUnlockedByPassword = false;
if (isset($_SESSION['website_unlocked']) && isset($_SESSION['website_unlock_time'])) {
    $timeSinceUnlock = time() - $_SESSION['website_unlock_time'];
    if ($timeSinceUnlock < 1800) { // 30 minutes
        $isUnlockedByPassword = true;
    } else {
        unset($_SESSION['website_unlocked']);
        unset($_SESSION['website_unlock_time']);
    }
}

if ($db) {
    $stmt = $db->prepare("SELECT * FROM website_templates WHERE is_active = TRUE ORDER BY template_id");
    $stmt->execute();
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT * FROM webconfiguration WHERE company_id = ?");
    $stmt->execute([$companyId]);
    $currentSettings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("
        SELECT tam.granted_at 
        FROM tenant_admin_modules tam 
        JOIN system_modules sm ON tam.module_id = sm.id 
        WHERE tam.tenant_admin_id = ? AND sm.slug = 'ditt_nettsted'
    ");
    $stmt->execute([$_SESSION['tenant_admin_id']]);
    $privilege = $stmt->fetch(PDO::FETCH_ASSOC);
    $privilegeGrantedAt = $privilege ? $privilege['granted_at'] : null;
    
    if ($currentSettings && $privilegeGrantedAt) {
        $configUpdatedAt = strtotime($currentSettings['updated_at']);
        $grantedAt = strtotime($privilegeGrantedAt);
        
        $isLocked = ($grantedAt <= $configUpdatedAt) && !$isUnlockedByPassword;
    }
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($companyName) ?> - Ditt Nettsted</title>
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
        
        .template-card {
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s ease;
            cursor: pointer;
            background: white;
        }
        
        .template-card:hover {
            border-color: var(--primary-start);
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.15);
            transform: translateY(-4px);
        }
        
        .template-card.selected {
            border-color: var(--primary-start);
            background: linear-gradient(to bottom, rgba(102, 126, 234, 0.05), white);
        }
        
        .template-preview {
            height: 200px;
            background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: #9ca3af;
        }
        
        .template-info {
            padding: 1.5rem;
        }
        
        .template-name {
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }
        
        .template-description {
            color: #6b7280;
            font-size: 0.875rem;
        }
        
        .selected-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: var(--primary-start);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .btn-primary-gradient {
            background: linear-gradient(135deg, var(--primary-start), var(--primary-end));
            border: none;
            color: white;
            font-weight: 500;
            padding: 0.75rem 2rem;
            transition: all 0.2s;
        }
        
        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(102, 126, 234, 0.3);
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.875rem;
        }
        
        .status-published {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-draft {
            background: #fef3c7;
            color: #92400e;
        }
        
        .unlock-form {
            max-width: 400px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .unlock-form .form-control {
            border-radius: 8px;
        }
        
        .unlock-form .btn {
            width: 100%;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-gradient navbar-dark mb-4">
        <div class="container-fluid">
            <span class="navbar-brand">
                <i class="bi bi-globe me-2"></i>
                Ditt Nettsted
            </span>
            <a href="company_dashboard.php" class="btn btn-sm btn-light">
                <i class="bi bi-arrow-left me-1"></i>Tilbake til dashboard
            </a>
        </div>
    </nav>

    <div class="container py-4">
        <?php if ($isLocked): ?>
            <div class="alert alert-warning text-center py-5">
                <i class="bi bi-lock-fill" style="font-size: 3rem;"></i>
                <h3 class="mt-3">Nettside Konfigurert</h3>
                <p class="mb-3">Nettsiden er allerede konfigurert. Oppgi admin-passord for å låse opp redigering.</p>
                <?php if ($currentSettings): ?>
                    <div class="mt-3 mb-4">
                        <strong>Valgt mal:</strong> 
                        <?php 
                        $selectedTemplate = array_filter($templates, function($t) use ($currentSettings) {
                            return $t['template_id'] == $currentSettings['template_id'];
                        });
                        $selectedTemplate = reset($selectedTemplate);
                        echo htmlspecialchars($selectedTemplate['name'] ?? 'Ukjent');
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="unlock-form">
                <h5 class="text-center mb-4"><i class="bi bi-key me-2"></i>Lås Opp Nettside</h5>
                <div id="unlockError" class="alert alert-danger d-none"></div>
                <form id="unlockForm">
                    <div class="mb-3">
                        <label for="adminPassword" class="form-label">Admin-passord</label>
                        <input type="password" class="form-control" id="adminPassword" placeholder="Skriv inn admin-passord" required>
                    </div>
                    <button type="submit" class="btn btn-primary-gradient">
                        <i class="bi bi-unlock me-2"></i>Lås Opp
                    </button>
                </form>
            </div>
        <?php else: ?>
            <?php if ($isUnlockedByPassword): ?>
                <div class="alert alert-success mb-4">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>Låst opp!</strong> Du kan nå redigere nettside-mal. Opplåsingen utløper om 30 minutter.
                </div>
            <?php endif; ?>
            <div class="row mb-4">
                <div class="col-md-8">
                    <h2>Velg Nettside Mal</h2>
                    <p class="text-muted">Velg en mal for kundens nettsted. Denne vil bli synlig på deres offentlige domene.</p>
                    <?php if ($currentSettings): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Merk:</strong> Du kan endre mal siden du nettopp fikk tilbake dette privilegiet.
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <?php if ($currentSettings && $currentSettings['is_published']): ?>
                        <span class="status-badge status-published">
                            <i class="bi bi-check-circle me-1"></i>Publisert
                        </span>
                    <?php else: ?>
                        <span class="status-badge status-draft">
                            <i class="bi bi-pencil me-1"></i>Utkast
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row g-4" id="templateGallery">
            <?php foreach ($templates as $template): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="template-card position-relative" 
                         data-template-id="<?= $template['template_id'] ?>"
                         <?= ($currentSettings && $currentSettings['template_id'] == $template['template_id']) ? 'data-selected="true"' : '' ?>>
                        
                        <?php if ($currentSettings && $currentSettings['template_id'] == $template['template_id']): ?>
                            <span class="selected-badge">
                                <i class="bi bi-check-circle-fill me-1"></i>Valgt
                            </span>
                        <?php endif; ?>
                        
                        <div class="template-preview">
                            <i class="bi bi-layout-text-window-reverse"></i>
                        </div>
                        <div class="template-info">
                            <div class="template-name"><?= htmlspecialchars($template['name']) ?></div>
                            <div class="template-description"><?= htmlspecialchars($template['description']) ?></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($templates)): ?>
            <div class="alert alert-info text-center">
                <i class="bi bi-info-circle me-2"></i>
                Ingen maler tilgjengelig for øyeblikket.
            </div>
        <?php endif; ?>

            <div class="mt-5 text-center">
                <button id="saveTemplate" class="btn btn-primary-gradient btn-lg" disabled>
                    <i class="bi bi-save me-2"></i>Lagre Valg
                </button>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const isLocked = <?= $isLocked ? 'true' : 'false' ?>;
        let selectedTemplateId = <?= $currentSettings ? $currentSettings['template_id'] : 'null' ?>;
        
        const templateCards = document.querySelectorAll('.template-card');
        const saveBtn = document.getElementById('saveTemplate');
        
        if (isLocked) {
            // Handle unlock form
            const unlockForm = document.getElementById('unlockForm');
            const unlockError = document.getElementById('unlockError');
            
            if (unlockForm) {
                unlockForm.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    
                    const password = document.getElementById('adminPassword').value;
                    const submitBtn = this.querySelector('button[type="submit"]');
                    
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifiserer...';
                    unlockError.classList.add('d-none');
                    
                    try {
                        const response = await fetch('../api/verify_admin_password.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({ password: password })
                        });
                        
                        const result = await response.json();
                        
                        if (result.success) {
                            window.location.reload();
                        } else {
                            unlockError.textContent = result.error || 'Feil passord';
                            unlockError.classList.remove('d-none');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="bi bi-unlock me-2"></i>Lås Opp';
                        }
                    } catch (error) {
                        unlockError.textContent = 'Nettverksfeil. Prøv igjen.';
                        unlockError.classList.remove('d-none');
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = '<i class="bi bi-unlock me-2"></i>Lås Opp';
                    }
                });
            }
        } else {
        
        templateCards.forEach(card => {
            if (card.dataset.selected === 'true') {
                card.classList.add('selected');
            }
            
            card.addEventListener('click', function() {
                templateCards.forEach(c => {
                    c.classList.remove('selected');
                    const badge = c.querySelector('.selected-badge');
                    if (badge) badge.remove();
                });
                
                this.classList.add('selected');
                selectedTemplateId = parseInt(this.dataset.templateId);
                
                const badge = document.createElement('span');
                badge.className = 'selected-badge';
                badge.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i>Valgt';
                this.appendChild(badge);
                
                saveBtn.disabled = false;
            });
        });
        
        saveBtn.addEventListener('click', async function() {
            if (!selectedTemplateId) return;
            
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Lagrer...';
            
            try {
                const response = await fetch('../api/save_website_template.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        template_id: selectedTemplateId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.innerHTML = '<i class="bi bi-check-circle me-2"></i>Lagret!';
                    this.classList.remove('btn-primary-gradient');
                    this.classList.add('btn-success');
                    
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    throw new Error(result.error || 'Ukjent feil');
                }
            } catch (error) {
                this.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Feil!';
                this.classList.remove('btn-primary-gradient');
                this.classList.add('btn-danger');
                alert('Kunne ikke lagre mal: ' + error.message);
                
                setTimeout(() => {
                    this.innerHTML = '<i class="bi bi-save me-2"></i>Lagre Valg';
                    this.classList.remove('btn-danger');
                    this.classList.add('btn-primary-gradient');
                    this.disabled = false;
                }, 2000);
            }
        });
        
        }  // End of else (not locked)
    </script>
</body>
</html>
