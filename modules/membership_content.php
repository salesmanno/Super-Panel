<style>
.editable-member-cell, .editable-plan-cell {
    cursor: pointer;
    position: relative;
    transition: background-color 0.2s;
}
.editable-member-cell:hover, .editable-plan-cell:hover {
    background-color: rgba(13, 110, 253, 0.1);
}
.editable-member-cell:hover::after, .editable-plan-cell:hover::after {
    content: '\F4C4';
    font-family: 'bootstrap-icons';
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.7rem;
    color: #0d6efd;
    opacity: 0.7;
}
.editable-member-cell input {
    width: 100%;
    border: 2px solid #0d6efd;
    border-radius: 4px;
    padding: 2px 6px;
}
.editable-plan-cell select {
    width: 100%;
    border: 2px solid #0d6efd;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 0.875rem;
}
</style>
<div class="module-content">
    <div id="membership-plans" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Medlemskapsplaner</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                        <i class="bi bi-plus-circle me-1"></i>Ny plan
                    </button>
                </div>
                <div class="row" id="membership-plans-list">
                    <div class="col-12 text-center text-muted py-4">
                        <i class="bi bi-card-list fs-1 d-block mb-3"></i>
                        Ingen medlemskapsplaner opprettet ennå
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="membership-members" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Medlemsoversikt</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createMemberModal">
                        <i class="bi bi-plus-circle me-1"></i>Legg til medlem
                    </button>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="member-search-input" placeholder="Søk etter fornavn, etternavn, e-post eller telefon...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="member-plan-filter">
                            <option value="">Alle medlemstyper</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="member-status-filter">
                            <option value="">Alle statuser</option>
                            <option value="active">Aktiv</option>
                            <option value="expired">Utløpt</option>
                            <option value="pending">Venter</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Medlemsnr</th>
                                <th>Fornavn</th>
                                <th>Etternavn</th>
                                <th>E-post</th>
                                <th>Telefon</th>
                                <th>Adresse</th>
                                <th>Medlemstype</th>
                                <th>Status</th>
                                <th>Medlem siden</th>
                                <th>Gyldig til</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody id="members-table-body">
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    Ingen medlemmer funnet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">Viser 0 av 0 medlemmer</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#">Forrige</a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="#">1</a>
                            </li>
                            <li class="page-item disabled">
                                <a class="page-link" href="#">Neste</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div id="membership-income" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-cash-stack me-2 text-success"></i>Medlemsinntekter <?php echo date('Y'); ?></h5>
                <p class="text-muted">Oversikt over alle medlemsbetalinger og gjenværende betalinger for inneværende år</p>
                
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <small>Samlet innbetalt (i år)</small>
                                <h4 class="mb-0" id="income-paid-total">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <small>Forventet årsinntekt</small>
                                <h4 class="mb-0" id="income-expected-annual">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <small>Gjenstår å betale</small>
                                <h4 class="mb-0" id="income-remaining-total">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <small>Aktive medlemmer</small>
                                <h4 class="mb-0" id="income-active-count">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Betalinger</small>
                                <h4 class="mb-0" id="income-transaction-count">0</h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Månedlig betalingsoversikt -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-calendar3 me-2"></i>Månedlig betalingsoversikt <?php echo date('Y'); ?></h6>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-light btn-sm" onclick="changePaymentYear(-1)" title="Forrige år">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <span class="btn btn-light btn-sm disabled" id="payment-year-display"><?php echo date('Y'); ?></span>
                                <button class="btn btn-light btn-sm" onclick="changePaymentYear(1)" title="Neste år">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row" id="monthly-payments-grid">
                            <div class="col-12 text-center text-muted py-3">
                                <div class="spinner-border spinner-border-sm me-2"></div>
                                Laster månedlig oversikt...
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="mb-0"><i class="bi bi-people me-2"></i>Medlemsoversikt</h6>
                            <div class="btn-group btn-group-sm" role="group">
                                <input type="radio" class="btn-check" name="payment-filter" id="filter-all" value="all" checked>
                                <label class="btn btn-outline-primary" for="filter-all">Alle</label>
                                
                                <input type="radio" class="btn-check" name="payment-filter" id="filter-paid" value="paid">
                                <label class="btn btn-outline-success" for="filter-paid">Betalt</label>
                                
                                <input type="radio" class="btn-check" name="payment-filter" id="filter-partial" value="partial">
                                <label class="btn btn-outline-warning" for="filter-partial">Delvis betalt</label>
                                
                                <input type="radio" class="btn-check" name="payment-filter" id="filter-unpaid" value="unpaid">
                                <label class="btn btn-outline-danger" for="filter-unpaid">Ikke betalt</label>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Fornavn</th>
                                        <th>Etternavn</th>
                                        <th>E-post</th>
                                        <th>Telefon</th>
                                        <th>Adresse</th>
                                        <th>Poststed</th>
                                        <th>Fødselsdato</th>
                                        <th>Medlemskapsplan</th>
                                        <th class="text-end">Årlig pris</th>
                                        <th class="text-end">Innbetalt</th>
                                        <th class="text-end">Gjenværende</th>
                                        <th>Betalingsstatus</th>
                                        <th>Medlem siden</th>
                                        <th>Gyldig til</th>
                                        <th>Siste betaling</th>
                                        <th>Ant. bet.</th>
                                        <th>Auto-forny</th>
                                        <th>Medlemsstatus</th>
                                        <th>Handling</th>
                                    </tr>
                                </thead>
                                <tbody id="income-member-list">
                                    <tr>
                                        <td colspan="18" class="text-center text-muted py-3">
                                            <i class="bi bi-hourglass-split me-2"></i>Laster medlemsdata...
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal for legg til medlem -->
<div class="modal fade" id="createMemberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Legg til nytt medlem</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="create-member-form">
                    <!-- Personinformasjon -->
                    <div class="card mb-3">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="bi bi-person me-2"></i>Personinformasjon</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Fornavn *</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Etternavn *</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">E-post *</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Telefon</label>
                                    <input type="tel" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Adresse</label>
                                    <input type="text" class="form-control" name="address">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Poststed</label>
                                    <input type="text" class="form-control" name="poststed">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Fødselsdato</label>
                                    <input type="date" class="form-control" name="dateofbirth">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Bilde-URL</label>
                                    <input type="url" class="form-control" name="image_url" placeholder="https://...">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Velg medlemskapsplan -->
                    <div class="card mb-3">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="bi bi-card-checklist me-2"></i>Velg medlemskapsplan</h6>
                        </div>
                        <div class="card-body">
                            <div id="membership-plan-cards" class="row g-2 mb-3">
                                <div class="col-12 text-center text-muted py-3">
                                    <div class="spinner-border spinner-border-sm me-2"></div>
                                    Laster planer...
                                </div>
                            </div>
                            <input type="hidden" name="membership_plan_id" id="selected-plan-id">
                            
                            <!-- Valgt plan info -->
                            <div id="selected-plan-info" class="alert alert-success d-none">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-check-circle-fill me-2 fs-4"></i>
                                    <div>
                                        <strong id="selected-plan-name">-</strong>
                                        <div class="small" id="selected-plan-details">-</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Medlemskapsdetaljer -->
                    <div class="card mb-3">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Medlemskapsdetaljer</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Startdato</label>
                                    <input type="date" class="form-control" name="join_date" id="member-join-date">
                                    <small class="text-muted">Standard: i dag</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Betalingsstatus</label>
                                    <select class="form-select" name="payment_status" id="member-payment-status">
                                        <option value="paid">Betalt</option>
                                        <option value="pending">Venter på betaling</option>
                                        <option value="free">Gratis / Gave</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notater</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="Interne notater om medlemmet..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="submitMember()" id="submit-member-btn">
                    <i class="bi bi-check-circle me-1"></i>Legg til medlem
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for opprett medlemskapsplan -->
<div class="modal fade" id="createPlanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Opprett medlemskapsplan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="create-plan-form" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Plan navn *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pris (NOK) *</label>
                            <input type="number" step="0.01" class="form-control" name="price" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Faktureringsperiode *</label>
                            <select class="form-select" name="billing_cycle" required>
                                <option value="monthly">Månedlig</option>
                                <option value="quarterly">Kvartalsvis</option>
                                <option value="yearly">Årlig</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fordeler</label>
                        <textarea class="form-control" name="benefits" rows="4" placeholder="- Tilgang til arrangement&#10;- Medlemsrabatt&#10;- Nyhetsbrev"></textarea>
                        <small class="text-muted">Skriv en fordel per linje</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bilde-URL</label>
                        <input type="url" class="form-control" name="plan_image_url" placeholder="https://example.com/plan-bilde.jpg">
                        <small class="text-muted">URL til bilde for denne planen</small>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="for_sale" id="for_sale" checked>
                        <label class="form-check-label" for="for_sale">
                            Til salgs / tilbys
                        </label>
                        <small class="text-muted d-block">Hvis avmerket, kan denne planen selges og tilbys til kunder</small>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_internal" id="is_internal">
                        <label class="form-check-label" for="is_internal">
                            Intern medlemskapsplan
                        </label>
                        <small class="text-muted d-block">Hvis avmerket, vises ikke denne planen i tjenester som kan kjøpes</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="submitMembershipPlan()">Opprett plan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for rediger medlemskapsplan -->
