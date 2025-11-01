<div class="tab-pane fade show active" id="firma-firmadata" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-building me-2"></i>Firmadata</h5>
                <p class="text-muted">Administrer bedriftens grunnleggende informasjon</p>
                
                <?php
                // Hent firmadata fra tenant_companies
                $companyData = [];
                try {
                    $db = getDBConnection();
                    if ($db) {
                        $stmt = $db->prepare("SELECT * FROM tenant_companies WHERE id = ?");
                        $stmt->execute([$_SESSION['company_id']]);
                        $companyData = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">Kunne ikke laste firmadata: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
                
                <?php if ($companyData): ?>
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>Subdomain:</strong> <?= htmlspecialchars($companyData['subdomain'] ?? 'N/A') ?>.salesman.no
                </div>
                
                <form id="companyDataForm" onsubmit="return handleSaveCompanyData(event);">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Firmanavn</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($companyData['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Juridisk navn</label>
                            <input type="text" name="legal_name" class="form-control" value="<?= htmlspecialchars($companyData['legal_name'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Organisasjonsnummer</label>
                            <input type="text" name="org_number" class="form-control" value="<?= htmlspecialchars($companyData['org_number'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">MVA-nummer</label>
                            <input type="text" name="tax_id" class="form-control" value="<?= htmlspecialchars($companyData['tax_id'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Selskapsform</label>
                            <select name="entity_type" class="form-select">
                                <option value="">Velg...</option>
                                <option value="AS" <?= ($companyData['entity_type'] ?? '') === 'AS' ? 'selected' : '' ?>>AS</option>
                                <option value="ASA" <?= ($companyData['entity_type'] ?? '') === 'ASA' ? 'selected' : '' ?>>ASA</option>
                                <option value="ENK" <?= ($companyData['entity_type'] ?? '') === 'ENK' ? 'selected' : '' ?>>ENK</option>
                                <option value="NUF" <?= ($companyData['entity_type'] ?? '') === 'NUF' ? 'selected' : '' ?>>NUF</option>
                                <option value="DA" <?= ($companyData['entity_type'] ?? '') === 'DA' ? 'selected' : '' ?>>DA</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="contact_phone" class="form-control" value="<?= htmlspecialchars($companyData['contact_phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-post</label>
                            <input type="email" name="contact_email" class="form-control" value="<?= htmlspecialchars($companyData['contact_email'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Domene</label>
                            <input type="text" name="domain" class="form-control" value="<?= htmlspecialchars($companyData['domain'] ?? '') ?>" placeholder="dinbedrift.no">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Bransje</label>
                            <input type="text" name="industry" class="form-control" value="<?= htmlspecialchars($companyData['industry'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Logo URL</label>
                            <input type="url" name="logo_url" class="form-control" value="<?= htmlspecialchars($companyData['logo_url'] ?? '') ?>" placeholder="https://...">
                        </div>
                    </div>
                    
                    <h6 class="mt-4 mb-3">Adresse</h6>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Gate/vei</label>
                            <input type="text" name="address_street" class="form-control" value="<?= htmlspecialchars($companyData['address_street'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Postnummer</label>
                            <input type="text" name="address_zip" class="form-control" value="<?= htmlspecialchars($companyData['address_zip'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Poststed</label>
                            <input type="text" name="address_city" class="form-control" value="<?= htmlspecialchars($companyData['address_city'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Land</label>
                            <input type="text" name="address_country" class="form-control" value="<?= htmlspecialchars($companyData['address_country'] ?? 'Norge') ?>">
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Lagre Endringer
                    </button>
                </form>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>Ingen firmadata funnet
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<div class="tab-pane fade" id="firma-about" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-info-circle me-2"></i>Om oss</h5>
                <p class="text-muted">Bedriftens historie og beskrivelse</p>
                
                <form id="about-us-form" onsubmit="return handleAboutUsSubmit(event);">
                    <div class="mb-3">
                        <label class="form-label">Om bedriften</label>
                        <textarea class="form-control" id="about-us-text" name="about_us" rows="10" style="line-height: 1.8;"><?= htmlspecialchars($companyData['about_us'] ?? '') ?></textarea>
                        <small class="form-text text-muted">Skriv en beskrivelse av bedriften, dens historie og verdier</small>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Lagre endringer
                    </button>
                </form>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0">Bedriftsinformasjon</h6>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Opprettet:</strong> 
                                <?= isset($companyData['created_at']) ? date('d.m.Y', strtotime($companyData['created_at'])) : 'N/A' ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Status:</strong> 
                                <?php if ($companyData['is_active'] ?? false): ?>
                                    <span class="badge bg-success">Aktiv</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inaktiv</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <strong>Subdomain:</strong> <?= htmlspecialchars($companyData['subdomain'] ?? '') ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<div class="tab-pane fade" id="firma-staff" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-people me-2"></i>Stillinger og ansatte</h5>
                <p class="text-muted">Oversikt over alle ansatte i bedriften</p>
                
                <?php
                // Hent ansatte fra tenant_staff
                $staff = [];
                $staffModules = [];
                $totalModules = 0;
                
                try {
                    $db = getDBConnection();
                    if ($db) {
                        // Hent alle ansatte
                        $stmt = $db->prepare("SELECT * FROM tenant_staff WHERE company_id = ? ORDER BY last_name, first_name");
                        $stmt->execute([$_SESSION['company_id']]);
                        $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Hent totalt antall aktive moduler
                        $activeValue = dbBool(true);
                        $stmt = $db->prepare("SELECT COUNT(*) FROM system_modules WHERE is_active = $activeValue");
                        $stmt->execute();
                        $totalModules = $stmt->fetchColumn();
                        
                        // Hent modultilgang for hver ansatt
                        foreach ($staff as $employee) {
                            // Finn tenant_admin basert på staff_id
                            $stmt = $db->prepare("SELECT id FROM tenant_admins WHERE tenant_staff_id = ?");
                            $stmt->execute([$employee['id']]);
                            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($admin) {
                                // Hent moduler for denne adminen
                                $stmt = $db->prepare("
                                    SELECT sm.name 
                                    FROM system_modules sm
                                    INNER JOIN tenant_admin_modules tam ON sm.id = tam.module_id
                                    WHERE tam.tenant_admin_id = ? AND sm.is_active = $activeValue
                                    ORDER BY sm.name
                                ");
                                $stmt->execute([$admin['id']]);
                                $modules = $stmt->fetchAll(PDO::FETCH_COLUMN);
                                
                                if (count($modules) >= $totalModules) {
                                    $staffModules[$employee['id']] = 'ALLE';
                                } else {
                                    $staffModules[$employee['id']] = implode(', ', $modules);
                                }
                            } else {
                                $staffModules[$employee['id']] = 'Ingen tilgang';
                            }
                        }
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">Kunne ikke laste ansatte: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
                
                <?php if (!empty($staff)): ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Navn</th>
                                <th>Stilling</th>
                                <th>E-post</th>
                                <th>Telefon</th>
                                <th>Avdeling</th>
                                <th>Modultilgang</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff as $employee): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($employee['job_title'] ?? 'N/A') ?></td>
                                <td>
                                    <?php if (!empty($employee['email'])): ?>
                                        <a href="mailto:<?= htmlspecialchars($employee['email']) ?>">
                                            <?= htmlspecialchars($employee['email']) ?>
                                        </a>
                                    <?php else: ?>
                                        N/A
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($employee['phone'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($employee['department'] ?? 'N/A') ?></td>
                                <td>
                                    <?php 
                                    $modules = $staffModules[$employee['id']] ?? 'Ingen tilgang';
                                    if ($modules === 'ALLE'): ?>
                                        <span class="badge bg-primary">ALLE</span>
                                    <?php elseif ($modules === 'Ingen tilgang'): ?>
                                        <span class="badge bg-secondary">Ingen tilgang</span>
                                    <?php else: ?>
                                        <small><?= htmlspecialchars($modules) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($employee['is_active'] ?? false): ?>
                                        <span class="badge bg-success">Aktiv</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Inaktiv</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>Ingen ansatte er registrert ennå
                </div>
                <?php endif; ?>
                
                <div class="mt-3">
                    <p class="text-muted">
                        <i class="bi bi-info-circle me-1"></i>
                        Totalt <?= count($staff) ?> ansatte registrert
                    </p>
                </div>
            </div>
        </div>
    </div>

<div class="tab-pane fade" id="firma-modules" role="tabpanel">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title"><i class="bi bi-grid-3x3-gap me-2"></i>Moduladministrasjon</h5>
            <p class="text-muted">Styr hvilke moduler ansatte har tilgang til</p>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-person-check me-2"></i>Velg ansatt</h6>
                        </div>
                        <div class="card-body">
                            <div id="admin-list" class="list-group">
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-hourglass-split me-2"></i>Laster...
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="bi bi-toggles me-2"></i>Modultilgang</h6>
                        </div>
                        <div class="card-body" id="module-access-panel">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                Velg en ansatt fra listen til venstre for å administrere modulene deres
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="tab-pane fade" id="firma-webside" role="tabpanel">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-palette me-2"></i>Webside</h5>
                <p class="text-muted">Administrer websideinnstillinger og design</p>
                
                <?php
                // Hent webside-data fra tenant_website_general og tenant_website_config
                $websiteGeneral = null;
                $websiteConfig = null;
                try {
                    $db = getDBConnection();
                    if ($db) {
                        // Fetch website general settings
                        $stmt = $db->prepare("SELECT * FROM tenant_website_general WHERE company_id = ?");
                        $stmt->execute([$_SESSION['company_id']]);
                        $websiteGeneral = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        // Fetch website config
                        $stmt = $db->prepare("SELECT * FROM tenant_website_config WHERE company_id = ?");
                        $stmt->execute([$_SESSION['company_id']]);
                        $websiteConfig = $stmt->fetch(PDO::FETCH_ASSOC);
                    }
                } catch (Exception $e) {
                    echo '<div class="alert alert-danger">Kunne ikke laste websidedata: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                ?>
                
                <form id="websideForm" onsubmit="return handleSaveWebsideData(event);">
                <?php if ($websiteGeneral || $websiteConfig): ?>
                    
                    <!-- Nettsted Config -->
                    <?php if ($websiteConfig): ?>
                    <h6 class="mb-3 mt-3"><i class="bi bi-gear me-2"></i>Nettsted Konfigurering</h6>
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Publisert</label>
                            <select name="config_is_published" class="form-select">
                                <option value="0" <?= !($websiteConfig['is_published'] ?? 0) ? 'selected' : '' ?>>Utkast</option>
                                <option value="1" <?= ($websiteConfig['is_published'] ?? 0) ? 'selected' : '' ?>>Publisert</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Aktivert</label>
                            <select name="config_is_enabled" class="form-select">
                                <option value="0" <?= !($websiteConfig['is_enabled'] ?? 0) ? 'selected' : '' ?>>Deaktivert</option>
                                <option value="1" <?= ($websiteConfig['is_enabled'] ?? 0) ? 'selected' : '' ?>>Aktivert</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sist oppdatert</label>
                            <input type="text" class="form-control-plaintext" readonly value="<?= $websiteConfig['updated_at'] ? date('d.m.Y H:i', strtotime($websiteConfig['updated_at'])) : '-' ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Visningsnavn</label>
                            <input type="text" name="config_display_name" class="form-control" value="<?= htmlspecialchars($websiteConfig['display_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SEO Tittel</label>
                            <input type="text" name="config_seo_title" class="form-control" value="<?= htmlspecialchars($websiteConfig['seo_title'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">SEO Beskrivelse</label>
                        <textarea name="config_seo_description" class="form-control" rows="2"><?= htmlspecialchars($websiteConfig['seo_description'] ?? '') ?></textarea>
                    </div>
                    
                    <hr class="my-4">
                    <?php endif; ?>
                    
                    <!-- Website General Settings -->
                    <?php if ($websiteGeneral): ?>
                    <h6 class="mb-3"><i class="bi bi-info-circle me-2"></i>Generelle Innstillinger</h6>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nettstedstittel</label>
                            <input type="text" name="general_site_title" class="form-control" value="<?= htmlspecialchars($websiteGeneral['site_title'] ?? '') ?>">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Aktiv</label>
                            <select name="general_is_active" class="form-select">
                                <option value="0" <?= !($websiteGeneral['is_active'] ?? 1) ? 'selected' : '' ?>>Inaktiv</option>
                                <option value="1" <?= ($websiteGeneral['is_active'] ?? 1) ? 'selected' : '' ?>>Aktiv</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Vedlikeholdsmodus</label>
                            <select name="general_maintenance_mode" class="form-select">
                                <option value="0" <?= !($websiteGeneral['maintenance_mode'] ?? 0) ? 'selected' : '' ?>>Av</option>
                                <option value="1" <?= ($websiteGeneral['maintenance_mode'] ?? 0) ? 'selected' : '' ?>>På</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="form-label">Beskrivelse</label>
                        <textarea name="general_site_description" class="form-control" rows="3"><?= htmlspecialchars($websiteGeneral['site_description'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- Design Settings -->
                    <h6 class="mb-3 mt-4"><i class="bi bi-palette-fill me-2"></i>Design</h6>
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Primærfarge</label>
                            <input type="color" name="general_primary_color" class="form-control form-control-color" value="<?= htmlspecialchars($websiteGeneral['primary_color'] ?? '#3366CC') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Sekundærfarge</label>
                            <input type="color" name="general_secondary_color" class="form-control form-control-color" value="<?= htmlspecialchars($websiteGeneral['secondary_color'] ?? '#666666') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Navigasjonsstil</label>
                            <select name="general_navigation_style" class="form-select">
                                <option value="top" <?= ($websiteGeneral['navigation_style'] ?? 'top') === 'top' ? 'selected' : '' ?>>Top</option>
                                <option value="side" <?= ($websiteGeneral['navigation_style'] ?? 'top') === 'side' ? 'selected' : '' ?>>Side</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Header layout</label>
                            <select name="general_header_layout" class="form-select">
                                <option value="standard" <?= ($websiteGeneral['header_layout'] ?? 'standard') === 'standard' ? 'selected' : '' ?>>Standard</option>
                                <option value="centered" <?= ($websiteGeneral['header_layout'] ?? 'standard') === 'centered' ? 'selected' : '' ?>>Sentrert</option>
                                <option value="minimal" <?= ($websiteGeneral['header_layout'] ?? 'standard') === 'minimal' ? 'selected' : '' ?>>Minimal</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Footer layout</label>
                            <select name="general_footer_layout" class="form-select">
                                <option value="standard" <?= ($websiteGeneral['footer_layout'] ?? 'standard') === 'standard' ? 'selected' : '' ?>>Standard</option>
                                <option value="minimal" <?= ($websiteGeneral['footer_layout'] ?? 'standard') === 'minimal' ? 'selected' : '' ?>>Minimal</option>
                                <option value="extended" <?= ($websiteGeneral['footer_layout'] ?? 'standard') === 'extended' ? 'selected' : '' ?>>Utvidet</option>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Hero Section -->
                    <h6 class="mb-3 mt-4"><i class="bi bi-image me-2"></i>Hero-seksjon</h6>
                    <div class="mb-3">
                        <label class="form-label">Hero aktivert</label>
                        <select name="general_hero_enabled" class="form-select">
                            <option value="0" <?= !($websiteGeneral['hero_enabled'] ?? 1) ? 'selected' : '' ?>>Av</option>
                            <option value="1" <?= ($websiteGeneral['hero_enabled'] ?? 1) ? 'selected' : '' ?>>På</option>
                        </select>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Hero-tittel</label>
                            <input type="text" name="general_hero_title" class="form-control" value="<?= htmlspecialchars($websiteGeneral['hero_title'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Knappetekst</label>
                            <input type="text" name="general_hero_button_text" class="form-control" value="<?= htmlspecialchars($websiteGeneral['hero_button_text'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Hero-beskrivelse</label>
                        <textarea name="general_hero_description" class="form-control" rows="2"><?= htmlspecialchars($websiteGeneral['hero_description'] ?? '') ?></textarea>
                    </div>
                    
                    <!-- Contact Information -->
                    <h6 class="mb-3 mt-4"><i class="bi bi-envelope me-2"></i>Kontaktinformasjon</h6>
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">E-post</label>
                            <input type="email" name="general_contact_email" class="form-control" value="<?= htmlspecialchars($websiteGeneral['contact_email'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="general_contact_phone" class="form-control" value="<?= htmlspecialchars($websiteGeneral['contact_phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="general_contact_address" class="form-control" value="<?= htmlspecialchars($websiteGeneral['contact_address'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <!-- Advanced Settings -->
                    <h6 class="mb-3 mt-4"><i class="bi bi-code-square me-2"></i>Avansert</h6>
                    <div class="row mb-4">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Google Analytics ID</label>
                            <input type="text" name="general_google_analytics_id" class="form-control" value="<?= htmlspecialchars($websiteGeneral['google_analytics_id'] ?? '') ?>" placeholder="G-XXXXXXXXXX">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Vedlikeholdsmelding</label>
                            <textarea name="general_maintenance_message" class="form-control" rows="2" placeholder="Vises når vedlikeholdsmodus er på"><?= htmlspecialchars($websiteGeneral['maintenance_message'] ?? '') ?></textarea>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Lagre Innstillinger
                    </button>
                    
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-info-circle me-2"></i>
                        <strong>Ingen websideinnstillinger funnet.</strong> Kontakt systemadministrator for å sette opp webside for din bedrift.
                    </div>
                <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

<!-- JavaScript functions are now in company_dashboard.php -->
