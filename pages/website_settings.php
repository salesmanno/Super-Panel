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

$websiteConfig = null;
$websiteGeneral = null;
$successMessage = '';
$errorMessage = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db->beginTransaction();
        
        // Update tenant_website_general
        $stmt = $db->prepare("
            UPDATE tenant_website_general SET
                site_title = ?,
                site_description = ?,
                primary_color = ?,
                secondary_color = ?,
                hero_enabled = ?,
                hero_title = ?,
                hero_description = ?,
                hero_button_text = ?,
                hero_button_url = ?,
                contact_email = ?,
                contact_phone = ?,
                contact_address = ?,
                footer_text = ?,
                updated_at = NOW()
            WHERE company_id = ?
        ");
        $stmt->execute([
            $_POST['site_title'],
            $_POST['site_description'],
            $_POST['primary_color'],
            $_POST['secondary_color'],
            isset($_POST['hero_enabled']) ? 1 : 0,
            $_POST['hero_title'],
            $_POST['hero_description'],
            $_POST['hero_button_text'],
            $_POST['hero_button_url'],
            $_POST['contact_email'],
            $_POST['contact_phone'],
            $_POST['contact_address'] ?? '',
            $_POST['footer_text'],
            $companyId
        ]);
        
        $db->commit();
        $successMessage = 'Nettstedinnstillinger oppdatert!';
    } catch (Exception $e) {
        $db->rollBack();
        $errorMessage = 'Feil ved oppdatering: ' . $e->getMessage();
    }
}

// Load current settings
if ($db) {
    $stmt = $db->prepare("SELECT * FROM tenant_website_general WHERE company_id = ?");
    $stmt->execute([$companyId]);
    $websiteGeneral = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $db->prepare("SELECT * FROM tenant_website_config WHERE company_id = ?");
    $stmt->execute([$companyId]);
    $websiteConfig = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($companyName) ?> - Nettstedinnstillinger</title>
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
        
        .settings-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .settings-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }
        
        .settings-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #1f2937;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .color-preview {
            width: 50px;
            height: 38px;
            border-radius: 6px;
            border: 2px solid #e5e7eb;
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
    </style>
</head>
<body>
    <nav class="navbar navbar-gradient navbar-dark mb-4">
        <div class="container-fluid">
            <span class="navbar-brand">
                <i class="bi bi-globe me-2"></i><?= htmlspecialchars($companyName) ?> - Nettstedinnstillinger
            </span>
            <a href="/pages/company_dashboard.php" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left me-1"></i>Tilbake til Dashboard
            </a>
        </div>
    </nav>

    <div class="settings-container">
        <?php if ($successMessage): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($successMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($errorMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <!-- General Settings -->
            <div class="settings-card">
                <h3><i class="bi bi-gear-fill text-primary"></i>Generelle Innstillinger</h3>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="site_title" class="form-label">Nettstedstittel</label>
                        <input type="text" class="form-control" id="site_title" name="site_title" 
                               value="<?= htmlspecialchars($websiteGeneral['site_title'] ?? '') ?>" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="contact_email" class="form-label">Kontakt E-post</label>
                        <input type="email" class="form-control" id="contact_email" name="contact_email" 
                               value="<?= htmlspecialchars($websiteGeneral['contact_email'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="site_description" class="form-label">Nettstedbeskrivelse</label>
                    <textarea class="form-control" id="site_description" name="site_description" rows="3"><?= htmlspecialchars($websiteGeneral['site_description'] ?? '') ?></textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="contact_phone" class="form-label">Kontakttelefon</label>
                        <input type="tel" class="form-control" id="contact_phone" name="contact_phone" 
                               value="<?= htmlspecialchars($websiteGeneral['contact_phone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="contact_address" class="form-label">Kontaktadresse</label>
                        <input type="text" class="form-control" id="contact_address" name="contact_address" 
                               value="<?= htmlspecialchars($websiteGeneral['contact_address'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Color Scheme -->
            <div class="settings-card">
                <h3><i class="bi bi-palette-fill text-primary"></i>Fargevalg</h3>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="primary_color" class="form-label">Primærfarge</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" 
                                   value="<?= htmlspecialchars($websiteGeneral['primary_color'] ?? '#667eea') ?>">
                            <input type="text" class="form-control" value="<?= htmlspecialchars($websiteGeneral['primary_color'] ?? '#667eea') ?>" readonly>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="secondary_color" class="form-label">Sekundærfarge</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="secondary_color" name="secondary_color" 
                                   value="<?= htmlspecialchars($websiteGeneral['secondary_color'] ?? '#764ba2') ?>">
                            <input type="text" class="form-control" value="<?= htmlspecialchars($websiteGeneral['secondary_color'] ?? '#764ba2') ?>" readonly>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hero Section -->
            <div class="settings-card">
                <h3><i class="bi bi-image-fill text-primary"></i>Hero-seksjon</h3>
                
                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="hero_enabled" name="hero_enabled" 
                           <?= ($websiteGeneral['hero_enabled'] ?? true) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="hero_enabled">Aktiver Hero-seksjon</label>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="hero_title" class="form-label">Hero-tittel</label>
                        <input type="text" class="form-control" id="hero_title" name="hero_title" 
                               value="<?= htmlspecialchars($websiteGeneral['hero_title'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="hero_button_text" class="form-label">Knappetekst</label>
                        <input type="text" class="form-control" id="hero_button_text" name="hero_button_text" 
                               value="<?= htmlspecialchars($websiteGeneral['hero_button_text'] ?? '') ?>">
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="hero_description" class="form-label">Hero-beskrivelse</label>
                        <textarea class="form-control" id="hero_description" name="hero_description" rows="2"><?= htmlspecialchars($websiteGeneral['hero_description'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="hero_button_url" class="form-label">Knappelenke</label>
                        <input type="text" class="form-control" id="hero_button_url" name="hero_button_url" 
                               value="<?= htmlspecialchars($websiteGeneral['hero_button_url'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="settings-card">
                <h3><i class="bi bi-card-text text-primary"></i>Footer</h3>
                
                <div class="mb-3">
                    <label for="footer_text" class="form-label">Footer-tekst</label>
                    <textarea class="form-control" id="footer_text" name="footer_text" rows="2"><?= htmlspecialchars($websiteGeneral['footer_text'] ?? '') ?></textarea>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary-gradient btn-lg">
                    <i class="bi bi-save me-2"></i>Lagre Endringer
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Update color input text fields
        document.getElementById('primary_color').addEventListener('change', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });
        
        document.getElementById('secondary_color').addEventListener('change', function(e) {
            e.target.nextElementSibling.value = e.target.value;
        });
    </script>
</body>
</html>