<div class="modal fade" id="editPlanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rediger medlemskapsplan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="edit-plan-form" autocomplete="off">
                    <input type="hidden" name="plan_id" id="edit-plan-id">
                    <div class="mb-3">
                        <label class="form-label">Plan navn *</label>
                        <input type="text" class="form-control" name="name" id="edit-plan-name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" name="description" id="edit-plan-description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pris (NOK) *</label>
                            <input type="number" step="0.01" class="form-control" name="price" id="edit-plan-price" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Faktureringsperiode *</label>
                            <select class="form-select" name="billing_cycle" id="edit-plan-billing" required>
                                <option value="monthly">Månedlig</option>
                                <option value="quarterly">Kvartalsvis</option>
                                <option value="yearly">Årlig</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fordeler</label>
                        <textarea class="form-control" name="benefits" id="edit-plan-benefits" rows="4"></textarea>
                        <small class="text-muted">Skriv en fordel per linje</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bilde-URL</label>
                        <input type="url" class="form-control" name="plan_image_url" id="edit-plan-image" placeholder="https://example.com/plan-bilde.jpg">
                        <small class="text-muted">URL til bilde for denne planen</small>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="for_sale" id="edit-for-sale">
                        <label class="form-check-label" for="edit-for-sale">
                            Til salgs / tilbys
                        </label>
                        <small class="text-muted d-block">Hvis avmerket, kan denne planen selges og tilbys til kunder</small>
                    </div>
                    <hr>
                    <h6 class="text-muted mb-3"><i class="bi bi-calendar-event me-1"></i>Planlagt prisendring</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ny pris fra dato</label>
                            <input type="date" class="form-control" name="future_price_change_date" id="edit-future-price-date">
                            <small class="text-muted">Dato for når ny pris trer i kraft</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ny pris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="future_price_new" id="edit-future-price-new" placeholder="0.00">
                            <small class="text-muted">Prisen som gjelder fra denne datoen</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="submitEditPlan()">
                    <i class="bi bi-check-circle me-1"></i>Lagre endringer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function editMembershipPlan(planId) {
    try {
        const response = await fetch(`/api/get_membership_plans.php?plan_id=${planId}`);
        const result = await response.json();
        
        if (result.success && result.plans && result.plans.length > 0) {
            const plan = result.plans[0];
            document.getElementById('edit-plan-id').value = plan.id || plan.plan_id;
            document.getElementById('edit-plan-name').value = plan.name || '';
            document.getElementById('edit-plan-description').value = plan.description || '';
            document.getElementById('edit-plan-price').value = plan.price || '';
            document.getElementById('edit-plan-billing').value = plan.billing_cycle || 'yearly';
            document.getElementById('edit-plan-benefits').value = plan.benefits || '';
            document.getElementById('edit-plan-image').value = plan.image_url || '';
            document.getElementById('edit-for-sale').checked = plan.for_sale ? true : false;
            document.getElementById('edit-future-price-date').value = plan.future_price_change_date || '';
            document.getElementById('edit-future-price-new').value = plan.future_price_new || '';
            
            const modal = new bootstrap.Modal(document.getElementById('editPlanModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Error loading plan:', error);
        toast.error('Feil', 'Kunne ikke laste medlemskapsplan');
    }
}

async function submitEditPlan() {
    const form = document.getElementById('edit-plan-form');
    const planId = document.getElementById('edit-plan-id').value;
    
    const futurePriceDate = document.getElementById('edit-future-price-date').value;
    const futurePriceNew = document.getElementById('edit-future-price-new').value;
    
    const planData = {
        plan_id: planId,
        name: document.getElementById('edit-plan-name').value,
        description: document.getElementById('edit-plan-description').value,
        price: parseFloat(document.getElementById('edit-plan-price').value),
        billing_cycle: document.getElementById('edit-plan-billing').value,
        benefits: document.getElementById('edit-plan-benefits').value,
        image_url: document.getElementById('edit-plan-image').value || null,
        for_sale: document.getElementById('edit-for-sale').checked ? 1 : 0,
        future_price_change_date: futurePriceDate || null,
        future_price_new: futurePriceNew ? parseFloat(futurePriceNew) : null
    };
    
    const toastId = toast.loading('Lagrer endringer...');
    
    try {
        const response = await fetch('/api/update_membership_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(planData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success(toastId, 'Medlemskapsplan', 'Planen er oppdatert');
            bootstrap.Modal.getInstance(document.getElementById('editPlanModal')).hide();
            loadMembershipPlans();
        } else {
            toast.error(toastId, 'Oppdatering av medlemskapsplan', result.message || 'Kunne ikke lagre endringer');
        }
    } catch (error) {
        console.error('Error updating plan:', error);
        toast.error(toastId, 'Oppdatering av medlemskapsplan', 'Kunne ikke lagre endringer');
    }
}

async function submitMembershipPlan() {
    const form = document.getElementById('create-plan-form');
    const formData = new FormData(form);
    
    const planData = {
        name: formData.get('name'),
        description: formData.get('description'),
        price: parseFloat(formData.get('price')),
        billing_cycle: formData.get('billing_cycle'),
        benefits: formData.get('benefits'),
        image_url: formData.get('plan_image_url') || null,
        for_sale: document.getElementById('for_sale')?.checked ? 1 : 0,
        is_internal: document.getElementById('is_internal')?.checked ? 1 : 0
    };
    
    const toastId = toast.loading('Oppretter medlemskapsplan');
    
    try {
        const response = await fetch('/api/create_membership_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(planData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success(toastId, 'Medlemskapsplan opprettet', 'Planen er nå tilgjengelig for medlemmer');
            bootstrap.Modal.getInstance(document.getElementById('createPlanModal')).hide();
            form.reset();
            loadMembershipPlans();
        } else {
            toast.error(toastId, 'Opprettelse av medlemskapsplan', result.message || 'Kunne ikke opprette plan');
        }
    } catch (error) {
        console.error('Error creating plan:', error);
        toast.error(toastId, 'Opprettelse av medlemskapsplan', 'Kunne ikke opprette plan');
    }
}

async function loadMembershipPlans() {
    try {
        const response = await fetch('../api/get_membership_plans.php');
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const result = await response.json();
        
        const plansList = document.getElementById('membership-plans-list');
        
        if (result.success && result.plans && result.plans.length > 0) {
            plansList.innerHTML = result.plans.map(plan => {
                // Support both billing_cycle (MySQL) and duration_months (PostgreSQL)
                let billingLabel = '';
                if (plan.billing_cycle) {
                    billingLabel = plan.billing_cycle === 'monthly' ? 'Månedlig' : 
                                  plan.billing_cycle === 'quarterly' ? 'Kvartalsvis' : 
                                  plan.billing_cycle === 'yearly' ? 'Årlig' : plan.billing_cycle;
                } else if (plan.duration_months) {
                    const durationMonths = plan.duration_months;
                    billingLabel = durationMonths === 1 ? 'Månedlig' : 
                                  durationMonths === 3 ? 'Kvartalsvis' : 
                                  durationMonths === 12 ? 'Årlig' : 
                                  `${durationMonths} måneder`;
                } else {
                    billingLabel = 'Årlig';
                }
                
                const planId = plan.id || plan.plan_id;
                const forSaleChecked = plan.for_sale ? 'checked' : '';
                return `
                    <div class="col-md-4 mb-3">
                        <div class="card border h-100 d-flex flex-column">
                            <div class="card-body d-flex flex-column">
                                <h6 class="mb-1">${plan.name}</h6>
                                <p class="text-muted small mb-2">${billingLabel}</p>
                                <h4 class="text-primary mb-2">${plan.price} NOK</h4>
                                <div class="flex-grow-1">
                                    ${plan.benefits ? '<div class="small text-muted">' + plan.benefits.split('\n').slice(0, 2).join('<br>') + '</div>' : '<div class="small text-muted" style="min-height: 2.5em;"></div>'}
                                </div>
                                <div class="mt-auto pt-2">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="for_sale_${planId}" ${forSaleChecked} onchange="togglePlanForSale('${planId}', this.checked)">
                                        <label class="form-check-label small" for="for_sale_${planId}">
                                            Til salgs / tilbys
                                        </label>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-secondary btn-sm" onclick="goToMembershipShareLink('${planId}')" title="Gå til delingslenke">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </button>
                                        <button class="btn btn-outline-primary btn-sm flex-fill" onclick="editMembershipPlan('${planId}')">Rediger</button>
                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteMembershipPlan('${planId}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            plansList.innerHTML = `
                <div class="col-12 text-center text-muted py-4">
                    <i class="bi bi-card-list fs-1 d-block mb-3"></i>
                    Ingen medlemskapsplaner opprettet ennå
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading plans:', error);
        const plansList = document.getElementById('membership-plans-list');
        plansList.innerHTML = `
            <div class="col-12 text-center text-danger py-4">
                <i class="bi bi-exclamation-triangle fs-1 d-block mb-3"></i>
                <p class="mb-0">Feil ved lasting av medlemskapsplaner</p>
                <small class="text-muted">${error.message}</small>
            </div>
        `;
    }
}

// Last inn planer når medlemskap-tab vises
document.getElementById('tab-membership')?.addEventListener('shown.bs.tab', loadMembershipPlans);

async function togglePlanForSale(planId, forSale) {
    const toastId = toast.loading(forSale ? 'Aktiverer salg...' : 'Deaktiverer salg...');
    
    try {
        const response = await fetch('/api/update_membership_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ plan_id: planId, for_sale: forSale ? 1 : 0 })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success(toastId, '', forSale ? 'Planen er nå til salgs' : 'Planen er nå bare intern');
        } else {
            toast.error(toastId, 'Oppdatering av plan', result.message || 'Kunne ikke oppdatere');
            loadMembershipPlans(); // Reload to reflect actual state
        }
    } catch (error) {
        console.error('Error toggling plan for_sale:', error);
        toast.error(toastId, 'Oppdatering av plan', 'Kunne ikke oppdatere');
        loadMembershipPlans(); // Reload to reflect actual state
    }
}

async function goToMembershipShareLink(planId) {
    try {
        const response = await fetch(`/api/get_share_link.php?type=membership&item_id=${planId}`);
        const result = await response.json();
        
        if (result.success && result.share_link) {
            window.open(result.share_link, '_blank');
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke hente delingslenke');
        }
    } catch (error) {
        console.error('Error opening share link:', error);
        toast.quickError('Feil', 'Kunne ikke åpne delingslenke');
    }
}

async function deleteMembershipPlan(planId) {
    if (!confirm('Er du sikker på at du vil slette denne medlemskapsplanen?')) {
        return;
    }
    
    const toastId = toast.loading('Sletter medlemskapsplan');
    
    try {
        const response = await fetch('/api/delete_membership_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ plan_id: planId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success(toastId, 'Medlemskapsplan slettet', 'Planen er nå fjernet fra systemet');
            loadMembershipPlans();
        } else {
            toast.error(toastId, 'Sletting av medlemskapsplan', result.message || 'Kunne ikke slette plan');
        }
    } catch (error) {
        console.error('Error deleting plan:', error);
        toast.error(toastId, 'Sletting av medlemskapsplan', 'Kunne ikke slette plan');
    }
}

async function submitMember() {
    const form = document.getElementById('create-member-form');
    const formData = new FormData(form);
    
    const memberData = {
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        address: formData.get('address'),
        poststed: formData.get('poststed'),
        dateofbirth: formData.get('dateofbirth'),
        membership_plan_id: formData.get('membership_plan_id') || null,
        notes: formData.get('notes'),
        image_url: formData.get('image_url') || null,
        join_date: formData.get('join_date') || null,
        payment_status: formData.get('payment_status') || 'paid'
    };
    
    if (!memberData.first_name || !memberData.last_name || !memberData.email) {
        toast.quickWarning('Manglende informasjon', 'Vennligst fyll ut alle obligatoriske felt (fornavn, etternavn, e-post)');
        return;
    }
    
    const toastId = toast.loading('Legger til nytt medlem');
    
    try {
        const response = await fetch('../api/create_member.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(memberData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success(toastId, 'Medlem lagt til', `${memberData.first_name} ${memberData.last_name} er nå registrert`);
            bootstrap.Modal.getInstance(document.getElementById('createMemberModal')).hide();
            form.reset();
            document.getElementById('selected-plan-id').value = '';
            document.getElementById('selected-plan-info').classList.add('d-none');
            document.querySelectorAll('.plan-card').forEach(c => {
                c.style.borderColor = 'transparent';
                c.style.backgroundColor = '';
            });
            loadMembers();
        } else {
            toast.error(toastId, 'Registrering av medlem', result.message || 'Kunne ikke legge til medlem');
        }
    } catch (error) {
        console.error('Error creating member:', error);
        toast.error(toastId, 'Registrering av medlem', 'Kunne ikke legge til medlem');
    }
}

async function loadMembers() {
    try {
        const response = await fetch('../api/get_members.php');
        const result = await response.json();
        
        console.log('Members API response:', result);
        
        if (result.success && result.members) {
            console.log(`✅ Loading ${result.members.length} members`);
            allMembers = result.members;
            renderMembersTable(allMembers);
        } else {
            console.log('No members found or error:', result);
            allMembers = [];
            renderMembersTable([]);
        }
    } catch (error) {
        console.error('Error loading members:', error);
        const tableBody = document.getElementById('members-table-body');
        tableBody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center text-danger py-4">
                    Feil ved lasting av medlemmer
                </td>
            </tr>
        `;
    }
}

async function loadPlansForSelect() {
    try {
        const response = await fetch('../api/get_membership_plans.php');
        const result = await response.json();
        
        const container = document.getElementById('membership-plan-cards');
        const hiddenInput = document.getElementById('selected-plan-id');
        const infoBox = document.getElementById('selected-plan-info');
        
        // Set default join date to today
        const joinDateInput = document.getElementById('member-join-date');
        if (joinDateInput && !joinDateInput.value) {
            joinDateInput.value = new Date().toISOString().split('T')[0];
        }
        
        if (result.success && result.plans && result.plans.length > 0) {
            // Store plans for later use
            window.membershipPlans = result.plans;
            
            // Build plan cards
            let html = '';
            result.plans.forEach((plan, index) => {
                const billingLabel = {
                    'monthly': 'mnd',
                    'quarterly': 'kvartal',
                    'yearly': 'år'
                }[plan.billing_cycle] || 'periode';
                
                const planImage = plan.image_url 
                    ? `<img src="${plan.image_url}" alt="${plan.name}" class="rounded mb-2" style="height: 40px; width: auto; object-fit: contain;">`
                    : `<i class="bi bi-card-heading fs-3 text-primary mb-2"></i>`;
                
                html += `
                    <div class="col-md-4 col-sm-6">
                        <div class="card h-100 plan-card" data-plan-id="${plan.plan_id}" data-plan-index="${index}" 
                             onclick="selectMembershipPlan('${plan.plan_id}', ${index})" 
                             style="cursor: pointer; transition: all 0.2s; border: 2px solid transparent;">
                            <div class="card-body text-center p-3">
                                ${planImage}
                                <h6 class="card-title mb-1">${plan.name || plan.plan_name}</h6>
                                <div class="fw-bold text-primary">${plan.price} kr<small class="text-muted">/${billingLabel}</small></div>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            // Add "Ingen plan" option
            html += `
                <div class="col-md-4 col-sm-6">
                    <div class="card h-100 plan-card" data-plan-id="" 
                         onclick="selectMembershipPlan('', -1)" 
                         style="cursor: pointer; transition: all 0.2s; border: 2px solid transparent;">
                        <div class="card-body text-center p-3">
                            <i class="bi bi-x-circle fs-3 text-muted mb-2"></i>
                            <h6 class="card-title mb-1 text-muted">Ingen plan</h6>
                            <div class="text-muted small">Legg til uten medlemskap</div>
                        </div>
                    </div>
                </div>
            `;
            
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div class="col-12">
                    <div class="alert alert-warning mb-0">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Ingen medlemskapsplaner funnet. <a href="#" onclick="document.querySelector('[data-bs-target=\\'#createPlanModal\\']').click()">Opprett en plan først.</a>
                    </div>
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading plans for select:', error);
        document.getElementById('membership-plan-cards').innerHTML = `
            <div class="col-12 text-center text-danger py-3">
                <i class="bi bi-exclamation-triangle me-2"></i>Feil ved lasting av planer
            </div>
        `;
    }
}

function selectMembershipPlan(planId, planIndex) {
    const hiddenInput = document.getElementById('selected-plan-id');
    const infoBox = document.getElementById('selected-plan-info');
    const nameEl = document.getElementById('selected-plan-name');
    const detailsEl = document.getElementById('selected-plan-details');
    
    // Update hidden input
    hiddenInput.value = planId;
    
    // Update visual selection
    document.querySelectorAll('.plan-card').forEach(card => {
        card.style.borderColor = 'transparent';
        card.style.backgroundColor = '';
    });
    
    const selectedCard = document.querySelector(`.plan-card[data-plan-id="${planId}"]`);
    if (selectedCard) {
        selectedCard.style.borderColor = '#198754';
        selectedCard.style.backgroundColor = 'rgba(25, 135, 84, 0.05)';
    }
    
    // Update info box
    if (planId && planIndex >= 0 && window.membershipPlans && window.membershipPlans[planIndex]) {
        const plan = window.membershipPlans[planIndex];
        const billingLabel = {
            'monthly': 'Månedlig',
            'quarterly': 'Kvartalsvis',
            'yearly': 'Årlig'
        }[plan.billing_cycle] || plan.billing_cycle;
        
        nameEl.textContent = plan.name || plan.plan_name;
        detailsEl.textContent = `${plan.price} kr - ${billingLabel} fakturering`;
        infoBox.classList.remove('d-none');
    } else {
        infoBox.classList.add('d-none');
    }
}

async function deleteMember(memberId, memberName) {
    if (!confirm(`Er du sikker på at du vil slette medlemmet "${memberName}"?\n\nDette kan ikke angres.`)) {
        return;
    }
    
    try {
        const response = await fetch('/api/delete_member.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ member_id: memberId })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Medlem slettet');
            await loadMembers();
        } else {
            alert('Feil ved sletting: ' + result.message);
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('Feil ved sletting av medlem');
    }
}

// Load membership plans into filter dropdown
async function loadPlansForFilter() {
    try {
        const response = await fetch('../api/get_membership_plans.php');
        const result = await response.json();
        
        const select = document.getElementById('member-plan-filter');
        
        if (result.success && result.plans && result.plans.length > 0) {
            const options = result.plans.map(plan => 
                `<option value="${plan.plan_id || plan.id}">${plan.name || plan.plan_name}</option>`
            ).join('');
            select.innerHTML = '<option value="">Alle medlemstyper</option>' + options;
        }
    } catch (error) {
        console.error('Error loading plans for filter:', error);
    }
}

// Filter members based on search, plan, and status
let allMembers = [];

function applyMemberFilters() {
    const searchTerm = document.getElementById('member-search-input').value.toLowerCase();
    const planFilter = document.getElementById('member-plan-filter').value;
    const statusFilter = document.getElementById('member-status-filter').value;
    
    let filtered = allMembers.filter(member => {
        // Search filter
        const matchesSearch = !searchTerm || 
            (member.first_name?.toLowerCase().includes(searchTerm)) ||
            (member.last_name?.toLowerCase().includes(searchTerm)) ||
            (member.email?.toLowerCase().includes(searchTerm)) ||
            (member.phone?.includes(searchTerm));
        
        // Plan filter
        const matchesPlan = !planFilter || member.membership_plan_id == planFilter;
        
        // Status filter
        const matchesStatus = !statusFilter || member.status === statusFilter;
        
        return matchesSearch && matchesPlan && matchesStatus;
    });
    
    renderMembersTable(filtered);
}

function renderMembersTable(members) {
    const tableBody = document.getElementById('members-table-body');
    
    if (members && members.length > 0) {
        tableBody.innerHTML = members.map(member => {
            // Check if membership is expired based on expiry_date
            const today = new Date();
            const expiryDate = member.expiry_date ? new Date(member.expiry_date) : null;
            const isExpired = expiryDate && expiryDate < today;
            
            const statusBadge = member.status === 'active' && !isExpired ? 'bg-success' : 
                               isExpired ? 'bg-danger' : 'bg-warning';
            const statusText = member.status === 'active' && !isExpired ? 'Aktiv' : 
                              isExpired ? 'Manglende betaling' : 'Venter';
            
            // Generate shorter member reference (last 6 chars of member_id)
            const shortRef = member.member_id ? String(member.member_id).substring(0, 8).toUpperCase() : '-';
            
            return `
                <tr data-member-id="${member.member_id}" data-plan-id="${member.membership_plan_id || ''}">
                    <td><code>${shortRef}</code></td>
                    <td class="editable-member-cell" data-field="first_name">${member.first_name}</td>
                    <td class="editable-member-cell" data-field="last_name">${member.last_name}</td>
                    <td class="editable-member-cell" data-field="email">${member.email}</td>
                    <td class="editable-member-cell" data-field="phone">${member.phone || '-'}</td>
                    <td>${member.address ? (member.address + ', ' + (member.poststed || '')) : '-'}</td>
                    <td class="editable-plan-cell" data-field="membership_plan_id" data-current-plan="${member.membership_plan_id || ''}">${member.membership_type || '-'}</td>
                    <td><span class="badge ${statusBadge}">${statusText}</span></td>
                    <td>${member.join_date || '-'}</td>
                    <td>${member.expiry_date || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="renewMembership('${member.member_id}')" title="Forny medlemskap">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-danger" onclick="deleteMember('${member.member_id}', '${member.first_name} ${member.last_name}')" title="Slett medlem">
                            <i class="bi bi-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    } else {
        tableBody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center text-muted py-4">
                    Ingen medlemmer funnet
                </td>
            </tr>
        `;
    }
}

// Auto-load members when Membership tab is shown
document.getElementById('tab-membership')?.addEventListener('shown.bs.tab', function() {
    console.log('Membership tab shown - loading members and plans');
    loadMembers();
    loadPlansForFilter();
    loadPlansForSelect();
});

// Last inn medlemmer når medlemmer-subtab vises
const memberTabLink = document.querySelector('a[href="#membership-members"]');
if (memberTabLink) {
    memberTabLink.addEventListener('click', function() {
        setTimeout(() => {
            console.log('Members subtab clicked - loading data');
            loadMembers();
            loadPlansForFilter();
        }, 100);
    });
}

// Also load on initial page load if members tab is active
if (window.location.hash === '#membership-members') {
    console.log('Initial load - members subtab active');
    loadMembers();
    loadPlansForFilter();
}

// Add filter event listeners
document.getElementById('member-search-input')?.addEventListener('input', applyMemberFilters);
document.getElementById('member-plan-filter')?.addEventListener('change', applyMemberFilters);
document.getElementById('member-status-filter')?.addEventListener('change', applyMemberFilters);

// Last inn planer for dropdown når modal åpnes
document.getElementById('createMemberModal')?.addEventListener('show.bs.modal', loadPlansForSelect);

// Auto-load membership overview data on page load
setTimeout(() => {
    const membershipTab = document.getElementById('content-membership');
    if (membershipTab && membershipTab.classList.contains('active')) {
        console.log('Membership module active - auto-loading all data');
        loadMembershipPlans();
        loadMembers();
        loadPlansForFilter();
    }
}, 500);

// Inline editing for member fields using event delegation
document.addEventListener('DOMContentLoaded', function() {
    const membersTableBody = document.getElementById('members-table-body');
    if (!membersTableBody) return;
    
    membersTableBody.addEventListener('click', async function(e) {
        const cell = e.target.closest('.editable-member-cell');
        if (!cell || cell.querySelector('input')) return; // Already editing
        
        const row = cell.closest('tr');
        const memberId = row.dataset.memberId;
        const field = cell.dataset.field;
        const currentValue = cell.textContent.trim() === '-' ? '' : cell.textContent.trim();
        
        // Create input
        const input = document.createElement('input');
        input.type = field === 'email' ? 'email' : field === 'phone' ? 'tel' : 'text';
        input.className = 'form-control form-control-sm';
        input.value = currentValue;
        input.style.width = '100%';
        
        // Replace content
        const originalHTML = cell.innerHTML;
        cell.innerHTML = '';
        cell.appendChild(input);
        input.focus();
        input.select();
        
        // Save function
        const saveEdit = async () => {
            const newValue = input.value.trim();
            if (newValue !== currentValue) {
                const updateData = {
                    member_id: memberId,
                    [field]: newValue
                };
                
                try {
                    const response = await fetch('/api/update_member.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(updateData)
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        await loadMembers();
                    } else {
                        cell.innerHTML = originalHTML;
                        alert('❌ Feil ved oppdatering: ' + result.message);
                    }
                } catch (error) {
                    cell.innerHTML = originalHTML;
                    console.error('Update error:', error);
                    alert('❌ Feil ved oppdatering av medlem');
                }
            } else {
                cell.innerHTML = originalHTML;
            }
        };
        
        input.addEventListener('blur', saveEdit);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveEdit();
            } else if (e.key === 'Escape') {
                cell.innerHTML = originalHTML;
            }
        });
    });
    
    // Handle plan type inline editing with dropdown
    membersTableBody.addEventListener('click', async function(e) {
        const cell = e.target.closest('.editable-plan-cell');
        if (!cell || cell.querySelector('select')) return; // Already editing
        
        const row = cell.closest('tr');
        const memberId = row.dataset.memberId;
        const currentPlanId = cell.dataset.currentPlan || '';
        const originalHTML = cell.innerHTML;
        
        // Get available plans from window.membershipPlans (loaded by loadPlansForSelect)
        let plans = window.membershipPlans || [];
        
        // If plans not loaded, fetch them
        if (plans.length === 0) {
            try {
                const response = await fetch('../api/get_membership_plans.php');
                const result = await response.json();
                if (result.success && result.plans) {
                    plans = result.plans;
                    window.membershipPlans = plans;
                }
            } catch (error) {
                console.error('Error fetching plans:', error);
                return;
            }
        }
        
        if (plans.length === 0) {
            alert('Ingen medlemsplaner funnet. Opprett en plan først.');
            return;
        }
        
        // Create select dropdown
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        select.innerHTML = plans.map(plan => 
            `<option value="${plan.id}" ${plan.id === currentPlanId ? 'selected' : ''}>${plan.name}</option>`
        ).join('');
        
        cell.innerHTML = '';
        cell.appendChild(select);
        select.focus();
        
        // Save function
        const savePlanChange = async () => {
            const newPlanId = select.value;
            if (newPlanId !== currentPlanId) {
                const updateData = {
                    member_id: memberId,
                    membership_plan_id: newPlanId
                };
                
                try {
                    const response = await fetch('/api/update_member.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(updateData)
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        await loadMembers();
                    } else {
                        cell.innerHTML = originalHTML;
                        alert('Feil ved oppdatering: ' + result.message);
                    }
                } catch (error) {
                    cell.innerHTML = originalHTML;
                    console.error('Update error:', error);
                    alert('Feil ved oppdatering av medlemstype');
                }
            } else {
                cell.innerHTML = originalHTML;
            }
        };
        
        select.addEventListener('blur', savePlanChange);
        select.addEventListener('change', savePlanChange);
        select.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cell.innerHTML = originalHTML;
            }
        });
    });
});

// Renew membership function
async function renewMembership(memberId) {
    const member = allMembers.find(m => m.member_id == memberId);
    if (!member) {
        alert('❌ Medlem ikke funnet');
        return;
    }
    
    const startDate = prompt('Fornyingsdato (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
    if (!startDate) return;
    
    try {
        const response = await fetch('/api/renew_membership.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                member_id: memberId,
                renewal_date: startDate
            })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('✅ Medlemskap fornyet! Ny utløpsdato: ' + result.new_expiry_date);
            await loadMembers();
        } else {
            alert('❌ Feil: ' + result.message);
        }
    } catch (error) {
        console.error('Renewal error:', error);
        alert('❌ Feil ved fornyelse av medlemskap');
    }
}

