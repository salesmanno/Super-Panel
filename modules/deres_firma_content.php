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
                    <div class="mb-3">
                        <label class="form-label">Firmanavn</label>
                        <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($companyData['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nettside URL</label>
                        <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($companyData['website'] ?? '') ?>" placeholder="https://example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Logo URL</label>
                        <input type="url" name="logo_url" class="form-control" value="<?= htmlspecialchars($companyData['logo_url'] ?? '') ?>" placeholder="https://example.com/logo.png">
                        <small class="text-muted">Direkte lenke til logo-bilde</small>
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

<!-- JavaScript functions are now in company_dashboard.php -->
