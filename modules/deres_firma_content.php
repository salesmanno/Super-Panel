<div class="module-content">
    <div id="firma-firmadata" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-building me-2"></i>Firmadata</h5>
                <p class="text-muted">Administrer bedriftens grunnleggende informasjon</p>
                
                <?php
                // Check if unlocked via admin password (expires after 30 minutes)
                $isFirmadataUnlocked = false;
                if (isset($_SESSION['website_unlocked']) && isset($_SESSION['website_unlock_time'])) {
                    $timeSinceUnlock = time() - $_SESSION['website_unlock_time'];
                    if ($timeSinceUnlock < 1800) { // 30 minutes
                        $isFirmadataUnlocked = true;
                    } else {
                        unset($_SESSION['website_unlocked']);
                        unset($_SESSION['website_unlock_time']);
                    }
                }
                
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
                
                <?php if (!$isFirmadataUnlocked): ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-lock me-2"></i>
                        <strong>Låst:</strong> Oppgi admin-passord for å redigere firmadata.
                    </div>
                    
                    <div id="unlockFirmadataError" class="alert alert-danger d-none"></div>
                    
                    <form id="unlockFirmadataForm" class="mb-4">
                        <div class="row">
                            <div class="col-md-6">
                                <label for="firmadataPassword" class="form-label">Admin-passord</label>
                                <input type="password" class="form-control" id="firmadataPassword" placeholder="Skriv inn admin-passord" required>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-unlock me-2"></i>Lås Opp
                                </button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>
                
                <?php if ($companyData): ?>
                <form id="companyDataForm" onsubmit="return handleSaveCompanyData(event);">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Firmanavn</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($companyData['name'] ?? '') ?>" <?= !$isFirmadataUnlocked ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Organisasjonsnummer</label>
                            <input type="text" name="org_number" class="form-control" value="<?= htmlspecialchars($companyData['org_number'] ?? '') ?>" <?= !$isFirmadataUnlocked ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" name="address" class="form-control" value="<?= htmlspecialchars($companyData['address'] ?? '') ?>" <?= !$isFirmadataUnlocked ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Postnummer</label>
                            <input type="text" name="postal_code" class="form-control" value="<?= htmlspecialchars($companyData['postal_code'] ?? '') ?>" <?= !$isFirmadataUnlocked ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Sted</label>
                            <input type="text" name="city" class="form-control" value="<?= htmlspecialchars($companyData['city'] ?? '') ?>" <?= !$isFirmadataUnlocked ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Telefon</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($companyData['phone'] ?? '') ?>" <?= !$isFirmadataUnlocked ? 'readonly' : '' ?>>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">E-post</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($companyData['email'] ?? '') ?>" <?= !$isFirmadataUnlocked ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Nettside</label>
                            <input type="url" name="website" class="form-control" value="<?= htmlspecialchars($companyData['website'] ?? '') ?>" <?= !$isFirmadataUnlocked ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Logo URL</label>
                            <input type="url" name="logo_url" class="form-control" value="<?= htmlspecialchars($companyData['logo_url'] ?? '') ?>" <?= !$isFirmadataUnlocked ? 'readonly' : '' ?>>
                        </div>
                    </div>
                    
                    <?php if ($isFirmadataUnlocked): ?>
                    <div class="alert alert-success mb-3">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Låst opp!</strong> Du kan nå redigere firmadata. Opplåsingen utløper om 30 minutter.
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-save me-2"></i>Lagre Endringer
                    </button>
                    <?php endif; ?>
                </form>
                <?php else: ?>
                <div class="alert alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>Ingen firmadata funnet
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div id="firma-about" class="subtab-content" style="display: none;">
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

    <div id="firma-staff" class="subtab-content" style="display: none;">
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
</div>

<script>
// Unlock firmadata with admin password
document.addEventListener('DOMContentLoaded', function() {
    const unlockForm = document.getElementById('unlockFirmadataForm');
    if (unlockForm) {
        unlockForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const password = document.getElementById('firmadataPassword').value;
            const submitBtn = this.querySelector('button[type="submit"]');
            const errorDiv = document.getElementById('unlockFirmadataError');
            
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifiserer...';
            errorDiv.classList.add('d-none');
            
            try {
                const response = await fetch('/api/verify_admin_password.php', {
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
                    errorDiv.textContent = result.error || 'Feil passord';
                    errorDiv.classList.remove('d-none');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-unlock me-2"></i>Lås Opp';
                }
            } catch (error) {
                errorDiv.textContent = 'Nettverksfeil. Prøv igjen.';
                errorDiv.classList.remove('d-none');
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-unlock me-2"></i>Lås Opp';
            }
        });
    }
});

// Save company data
async function handleSaveCompanyData(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    const data = {};
    formData.forEach((value, key) => {
        data[key] = value;
    });
    
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Lagrer...';
    
    try {
        const response = await fetch('/api/update_company_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Firmadata lagret!');
        } else {
            alert('❌ Feil: ' + (result.error || 'Kunne ikke lagre firmadata'));
        }
    } catch (error) {
        console.error('Error saving company data:', error);
        alert('❌ Feil ved lagring av firmadata');
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="bi bi-save me-2"></i>Lagre Endringer';
    }
    
    return false;
}

async function handleAboutUsSubmit(event) {
    event.preventDefault();
    event.stopPropagation();
    
    console.log('About us form submitted');
    
    const aboutUsText = document.getElementById('about-us-text').value;
    
    try {
        const response = await fetch('/api/update_about_us.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ 
                about_us: aboutUsText,
                company_id: <?= $_SESSION['company_id'] ?>
            })
        });
        
        const result = await response.json();
        console.log('API response:', result);
        
        if (result.success) {
            alert('✅ "Om oss" tekst lagret!');
        } else {
            alert('❌ Feil: ' + (result.message || 'Kunne ikke lagre tekst'));
        }
    } catch (error) {
        console.error('Error saving about us:', error);
        alert('❌ Feil ved lagring av tekst');
    }
    
    return false;
}
</script>