// Load membership income statistics
let currentMembershipFilter = 'all';

async function loadMembershipIncome(filter = 'all') {
    currentMembershipFilter = filter;
    
    try {
        const response = await fetch(`/api/get_membership_income_tracking.php?filter=${filter}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('income-paid-total').textContent = data.summary.total_paid.toLocaleString('nb-NO') + ' NOK';
            document.getElementById('income-expected-annual').textContent = data.summary.expected_annual_income.toLocaleString('nb-NO') + ' NOK';
            document.getElementById('income-remaining-total').textContent = data.summary.total_remaining.toLocaleString('nb-NO') + ' NOK';
            document.getElementById('income-active-count').textContent = data.summary.active_count;
            document.getElementById('income-transaction-count').textContent = data.summary.transaction_count;
            
            const memberList = document.getElementById('income-member-list');
            if (data.members && data.members.length > 0) {
                memberList.innerHTML = data.members.map(m => {
                    const statusBadge = m.payment_status === 'paid' ? 'bg-success' : 
                                       m.payment_status === 'partial' ? 'bg-warning' : 'bg-danger';
                    const statusText = m.payment_status === 'paid' ? 'Betalt' : 
                                      m.payment_status === 'partial' ? 'Delvis betalt' : 'Ikke betalt';
                    const lastPayment = m.last_payment_date ? 
                        new Date(m.last_payment_date).toLocaleDateString('nb-NO') : 
                        '-';
                    const joinDate = m.join_date ? 
                        new Date(m.join_date).toLocaleDateString('nb-NO') : 
                        '-';
                    const expiryDate = m.expiry_date ? 
                        new Date(m.expiry_date).toLocaleDateString('nb-NO') : 
                        '-';
                    const birthDate = m.dateofbirth ? 
                        new Date(m.dateofbirth).toLocaleDateString('nb-NO') : 
                        '-';
                    
                    const memberStatusBadge = m.member_status === 'active' ? 'bg-success' : 
                                             m.member_status === 'expired' ? 'bg-danger' : 
                                             m.member_status === 'pending' ? 'bg-warning' : 'bg-secondary';
                    const memberStatusText = m.member_status === 'active' ? 'Aktiv' : 
                                            m.member_status === 'expired' ? 'Utløpt' : 
                                            m.member_status === 'pending' ? 'På vent' : m.member_status;
                    
                    return `
                        <tr>
                            <td><strong>${m.first_name || '-'}</strong></td>
                            <td><strong>${m.last_name || '-'}</strong></td>
                            <td><small>${m.email || '-'}</small></td>
                            <td><small>${m.phone || '-'}</small></td>
                            <td><small>${m.address || '-'}</small></td>
                            <td><small>${m.poststed || '-'}</small></td>
                            <td><small>${birthDate}</small></td>
                            <td><span class="badge bg-info">${m.plan_name}</span></td>
                            <td class="text-end">${parseFloat(m.yearly_price).toLocaleString('nb-NO')} NOK</td>
                            <td class="text-end"><strong class="text-success">${parseFloat(m.total_paid).toLocaleString('nb-NO')} NOK</strong></td>
                            <td class="text-end"><strong class="text-danger">${parseFloat(m.remaining).toLocaleString('nb-NO')} NOK</strong></td>
                            <td><span class="badge ${statusBadge}">${statusText}</span></td>
                            <td><small>${joinDate}</small></td>
                            <td><small>${expiryDate}</small></td>
                            <td><small>${lastPayment}</small></td>
                            <td class="text-center">${m.payment_count || 0}</td>
                            <td class="text-center">${m.auto_renew ? '<i class="bi bi-check-circle text-success"></i>' : '<i class="bi bi-x-circle text-muted"></i>'}</td>
                            <td><span class="badge ${memberStatusBadge}">${memberStatusText}</span></td>
                        </tr>
                    `;
                }).join('');
            } else {
                memberList.innerHTML = `
                    <tr>
                        <td colspan="18" class="text-center text-muted py-3">
                            <i class="bi bi-inbox me-2"></i>Ingen medlemmer funnet for valgt filter
                        </td>
                    </tr>
                `;
            }
        } else {
            console.error('Error loading membership income:', data.message);
        }
    } catch (error) {
        console.error('Error loading membership income:', error);
    }
}

document.querySelectorAll('input[name="payment-filter"]').forEach(radio => {
    radio.addEventListener('change', function() {
        loadMembershipIncome(this.value);
    });
});

// Load income when income subtab is shown
const incomeTabLink = document.querySelector('a[href="#membership-income"]');
if (incomeTabLink) {
    incomeTabLink.addEventListener('click', function() {
        setTimeout(() => {
            console.log('Income subtab clicked - loading data');
            loadMembershipIncome();
            loadMonthlyPayments();
        }, 100);
    });
}

// Monthly payments tracking
let currentPaymentYear = new Date().getFullYear();

function changePaymentYear(delta) {
    currentPaymentYear += delta;
    document.getElementById('payment-year-display').textContent = currentPaymentYear;
    loadMonthlyPayments();
}

const monthNames = ['Januar', 'Februar', 'Mars', 'April', 'Mai', 'Juni', 
                    'Juli', 'August', 'September', 'Oktober', 'November', 'Desember'];

async function loadMonthlyPayments() {
    const grid = document.getElementById('monthly-payments-grid');
    grid.innerHTML = `<div class="col-12 text-center text-muted py-3">
        <div class="spinner-border spinner-border-sm me-2"></div>
        Laster månedlig oversikt...
    </div>`;
    
    try {
        const response = await fetch(`/api/get_monthly_membership_payments.php?year=${currentPaymentYear}`);
        const data = await response.json();
        
        if (data.success) {
            const currentMonth = new Date().getMonth();
            const currentYear = new Date().getFullYear();
            
            grid.innerHTML = data.months.map((month, index) => {
                const isPast = currentPaymentYear < currentYear || 
                              (currentPaymentYear === currentYear && index < currentMonth);
                const isCurrent = currentPaymentYear === currentYear && index === currentMonth;
                const isFuture = currentPaymentYear > currentYear || 
                                (currentPaymentYear === currentYear && index > currentMonth);
                
                let bgClass = 'bg-light';
                let textClass = '';
                let borderClass = '';
                
                if (isCurrent) {
                    bgClass = 'bg-primary';
                    textClass = 'text-white';
                    borderClass = 'border-primary border-3';
                } else if (isPast && month.paid_count > 0) {
                    bgClass = month.paid_amount >= month.expected_amount ? 'bg-success-subtle' : 'bg-warning-subtle';
                }
                
                const paidPercent = month.expected_amount > 0 
                    ? Math.min(100, Math.round((month.paid_amount / month.expected_amount) * 100))
                    : 0;
                
                return `
                    <div class="col-md-3 col-sm-4 col-6 mb-3">
                        <div class="card h-100 ${borderClass}" style="cursor: pointer;" onclick="showMonthDetails(${index + 1}, '${monthNames[index]}')">
                            <div class="card-header ${bgClass} ${textClass} py-2">
                                <strong>${monthNames[index]}</strong>
                                ${isCurrent ? '<span class="badge bg-light text-primary ms-2">Nå</span>' : ''}
                            </div>
                            <div class="card-body py-2">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Forventet:</small>
                                    <strong>${month.expected_amount.toLocaleString('nb-NO')} NOK</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Innbetalt:</small>
                                    <strong class="text-success">${month.paid_amount.toLocaleString('nb-NO')} NOK</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <small class="text-muted">Gjenstår:</small>
                                    <strong class="${month.remaining_amount > 0 ? 'text-danger' : 'text-success'}">${month.remaining_amount.toLocaleString('nb-NO')} NOK</strong>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar ${paidPercent >= 100 ? 'bg-success' : paidPercent > 0 ? 'bg-warning' : 'bg-secondary'}" 
                                         style="width: ${paidPercent}%"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <span class="badge bg-info">${month.member_count} medl.</span>
                                    <span class="badge ${month.unpaid_count > 0 ? 'bg-danger' : 'bg-success'}">${month.unpaid_count} ubetalt</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            grid.innerHTML = `<div class="col-12 text-center text-danger py-3">
                <i class="bi bi-exclamation-circle me-2"></i>${data.message || 'Feil ved lasting av data'}
            </div>`;
        }
    } catch (error) {
        console.error('Error loading monthly payments:', error);
        grid.innerHTML = `<div class="col-12 text-center text-danger py-3">
            <i class="bi bi-exclamation-circle me-2"></i>Kunne ikke laste månedlig oversikt
        </div>`;
    }
}

