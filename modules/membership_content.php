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
                <h5 class="card-title">Medlemsinntekter</h5>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Total omsetning</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Aktive medlemmer</small>
                                <h4 class="mb-0">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Denne måneden</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">I år</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
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
    
    try {
        const response = await fetch('/api/create_membership_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(planData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Medlemskapsplan opprettet!');
            bootstrap.Modal.getInstance(document.getElementById('createPlanModal')).hide();
            form.reset();
            loadMembershipPlans();
        } else {
            alert('❌ Feil: ' + (result.message || 'Kunne ikke opprette plan'));
        }
    } catch (error) {
        console.error('Error creating plan:', error);
        alert('❌ Feil ved opprettelse av plan');
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
                
                return `
                    <div class="col-md-4 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <h6>${plan.name}</h6>
                                <p class="text-muted small">${billingLabel}</p>
                                <h4 class="text-primary">${plan.price} NOK</h4>
                                ${plan.benefits ? '<div class="small text-muted mt-2">' + plan.benefits.split('\n').slice(0, 2).join('<br>') + '</div>' : ''}
                                <button class="btn btn-outline-primary btn-sm w-100 mt-2">Rediger</button>
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
        notes: formData.get('notes')
    };
    
    if (!memberData.first_name || !memberData.last_name || !memberData.email) {
        alert('⚠️ Vennligst fyll ut alle obligatoriske felt (fornavn, etternavn, e-post)');
        return;
    }
    
    try {
        const response = await fetch('../api/create_member.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(memberData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Medlem lagt til!');
            bootstrap.Modal.getInstance(document.getElementById('createMemberModal')).hide();
            form.reset();
            loadMembers();
        } else {
            alert('❌ Feil: ' + (result.message || 'Kunne ikke legge til medlem'));
        }
    } catch (error) {
        console.error('Error creating member:', error);
        alert('❌ Feil ved opprettelse av medlem');
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
            const statusBadge = member.status === 'active' ? 'bg-success' : 
                               member.status === 'expired' ? 'bg-danger' : 'bg-warning';
            const statusText = member.status === 'active' ? 'Aktiv' : 
                              member.status === 'expired' ? 'Utløpt' : 'Venter';
            
            return `
                <tr>
                    <td>${member.member_id}</td>
                    <td>${member.first_name}</td>
                    <td>${member.last_name}</td>
                    <td>${member.email}</td>
                    <td>${member.phone || '-'}</td>
                    <td>${member.address ? (member.address + ', ' + (member.poststed || '')) : '-'}</td>
                    <td>${member.membership_type || '-'}</td>
                    <td><span class="badge ${statusBadge}">${statusText}</span></td>
                    <td>${member.join_date || '-'}</td>
                    <td>${member.expiry_date || '-'}</td>
                    <td>
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

// Last inn medlemmer når medlemmer-tab vises
const memberTabLink = document.querySelector('a[href="#membership-members"]');
if (memberTabLink) {
    memberTabLink.addEventListener('click', function() {
        setTimeout(() => {
            loadMembers();
            loadPlansForFilter();
        }, 100);
    });
}

// Also load on initial page load if members tab is active
if (window.location.hash === '#membership-members') {
    loadMembers();
    loadPlansForFilter();
}

// Add filter event listeners
document.getElementById('member-search-input')?.addEventListener('input', applyMemberFilters);
document.getElementById('member-plan-filter')?.addEventListener('change', applyMemberFilters);
document.getElementById('member-status-filter')?.addEventListener('change', applyMemberFilters);

// Last inn planer for dropdown når modal åpnes
document.getElementById('createMemberModal')?.addEventListener('show.bs.modal', loadPlansForSelect);
</script>

