<?php
// Note: $db is available from parent context (company_dashboard.php)
// Fetch company payment settings
$companyData = [];
try {
    if (isset($_SESSION['company_id']) && isset($db)) {
        $stmt = $db->prepare("SELECT Cash_payment FROM tenant_companies WHERE id = ?");
        $stmt->execute([$_SESSION['company_id']]);
        $companyData = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    error_log("Error fetching company data: " . $e->getMessage());
}

// Check if user has membership module access (if not set by parent)
if (!isset($hasMembershipAccess)) {
    $hasMembershipAccess = false;
    try {
        if (isset($_SESSION['tenant_admin_id']) && isset($db)) {
            require_once __DIR__ . '/../includes/functions.php';
            $adminModules = getTenantAdminModules($_SESSION['tenant_admin_id'], $db);
            error_log("events_content - Checking membership access for admin: " . $_SESSION['tenant_admin_id']);
            error_log("events_content - Admin has " . count($adminModules) . " modules");
            foreach ($adminModules as $module) {
                error_log("events_content - Module: " . $module['module_key']);
                if ($module['module_key'] === 'membership') {
                    $hasMembershipAccess = true;
                    error_log("events_content - MEMBERSHIP ACCESS GRANTED!");
                    break;
                }
            }
        }
    } catch (Exception $e) {
        error_log("Error checking membership access: " . $e->getMessage());
    }
}
error_log("events_content - Final hasMembershipAccess value: " . ($hasMembershipAccess ? 'TRUE' : 'FALSE'));
?>
<style>
.event-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s ease;
    background: white;
    height: 100%;
}

.event-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.event-image {
    height: 180px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    background-size: cover;
    background-position: center;
    position: relative;
}

.event-image-placeholder {
    height: 180px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
}

.event-image-placeholder i {
    font-size: 3rem;
    color: white;
    opacity: 0.7;
}

.event-badge-overlay {
    position: absolute;
    top: 12px;
    right: 12px;
}

.event-body {
    padding: 1.5rem;
}

.event-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.event-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin: 1rem 0;
}

.event-meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #6b7280;
}

.event-meta-item i {
    width: 20px;
    color: #667eea;
}

.event-footer {
    padding: 1rem 1.5rem;
    border-top: 1px solid #f3f4f6;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.event-price {
    font-size: 1.25rem;
    font-weight: 700;
    color: #667eea;
}

.event-price-free {
    color: #10b981;
}

.event-actions {
    display: flex;
    gap: 0.5rem;
}

.event-results-collapse {
    border-top: 2px solid #667eea;
    background: #f8f9fa;
}

.event-results-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
}

.event-results-body {
    padding: 1.5rem;
}

.event-finance-list {
    background: white;
    border-radius: 8px;
    padding: 0.75rem;
    min-height: 60px;
}

.event-finance-item {
    display: flex;
    justify-content-between;
    align-items-center;
    padding: 0.5rem 0;
    border-bottom: 1px solid #e5e7eb;
}

.event-finance-item:last-child {
    border-bottom: none;
}

.event-results-summary {
    background: white;
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
}

.loading-events {
    text-align: center;
    padding: 4rem 2rem;
    color: #6b7280;
}

.empty-events {
    text-align: center;
    padding: 4rem 2rem;
}

.empty-events i {
    font-size: 4rem;
    color: #d1d5db;
    margin-bottom: 1rem;
}
</style>