async function showMonthDetails(monthNum, monthName) {
    try {
        const response = await fetch(`/api/get_monthly_membership_payments.php?year=${currentPaymentYear}&month=${monthNum}`);
        const data = await response.json();
        
        if (data.success && data.members) {
            let html = `
                <div class="modal fade" id="monthDetailsModal" tabindex="-1">
                    <div class="modal-dialog modal-xl">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title"><i class="bi bi-calendar3 me-2"></i>${monthName} ${currentPaymentYear} - Betalingsoversikt</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <small class="text-muted">Forventet</small>
                                                <h5 class="mb-0">${data.summary.expected.toLocaleString('nb-NO')} NOK</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-success text-white">
                                            <div class="card-body text-center">
                                                <small>Innbetalt</small>
                                                <h5 class="mb-0">${data.summary.paid.toLocaleString('nb-NO')} NOK</h5>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card ${data.summary.remaining > 0 ? 'bg-danger' : 'bg-success'} text-white">
                                            <div class="card-body text-center">
                                                <small>Gjenstår</small>
                                                <h5 class="mb-0">${data.summary.remaining.toLocaleString('nb-NO')} NOK</h5>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Navn</th>
                                                <th>E-post</th>
                                                <th>Telefon</th>
                                                <th>Medlemskap</th>
                                                <th class="text-end">Beløp</th>
                                                <th>Status</th>
                                                <th>Forfallsdato</th>
                                                <th>Handling</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            ${data.members.map(m => {
                                                const statusBadge = m.is_paid ? 'bg-success' : 'bg-danger';
                                                const statusText = m.is_paid ? 'Betalt' : 'Ikke betalt';
                                                return `
                                                    <tr>
                                                        <td><strong>${m.first_name} ${m.last_name}</strong></td>
                                                        <td><small>${m.email || '-'}</small></td>
                                                        <td><small>${m.phone || '-'}</small></td>
                                                        <td><span class="badge bg-info">${m.plan_name}</span></td>
                                                        <td class="text-end">${parseFloat(m.amount).toLocaleString('nb-NO')} NOK</td>
                                                        <td><span class="badge ${statusBadge}">${statusText}</span></td>
                                                        <td><small>${m.due_date || '-'}</small></td>
                                                        <td>
                                                            ${!m.is_paid ? `
                                                                <button class="btn btn-sm btn-success" onclick="registerPayment('${m.member_id}', '${m.first_name} ${m.last_name}', ${m.amount}, '${m.email}')" title="Registrer betaling">
                                                                    <i class="bi bi-cash-coin"></i>
                                                                </button>
                                                            ` : `
                                                                <i class="bi bi-check-circle text-success" title="Betalt"></i>
                                                            `}
                                                        </td>
                                                    </tr>
                                                `;
                                            }).join('')}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remove existing modal if any
            const existingModal = document.getElementById('monthDetailsModal');
            if (existingModal) existingModal.remove();
            
            document.body.insertAdjacentHTML('beforeend', html);
            const modal = new bootstrap.Modal(document.getElementById('monthDetailsModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Error loading month details:', error);
        alert('Kunne ikke laste månedsdetaljer');
    }
}

async function registerPayment(memberId, memberName, amount, email) {
    const confirmed = confirm(`Registrer betaling på ${amount.toLocaleString('nb-NO')} NOK for ${memberName}?`);
    if (!confirmed) return;
    
    try {
        const response = await fetch('/api/log_membership_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                member_id: memberId,
                customer_email: email,
                amount: amount,
                payment_method: 'vipps',
                notes: 'Manuelt registrert'
            })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Betaling registrert!');
            // Close modal and reload data
            const modal = bootstrap.Modal.getInstance(document.getElementById('monthDetailsModal'));
            if (modal) modal.hide();
            loadMonthlyPayments();
            loadMembershipIncome();
        } else {
            alert('Feil: ' + result.message);
        }
    } catch (error) {
        console.error('Error registering payment:', error);
        alert('Feil ved registrering av betaling');
    }
}
</script>

