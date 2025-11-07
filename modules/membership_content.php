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

    <div id="membership-requests" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-person-plus me-2"></i>Medlemsforespørsler</h5>
                <p class="text-muted">Se og behandle innkommende medlemskapsforespørsler</p>
                
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" placeholder="Søk i forespørsler...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select">
                                <option value="">Alle medlemstyper</option>
                                <option value="standard">Standardmedlemskap</option>
                                <option value="premium">Premium</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select">
                                <option value="">Alle statuser</option>
                                <option value="pending">Venter</option>
                                <option value="approved">Godkjent</option>
                                <option value="rejected">Avvist</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dato</th>
                                <th>Fornavn</th>
                                <th>Etternavn</th>
                                <th>E-post</th>
                                <th>Telefon</th>
                                <th>Medlemstype</th>
                                <th>Status</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
                                    Ingen forespørsler å behandle
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    Nye medlemsforespørsler vises her når de mottas via nettside eller påmeldingsskjema
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
                                <small>Totalt innbetalt (i år)</small>
                                <h4 class="mb-0" id="income-paid-total">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <small>Gjenværende betaling</small>
                                <h4 class="mb-0" id="income-remaining-total">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <small>Aktive medlemmer</small>
                                <h4 class="mb-0" id="income-active-count">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Antall transaksjoner</small>
                                <h4 class="mb-0" id="income-transaction-count">0</h4>
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
            <div class="modal-header">
                <h5 class="modal-title">Legg til nytt medlem</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="create-member-form">
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
                    <div class="mb-3">
                        <label class="form-label">Adresse</label>
                        <input type="text" class="form-control" name="address">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Poststed</label>
                            <input type="text" class="form-control" name="poststed">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fødselsdato</label>
                            <input type="date" class="form-control" name="dateofbirth">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Medlemskapsplan</label>
                        <select class="form-select" name="membership_plan_id" id="member-plan-select">
                            <option value="">Ingen plan valgt</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bilde-URL</label>
                        <input type="url" class="form-control" name="image_url" placeholder="https://example.com/bilde.jpg">
                        <small class="text-muted">URL til profilbilde eller medlemsbilde</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notater</label>
                        <textarea class="form-control" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="submitMember()">
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
                <form id="create-plan-form">
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
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="submitMembershipPlan()">Opprett plan</button>
            </div>
        </div>
    </div>
</div>

<script>
async function submitMembershipPlan() {
    const form = document.getElementById('create-plan-form');
    const formData = new FormData(form);
    
    const planData = {
        name: formData.get('name'),
        description: formData.get('description'),
        price: parseFloat(formData.get('price')),
        billing_cycle: formData.get('billing_cycle'),
        benefits: formData.get('benefits')
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
                return `
                    <div class="col-md-4 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <h6>${plan.name}</h6>
                                <p class="text-muted small">${billingLabel}</p>
                                <h4 class="text-primary">${plan.price} NOK</h4>
                                ${plan.benefits ? '<div class="small text-muted mt-2">' + plan.benefits.split('\n').slice(0, 2).join('<br>') + '</div>' : ''}
                                <div class="d-flex gap-2 mt-2">
                                    <button class="btn btn-outline-primary btn-sm flex-fill">Rediger</button>
                                    <button class="btn btn-outline-danger btn-sm" onclick="deleteMembershipPlan('${planId}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
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
    }
}

// Last inn planer når medlemskap-tab vises
document.getElementById('tab-membership')?.addEventListener('shown.bs.tab', loadMembershipPlans);

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
        image_url: formData.get('image_url') || null
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
        
        const select = document.getElementById('member-plan-select');
        
        if (result.success && result.plans && result.plans.length > 0) {
            const options = result.plans.map(plan => 
                `<option value="${plan.plan_id}">${plan.name || plan.plan_name} - ${plan.price} NOK</option>`
            ).join('');
            select.innerHTML = '<option value="">Ingen plan valgt</option>' + options;
        }
    } catch (error) {
        console.error('Error loading plans for select:', error);
    }
}

function editMember(memberId) {
    alert('Redigering av medlem kommer snart!');
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
                <tr data-member-id="${member.member_id}">
                    <td><code>${shortRef}</code></td>
                    <td class="editable-member-cell" data-field="first_name">${member.first_name}</td>
                    <td class="editable-member-cell" data-field="last_name">${member.last_name}</td>
                    <td class="editable-member-cell" data-field="email">${member.email}</td>
                    <td class="editable-member-cell" data-field="phone">${member.phone || '-'}</td>
                    <td>${member.address ? (member.address + ', ' + (member.poststed || '')) : '-'}</td>
                    <td>${member.membership_type || '-'}</td>
                    <td><span class="badge ${statusBadge}">${statusText}</span></td>
                    <td>${member.join_date || '-'}</td>
                    <td>${member.expiry_date || '-'}</td>
                    <td>
                        <button class="btn btn-sm btn-success" onclick="renewMembership('${member.member_id}')" title="Forny medlemskap">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary" onclick="editMember(${member.member_id})">
                            <i class="bi bi-pencil"></i>
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
        }, 100);
    });
}
</script>