<div class="module-content">
    <div id="events-my" class="subtab-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-1"><i class="bi bi-calendar-event me-2"></i>Dine arrangement</h4>
                <p class="text-muted mb-0">Oversikt over alle dine arrangementer</p>
            </div>
            <button class="btn btn-primary" onclick="document.querySelector('[href=\'#events-create\']').click()">
                <i class="bi bi-plus-circle me-2"></i>Opprett nytt
            </button>
        </div>
        
        <div id="events-list" class="row g-4">
            <div class="col-12">
                <div class="loading-events">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <p class="mb-0">Laster arrangementer...</p>
                </div>
            </div>
        </div>
    </div>

    <div id="events-create" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Opprett nytt arrangement</h5>
                <form id="create-event-form">
                    <div class="mb-3">
                        <label class="form-label">Arrangement navn *</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Bilde-URL</label>
                        <input type="url" class="form-control" name="event_image_url" placeholder="https://...">
                        <small class="form-text text-muted">Valgfri: URL til arrangementsbilde</small>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Startdato *</label>
                            <input type="date" class="form-control" name="start_date" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Starttid *</label>
                            <input type="time" class="form-control" name="start_time" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sluttdato</label>
                            <input type="date" class="form-control" name="end_date">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sluttid</label>
                            <input type="time" class="form-control" name="end_time">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Aldersgrense</label>
                            <input type="number" class="form-control" name="age_limit">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stedsnavn</label>
                            <input type="text" class="form-control" name="venue_name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" class="form-control" name="event_address">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">By</label>
                            <input type="text" class="form-control" name="event_city">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Postnummer</label>
                            <input type="text" class="form-control" name="event_postal">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Min. deltakere</label>
                            <input type="number" class="form-control" name="min_attendees" id="min_attendees">
                        </div>
                        <div class="col-md-3 mb-3" id="registration_deadline_container" style="display: none;">
                            <label class="form-label">Frist for min. deltakere *</label>
                            <input type="date" class="form-control" name="registration_deadline" id="registration_deadline">
                            <small class="form-text text-muted">Må være før startdato</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Maks. deltakere</label>
                            <input type="number" class="form-control" name="max_attendees">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Billettpris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="ticket_price">
                        </div>
                    </div>

                    <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Medlemspris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="member_ticket_price">
                            <small class="form-text text-muted">La stå tom hvis samme pris som ordinær billett</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Standard betalingsmetode</label>
                            <select class="form-select" name="default_payment_method">
                                <option value="">Velg betalingsmetode</option>
                                <?php if (!empty($companyData['Cash_payment'])): ?>
                                <option value="cash" selected>Kontant</option>
                                <?php endif; ?>
                                <option value="card">Kort</option>
                                <option value="vipps">Vipps</option>
                                <option value="online">Online</option>
                                <option value="invoice">Faktura</option>
                            </select>
                            <small class="form-text text-muted">Denne metoden vil være forhåndsvalgt ved påmelding</small>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Combined Registration & Membership Card -->
                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-ui-checks me-2"></i>Påmelding & Medlemskap</h6>
                            <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                            <span class="badge bg-light text-primary" id="member-restriction-badge" style="display: none;">
                                <i class="bi bi-shield-check me-1"></i>Kun medlemmer aktivert
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <!-- Registration Fields Section -->
                            <h6 class="mb-3"><i class="bi bi-person-lines-fill me-2"></i>Påmeldingsfelt</h6>
                            <p class="text-muted small mb-3">Konfigurer hvilke opplysninger som skal samles inn ved påmelding</p>
                            
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Standard felt (alltid inkludert):</strong><br>
                                <small>✓ Fornavn &nbsp; ✓ Etternavn</small>
                            </div>
                            
                            <h6 class="mb-3">Valgfrie felt:</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_email" id="field_email" checked>
                                        <label class="form-check-label" for="field_email">
                                            <i class="bi bi-envelope text-primary me-1"></i> E-postadresse
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_phone" id="field_phone" checked>
                                        <label class="form-check-label" for="field_phone">
                                            <i class="bi bi-telephone text-primary me-1"></i> Telefonnummer
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_age" id="field_age" checked>
                                        <label class="form-check-label" for="field_age">
                                            <i class="bi bi-person-badge text-primary me-1"></i> Alder
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_street" id="field_street" checked>
                                        <label class="form-check-label" for="field_street">
                                            <i class="bi bi-house text-primary me-1"></i> Gateadresse
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_zip" id="field_zip" checked>
                                        <label class="form-check-label" for="field_zip">
                                            <i class="bi bi-mailbox text-primary me-1"></i> Postnummer
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_city" id="field_city" checked>
                                        <label class="form-check-label" for="field_city">
                                            <i class="bi bi-geo-alt text-primary me-1"></i> Poststed
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_postal" id="field_postal" checked>
                                        <label class="form-check-label" for="field_postal">
                                            <i class="bi bi-box text-primary me-1"></i> Postboks
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                            <hr class="my-4">
                            
                            <!-- Membership Section -->
                            <h6 class="mb-3"><i class="bi bi-card-checklist me-2"></i>Medlemskap</h6>
                            
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_member_exclusive" id="memberExclusive">
                                    <label class="form-check-label" for="memberExclusive">
                                        <strong><i class="bi bi-shield-lock me-1"></i>Kun medlemmer kan melde seg på</strong>
                                    </label>
                                </div>
                                <small class="form-text text-muted ms-4">
                                    Aktiver for å begrense påmelding til kun medlemmer. Velg spesifikke planer nedenfor for ytterligere begrensning.
                                </small>
                            </div>
                            
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Slik fungerer medlemskapsplaner:</strong>
                                <ul class="mb-0 mt-2 small">
                                    <li><strong>Ingen planer valgt:</strong> Alle medlemskap får medlemspris / adgang</li>
                                    <li><strong>Spesifikke planer valgt:</strong> Kun disse planene får medlemspris / adgang</li>
                                    <li><strong>"Kun medlemmer" AV:</strong> Planer gir kun medlemspris (alle kan delta)</li>
                                    <li><strong>"Kun medlemmer" PÅ:</strong> Planer begrenser også hvem som kan delta</li>
                                </ul>
                            </div>
                            
                            <h6 class="mb-3">Velg medlemskapsplaner:</h6>
                            <div id="membership-plans-container">
                                <div class="text-center text-muted">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Laster medlemskapsplaner...
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-circle me-1"></i>Opprett arrangement
                        </button>
                        <button type="reset" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>Nullstill
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="events-checkin" class="subtab-content" style="display: none;">
        <div class="row g-3">
            <!-- Left Column: Event Selection & Scanner -->
            <div class="col-md-4">
                <div class="card mb-3">
                    <div class="card-header bg-gradient text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Velg arrangement</h6>
                    </div>
                    <div class="card-body">
                        <select class="form-select" id="checkin-event-selector">
                            <option value="">Laster...</option>
                        </select>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div id="checkin-stats" class="card mb-3" style="display: none;">
                    <div class="card-body p-2">
                        <div class="row g-2 text-center small">
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <div class="text-muted">Påmeldte</div>
                                    <div class="fs-5 fw-bold" id="checkin-total-attendees">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <div class="text-muted">Betalt</div>
                                    <div class="fs-5 fw-bold text-success" id="checkin-paid-count">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <div class="text-muted">Venter</div>
                                    <div class="fs-5 fw-bold text-warning" id="checkin-pending-count">0</div>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="p-2 bg-light rounded">
                                    <div class="text-muted">Inntekt</div>
                                    <div class="fs-5 fw-bold text-primary" id="checkin-total-revenue">0 kr</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Guest Registration -->
                <div id="quick-guest-registration" class="card" style="display: none;">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0"><i class="bi bi-person-plus me-2"></i>Registrer gjest</h6>
                    </div>
                    <div class="card-body">
                        <form id="quick-guest-form">
                            <div class="mb-2">
                                <label class="form-label small mb-1">Fornavn</label>
                                <input type="text" class="form-control form-control-sm" id="guest-fornavn" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Etternavn</label>
                                <input type="text" class="form-control form-control-sm" id="guest-etternavn" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Pris (kr)</label>
                                <input type="number" class="form-control form-control-sm" id="guest-price" min="0" step="0.01" required>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small mb-1">Betalingsmetode</label>
                                <select class="form-select form-select-sm" id="guest-payment-method" required>
                                    <option value="cash">Kontant</option>
                                    <option value="card">Kort</option>
                                    <option value="vipps">Vipps</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small mb-1">Status</label>
                                <select class="form-select form-select-sm" id="guest-payment-status" required>
                                    <option value="paid">Betalt</option>
                                    <option value="pending">Venter betaling</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-success btn-sm w-100">
                                <i class="bi bi-check-circle me-1"></i>Registrer
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Right Column: Attendee List -->
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-people me-2"></i>Påmeldte deltakere</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" id="checkin-list-container" style="display: none;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Navn</th>
                                        <th>Kontakt</th>
                                        <th>Type</th>
                                        <th>Pris</th>
                                        <th>Status</th>
                                        <th>Tid</th>
                                    </tr>
                                </thead>
                                <tbody id="checkin-attendees-list">
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            Ingen deltakere ennå
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="text-center text-muted py-5" id="checkin-empty-state">
                            <i class="bi bi-people fs-1"></i>
                            <p class="mt-2">Velg et arrangement for å se deltakere</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="events-results" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-graph-up me-2"></i>Arrangement Resultat</h5>
                <p class="text-muted">Se og administrer inntekter og utgifter for arrangementer</p>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Arrangement</th>
                                <th>Dato</th>
                                <th>Påmeldte</th>
                                <th class="text-end">Inntekt</th>
                                <th class="text-end">Utgift</th>
                                <th class="text-end">Netto</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody id="events-results-list">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                    Laster arrangementer...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div class="modal fade" id="editEventModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Rediger arrangement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="edit-event-form">
                    <input type="hidden" name="event_id" id="edit-event-id">
                    
                    <div class="row">
                        <div class="col-md-<?php echo isset($hasMembershipAccess) && $hasMembershipAccess ? '8' : '12'; ?> mb-3">
                            <label class="form-label">Arrangement navn *</label>
                            <input type="text" class="form-control" name="title" id="edit-title" required>
                        </div>
                        <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Kun for medlemmer?</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_member_exclusive" id="edit-member-exclusive">
                                <label class="form-check-label" for="edit-member-exclusive">Kun medlemmer</label>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" name="description" id="edit-description" rows="3"></textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Startdato *</label>
                            <input type="date" class="form-control" name="start_date" id="edit-start-date" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Starttid *</label>
                            <input type="time" class="form-control" name="start_time" id="edit-start-time" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sluttdato</label>
                            <input type="date" class="form-control" name="end_date" id="edit-end-date">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Sluttid</label>
                            <input type="time" class="form-control" name="end_time" id="edit-end-time">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Aldersgrense</label>
                            <input type="number" class="form-control" name="age_limit" id="edit-age-limit">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Stedsnavn</label>
                            <input type="text" class="form-control" name="venue_name" id="edit-venue-name">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Adresse</label>
                            <input type="text" class="form-control" name="event_address" id="edit-event-address">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">By</label>
                            <input type="text" class="form-control" name="event_city" id="edit-event-city">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Postnummer</label>
                            <input type="text" class="form-control" name="event_postal" id="edit-event-postal">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Min. deltakere</label>
                            <input type="number" class="form-control" name="min_attendees" id="edit-min-attendees">
                        </div>
                        <div class="col-md-3 mb-3" id="edit-registration-deadline-container" style="display: none;">
                            <label class="form-label">Frist for min. deltakere *</label>
                            <input type="date" class="form-control" name="registration_deadline" id="edit-registration-deadline">
                            <small class="form-text text-muted">Må være før startdato</small>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Maks. deltakere</label>
                            <input type="number" class="form-control" name="max_attendees" id="edit-max-attendees">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">Billettpris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="ticket_price" id="edit-ticket-price">
                        </div>
                    </div>

                    <?php if (isset($hasMembershipAccess) && $hasMembershipAccess): ?>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Medlemspris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="member_ticket_price" id="edit-member-ticket-price">
                            <small class="form-text text-muted">La stå tom hvis samme pris som ordinær billett</small>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Standard betalingsmetode</label>
                            <select class="form-select" name="default_payment_method" id="edit-default-payment-method">
                                <option value="">Velg betalingsmetode</option>
                                <?php if (!empty($companyData['Cash_payment'])): ?>
                                <option value="cash">Kontant</option>
                                <?php endif; ?>
                                <option value="card">Kort</option>
                                <option value="vipps">Vipps</option>
                                <option value="online">Online</option>
                                <option value="invoice">Faktura</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bilde-URL</label>
                            <input type="url" class="form-control" name="event_image_url" id="edit-event-image-url">
                            <small class="form-text text-muted">Valgfri: URL til arrangementsbilde</small>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="card border-primary mb-3">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="bi bi-ui-checks me-2"></i>Påmeldingsfelt</h6>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small mb-3">Konfigurer hvilke opplysninger som skal samles inn ved påmelding</p>
                            
                            <div class="alert alert-info mb-3">
                                <i class="bi bi-info-circle me-2"></i>
                                <strong>Standard felt (alltid inkludert):</strong><br>
                                <small>✓ Fornavn &nbsp; ✓ Etternavn &nbsp; ✓ E-postadresse</small>
                            </div>
                            
                            <h6 class="mb-3">Valgfrie tilleggsfelt:</h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_phone" id="edit-field-phone">
                                        <label class="form-check-label" for="edit-field-phone">
                                            <i class="bi bi-telephone text-primary me-1"></i> Telefonnummer
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_street" id="edit-field-street">
                                        <label class="form-check-label" for="edit-field-street">
                                            <i class="bi bi-house text-primary me-1"></i> Gateadresse
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_zip" id="edit-field-zip">
                                        <label class="form-check-label" for="edit-field-zip">
                                            <i class="bi bi-mailbox text-primary me-1"></i> Postnummer
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_city" id="edit-field-city">
                                        <label class="form-check-label" for="edit-field-city">
                                            <i class="bi bi-geo-alt text-primary me-1"></i> Poststed
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_postal" id="edit-field-postal">
                                        <label class="form-check-label" for="edit-field-postal">
                                            <i class="bi bi-box text-primary me-1"></i> Postboks
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="field_age" id="edit-field-age">
                                        <label class="form-check-label" for="edit-field-age">
                                            <i class="bi bi-person-badge text-primary me-1"></i> Alder
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="saveEventChanges()">
                    <i class="bi bi-check-circle me-1"></i>Lagre endringer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Income Modal -->
<div class="modal fade" id="addIncomeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>Legg til inntekt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-income-form">
                    <input type="hidden" id="income-event-id" name="event_id">
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse *</label>
                        <input type="text" class="form-control" name="description" required placeholder="F.eks. sponsorstøtte, salg av varer, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beløp (NOK) *</label>
                        <input type="number" class="form-control" name="amount" required min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dato *</label>
                        <input type="date" class="form-control" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notater</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-success" onclick="saveIncome()">
                    <i class="bi bi-check-circle me-1"></i>Legg til
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-dash-circle me-2"></i>Legg til utgift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="add-expense-form">
                    <input type="hidden" id="expense-event-id" name="event_id">
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse *</label>
                        <input type="text" class="form-control" name="description" required placeholder="F.eks. lokale, catering, utstyr, etc.">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beløp (NOK) *</label>
                        <input type="number" class="form-control" name="amount" required min="0" step="0.01">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Dato *</label>
                        <input type="date" class="form-control" name="date" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" name="category">
                            <option value="">Velg kategori</option>
                            <option value="venue">Lokale</option>
                            <option value="catering">Catering</option>
                            <option value="equipment">Utstyr</option>
                            <option value="marketing">Markedsføring</option>
                            <option value="staff">Personal</option>
                            <option value="other">Annet</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notater</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-danger" onclick="saveExpense()">
                    <i class="bi bi-check-circle me-1"></i>Legg til
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Event Details Modal -->
<div class="modal fade" id="viewEventDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-graph-up me-2"></i><span id="details-event-title">Arrangement Resultat</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="details-event-id">
                
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card bg-success bg-opacity-10">
                            <div class="card-body">
                                <small class="text-muted">Total inntekt</small>
                                <h4 class="mb-0 text-success" id="details-total-income">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-danger bg-opacity-10">
                            <div class="card-body">
                                <small class="text-muted">Total utgift</small>
                                <h4 class="mb-0 text-danger" id="details-total-expense">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-primary bg-opacity-10">
                            <div class="card-body">
                                <small class="text-muted">Netto resultat</small>
                                <h4 class="mb-0 text-primary" id="details-net-result">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="btn-group mb-3 w-100" role="group">
                    <button type="button" class="btn btn-success" onclick="openAddIncomeModal()">
                        <i class="bi bi-plus-circle me-1"></i>Legg til inntekt
                    </button>
                    <button type="button" class="btn btn-danger" onclick="openAddExpenseModal()">
                        <i class="bi bi-dash-circle me-1"></i>Legg til utgift
                    </button>
                </div>

                <!-- Transactions List -->
                <h6 class="mb-3">Transaksjoner</h6>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Dato</th>
                                <th>Type</th>
                                <th>Beskrivelse</th>
                                <th>Beløp</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody id="details-transactions-list">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-3">
                                    Ingen transaksjoner ennå
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Lukk</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load events when module is active
    if (document.getElementById('events-my')) {
        loadEvents();
    }
    
    // Load membership plans for event creation
    const membershipPlansContainer = document.getElementById('membership-plans-container');
    if (membershipPlansContainer) {
        loadMembershipPlans();
    }

    // Handle min_attendees field - show/hide deadline
    const minAttendeesInput = document.getElementById('min_attendees');
    const deadlineContainer = document.getElementById('registration_deadline_container');
    const deadlineInput = document.getElementById('registration_deadline');
    
    if (minAttendeesInput && deadlineContainer) {
        minAttendeesInput.addEventListener('input', function() {
            if (this.value && parseInt(this.value) > 0) {
                deadlineContainer.style.display = 'block';
                deadlineInput.required = true;
            } else {
                deadlineContainer.style.display = 'none';
                deadlineInput.required = false;
                deadlineInput.value = '';
            }
        });
    }
    
    // Handle "Kun medlemmer" toggle - show/hide badge
    const memberExclusiveCheckbox = document.getElementById('memberExclusive');
    const memberRestrictionBadge = document.getElementById('member-restriction-badge');
    
    if (memberExclusiveCheckbox && memberRestrictionBadge) {
        memberExclusiveCheckbox.addEventListener('change', function() {
            if (this.checked) {
                memberRestrictionBadge.style.display = 'inline-block';
            } else {
                memberRestrictionBadge.style.display = 'none';
            }
        });
    }

    // Create event form submission
    const createEventForm = document.getElementById('create-event-form');
    if (createEventForm) {
        createEventForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            // Validate registration deadline
            const minAttendees = formData.get('min_attendees');
            const registrationDeadline = formData.get('registration_deadline');
            const startDate = formData.get('start_date');
            
            if (minAttendees && parseInt(minAttendees) > 0 && !registrationDeadline) {
                toast.quickWarning('Validering', 'Du må velge en frist for minimum deltakere');
                return;
            }
            
            if (registrationDeadline && startDate) {
                const deadlineDate = new Date(registrationDeadline);
                const eventStartDate = new Date(startDate);
                
                if (deadlineDate >= eventStartDate) {
                    toast.quickWarning('Validering', 'Frist for min. deltakere må være før startdato');
                    return;
                }
            }
            
            // Collect selected membership plans
            const selectedPlans = [];
            document.querySelectorAll('.membership-plan-checkbox:checked').forEach(checkbox => {
                selectedPlans.push(checkbox.value);
                console.log('Selected plan:', checkbox.value, '- Name:', checkbox.dataset.planName);
            });
            
            // Debug: Log selected plans
            if (selectedPlans.length > 0) {
                console.log('✓ Valgte medlemskapsplaner:', selectedPlans);
            } else {
                console.log('ℹ Ingen medlemskapsplaner valgt - alle aktive medlemmer kvalifiserer');
            }
            
            const data = {
                title: formData.get('title'),
                description: formData.get('description'),
                start_date: startDate,
                start_time: formData.get('start_time'),
                end_date: formData.get('end_date') || null,
                end_time: formData.get('end_time') || null,
                venue_name: formData.get('venue_name'),
                event_address: formData.get('event_address'),
                event_city: formData.get('event_city'),
                event_postal: formData.get('event_postal'),
                min_attendees: minAttendees || null,
                registration_deadline: registrationDeadline || null,
                max_attendees: formData.get('max_attendees') || null,
                age_limit: formData.get('age_limit') || null,
                ticket_price: formData.get('ticket_price') || 0,
                member_ticket_price: formData.get('member_ticket_price') || null,
                event_image_url: formData.get('event_image_url') || null,
                default_payment_method: formData.get('default_payment_method') || null,
                is_member_exclusive: formData.get('is_member_exclusive') ? 1 : 0,
                membership_plan_ids: selectedPlans,
                // Registration fields
                field_phone: formData.get('field_phone') ? 1 : 0,
                field_street: formData.get('field_street') ? 1 : 0,
                field_zip: formData.get('field_zip') ? 1 : 0,
                field_city: formData.get('field_city') ? 1 : 0,
                field_postal: formData.get('field_postal') ? 1 : 0,
                field_age: formData.get('field_age') ? 1 : 0
            };

            try {
                const response = await fetch('../api/create_event.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    toast.quickSuccess('Arrangement', 'Arrangement opprettet!');
                    createEventForm.reset();
                    loadEvents();
                    // Switch to "Dine arrangement" tab
                    document.querySelector('[href="#events-my"]').click();
                } else {
                    toast.quickError('Feil', result.message || 'Kunne ikke opprette arrangement');
                }
            } catch (error) {
                console.error('Error creating event:', error);
                toast.quickError('Feil', 'Feil ved opprettelse av arrangement');
            }
        });
    }
});

async function loadMembershipPlans() {
    const container = document.getElementById('membership-plans-container');
    if (!container) {
        console.warn('Membership plans container not found');
        return;
    }
    
    console.log('Loading membership plans...');
    
    try {
        console.log('STARTING loadMembershipPlans()');
        const response = await fetch('/api/get_membership_plans.php');
        console.log('Fetch response status:', response.status);
        const result = await response.json();
        
        console.log('Membership plans API response:', result);
        console.log('Result success:', result.success);
        console.log('Plans count:', result.plans ? result.plans.length : 0);
        
        if (result.success && result.plans && result.plans.length > 0) {
            console.log(`Found ${result.plans.length} membership plan(s)`);
            
            container.innerHTML = `
                <div class="row" id="plans-checkboxes">
                    ${result.plans.map(plan => {
                        const planId = plan.plan_id || plan.id;
                        const billingInfo = plan.price ? 
                            `<span class="text-muted"> - ${plan.price} kr/${plan.billing_cycle || (plan.duration_months + ' mnd')}</span>` : '';
                        return `
                            <div class="col-md-6 mb-2">
                                <div class="form-check">
                                    <input class="form-check-input membership-plan-checkbox" 
                                           type="checkbox" 
                                           name="membership_plan_${planId}" 
                                           value="${planId}" 
                                           id="plan-${planId}"
                                           data-plan-name="${plan.name}">
                                    <label class="form-check-label" for="plan-${planId}">
                                        <strong>${plan.name}</strong>
                                        ${billingInfo}
                                    </label>
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
                <div class="mt-3">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="selectAllPlans()">
                        <i class="bi bi-check-all me-1"></i>Velg alle
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAllPlans()">
                        <i class="bi bi-x-circle me-1"></i>Fjern alle
                    </button>
                    <span class="ms-3 text-muted small" id="selected-plans-count">0 valgt</span>
                </div>
            `;
            
            // Add change listeners to update count
            document.querySelectorAll('.membership-plan-checkbox').forEach(checkbox => {
                checkbox.addEventListener('change', updateSelectedPlansCount);
            });
            
            console.log('Membership plans loaded successfully');
        } else {
            console.log('No membership plans found');
            container.innerHTML = `
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    Ingen medlemskapsplaner funnet. Du må først opprette medlemskapsplaner i Medlemskap-modulen.
                </div>
            `;
        }
    } catch (error) {
        console.error('CATCH ERROR loading membership plans:', error);
        console.error('Error details:', error.message, error.stack);
        container.innerHTML = `
            <div class="alert alert-danger mb-0">
                <i class="bi bi-exclamation-triangle me-2"></i>
                Kunne ikke laste medlemskapsplaner: ${error.message}
            </div>
        `;
    }
}

function updateSelectedPlansCount() {
    const checkboxes = document.querySelectorAll('.membership-plan-checkbox');
    const checkedCount = document.querySelectorAll('.membership-plan-checkbox:checked').length;
    const countElement = document.getElementById('selected-plans-count');
    
    if (countElement) {
        if (checkedCount === 0) {
            countElement.textContent = '0 valgt (alle medlemskap kvalifiserer)';
            countElement.className = 'ms-3 text-muted small';
        } else {
            countElement.textContent = `${checkedCount} av ${checkboxes.length} valgt`;
            countElement.className = 'ms-3 text-success small fw-bold';
        }
    }
}

function selectAllPlans() {
    document.querySelectorAll('.membership-plan-checkbox').forEach(checkbox => {
        checkbox.checked = true;
    });
    updateSelectedPlansCount();
}

function deselectAllPlans() {
    document.querySelectorAll('.membership-plan-checkbox').forEach(checkbox => {
        checkbox.checked = false;
    });
    updateSelectedPlansCount();
}

async function loadEvents() {
    try {
        const response = await fetch('../api/get_events.php');
        const result = await response.json();

        const eventsList = document.getElementById('events-list');
        if (result.success && result.events && result.events.length > 0) {
            eventsList.innerHTML = result.events.map(event => {
                const startDate = new Date(event.start_date + ' ' + event.start_time);
                const dateStr = startDate.toLocaleDateString('no-NO', { day: 'numeric', month: 'short', year: 'numeric' });
                const timeStr = event.start_time ? event.start_time.substring(0, 5) : '';
                
                let priceDisplay = '<span class="event-price event-price-free">Gratis</span>';
                if (event.ticket_price > 0) {
                    priceDisplay = `<span class="event-price">${event.ticket_price} kr</span>`;
                    if (event.member_ticket_price !== null && event.member_ticket_price !== undefined && event.member_ticket_price > 0) {
                        priceDisplay += `<br><small class="text-muted">${event.member_ticket_price} kr (medlem)</small>`;
                    }
                }
                
                const imageHtml = event.event_image_url 
                    ? `<div class="event-image" style="background-image: url('${event.event_image_url}')"></div>`
                    : `<div class="event-image-placeholder"><i class="bi bi-calendar-event"></i></div>`;
                
                const memberBadge = event.is_member_exclusive == 1 
                    ? '<span class="badge bg-warning event-badge-overlay">Kun medlemmer</span>' 
                    : '';
                
                const location = event.venue_name || event.event_city || 'Ikke angitt';
                const capacity = event.max_attendees ? `${event.current_attendees || 0} / ${event.max_attendees}` : 'Ubegrenset';
                const createdBy = (event.first_name && event.last_name) 
                    ? `${event.first_name} ${event.last_name}` 
                    : 'Ukjent bruker';
                
                return `
                    <div class="col-md-4">
                        <div class="event-card">
                            ${imageHtml}
                            ${memberBadge}
                            <div class="event-body" onclick="toggleEventResults('${event.id}')" style="cursor: pointer;">
                                <h5 class="event-title">${event.title}</h5>
                                <div class="event-meta">
                                    <div class="event-meta-item">
                                        <i class="bi bi-calendar3"></i>
                                        <span>${dateStr} ${timeStr}</span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="bi bi-geo-alt"></i>
                                        <span>${location}</span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="bi bi-people"></i>
                                        <span>${capacity} plasser</span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="bi bi-person-circle"></i>
                                        <span>Opprettet av ${createdBy}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="event-footer">
                                <div>${priceDisplay}</div>
                                <div class="event-actions">
                                    <button class="btn btn-sm btn-outline-primary" onclick="event.stopPropagation(); viewEvent('${event.id}')" title="Vis detaljer">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-warning" onclick="event.stopPropagation(); editEvent('${event.id}')" title="Rediger">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteEvent('${event.id}')" title="Slett">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Event Results Collapse -->
                            <div id="event-results-${event.id}" class="event-results-collapse" style="display: none;">
                                <div class="event-results-header">
                                    <i class="bi bi-graph-up me-2"></i>Arrangement Resultat
                                </div>
                                <div class="event-results-body">
                                    <!-- Income Section -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong class="text-success"><i class="bi bi-arrow-down-circle me-1"></i>Inntekter</strong>
                                            <button class="btn btn-sm btn-success" onclick="event.stopPropagation(); addEventIncome('${event.id}')">
                                                <i class="bi bi-plus-circle me-1"></i>Legg til
                                            </button>
                                        </div>
                                        <div id="event-income-${event.id}" class="event-finance-list">
                                            <small class="text-muted">Ingen inntekter registrert</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Expense Section -->
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong class="text-danger"><i class="bi bi-arrow-up-circle me-1"></i>Utgifter</strong>
                                            <button class="btn btn-sm btn-danger" onclick="event.stopPropagation(); addEventExpense('${event.id}')">
                                                <i class="bi bi-plus-circle me-1"></i>Legg til
                                            </button>
                                        </div>
                                        <div id="event-expense-${event.id}" class="event-finance-list">
                                            <small class="text-muted">Ingen utgifter registrert</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Summary -->
                                    <div class="event-results-summary">
                                        <div class="d-flex justify-content-between">
                                            <span>Total inntekt:</span>
                                            <span class="text-success fw-bold" id="event-total-income-${event.id}">0 NOK</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span>Total utgift:</span>
                                            <span class="text-danger fw-bold" id="event-total-expense-${event.id}">0 NOK</span>
                                        </div>
                                        <div class="d-flex justify-content-between border-top pt-2 mt-2">
                                            <strong>Netto resultat:</strong>
                                            <strong id="event-net-result-${event.id}">0 NOK</strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            eventsList.innerHTML = `
                <div class="col-12">
                    <div class="empty-events">
                        <i class="bi bi-calendar-x"></i>
                        <h5>Ingen arrangementer ennå</h5>
                        <p class="text-muted">Opprett ditt første arrangement ved å klikke på "Opprett nytt" knappen ovenfor</p>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading events:', error);
        document.getElementById('events-list').innerHTML = `
            <div class="col-12">
                <div class="empty-events">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                    <h5>Feil ved lasting av arrangementer</h5>
                    <p class="text-muted">Vennligst prøv igjen senere</p>
                </div>
            </div>
        `;
    }
}

function viewEvent(eventId) {
    toast.quickInfo('Info', 'Visning av arrangement: ' + eventId);
}

function deleteEvent(eventId) {
    if (confirm('Er du sikker på at du vil slette dette arrangementet?')) {
        fetch('../api/delete_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ event_id: eventId })
        })
        .then(res => res.json())
        .then(result => {
            if (result.success) {
                toast.quickSuccess('Arrangement', 'Arrangement slettet');
                loadEvents();
            } else {
                toast.quickError('Feil', result.message || 'Kunne ikke slette');
            }
        })
        .catch(error => {
            console.error('Error deleting event:', error);
            toast.quickError('Feil', 'Feil ved sletting');
        });
    }
}

// ========== CHECK-IN FUNCTIONALITY ==========

let selectedEventId = null;

// Load events into check-in selector
async function loadCheckinEventSelector() {
    try {
        const response = await fetch('/api/get_events.php');
        const result = await response.json();
        
        const selector = document.getElementById('checkin-event-selector');
        if (result.success && result.events && result.events.length > 0) {
            selector.innerHTML = '<option value="">-- Velg arrangement --</option>' + 
                result.events.map(event => `
                    <option value="${event.id}">
                        ${event.title} - ${event.start_date} ${event.start_time}
                    </option>
                `).join('');
        } else {
            selector.innerHTML = '<option value="">Ingen arrangementer tilgjengelig</option>';
        }
    } catch (error) {
        console.error('Error loading events:', error);
    }
}

// Handle payment toggle
document.getElementById('isPayingCheckbox')?.addEventListener('change', function(e) {
    const paymentContainer = document.getElementById('payment-method-container');
    const paymentSelect = document.querySelector('[name="payment_method"]');
    
    if (e.target.checked) {
        paymentContainer.style.display = 'block';
        paymentSelect.required = true;
    } else {
        paymentContainer.style.display = 'none';
        paymentSelect.required = false;
    }
});

// Check if email matches staff member
document.querySelector('[name="attendee_email"]')?.addEventListener('blur', async function(e) {
    const email = e.target.value.trim();
    if (!email) return;
    
    try {
        const response = await fetch(`../api/check_staff.php?email=${encodeURIComponent(email)}&company_id=<?= $_SESSION['company_id'] ?>`);
        const result = await response.json();
        
        if (result.is_staff) {
            document.getElementById('staff-info').style.display = 'block';
            document.getElementById('staff-info-text').textContent = `${result.name} er registrert som ansatt`;
            document.querySelector('[name="ticket_type"]').value = 'staff';
        } else {
            document.getElementById('staff-info').style.display = 'none';
        }
    } catch (error) {
        console.error('Error checking staff:', error);
    }
});

// Handle event selection
document.getElementById('checkin-event-selector')?.addEventListener('change', async function(e) {
    selectedEventId = e.target.value;
    
    if (selectedEventId) {
        document.getElementById('checkin-stats').style.display = 'block';
        document.getElementById('quick-guest-registration').style.display = 'block';
        document.getElementById('checkin-list-container').style.display = 'block';
        document.getElementById('checkin-empty-state').style.display = 'none';
        
        // Load event price to pre-fill guest form
        try {
            const response = await fetch(`/api/get_event.php?id=${selectedEventId}`);
            const result = await response.json();
            if (result.success && result.event) {
                document.getElementById('guest-price').value = result.event.ticket_price || 0;
            }
        } catch (error) {
            console.error('Error loading event price:', error);
        }
        
        loadCheckins(selectedEventId);
    } else {
        document.getElementById('checkin-stats').style.display = 'none';
        document.getElementById('quick-guest-registration').style.display = 'none';
        document.getElementById('checkin-list-container').style.display = 'none';
    }
});

// Handle quick guest registration
document.getElementById('quick-guest-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!selectedEventId) {
        toast.quickWarning('Validering', 'Velg et arrangement først');
        return;
    }
    
    const formData = {
        event_id: selectedEventId,
        fornavn: document.getElementById('guest-fornavn').value.trim(),
        etternavn: document.getElementById('guest-etternavn').value.trim(),
        ticket_price: parseFloat(document.getElementById('guest-price').value),
        payment_method: document.getElementById('guest-payment-method').value,
        payment_status: document.getElementById('guest-payment-status').value
    };
    
    try {
        const response = await fetch('/api/register_guest.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(formData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Reset form
            document.getElementById('quick-guest-form').reset();
            
            // Reload event price
            try {
                const eventResponse = await fetch(`/api/get_event.php?id=${selectedEventId}`);
                const eventResult = await eventResponse.json();
                if (eventResult.success && eventResult.event) {
                    document.getElementById('guest-price').value = eventResult.event.ticket_price || 0;
                }
            } catch (error) {
                console.error('Error reloading event price:', error);
            }
            
            // Reload checkins
            await loadCheckins(selectedEventId);
            
            toast.quickSuccess('Gjest', 'Gjest registrert!');
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke registrere gjest');
        }
    } catch (error) {
        console.error('Error registering guest:', error);
        toast.quickError('Gjest', 'Kunne ikke registrere gjest');
    }
});

// Load and display dynamic registration fields
async function loadEventRegistrationFields(eventId) {
    try {
        const response = await fetch(`/api/get_event_fields.php?event_id=${eventId}`);
        const result = await response.json();
        
        const container = document.getElementById('dynamic-registration-fields');
        container.innerHTML = '';
        
        if (result.success && result.fields && result.fields.length > 0) {
            const row = document.createElement('div');
            row.className = 'row';
            
            result.fields.forEach(field => {
                const colDiv = document.createElement('div');
                colDiv.className = 'col-md-6 mb-3';
                
                const label = document.createElement('label');
                label.className = 'form-label';
                label.textContent = field.field_label + (field.is_required ? ' *' : '');
                
                let input;
                if (field.field_type === 'textarea') {
                    input = document.createElement('textarea');
                    input.rows = 2;
                } else {
                    input = document.createElement('input');
                    input.type = field.field_type || 'text';
                }
                
                input.className = 'form-control';
                input.name = 'field_' + field.field_name;
                if (field.is_required) {
                    input.required = true;
                }
                
                colDiv.appendChild(label);
                colDiv.appendChild(input);
                row.appendChild(colDiv);
            });
            
            container.appendChild(row);
        }
    } catch (error) {
        console.error('Error loading event fields:', error);
    }
}

// Attendee scanner functionality
let scannerTimeout;
document.getElementById('attendee-scanner-input')?.addEventListener('input', function(e) {
    const scanInput = e.target.value.trim();
    
    // Clear previous timeout
    clearTimeout(scannerTimeout);
    
    // Only search if 3+ characters
    if (scanInput.length < 3) {
        return;
    }
    
    // Add visual feedback
    this.style.borderColor = '#ffc107';
    this.style.boxShadow = '0 0 0 0.2rem rgba(255, 193, 7, 0.25)';
    
    // Debounce search
    scannerTimeout = setTimeout(async () => {
        try {
            const response = await fetch(`../api/search_attendee.php?query=${encodeURIComponent(scanInput)}&company_id=<?= $_SESSION['company_id'] ?>`);
            const result = await response.json();
            
            if (result.success && result.attendee) {
                // Fill form with attendee data
                document.querySelector('[name="attendee_name"]').value = result.attendee.name || '';
                document.querySelector('[name="attendee_email"]').value = result.attendee.email || '';
                document.querySelector('[name="attendee_phone"]').value = result.attendee.phone || '';
                
                // Success feedback
                e.target.style.borderColor = '#28a745';
                e.target.style.boxShadow = '0 0 0 0.2rem rgba(40, 167, 69, 0.25)';
                
                // Clear scanner after 1 second
                setTimeout(() => {
                    e.target.value = '';
                    e.target.style.borderColor = '';
                    e.target.style.boxShadow = '';
                }, 1000);
            } else {
                // Not found feedback
                e.target.style.borderColor = '#dc3545';
                e.target.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
                
                setTimeout(() => {
                    e.target.style.borderColor = '';
                    e.target.style.boxShadow = '';
                }, 2000);
            }
        } catch (error) {
            console.error('Error searching attendee:', error);
            e.target.style.borderColor = '#dc3545';
            e.target.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
        }
    }, 300);
});

// Check-in form submission
document.getElementById('checkin-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    if (!selectedEventId) {
        toast.quickWarning('Validering', 'Velg et arrangement først');
        return;
    }
    
    const formData = new FormData(this);
    const isPaying = document.getElementById('isPayingCheckbox').checked;
    const data = {
        event_id: selectedEventId,
        attendee_name: formData.get('attendee_name'),
        attendee_email: formData.get('attendee_email'),
        ticket_type: formData.get('ticket_type'),
        payment_method: isPaying ? formData.get('payment_method') : 'free',
        is_paying: isPaying,
        notes: formData.get('notes')
    };
    
    // Include all dynamic field values
    for (const [key, value] of formData.entries()) {
        if (key.startsWith('field_')) {
            data[key] = value;
        }
    }
    
    try {
        const response = await fetch('../api/checkin_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Innsjekking', 'Deltaker sjekket inn!');
            this.reset();
            loadCheckins(selectedEventId);
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke sjekke inn');
        }
    } catch (error) {
        console.error('Error checking in:', error);
        toast.quickError('Innsjekking', 'Feil ved innsjekking');
    }
});

// Load check-ins for selected event
async function loadCheckins(eventId) {
    try {
        const response = await fetch(`/api/get_event_checkins.php?event_id=${eventId}`);
        const result = await response.json();
        
        if (result.success) {
            // Update statistics
            document.getElementById('checkin-total-attendees').textContent = result.summary.total_attendees;
            document.getElementById('checkin-paid-count').textContent = result.summary.paid_count;
            document.getElementById('checkin-pending-count').textContent = result.summary.pending_count;
            document.getElementById('checkin-total-revenue').textContent = result.summary.total_revenue.toFixed(2) + ' kr';
            
            // Update attendee list
            const list = document.getElementById('checkin-attendees-list');
            if (result.checkins && result.checkins.length > 0) {
                list.innerHTML = result.checkins.map(checkin => {
                    const name = checkin.attendee_name || (checkin.first_name + ' ' + checkin.last_name);
                    const contact = checkin.attendee_email || checkin.attendee_phone || '-';
                    const checkinTime = checkin.check_in_time ? new Date(checkin.check_in_time).toLocaleTimeString('no-NO', {hour: '2-digit', minute: '2-digit'}) : '-';
                    
                    const paymentBadge = checkin.payment_status === 'paid' 
                        ? '<span class="badge bg-success">Betalt</span>' 
                        : '<span class="badge bg-warning">Venter</span>';
                    
                    return `
                        <tr>
                            <td>${name}</td>
                            <td><small>${contact}</small></td>
                            <td><small>${checkin.ticket_type}</small></td>
                            <td>${checkin.ticket_price} kr</td>
                            <td>${paymentBadge}</td>
                            <td><small class="text-muted">${checkinTime}</small></td>
                        </tr>
                    `;
                }).join('');
            } else {
                list.innerHTML = `
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            Ingen deltakere ennå
                        </td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading check-ins:', error);
    }
}

// Load check-in selector when check-in tab is shown
document.querySelector('[href="#events-checkin"]')?.addEventListener('click', function() {
    loadCheckinEventSelector();
});

// Load events results
async function loadEventsResults() {
    try {
        const response = await fetch('/api/get_events_results.php');
        const result = await response.json();
        
        if (result.success) {
            // Oppdater arrangement liste
            const resultsList = document.getElementById('events-results-list');
            
            if (result.events && result.events.length > 0) {
                resultsList.innerHTML = result.events.map(event => {
                    const netColor = event.net_result >= 0 ? 'text-success' : 'text-danger';
                    
                    return `
                        <tr>
                            <td><strong>${event.title}</strong></td>
                            <td>${event.start_date} ${event.start_time || ''}</td>
                            <td>${event.attendee_count || 0}</td>
                            <td class="text-success">${parseFloat(event.income || 0).toFixed(2)} NOK</td>
                            <td class="text-danger">${parseFloat(event.expense || 0).toFixed(2)} NOK</td>
                            <td class="${netColor}"><strong>${parseFloat(event.net_result || 0).toFixed(2)} NOK</strong></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewEventDetails(${event.id}, '${event.title.replace(/'/g, "\\'")}')">
                                    <i class="bi bi-eye me-1"></i>Detaljer
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                resultsList.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Ingen resultater tilgjengelig
                        </td>
                    </tr>
                `;
            }
        }
    } catch (error) {
        console.error('Error loading events results:', error);
        document.getElementById('events-results-list').innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger py-4">
                    Feil ved lasting av resultater
                </td>
            </tr>
        `;
    }
}

// Load results when results tab is shown
document.querySelector('[href="#events-results"]')?.addEventListener('shown.bs.tab', function() {
    loadEventsResults();
});

// Toggle event results dropdown
async function toggleEventResults(eventId) {
    const resultsDiv = document.getElementById(`event-results-${eventId}`);
    if (resultsDiv) {
        const isHidden = resultsDiv.style.display === 'none';
        resultsDiv.style.display = isHidden ? 'block' : 'none';
        
        // Load finances when opening
        if (isHidden) {
            await loadEventFinances(eventId);
        }
    }
}

// Load event finances from unified_ledger
async function loadEventFinances(eventId) {
    try {
        const response = await fetch(`/api/get_event_finances.php?event_id=${eventId}`);
        const result = await response.json();
        
        if (result.success) {
            const incomeList = document.getElementById(`event-income-${eventId}`);
            const expenseList = document.getElementById(`event-expense-${eventId}`);
            
            // Display income
            if (result.income && result.income.length > 0) {
                incomeList.innerHTML = result.income.map(item => {
                    const date = new Date(item.transaction_date).toLocaleDateString('no-NO');
                    return `
                        <div class="event-finance-item">
                            <div>
                                <span>${item.item_name}</span>
                                <small class="text-muted d-block">${date} - ${item.payment_method || 'Kontant'}</small>
                            </div>
                            <strong class="text-success">+${parseFloat(item.selling_price).toFixed(2)} NOK</strong>
                        </div>
                    `;
                }).join('');
            } else {
                incomeList.innerHTML = '<small class="text-muted">Ingen inntekter registrert</small>';
            }
            
            // Display expenses
            if (result.expenses && result.expenses.length > 0) {
                expenseList.innerHTML = result.expenses.map(item => {
                    const date = new Date(item.transaction_date).toLocaleDateString('no-NO');
                    return `
                        <div class="event-finance-item">
                            <div>
                                <span>${item.item_name}</span>
                                <small class="text-muted d-block">${date} - ${item.payment_method || 'Kontant'}</small>
                            </div>
                            <strong class="text-danger">-${Math.abs(parseFloat(item.selling_price)).toFixed(2)} NOK</strong>
                        </div>
                    `;
                }).join('');
            } else {
                expenseList.innerHTML = '<small class="text-muted">Ingen utgifter registrert</small>';
            }
            
            // Update summary
            document.getElementById(`event-total-income-${eventId}`).textContent = `${result.summary.total_income.toFixed(2)} NOK`;
            document.getElementById(`event-total-expense-${eventId}`).textContent = `${result.summary.total_expense.toFixed(2)} NOK`;
            
            const netResultEl = document.getElementById(`event-net-result-${eventId}`);
            netResultEl.textContent = `${result.summary.net_result.toFixed(2)} NOK`;
            netResultEl.className = result.summary.net_result >= 0 ? 'text-success fw-bold' : 'text-danger fw-bold';
        }
    } catch (error) {
        console.error('Error loading event finances:', error);
    }
}

// Add event income
async function addEventIncome(eventId) {
    const description = prompt('Beskrivelse av inntekt:');
    if (!description) return;
    
    const amount = parseFloat(prompt('Beløp (NOK):'));
    if (!amount || amount <= 0) {
        toast.quickWarning('Validering', 'Ugyldig beløp');
        return;
    }
    
    try {
        const response = await fetch('/api/add_event_finance.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                event_id: eventId,
                type: 'income',
                item_name: description,
                amount: amount,
                payment_method: 'cash'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Inntekt', 'Inntekt lagt til');
            await loadEventFinances(eventId);
        } else {
            toast.quickError('Feil', result.message);
        }
    } catch (error) {
        console.error('Error adding income:', error);
        toast.quickError('Inntekt', 'Feil ved lagring av inntekt');
    }
}

// Add event expense
async function addEventExpense(eventId) {
    const description = prompt('Beskrivelse av utgift:');
    if (!description) return;
    
    const amount = parseFloat(prompt('Beløp (NOK):'));
    if (!amount || amount <= 0) {
        toast.quickWarning('Validering', 'Ugyldig beløp');
        return;
    }
    
    try {
        const response = await fetch('/api/add_event_finance.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                event_id: eventId,
                type: 'expense',
                item_name: description,
                amount: amount,
                payment_method: 'cash'
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Utgift', 'Utgift lagt til');
            await loadEventFinances(eventId);
        } else {
            toast.quickError('Feil', result.message);
        }
    } catch (error) {
        console.error('Error adding expense:', error);
        toast.quickError('Utgift', 'Feil ved lagring av utgift');
    }
}

// Edit event function
async function editEvent(eventId) {
    try {
        // Fetch event details
        const response = await fetch(`/api/get_events.php`);
        const result = await response.json();
        
        if (!result.success) {
            toast.quickError('Arrangement', 'Kunne ikke laste arrangement');
            return;
        }
        
        const event = result.events.find(e => e.id === eventId);
        if (!event) {
            toast.quickError('Arrangement', 'Arrangement ikke funnet');
            return;
        }
        
        // Populate form fields
        document.getElementById('edit-event-id').value = event.id;
        document.getElementById('edit-title').value = event.title || '';
        document.getElementById('edit-description').value = event.description || '';
        document.getElementById('edit-start-date').value = event.start_date || '';
        document.getElementById('edit-start-time').value = event.start_time || '';
        document.getElementById('edit-end-date').value = event.end_date || '';
        document.getElementById('edit-end-time').value = event.end_time || '';
        document.getElementById('edit-age-limit').value = event.age_limit || '';
        document.getElementById('edit-venue-name').value = event.venue_name || '';
        document.getElementById('edit-event-address').value = event.event_address || '';
        document.getElementById('edit-event-city').value = event.event_city || '';
        document.getElementById('edit-event-postal').value = event.event_postal || '';
        document.getElementById('edit-min-attendees').value = event.min_attendees || '';
        document.getElementById('edit-registration-deadline').value = event.registration_deadline || '';
        document.getElementById('edit-max-attendees').value = event.max_attendees || '';
        document.getElementById('edit-ticket-price').value = event.ticket_price || 0;
        document.getElementById('edit-member-ticket-price').value = event.member_ticket_price || '';
        document.getElementById('edit-default-payment-method').value = event.default_payment_method || '';
        document.getElementById('edit-event-image-url').value = event.event_image_url || '';
        
        // Set checkbox
        const memberExclusiveCheckbox = document.getElementById('edit-member-exclusive');
        if (memberExclusiveCheckbox) {
            memberExclusiveCheckbox.checked = event.is_member_exclusive == 1;
        }
        
        // Show/hide registration deadline if min_attendees is set
        const editDeadlineContainer = document.getElementById('edit-registration-deadline-container');
        if (event.min_attendees && parseInt(event.min_attendees) > 0) {
            editDeadlineContainer.style.display = 'block';
        } else {
            editDeadlineContainer.style.display = 'none';
        }
        
        // Fetch and set registration fields
        const fieldsResponse = await fetch(`/api/get_event_registration_fields.php?event_id=${eventId}`);
        const fieldsResult = await fieldsResponse.json();
        
        // Reset all optional field checkboxes
        document.getElementById('edit-field-phone').checked = false;
        document.getElementById('edit-field-street').checked = false;
        document.getElementById('edit-field-zip').checked = false;
        document.getElementById('edit-field-city').checked = false;
        document.getElementById('edit-field-postal').checked = false;
        document.getElementById('edit-field-age').checked = false;
        
        if (fieldsResult.success && fieldsResult.fields) {
            fieldsResult.fields.forEach(field => {
                const checkbox = document.getElementById(`edit-field-${field.field_name}`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });
        }
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editEventModal'));
        modal.show();
        
    } catch (error) {
        console.error('Error loading event for edit:', error);
        toast.quickError('Arrangement', 'Feil ved lasting av arrangement');
    }
}

// Handle min_attendees field in edit modal
const editMinAttendeesInput = document.getElementById('edit-min-attendees');
const editDeadlineContainer = document.getElementById('edit-registration-deadline-container');
const editDeadlineInput = document.getElementById('edit-registration-deadline');

if (editMinAttendeesInput && editDeadlineContainer) {
    editMinAttendeesInput.addEventListener('input', function() {
        if (this.value && parseInt(this.value) > 0) {
            editDeadlineContainer.style.display = 'block';
            editDeadlineInput.required = true;
        } else {
            editDeadlineContainer.style.display = 'none';
            editDeadlineInput.required = false;
            editDeadlineInput.value = '';
        }
    });
}

// Save event changes
async function saveEventChanges() {
    const form = document.getElementById('edit-event-form');
    const formData = new FormData(form);
    
    // Validate registration deadline
    const minAttendees = formData.get('min_attendees');
    const registrationDeadline = formData.get('registration_deadline');
    const startDate = formData.get('start_date');
    
    if (minAttendees && parseInt(minAttendees) > 0 && !registrationDeadline) {
        toast.quickWarning('Validering', 'Du må velge en frist for minimum deltakere');
        return;
    }
    
    if (registrationDeadline && startDate) {
        const deadlineDate = new Date(registrationDeadline);
        const eventStartDate = new Date(startDate);
        
        if (deadlineDate >= eventStartDate) {
            toast.quickWarning('Validering', 'Frist for min. deltakere må være før startdato');
            return;
        }
    }
    
    const data = {
        event_id: formData.get('event_id'),
        title: formData.get('title'),
        description: formData.get('description'),
        start_date: startDate,
        start_time: formData.get('start_time'),
        end_date: formData.get('end_date') || null,
        end_time: formData.get('end_time') || null,
        venue_name: formData.get('venue_name'),
        event_address: formData.get('event_address'),
        event_city: formData.get('event_city'),
        event_postal: formData.get('event_postal'),
        min_attendees: minAttendees || null,
        registration_deadline: registrationDeadline || null,
        max_attendees: formData.get('max_attendees') || null,
        age_limit: formData.get('age_limit') || null,
        ticket_price: formData.get('ticket_price') || 0,
        member_ticket_price: formData.get('member_ticket_price') || null,
        event_image_url: formData.get('event_image_url') || null,
        default_payment_method: formData.get('default_payment_method') || null,
        is_member_exclusive: formData.get('is_member_exclusive') ? 1 : 0,
        // Registration fields
        field_phone: formData.get('field_phone') ? 1 : 0,
        field_street: formData.get('field_street') ? 1 : 0,
        field_zip: formData.get('field_zip') ? 1 : 0,
        field_city: formData.get('field_city') ? 1 : 0,
        field_postal: formData.get('field_postal') ? 1 : 0,
        field_age: formData.get('field_age') ? 1 : 0
    };

    try {
        const response = await fetch('../api/update_event.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            toast.quickSuccess('Arrangement', 'Arrangement oppdatert!');
            bootstrap.Modal.getInstance(document.getElementById('editEventModal')).hide();
            loadEvents();
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke oppdatere arrangement');
        }
    } catch (error) {
        console.error('Error updating event:', error);
        toast.quickError('Arrangement', 'Feil ved oppdatering av arrangement');
    }
}

function openAddIncomeModal() {
    const eventId = document.getElementById('details-event-id').value;
    document.getElementById('income-event-id').value = eventId;
    document.getElementById('add-income-form').reset();
    document.getElementById('income-event-id').value = eventId;
    
    const detailsModal = bootstrap.Modal.getInstance(document.getElementById('viewEventDetailsModal'));
    if (detailsModal) detailsModal.hide();
    
    const incomeModal = new bootstrap.Modal(document.getElementById('addIncomeModal'));
    incomeModal.show();
}

function openAddExpenseModal() {
    const eventId = document.getElementById('details-event-id').value;
    document.getElementById('expense-event-id').value = eventId;
    document.getElementById('add-expense-form').reset();
    document.getElementById('expense-event-id').value = eventId;
    
    const detailsModal = bootstrap.Modal.getInstance(document.getElementById('viewEventDetailsModal'));
    if (detailsModal) detailsModal.hide();
    
    const expenseModal = new bootstrap.Modal(document.getElementById('addExpenseModal'));
    expenseModal.show();
}

async function saveIncome() {
    const form = document.getElementById('add-income-form');
    const formData = new FormData(form);
    
    const data = {
        event_id: formData.get('event_id'),
        description: formData.get('description'),
        amount: parseFloat(formData.get('amount')),
        date: formData.get('date'),
        notes: formData.get('notes')
    };
    
    if (!data.description || !data.amount || data.amount <= 0) {
        toast.quickWarning('Validering', 'Vennligst fyll ut beskrivelse og gyldig beløp');
        return;
    }
    
    try {
        const response = await fetch('/api/add_event_income.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Inntekt', 'Inntekt lagt til!');
            bootstrap.Modal.getInstance(document.getElementById('addIncomeModal')).hide();
            viewEventDetails(data.event_id);
            loadEventsResults();
        } else {
            toast.quickError('Feil', result.message || 'Feil ved lagring');
        }
    } catch (error) {
        console.error('Error saving income:', error);
        toast.quickError('Inntekt', 'Feil ved lagring av inntekt');
    }
}

async function saveExpense() {
    const form = document.getElementById('add-expense-form');
    const formData = new FormData(form);
    
    const data = {
        event_id: formData.get('event_id'),
        description: formData.get('description'),
        amount: parseFloat(formData.get('amount')),
        date: formData.get('date'),
        category: formData.get('category'),
        notes: formData.get('notes')
    };
    
    if (!data.description || !data.amount || data.amount <= 0) {
        toast.quickWarning('Validering', 'Vennligst fyll ut beskrivelse og gyldig beløp');
        return;
    }
    
    try {
        const response = await fetch('/api/add_event_expense.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.quickSuccess('Utgift', 'Utgift lagt til!');
            bootstrap.Modal.getInstance(document.getElementById('addExpenseModal')).hide();
            viewEventDetails(data.event_id);
            loadEventsResults();
        } else {
            toast.quickError('Feil', result.message || 'Feil ved lagring');
        }
    } catch (error) {
        console.error('Error saving expense:', error);
        toast.quickError('Utgift', 'Feil ved lagring av utgift');
    }
}

async function viewEventDetails(eventId, eventTitle = 'Arrangement') {
    document.getElementById('details-event-id').value = eventId;
    document.getElementById('details-event-title').textContent = eventTitle;
    
    try {
        const response = await fetch(`/api/get_event_transactions.php?event_id=${eventId}`);
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('details-total-income').textContent = result.summary.total_income.toLocaleString('nb-NO') + ' NOK';
            document.getElementById('details-total-expense').textContent = result.summary.total_expense.toLocaleString('nb-NO') + ' NOK';
            document.getElementById('details-net-result').textContent = result.summary.net_result.toLocaleString('nb-NO') + ' NOK';
            
            const transactionsList = document.getElementById('details-transactions-list');
            if (result.transactions && result.transactions.length > 0) {
                transactionsList.innerHTML = result.transactions.map(t => {
                    const date = new Date(t.transaction_date).toLocaleDateString('nb-NO');
                    const typeClass = t.entry_type === 'income' ? 'text-success' : 'text-danger';
                    const typeIcon = t.entry_type === 'income' ? 'plus-circle' : 'dash-circle';
                    const typeText = t.entry_type === 'income' ? 'Inntekt' : 'Utgift';
                    const amount = Math.abs(parseFloat(t.total_amount));
                    
                    return `
                        <tr>
                            <td><small>${date}</small></td>
                            <td><span class="badge ${t.entry_type === 'income' ? 'bg-success' : 'bg-danger'}"><i class="bi bi-${typeIcon} me-1"></i>${typeText}</span></td>
                            <td>${t.item_name}</td>
                            <td class="${typeClass}"><strong>${amount.toLocaleString('nb-NO')} NOK</strong></td>
                            <td>
                                <button class="btn btn-sm btn-outline-danger" onclick="deleteTransaction('${t.id}')" title="Slett">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                }).join('');
            } else {
                transactionsList.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center text-muted py-3">
                            Ingen transaksjoner ennå
                        </td>
                    </tr>
                `;
            }
            
            const detailsModal = new bootstrap.Modal(document.getElementById('viewEventDetailsModal'));
            detailsModal.show();
        }
    } catch (error) {
        console.error('Error loading event details:', error);
        toast.quickError('Arrangement', 'Feil ved lasting av arrangementsdetaljer');
    }
}

async function deleteTransaction(transactionId) {
    if (!confirm('Er du sikker på at du vil slette denne transaksjonen?')) {
        return;
    }
    
    toast.quickWarning('Info', 'Sletting av transaksjoner er ikke implementert ennå');
}
</script>
