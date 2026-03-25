<style>
.economy-stat-card {
    border-radius: 10px;
    padding: 1rem;
    transition: transform 0.2s;
}
.economy-stat-card:hover {
    transform: translateY(-2px);
}
.module-badge {
    font-size: 0.7rem;
    padding: 0.25rem 0.5rem;
}
.transaction-row {
    border-left: 3px solid transparent;
}
.transaction-row.income {
    border-left-color: #198754;
}
.transaction-row.expense {
    border-left-color: #dc3545;
}
.filter-btn.active {
    font-weight: 600;
}
</style>

<div class="module-content">
    <div id="economy-results" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Økonomisk oversikt</h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="loadEconomyOverview()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                
                <div class="row g-3 mb-4" id="overview-stats">
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-success text-white">
                            <small>Total inntekt</small>
                            <h4 class="mb-0" id="stat-total-income">-</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-danger text-white">
                            <small>Totale utgifter</small>
                            <h4 class="mb-0" id="stat-total-expenses">-</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-primary text-white">
                            <small>Netto resultat</small>
                            <h4 class="mb-0" id="stat-profit">-</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-info text-white">
                            <small>Denne måneden</small>
                            <h4 class="mb-0" id="stat-month">-</h4>
                        </div>
                    </div>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="mb-2"><i class="bi bi-calendar3 me-2"></i>Dette året</h6>
                                <div class="d-flex justify-content-between">
                                    <span class="text-success">Inntekt: <strong id="stat-year-income">-</strong></span>
                                    <span class="text-danger">Utgift: <strong id="stat-year-expenses">-</strong></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card bg-light">
                            <div class="card-body">
                                <h6 class="mb-2"><i class="bi bi-graph-up me-2"></i>Transaksjoner</h6>
                                <span>Totalt: <strong id="stat-count">-</strong> transaksjoner</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Siste transaksjoner</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Dato</th>
                                        <th>Beskrivelse</th>
                                        <th>Modul</th>
                                        <th>Betaling</th>
                                        <th class="text-end">Beløp</th>
                                    </tr>
                                </thead>
                                <tbody id="recent-transactions">
                                    <tr><td colspan="5" class="text-center py-3 text-muted">Laster...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="economy-income" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bi bi-arrow-down-circle text-success me-2"></i>Inntekter</h5>
                    <button class="btn btn-success btn-sm" onclick="showAddTransactionModal('income')">
                        <i class="bi bi-plus-lg me-1"></i>Ny inntekt
                    </button>
                </div>
                
                <div class="row g-3 mb-4" id="income-stats">
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-success text-white">
                            <small>Total inntekt</small>
                            <h4 class="mb-0" id="income-total">-</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-light">
                            <small class="text-muted">Denne måneden</small>
                            <h4 class="mb-0" id="income-month">-</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-light">
                            <small class="text-muted">Dette året</small>
                            <h4 class="mb-0" id="income-year">-</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-light">
                            <small class="text-muted">Antall</small>
                            <h4 class="mb-0" id="income-count">-</h4>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="mb-0">Inntektstransaksjoner</h6>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="date" class="form-control form-control-sm" id="income-start-date" style="width: 140px;">
                                <span>til</span>
                                <input type="date" class="form-control form-control-sm" id="income-end-date" style="width: 140px;">
                                <button class="btn btn-sm btn-outline-primary" onclick="loadIncomeData()">
                                    <i class="bi bi-funnel"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Dato</th>
                                        <th>Beskrivelse</th>
                                        <th>Kategori</th>
                                        <th>Betaling</th>
                                        <th class="text-end">Beløp</th>
                                    </tr>
                                </thead>
                                <tbody id="income-transactions">
                                    <tr><td colspan="5" class="text-center py-3 text-muted">Laster...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Fornyelser-seksjon -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Fornyelser</h6>
                    </div>
                    <div class="card-body">

                        <!-- Oppslagspanel -->
                        <div class="mb-3">
                            <label class="form-label fw-semibold small text-muted">Slå opp på order_id</label>
                            <div class="input-group input-group-sm" style="max-width:420px;">
                                <input type="text" class="form-control" id="renewal-lookup-input" placeholder="Skriv inn order_id…">
                                <button class="btn btn-outline-secondary" onclick="lookupRenewal()"><i class="bi bi-search"></i></button>
                            </div>
                            <div id="renewal-lookup-result" class="mt-3 p-3 border rounded bg-light" style="display:none; max-width:560px;">
                                <div class="mb-2">
                                    <span class="text-muted small">Navn</span>
                                    <div class="fw-semibold" id="rl-name">–</div>
                                </div>
                                <div class="row g-2 mb-2">
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Fornyelse status</label>
                                        <select class="form-select form-select-sm" id="rl-renewal-status">
                                            <option value="pending">pending</option>
                                            <option value="payment_pending">payment_pending</option>
                                            <option value="completed">completed</option>
                                            <option value="cancelled">cancelled</option>
                                            <option value="expired">expired</option>
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label small mb-1">Betalingsstatus (membership)</label>
                                        <select class="form-select form-select-sm" id="rl-payment-status">
                                            <option value="paid">paid</option>
                                            <option value="unpaid">unpaid</option>
                                            <option value="pending">pending</option>
                                            <option value="refunded">refunded</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label small mb-1">Utløpsdato (membership)</label>
                                    <input type="date" class="form-control form-control-sm" id="rl-expiration-date" style="max-width:200px;">
                                </div>
                                <input type="hidden" id="rl-renewal-id">
                                <input type="hidden" id="rl-member-id">
                                <div class="d-flex align-items-center gap-2">
                                    <button class="btn btn-sm btn-primary" onclick="saveRenewalLookup()">
                                        <i class="bi bi-floppy me-1"></i>Lagre
                                    </button>
                                    <span id="rl-save-msg" class="small"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Liste -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold small">Alle fornyelser</span>
                            <div class="input-group input-group-sm" style="max-width:300px;">
                                <input type="text" class="form-control" id="renewal-search" placeholder="Søk order_id…">
                                <button class="btn btn-outline-secondary" onclick="loadRenewalList(1)"><i class="bi bi-search"></i></button>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Navn</th>
                                        <th>Plan</th>
                                        <th class="text-end">Beløp</th>
                                        <th>Status</th>
                                        <th>Dato</th>
                                    </tr>
                                </thead>
                                <tbody id="renewal-list-body">
                                    <tr><td colspan="5" class="text-center text-muted py-3">Laster…</td></tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mt-2" id="renewal-pagination" style="display:none!important;">
                            <button class="btn btn-sm btn-outline-secondary" id="renewal-prev" onclick="loadRenewalList(renewalPage - 1)" disabled>&#8592;</button>
                            <span class="small text-muted" id="renewal-page-info">Side 1 av 1</span>
                            <button class="btn btn-sm btn-outline-secondary" id="renewal-next" onclick="loadRenewalList(renewalPage + 1)" disabled>&#8594;</button>
                        </div>

                    </div>
                </div>
                <!-- /Fornyelser-seksjon -->

            </div>
        </div>
    </div>

    <div id="economy-expense" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bi bi-arrow-up-circle text-danger me-2"></i>Utgifter</h5>
                    <button class="btn btn-danger btn-sm" onclick="showAddTransactionModal('expense')">
                        <i class="bi bi-plus-lg me-1"></i>Ny utgift
                    </button>
                </div>
                
                <div class="row g-3 mb-4" id="expense-stats">
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-danger text-white">
                            <small>Totale utgifter</small>
                            <h4 class="mb-0" id="expense-total">-</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-light">
                            <small class="text-muted">Denne måneden</small>
                            <h4 class="mb-0" id="expense-month">-</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-light">
                            <small class="text-muted">Dette året</small>
                            <h4 class="mb-0" id="expense-year">-</h4>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="economy-stat-card bg-light">
                            <small class="text-muted">Antall</small>
                            <h4 class="mb-0" id="expense-count">-</h4>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="mb-0">Utgiftstransaksjoner</h6>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="date" class="form-control form-control-sm" id="expense-start-date" style="width: 140px;">
                                <span>til</span>
                                <input type="date" class="form-control form-control-sm" id="expense-end-date" style="width: 140px;">
                                <button class="btn btn-sm btn-outline-primary" onclick="loadExpenseData()">
                                    <i class="bi bi-funnel"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Dato</th>
                                        <th>Beskrivelse</th>
                                        <th>Kategori</th>
                                        <th>Betaling</th>
                                        <th class="text-end">Beløp</th>
                                    </tr>
                                </thead>
                                <tbody id="expense-transactions">
                                    <tr><td colspan="5" class="text-center py-3 text-muted">Laster...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="economy-pending" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bi bi-hourglass-split text-warning me-2"></i>Ventende betalinger</h5>
                    <button class="btn btn-sm btn-outline-primary" onclick="loadPendingPayments()">
                        <i class="bi bi-arrow-clockwise"></i>
                    </button>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="economy-stat-card bg-warning text-dark">
                            <small>Ventende beløp</small>
                            <h4 class="mb-0" id="pending-total">-</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="economy-stat-card bg-light">
                            <small class="text-muted">Antall ventende</small>
                            <h4 class="mb-0" id="pending-count">-</h4>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0 py-2">
                            <small><i class="bi bi-info-circle me-1"></i>Disse transaksjonene venter på betalingsbekreftelse</small>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">Transaksjoner som venter på betaling</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Dato</th>
                                        <th>Beskrivelse</th>
                                        <th>Modul</th>
                                        <th class="text-end">Beløp</th>
                                        <th class="text-center">Handling</th>
                                    </tr>
                                </thead>
                                <tbody id="pending-transactions">
                                    <tr><td colspan="5" class="text-center py-3 text-muted">Laster...</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="economy-payment" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-credit-card-2-front me-2"></i>Betalingsløsninger</h5>
                <p class="text-muted">Administrer tilgjengelige betalingsmetoder</p>
                <div id="payment-gateways-container" class="row g-3"></div>
            </div>
        </div>
    </div>

    <div id="economy-terms" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-file-earmark-text me-2"></i>Kjøpsbetingelser</h5>
                <p class="text-muted">Administrer kjøpsbetingelser og vilkår</p>
                <button class="btn btn-primary mb-3" onclick="showCreateTermsModal()">
                    <i class="bi bi-plus-circle me-1"></i>Nye betingelser
                </button>
                <div class="table-responsive">
                    <table class="table table-sm" id="terms-table">
                        <thead><tr><th>Tittel</th><th>Versjon</th><th>Status</th><th>Handlinger</th></tr></thead>
                        <tbody><tr><td colspan="4" class="text-center py-3 text-muted">Ingen betingelser registrert</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="economy-invoices" class="subtab-content" style="display: none;">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center gap-2">
                <h5 class="mb-0"><i class="bi bi-receipt-cutoff me-1"></i>Fakturaer</h5>
                <span id="inv-badge-pending" class="badge bg-warning text-dark" style="display:none;"></span>
                <span id="inv-badge-overdue" class="badge bg-danger" style="display:none;"></span>
            </div>
            <button class="btn btn-primary btn-sm" onclick="ecToggleCreateForm()">
                <i class="bi bi-plus-lg me-1"></i>Ny faktura
            </button>
        </div>

        <div id="invoice-create-form" style="display:none;" class="card border-primary mb-3">
            <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-plus-circle me-1"></i>Ny faktura</span>
                <button type="button" class="btn-close btn-close-white btn-sm" onclick="ecToggleCreateForm()"></button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label form-label-sm fw-semibold">Fakturatype</label>
                        <select id="inv-type" class="form-select form-select-sm" onchange="ecToggleFormByType(this.value)">
                            <option value="new_member" selected>Nytt medlemskap</option>
                            <option value="renewal">Fornyelse</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-sm fw-semibold">Forfallsdato</label>
                        <input type="date" id="inv-due" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label form-label-sm fw-semibold">Bankkonto</label>
                        <select id="inv-bank-account" class="form-select form-select-sm">
                            <option value="">Velg konto...</option>
                        </select>
                    </div>
                </div>

                <hr class="my-2">

                <div id="inv-section-general">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Kunde</label>
                            <select id="inv-customer-general" class="form-select form-select-sm">
                                <option value="">Velg kunde...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Beløp (NOK)</label>
                            <input type="number" id="inv-manual-amount" class="form-control form-control-sm" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div id="inv-section-new-member" style="display:none;">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Kunde <small class="text-muted">(uten aktivt medlemskap)</small></label>
                            <select id="inv-customer" class="form-select form-select-sm" onchange="ecCustomerSelectChanged(this.value)">
                                <option value="">Velg kunde...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Medlemskapsplan</label>
                            <select id="inv-plan" class="form-select form-select-sm" onchange="ecUpdateAmountPreview()">
                                <option value="">Velg plan...</option>
                            </select>
                        </div>
                        <div id="inv-manual-customer-fields" class="col-12" style="display:none;">
                            <div class="row g-2 mt-0">
                                <div class="col-md-5">
                                    <label class="form-label form-label-sm">Fullt navn <span class="text-danger">*</span></label>
                                    <input type="text" id="inv-manual-name" class="form-control form-control-sm" placeholder="Ola Nordmann">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label form-label-sm">E-post <small class="text-muted">(valgfri)</small></label>
                                    <input type="email" id="inv-manual-email" class="form-control form-control-sm" placeholder="epost@eksempel.no">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label form-label-sm">Antall</label>
                            <input type="number" id="inv-qty" class="form-control form-control-sm" value="1" min="1" onchange="ecUpdateAmountPreview()">
                        </div>
                        <div class="col-md-4 d-flex align-items-end pb-1">
                            <span class="text-muted small me-1">Totalt:</span>
                            <span id="inv-amount-preview" class="fw-bold text-success"></span>
                        </div>
                    </div>
                </div>

                <div id="inv-section-renewal" style="display:none;">
                    <div class="row g-2">
                        <div class="col-md-7">
                            <label class="form-label form-label-sm">Velg kunde å fornye</label>
                            <select id="inv-customer-renewal" class="form-select form-select-sm" onchange="ecRenewalCustomerChanged(this.value)">
                                <option value="">Velg kunde...</option>
                            </select>
                        </div>
                    </div>
                    <div id="inv-renewal-info" class="mt-2" style="display:none;">
                        <div class="p-2 rounded bg-light border small">
                            <div class="row g-1 text-sm">
                                <div class="col-sm-4"><span class="text-muted">Kunde:</span> <strong id="inv-ri-name"></strong></div>
                                <div class="col-sm-4"><span class="text-muted">Plan:</span> <strong id="inv-ri-plan"></strong></div>
                                <div class="col-sm-4"><span class="text-muted">Beløp:</span> <strong id="inv-ri-amount"></strong></div>
                                <div class="col-sm-4"><span class="text-muted">Nåværende utløp:</span> <span id="inv-ri-expiry" class="text-danger fw-semibold"></span></div>
                                <div class="col-sm-4"><span class="text-muted">Nytt utløp:</span> <span id="inv-ri-new-expiry" class="text-success fw-semibold"></span></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-3">
                    <label class="form-label form-label-sm">Notat / betalingsbetingelser</label>
                    <textarea id="inv-notes" class="form-control form-control-sm" rows="2"></textarea>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-success btn-sm px-3" onclick="ecCreateInvoice()">
                        <i class="bi bi-check2-circle me-1"></i>Opprett faktura
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="ecToggleCreateForm()">Avbryt</button>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="fw-semibold text-dark"><i class="bi bi-hourglass-split text-warning me-1"></i>Ventende &amp; Forfalt</span>
                <span id="inv-active-count" class="badge bg-secondary">0</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Fakturanr</th>
                                <th>Type</th>
                                <th>Kunde</th>
                                <th>Beløp</th>
                                <th>Forfall</th>
                                <th>Status</th>
                                <th>Opprettet av</th>
                                <th style="width:160px"></th>
                            </tr>
                        </thead>
                        <tbody id="inv-active-body">
                            <tr><td colspan="8" class="text-center py-3 text-muted">Laster...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center py-2" role="button" onclick="ecTogglePaidList()" style="cursor:pointer;">
                <span class="fw-semibold text-dark"><i class="bi bi-check-circle text-success me-1"></i>Betalte fakturaer</span>
                <div class="d-flex align-items-center gap-2">
                    <span id="inv-paid-count" class="badge bg-success">0</span>
                    <i class="bi bi-chevron-down" id="inv-paid-chevron"></i>
                </div>
            </div>
            <div id="inv-paid-list" style="display:none;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Fakturanr</th>
                                <th>Type</th>
                                <th>Kunde</th>
                                <th>Beløp</th>
                                <th>Betalt</th>
                                <th>Opprettet av</th>
                            </tr>
                        </thead>
                        <tbody id="inv-paid-body">
                            <tr><td colspan="6" class="text-center py-3 text-muted">Ingen betalte fakturaer</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal fade" id="addTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTransactionModalTitle">Ny transaksjon</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="transactionForm">
                    <input type="hidden" id="transaction-type" value="income">
                    
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse *</label>
                        <input type="text" class="form-control" id="transaction-description" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Beløp (NOK) *</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="transaction-amount" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Kategori</label>
                        <select class="form-select" id="transaction-category">
                            <option value="general">Generelt</option>
                            <option value="sales">Salg</option>
                            <option value="events">Arrangementer</option>
                            <option value="membership">Medlemskap</option>
                            <option value="rental">Utleie</option>
                            <option value="reservations">Reservasjoner</option>
                            <option value="services">Tjenester</option>
                            <option value="supplies">Forbruksvarer</option>
                            <option value="equipment">Utstyr</option>
                            <option value="marketing">Markedsføring</option>
                            <option value="rent">Husleie</option>
                            <option value="utilities">Strøm/vann</option>
                            <option value="salary">Lønn</option>
                            <option value="insurance">Forsikring</option>
                            <option value="maintenance">Vedlikehold</option>
                            <option value="other">Annet</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label" id="customer-label">Kunde/Leverandør</label>
                        <input type="text" class="form-control" id="transaction-customer">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Betalingsmetode</label>
                        <select class="form-select" id="transaction-payment-method">
                            <option value="cash">Kontant</option>
                            <option value="vipps">Vipps</option>
                            <option value="card">Kort</option>
                            <option value="bank">Bankoverføring</option>
                            <option value="invoice">Faktura</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Dato</label>
                        <input type="date" class="form-control" id="transaction-date">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notater</label>
                        <textarea class="form-control" id="transaction-notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" id="save-transaction-btn" onclick="saveTransaction()">Lagre</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bi bi-check-circle me-2"></i>Bekreft betaling</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="confirm-ledger-id">
                
                <div class="text-center mb-3">
                    <p class="mb-1"><strong id="confirm-item-name">-</strong></p>
                    <h3 class="text-success mb-0" id="confirm-amount">-</h3>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Betalingsmetode</label>
                    <select class="form-select" id="confirm-payment-method">
                        <option value="cash">Kontant</option>
                        <option value="vipps">Vipps</option>
                        <option value="card">Kort</option>
                        <option value="bank">Bankoverføring</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-success" onclick="confirmPayment()">
                    <i class="bi bi-check-lg me-1"></i>Bekreft
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="termsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalTitle">Nye kjøpsbetingelser</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="termsForm">
                    <input type="hidden" id="terms-id">
                    
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Tittel *</label>
                            <input type="text" class="form-control" id="terms-title" required placeholder="F.eks. Generelle kjøpsbetingelser">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Versjon</label>
                            <input type="text" class="form-control" id="terms-version" value="1.0" placeholder="1.0">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Innhold *</label>
                        <textarea class="form-control" id="terms-content" rows="10" required placeholder="Skriv inn kjøpsbetingelsene her..."></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ikrafttredelsesdato *</label>
                            <input type="date" class="form-control" id="terms-effective-date" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Aksepteringstype</label>
                            <select class="form-select" id="terms-acceptance-type">
                                <option value="checkbox">Avkrysningsboks</option>
                                <option value="signature">Digital signatur</option>
                                <option value="implicit">Implisitt aksept</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="terms-requires-acceptance" checked>
                        <label class="form-check-label" for="terms-requires-acceptance">
                            Krever kundeaksept før kjøp
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="saveTerms()">
                    <i class="bi bi-check-lg me-1"></i>Lagre
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="termsAcceptancesModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-people me-2"></i>Kunder som har akseptert</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Etternavn</th>
                                <th>Fornavn</th>
                                <th>E-post / Telefon</th>
                                <th>Akseptert</th>
                            </tr>
                        </thead>
                        <tbody id="acceptances-list">
                            <tr><td colspan="4" class="text-center py-3 text-muted">Laster...</td></tr>
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
const moduleLabels = {
    'sales': 'Salg',
    'events': 'Arrangementer',
    'membership': 'Medlemskap',
    'rental': 'Utleie',
    'reservations': 'Reservasjoner',
    'general': 'Generelt',
    'services': 'Tjenester',
    'manual': 'Manuell'
};

const paymentLabels = {
    'cash': 'Kontant',
    'vipps': 'Vipps',
    'card': 'Kort',
    'stripe': 'Kort',
    'bank': 'Bank',
    'invoice': 'Faktura',
    'paypal': 'PayPal'
};

function formatCurrency(amount) {
    return new Intl.NumberFormat('nb-NO', { style: 'currency', currency: 'NOK', maximumFractionDigits: 0 }).format(amount);
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    return new Date(dateStr).toLocaleDateString('nb-NO');
}

async function loadEconomyOverview() {
    try {
        const response = await fetch('/api/get_economy_data.php?view=overview');
        const result = await response.json();
        
        if (result.success && result.data) {
            const d = result.data;
            document.getElementById('stat-total-income').textContent = formatCurrency(d.total_income || 0);
            document.getElementById('stat-total-expenses').textContent = formatCurrency(d.total_expenses || 0);
            document.getElementById('stat-profit').textContent = formatCurrency(d.total_profit || 0);
            document.getElementById('stat-month').textContent = formatCurrency((d.month_income || 0) - (d.month_expenses || 0));
            document.getElementById('stat-year-income').textContent = formatCurrency(d.year_income || 0);
            document.getElementById('stat-year-expenses').textContent = formatCurrency(d.year_expenses || 0);
            document.getElementById('stat-count').textContent = d.transaction_count || 0;
            
            renderRecentTransactions(d.recent_transactions || []);
        } else {
            // Show zeros if no data or error
            document.getElementById('stat-total-income').textContent = formatCurrency(0);
            document.getElementById('stat-total-expenses').textContent = formatCurrency(0);
            document.getElementById('stat-profit').textContent = formatCurrency(0);
            document.getElementById('stat-month').textContent = formatCurrency(0);
            document.getElementById('stat-year-income').textContent = formatCurrency(0);
            document.getElementById('stat-year-expenses').textContent = formatCurrency(0);
            document.getElementById('stat-count').textContent = '0';
            console.log('Economy API response:', result);
        }
    } catch (error) {
        console.error('Load overview error:', error);
        // Show zeros on error
        document.getElementById('stat-total-income').textContent = formatCurrency(0);
        document.getElementById('stat-total-expenses').textContent = formatCurrency(0);
        document.getElementById('stat-profit').textContent = formatCurrency(0);
        document.getElementById('stat-month').textContent = formatCurrency(0);
    }
}

function renderRecentTransactions(transactions) {
    const tbody = document.getElementById('recent-transactions');
    
    if (!transactions.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Ingen transaksjoner funnet</td></tr>';
        return;
    }
    
    tbody.innerHTML = transactions.map(t => {
        const isIncome = parseFloat(t.selling_price) > 0;
        const amount = Math.abs(parseFloat(t.selling_price));
        return `
            <tr class="transaction-row ${isIncome ? 'income' : 'expense'}">
                <td>${formatDate(t.transaction_date)}</td>
                <td>${t.item_name || '-'}</td>
                <td><span class="badge bg-secondary module-badge">${moduleLabels[t.module_source] || t.module_source}</span></td>
                <td>${paymentLabels[t.payment_method] || t.payment_method || '-'}</td>
                <td class="text-end ${isIncome ? 'text-success' : 'text-danger'}">
                    ${isIncome ? '+' : '-'}${formatCurrency(amount)}
                </td>
            </tr>
        `;
    }).join('');
}

async function loadIncomeData() {
    const startDate = document.getElementById('income-start-date')?.value;
    const endDate = document.getElementById('income-end-date')?.value;
    
    let url = '/api/get_economy_data.php?view=income';
    if (startDate && endDate) {
        url += `&start_date=${startDate}&end_date=${endDate}`;
    }
    
    try {
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success && result.data) {
            const d = result.data;
            document.getElementById('income-total').textContent = formatCurrency(d.total || 0);
            document.getElementById('income-count').textContent = d.count || 0;
            
            renderIncomeTransactions(d.transactions || []);
        } else {
            document.getElementById('income-total').textContent = formatCurrency(0);
            document.getElementById('income-count').textContent = '0';
            renderIncomeTransactions([]);
        }
    } catch (error) {
        console.error('Load income error:', error);
        document.getElementById('income-total').textContent = formatCurrency(0);
        document.getElementById('income-count').textContent = '0';
    }
    
    try {
        const overviewRes = await fetch('/api/get_economy_data.php?view=overview');
        const overviewData = await overviewRes.json();
        if (overviewData.success && overviewData.data) {
            document.getElementById('income-month').textContent = formatCurrency(overviewData.data.month_income || 0);
            document.getElementById('income-year').textContent = formatCurrency(overviewData.data.year_income || 0);
        } else {
            document.getElementById('income-month').textContent = formatCurrency(0);
            document.getElementById('income-year').textContent = formatCurrency(0);
        }
    } catch (e) {
        document.getElementById('income-month').textContent = formatCurrency(0);
        document.getElementById('income-year').textContent = formatCurrency(0);
    }
}

function renderIncomeTransactions(transactions) {
    const tbody = document.getElementById('income-transactions');
    
    if (!transactions.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Ingen inntekter registrert</td></tr>';
        return;
    }
    
    tbody.innerHTML = transactions.map(t => {
        const amount = t.vipps_adjusted
            ? t.adjusted_amount
            : (t.adjusted_amount ?? t.selling_price);
        const feeNote = t.vipps_adjusted
            ? ` <span class="text-muted small" title="Brutto: ${formatCurrency(t.gross_amount)} — Vipps-gebyr: ${formatCurrency(t.fee_amount)}" style="cursor:help">(etter gebyr)</span>`
            : '';
        return `
        <tr class="transaction-row income">
            <td>${formatDate(t.transaction_date)}</td>
            <td>${t.item_name || '-'}</td>
            <td><span class="badge bg-secondary module-badge">${moduleLabels[t.category] || t.category}</span></td>
            <td>${paymentLabels[t.payment_method] || t.payment_method || '-'}</td>
            <td class="text-end text-success">+${formatCurrency(amount)}${feeNote}</td>
        </tr>`;
    }).join('');
}

async function loadExpenseData() {
    const startDate = document.getElementById('expense-start-date')?.value;
    const endDate = document.getElementById('expense-end-date')?.value;
    
    let url = '/api/get_economy_data.php?view=expense';
    if (startDate && endDate) {
        url += `&start_date=${startDate}&end_date=${endDate}`;
    }
    
    try {
        const response = await fetch(url);
        const result = await response.json();
        
        if (result.success && result.data) {
            const d = result.data;
            document.getElementById('expense-total').textContent = formatCurrency(d.total || 0);
            document.getElementById('expense-count').textContent = d.count || 0;
            
            renderExpenseTransactions(d.transactions || []);
        } else {
            document.getElementById('expense-total').textContent = formatCurrency(0);
            document.getElementById('expense-count').textContent = '0';
            renderExpenseTransactions([]);
        }
    } catch (error) {
        console.error('Load expense error:', error);
        document.getElementById('expense-total').textContent = formatCurrency(0);
        document.getElementById('expense-count').textContent = '0';
    }
    
    try {
        const overviewRes = await fetch('/api/get_economy_data.php?view=overview');
        const overviewData = await overviewRes.json();
        if (overviewData.success && overviewData.data) {
            document.getElementById('expense-month').textContent = formatCurrency(overviewData.data.month_expenses || 0);
            document.getElementById('expense-year').textContent = formatCurrency(overviewData.data.year_expenses || 0);
        } else {
            document.getElementById('expense-month').textContent = formatCurrency(0);
            document.getElementById('expense-year').textContent = formatCurrency(0);
        }
    } catch (e) {
        document.getElementById('expense-month').textContent = formatCurrency(0);
        document.getElementById('expense-year').textContent = formatCurrency(0);
    }
}

function renderExpenseTransactions(transactions) {
    const tbody = document.getElementById('expense-transactions');
    
    if (!transactions.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3 text-muted">Ingen utgifter registrert</td></tr>';
        return;
    }
    
    tbody.innerHTML = transactions.map(t => `
        <tr class="transaction-row expense">
            <td>${formatDate(t.transaction_date)}</td>
            <td>${t.item_name || '-'}</td>
            <td><span class="badge bg-secondary module-badge">${moduleLabels[t.category] || t.category}</span></td>
            <td>${paymentLabels[t.payment_method] || t.payment_method || '-'}</td>
            <td class="text-end text-danger">-${formatCurrency(t.amount)}</td>
        </tr>
    `).join('');
}

function showAddTransactionModal(type) {
    document.getElementById('transaction-type').value = type;
    document.getElementById('transactionForm').reset();
    
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('transaction-date').value = today;
    
    if (type === 'income') {
        document.getElementById('addTransactionModalTitle').textContent = 'Ny inntekt';
        document.getElementById('customer-label').textContent = 'Kunde';
        document.getElementById('save-transaction-btn').className = 'btn btn-success';
    } else {
        document.getElementById('addTransactionModalTitle').textContent = 'Ny utgift';
        document.getElementById('customer-label').textContent = 'Leverandør';
        document.getElementById('save-transaction-btn').className = 'btn btn-danger';
    }
    
    new bootstrap.Modal(document.getElementById('addTransactionModal')).show();
}

async function saveTransaction() {
    const type = document.getElementById('transaction-type').value;
    const description = document.getElementById('transaction-description').value.trim();
    const amount = parseFloat(document.getElementById('transaction-amount').value);
    
    if (!description || !amount || amount <= 0) {
        alert('Beskrivelse og beløp er påkrevd');
        return;
    }
    
    const data = {
        type: type,
        item_name: description,
        amount: amount,
        category: document.getElementById('transaction-category').value,
        customer_name: document.getElementById('transaction-customer').value.trim(),
        payment_method: document.getElementById('transaction-payment-method').value,
        transaction_date: document.getElementById('transaction-date').value || new Date().toISOString().split('T')[0],
        notes: document.getElementById('transaction-notes').value.trim()
    };
    
    try {
        const response = await fetch('/api/add_manual_transaction.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('addTransactionModal')).hide();
            loadEconomyOverview();
            if (type === 'income') loadIncomeData();
            else loadExpenseData();
        } else {
            alert('Feil: ' + result.message);
        }
    } catch (error) {
        console.error('Save transaction error:', error);
        alert('Kunne ikke lagre transaksjon');
    }
}

const gatewayDescriptions = {
    'cash':    'Kontant betaling',
    'vipps':   'Norsk mobilbetaling',
    'stripe':  'Kort og nettbetaling',
    'paypal':  'Internasjonal betaling',
    'klarna':  'Betal senere',
    'bank':    'Bankoverføring',
    'faktura': 'Fakturabetaling via bankkonto'
};

async function loadPaymentGateways() {
    const container = document.getElementById('payment-gateways-container');
    if (!container) return;
    
    container.innerHTML = '<div class="col-12 text-center py-4"><div class="spinner-border text-primary"></div></div>';
    
    try {
        const response = await fetch('/api/get_payment_gateways.php');
        const result = await response.json();
        
        if (!result.success || !result.gateways) {
            container.innerHTML = '<div class="col-12"><div class="alert alert-warning">Kunne ikke laste betalingsløsninger</div></div>';
            return;
        }
        
        container.innerHTML = result.gateways.map(gw => {
            const isActive = gw.is_active;
            const isConfigured = gw.is_configured;
            const testMode = gw.test_mode;
            const gwId = gw.id || null;
            const gwName = gw.gateway_name || gw.gateway_type;
            const desc = gatewayDescriptions[gw.gateway_type || gwName] || '';
            
            let statusHtml = '';
            let actionBtn = '';
            
            if (isActive) {
                statusHtml = '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aktiv</span>';
                if (testMode && gwName !== 'cash') {
                    statusHtml += ' <span class="badge bg-warning text-dark">Testmodus</span>';
                }
                actionBtn = `<button class="btn btn-outline-danger btn-sm w-100" onclick="toggleGateway('${gwId}', '${gwName}', false)">
                    <i class="bi bi-toggle-on me-1"></i>Deaktiver
                </button>`;
            } else if (isConfigured) {
                statusHtml = '<span class="badge bg-info">Konfigurert</span>';
                actionBtn = `<button class="btn btn-success btn-sm w-100" onclick="toggleGateway('${gwId}', '${gwName}', true)">
                    <i class="bi bi-toggle-off me-1"></i>Aktiver
                </button>`;
            } else {
                statusHtml = '<span class="badge bg-secondary">Ikke konfigurert</span>';
                actionBtn = `<button class="btn btn-outline-secondary btn-sm w-100" disabled>
                    <i class="bi bi-lock me-1"></i>Mangler oppsett
                </button>`;
            }
            
            const hasFees = ['vipps','stripe','paypal','klarna'].includes(gw.gateway_type);
            const feeFixed   = (gw.fee_fixed   || 0).toFixed(2);
            const feePercent = (gw.fee_percent || 0).toFixed(2);
            const feePreview = (parseFloat(feeFixed) + 100 * parseFloat(feePercent) / 100).toFixed(2);

            const fakturaSection = gw.gateway_type === 'faktura' ? `
                <hr class="my-2">
                <p class="small fw-semibold text-muted mb-1"><i class="bi bi-bank me-1"></i>Bankkontoer</p>
                ${gw.bank_accounts && gw.bank_accounts.length > 0
                    ? gw.bank_accounts.map(ba => `
                        <div class="small text-dark mb-1">
                            <span class="fw-semibold">${ba.bank_name || 'Bankkonto'}</span>
                            <span class="text-muted ms-1">${ba.account_number}</span>
                        </div>`).join('')
                    : `<div class="small text-muted fst-italic">Ingen bankkontoer registrert ennå.<br>Legg til i Bankkontoer-seksjonen.</div>`
                }
            ` : '';

            const feeSection = hasFees ? `
                <hr class="my-2">
                <p class="small fw-semibold text-muted mb-1">Transaksjonsgebyr</p>
                <div class="row g-1 mb-1">
                    <div class="col-6">
                        <label class="form-label form-label-sm mb-0 text-muted" style="font-size:0.72rem">Fast gebyr (kr)</label>
                        <input type="number" id="fee-fixed-${gwId}" class="form-control form-control-sm"
                               value="${feeFixed}" min="0" step="0.01"
                               oninput="gwFeePreview('${gwId}')">
                    </div>
                    <div class="col-6">
                        <label class="form-label form-label-sm mb-0 text-muted" style="font-size:0.72rem">Prosent (%)</label>
                        <input type="number" id="fee-pct-${gwId}" class="form-control form-control-sm"
                               value="${feePercent}" min="0" max="100" step="0.01"
                               oninput="gwFeePreview('${gwId}')">
                    </div>
                </div>
                <div id="fee-preview-${gwId}" class="small text-muted mb-1">Gebyr på 100 kr: <strong>${feePreview} kr</strong></div>
                <button class="btn btn-outline-secondary btn-sm w-100" onclick="saveGatewayFees('${gwId}', this)">
                    <i class="bi bi-floppy me-1"></i>Lagre gebyr
                </button>
                <div id="fee-msg-${gwId}" class="small mt-1" style="min-height:1.2em"></div>
            ` : '';

            return `
                <div class="col-md-4 col-sm-6">
                    <div class="card h-100 ${isActive ? 'border-success' : ''}">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-2">
                                <i class="bi bi-${gw.icon || 'credit-card'} fs-4 me-2 ${isActive ? 'text-success' : 'text-muted'}"></i>
                                <h6 class="mb-0">${gw.display_name}</h6>
                            </div>
                            <p class="small text-muted mb-2">${desc}</p>
                            <div class="mb-2">${statusHtml}</div>
                            ${actionBtn}
                            ${fakturaSection}
                            ${feeSection}
                        </div>
                    </div>
                </div>
            `;
        }).join('');
        
    } catch (error) {
        console.error('Load gateways error:', error);
        container.innerHTML = '<div class="col-12"><div class="alert alert-danger">Feil ved lasting</div></div>';
    }
}

async function toggleGateway(gatewayId, gatewayName, activate) {
    try {
        const payload = { gateway_name: gatewayName, is_active: activate };
        if (gatewayId && gatewayId !== 'null') payload.id = gatewayId;
        
        const response = await fetch('/api/toggle_payment_gateway.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        
        const result = await response.json();
        if (result.success) {
            loadPaymentGateways();
        } else {
            alert('Feil: ' + (result.message || 'Kunne ikke oppdatere'));
        }
    } catch (error) {
        console.error('Toggle gateway error:', error);
    }
}

function gwFeePreview(gwId) {
    const fixed   = parseFloat(document.getElementById('fee-fixed-' + gwId)?.value) || 0;
    const pct     = parseFloat(document.getElementById('fee-pct-'   + gwId)?.value) || 0;
    const preview = document.getElementById('fee-preview-' + gwId);
    if (preview) {
        const cost = (fixed + 100 * pct / 100).toFixed(2);
        preview.innerHTML = `Gebyr på 100 kr: <strong>${cost} kr</strong>`;
    }
}

async function saveGatewayFees(gwId, btn) {
    const fixed   = parseFloat(document.getElementById('fee-fixed-' + gwId)?.value);
    const pct     = parseFloat(document.getElementById('fee-pct-'   + gwId)?.value);
    const msgEl   = document.getElementById('fee-msg-' + gwId);

    if (isNaN(fixed) || isNaN(pct) || fixed < 0 || pct < 0 || pct > 100) {
        if (msgEl) { msgEl.textContent = 'Ugyldige verdier'; msgEl.className = 'small mt-1 text-danger'; }
        return;
    }

    const origHtml  = btn.innerHTML;
    btn.disabled    = true;
    btn.innerHTML   = '<span class="spinner-border spinner-border-sm"></span>';

    try {
        const res  = await fetch('/api/save_gateway_fees.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({gateway_id: gwId, fee_fixed: fixed, fee_percent: pct})
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);
        if (msgEl) { msgEl.textContent = 'Lagret'; msgEl.className = 'small mt-1 text-success'; }
        setTimeout(() => { if (msgEl) msgEl.textContent = ''; }, 3000);
    } catch (err) {
        if (msgEl) { msgEl.textContent = 'Feil: ' + err.message; msgEl.className = 'small mt-1 text-danger'; }
    } finally {
        btn.disabled  = false;
        btn.innerHTML = origHtml;
    }
}

async function loadPendingPayments() {
    try {
        const response = await fetch('/api/get_pending_payments.php');
        const result = await response.json();
        
        if (result.success) {
            document.getElementById('pending-total').textContent = formatCurrency(result.total_pending);
            document.getElementById('pending-count').textContent = result.count;
            renderPendingTransactions(result.transactions || []);
        }
    } catch (error) {
        console.error('Load pending error:', error);
    }
}

function renderPendingTransactions(transactions) {
    const tbody = document.getElementById('pending-transactions');
    
    if (!transactions.length) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 text-muted"><i class="bi bi-check-circle text-success me-2"></i>Ingen ventende betalinger</td></tr>';
        return;
    }
    
    tbody.innerHTML = transactions.map(t => `
        <tr>
            <td>${formatDate(t.transaction_date)}</td>
            <td>${t.item_name || '-'}</td>
            <td><span class="badge bg-secondary module-badge">${moduleLabels[t.module_source] || t.module_source}</span></td>
            <td class="text-end text-warning fw-bold">${formatCurrency(t.selling_price)}</td>
            <td class="text-center">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-success" onclick="showConfirmPaymentModal('${t.id}', '${t.item_name}', ${t.selling_price})" title="Bekreft betaling">
                        <i class="bi bi-check-lg"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="cancelPendingPayment('${t.id}')" title="Kanseller">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

function showConfirmPaymentModal(ledgerId, itemName, amount) {
    document.getElementById('confirm-ledger-id').value = ledgerId;
    document.getElementById('confirm-item-name').textContent = itemName;
    document.getElementById('confirm-amount').textContent = formatCurrency(amount);
    document.getElementById('confirm-payment-method').value = 'cash';
    new bootstrap.Modal(document.getElementById('confirmPaymentModal')).show();
}

async function confirmPayment() {
    const ledgerId = document.getElementById('confirm-ledger-id').value;
    const paymentMethod = document.getElementById('confirm-payment-method').value;
    
    try {
        const response = await fetch('/api/confirm_pending_payment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ ledger_id: ledgerId, payment_method: paymentMethod })
        });
        
        const result = await response.json();
        
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('confirmPaymentModal')).hide();
            loadPendingPayments();
            loadEconomyOverview();
        } else {
            alert('Feil: ' + result.message);
        }
    } catch (error) {
        console.error('Confirm payment error:', error);
        alert('Kunne ikke bekrefte betaling');
    }
}

async function cancelPendingPayment(ledgerId) {
    if (!confirm('Er du sikker på at du vil kansellere denne transaksjonen?')) return;
    
    try {
        const response = await fetch('/api/cancel_pending_payment.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ ledger_id: ledgerId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadPendingPayments();
        } else {
            alert('Feil: ' + result.message);
        }
    } catch (error) {
        console.error('Cancel payment error:', error);
    }
}

document.addEventListener('subtab-changed', function(event) {
    if (event.detail.module !== 'economy') return;
    
    const subtab = event.detail.subtab;
    if (subtab === 'economy-results') loadEconomyOverview();
    else if (subtab === 'economy-income') { loadIncomeData(); loadRenewalList(1); }
    else if (subtab === 'economy-expense') loadExpenseData();
    else if (subtab === 'economy-pending') loadPendingPayments();
    else if (subtab === 'economy-payment') loadPaymentGateways();
    else if (subtab === 'economy-terms') loadTermsConditions();
    else if (subtab === 'economy-invoices') ecLoadInvoices();
});

let ecAllInvoices = [];
let ecPlanData    = [];
let ecRenewalData = [];
let ecInvConfig   = {};
let ecActiveTerms = '';

async function ecLoadInvoices() {
    await Promise.all([ecPopulateCustomers(), ecPopulatePlans(), ecPopulateBankAccounts()]);
    try {
        const [invRes, termsRes] = await Promise.all([
            fetch('/api/get_invoices.php'),
            fetch('/api/get_terms_conditions.php')
        ]);
        const data      = await invRes.json();
        const termsData = await termsRes.json();
        if (!data.success) throw new Error(data.error);
        ecAllInvoices = data.invoices || [];
        ecInvConfig   = data.invoice_config || {};
        if (termsData.success && termsData.data && termsData.data.length > 0) {
            ecActiveTerms = termsData.data[0].content || '';
        }
        ecApplyConfig();
        ecRenderBothLists();
    } catch (err) {
        document.getElementById('inv-active-body').innerHTML =
            `<tr><td colspan="8" class="text-center text-danger py-3">${err.message}</td></tr>`;
    }
}

function ecApplyConfig() {
    if (ecInvConfig.invoice_due_days) {
        const due = new Date();
        due.setDate(due.getDate() + parseInt(ecInvConfig.invoice_due_days));
        const el = document.getElementById('inv-due');
        if (el) el.value = due.toISOString().split('T')[0];
    }
    if (ecInvConfig.invoice_terms) {
        const el = document.getElementById('inv-notes');
        if (el && !el.value) el.value = ecInvConfig.invoice_terms;
    }
}

function ecInvoiceTypeBadge(type) {
    if (type === 'new_member') return '<span class="badge bg-primary"><i class="bi bi-person-plus me-1"></i>Nytt</span>';
    if (type === 'renewal')    return '<span class="badge" style="background:#6f42c1"><i class="bi bi-arrow-repeat me-1"></i>Fornyelse</span>';
    return '<span class="badge bg-secondary">Generell</span>';
}

function ecStatusBadge(inv) {
    if (inv.computed_overdue) return '<span class="badge bg-danger">Forfalt</span>';
    if (inv.status === 'paid') return '<span class="badge bg-success">Betalt</span>';
    return '<span class="badge bg-warning text-dark">Ventende</span>';
}

function ecFmtDate(d) {
    if (!d) return '—';
    const parts = d.split('-');
    return parts.length === 3 ? `${parts[2]}.${parts[1]}.${parts[0]}` : d;
}

function ecFmtAmount(amount, currency) {
    return parseFloat(amount || 0).toLocaleString('nb-NO', {minimumFractionDigits: 2}) + ' ' + (currency || 'NOK');
}

function ecRenderBothLists() {
    const active = ecAllInvoices.filter(i => i.status !== 'paid');
    const paid   = ecAllInvoices.filter(i => i.status === 'paid');

    const pendingCount  = active.filter(i => !i.computed_overdue).length;
    const overdueCount  = active.filter(i =>  i.computed_overdue).length;

    const badgePending = document.getElementById('inv-badge-pending');
    const badgeOverdue = document.getElementById('inv-badge-overdue');
    if (pendingCount > 0) { badgePending.textContent = pendingCount + ' ventende'; badgePending.style.display = ''; }
    else badgePending.style.display = 'none';
    if (overdueCount > 0) { badgeOverdue.textContent = overdueCount + ' forfalt'; badgeOverdue.style.display = ''; }
    else badgeOverdue.style.display = 'none';

    document.getElementById('inv-active-count').textContent = active.length;
    document.getElementById('inv-paid-count').textContent   = paid.length;

    const activeBody = document.getElementById('inv-active-body');
    if (!active.length) {
        activeBody.innerHTML = '<tr><td colspan="8" class="text-center py-3 text-muted small">Ingen ventende eller forfalte fakturaer</td></tr>';
    } else {
        activeBody.innerHTML = active.map(inv => {
            const rowClass = inv.computed_overdue ? 'table-danger' : '';
            return `<tr class="${rowClass}" id="inv-row-${inv.invoice_id}">
                <td class="ps-3 fw-semibold">${inv.invoice_number}</td>
                <td>${ecInvoiceTypeBadge(inv.invoice_type || 'general')}</td>
                <td>${inv.customer_name || '<span class="text-muted">—</span>'}</td>
                <td>${ecFmtAmount(inv.total_amount, inv.currency)}</td>
                <td>${ecFmtDate(inv.due_date)}</td>
                <td>${ecStatusBadge(inv)}</td>
                <td><span class="text-muted small">${inv.created_by_username || '—'}</span></td>
                <td class="text-end pe-2">
                    <button class="btn btn-success btn-sm me-1" onclick="ecMarkPaid('${inv.invoice_id}', this)">
                        <i class="bi bi-check2 me-1"></i>Betalt
                    </button>
                    <button class="btn btn-outline-danger btn-sm" onclick="ecVoidInvoice('${inv.invoice_id}', this)" title="Ugyldiggjør">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </td>
            </tr>`;
        }).join('');
    }

    const paidBody = document.getElementById('inv-paid-body');
    if (!paid.length) {
        paidBody.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted small">Ingen betalte fakturaer</td></tr>';
    } else {
        paidBody.innerHTML = paid.map(inv => `<tr>
            <td class="ps-3 fw-semibold">${inv.invoice_number}</td>
            <td>${ecInvoiceTypeBadge(inv.invoice_type || 'general')}</td>
            <td>${inv.customer_name || '<span class="text-muted">—</span>'}</td>
            <td>${ecFmtAmount(inv.total_amount, inv.currency)}</td>
            <td>${ecFmtDate(inv.paid_at || inv.invoice_date)}</td>
            <td><span class="text-muted small">${inv.created_by_username || '—'}</span></td>
        </tr>`).join('');
    }
}

function ecTogglePaidList() {
    const list    = document.getElementById('inv-paid-list');
    const chevron = document.getElementById('inv-paid-chevron');
    const isHidden = list.style.display === 'none';
    list.style.display    = isHidden ? 'block' : 'none';
    chevron.className     = isHidden ? 'bi bi-chevron-up' : 'bi bi-chevron-down';
}

function ecToggleCreateForm() {
    const form     = document.getElementById('invoice-create-form');
    const isHidden = form.style.display === 'none';
    form.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        document.getElementById('inv-type').value = 'new_member';
        ecToggleFormByType('new_member');
        document.getElementById('inv-notes').value = ecActiveTerms || '';
        document.getElementById('inv-manual-customer-fields').style.display = 'none';
        ecApplyConfig();
    }
}

function ecToggleFormByType(type) {
    document.getElementById('inv-section-general').style.display    = type === 'general'    ? 'block' : 'none';
    document.getElementById('inv-section-new-member').style.display = type === 'new_member' ? 'block' : 'none';
    document.getElementById('inv-section-renewal').style.display    = type === 'renewal'    ? 'block' : 'none';
}

async function ecPopulateCustomers() {
    try {
        const [resAll, resNew, resRenewal] = await Promise.all([
            fetch('/api/get_customers_simple.php'),
            fetch('/api/get_customers_simple.php?context=new_member'),
            fetch('/api/get_customers_simple.php?context=renewal')
        ]);
        const dataAll     = await resAll.json();
        const dataNew     = await resNew.json();
        const dataRenewal = await resRenewal.json();

        const makeOpts = (list, label) => list && list.length
            ? '<option value="">Velg kunde...</option>' + list.map(c =>
                `<option value="${c.id}">${c.name}${c.email ? ' — ' + c.email : ''}</option>`).join('')
            : `<option value="">Ingen ${label} funnet</option>`;

        document.getElementById('inv-customer-general').innerHTML = makeOpts(dataAll.customers, 'kunder');

        const newMemberOpts = '<option value="">Velg kunde...</option>'
            + '<option value="__manual__">— Manuell utfylling —</option>'
            + (dataNew.customers && dataNew.customers.length
                ? dataNew.customers.map(c =>
                    `<option value="${c.id}">${c.name}${c.email ? ' — ' + c.email : ''}</option>`).join('')
                : '');
        document.getElementById('inv-customer').innerHTML = newMemberOpts;

        ecRenewalData = dataRenewal.customers || [];
        const renewalSel = document.getElementById('inv-customer-renewal');
        renewalSel.innerHTML = ecRenewalData.length
            ? '<option value="">Velg kunde...</option>' + ecRenewalData.map(c => {
                const expiry = c.expiration_date ? ' — utløper ' + ecFmtDate(c.expiration_date) : '';
                return `<option value="${c.id}">${c.name}${c.email ? ' (' + c.email + ')' : ''}${expiry}</option>`;
              }).join('')
            : '<option value="">Ingen kunder med eksisterende medlemskap funnet</option>';

    } catch (e) {
        const err = '<option value="">Feil ved lasting</option>';
        document.getElementById('inv-customer').innerHTML          = err;
        document.getElementById('inv-customer-general').innerHTML  = err;
        document.getElementById('inv-customer-renewal').innerHTML  = err;
    }
}

function ecCustomerSelectChanged(val) {
    const manualFields = document.getElementById('inv-manual-customer-fields');
    if (manualFields) manualFields.style.display = val === '__manual__' ? 'block' : 'none';
}

function ecRenewalCustomerChanged(customerId) {
    const infoPanel = document.getElementById('inv-renewal-info');
    const c = ecRenewalData.find(x => x.id == customerId);
    if (!c) { infoPanel.style.display = 'none'; return; }
    const months    = ecBillingCycleToMonths(c.billing_cycle);
    const today     = new Date().toISOString().split('T')[0];
    const baseDate  = (c.expiration_date && c.expiration_date > today) ? new Date(c.expiration_date) : new Date();
    const newExpiry = new Date(baseDate);
    newExpiry.setMonth(newExpiry.getMonth() + months);
    const fmtNew = `${String(newExpiry.getDate()).padStart(2,'0')}.${String(newExpiry.getMonth()+1).padStart(2,'0')}.${newExpiry.getFullYear()}`;
    document.getElementById('inv-ri-name').textContent       = c.name;
    document.getElementById('inv-ri-plan').textContent       = c.plan_name || '—';
    document.getElementById('inv-ri-expiry').textContent     = ecFmtDate(c.expiration_date);
    document.getElementById('inv-ri-new-expiry').textContent = fmtNew;
    document.getElementById('inv-ri-amount').textContent     = ecFmtAmount(c.plan_price, c.currency);
    infoPanel.style.display = 'block';
}

async function ecPopulatePlans() {
    try {
        const res  = await fetch('/api/get_membership_plans.php');
        const data = await res.json();
        ecPlanData = data.plans || [];
        const sel  = document.getElementById('inv-plan');
        sel.innerHTML = ecPlanData.length
            ? '<option value="">Velg plan...</option>' + ecPlanData.map(p =>
                `<option value="${p.id}" data-price="${p.price}" data-currency="${p.currency || 'NOK'}" data-cycle="${p.billing_cycle || 'yearly'}">${p.name} — ${ecFmtAmount(p.price, p.currency)}</option>`
              ).join('')
            : '<option value="">Ingen planer funnet</option>';
        ecUpdateAmountPreview();
    } catch (e) {
        document.getElementById('inv-plan').innerHTML = '<option value="">Feil ved lasting</option>';
    }
}

async function ecPopulateBankAccounts() {
    try {
        const res  = await fetch('/api/get_bank_accounts.php');
        const data = await res.json();
        const sel  = document.getElementById('inv-bank-account');
        if (!sel) return;
        const accounts = data.accounts || [];
        sel.innerHTML = '<option value="">Velg konto...</option>'
            + accounts.map(a => {
                const label = [a.account_number, a.bank_name].filter(Boolean).join(' – ');
                return `<option value="${a.id}">${label}</option>`;
            }).join('');
    } catch (e) {
        const sel = document.getElementById('inv-bank-account');
        if (sel) sel.innerHTML = '<option value="">Feil ved lasting</option>';
    }
}

function ecBillingCycleToMonths(cycle) {
    return {monthly: 1, quarterly: 3, yearly: 12}[cycle] ?? 12;
}

function ecUpdateAmountPreview() {
    const planSel = document.getElementById('inv-plan');
    const qty     = parseInt(document.getElementById('inv-qty').value) || 1;
    const sel     = planSel.options[planSel.selectedIndex];
    const preview = document.getElementById('inv-amount-preview');
    if (sel && sel.dataset.price) {
        preview.textContent = ecFmtAmount(parseFloat(sel.dataset.price) * qty, sel.dataset.currency);
    } else {
        preview.textContent = '';
    }
}

async function ecCreateInvoice() {
    const invoiceType   = document.getElementById('inv-type').value;
    const dueDate       = document.getElementById('inv-due').value;
    const notes         = document.getElementById('inv-notes').value.trim();
    const bankAccountId = document.getElementById('inv-bank-account')?.value || null;
    const btn           = document.querySelector('#invoice-create-form .btn-success');
    let payload = { invoice_type: invoiceType, due_date: dueDate, notes, bank_account_id: bankAccountId || null };

    if (invoiceType === 'general') {
        const customerId = document.getElementById('inv-customer-general').value;
        const amount     = document.getElementById('inv-manual-amount').value;
        if (!customerId) { alert('Velg en kunde'); return; }
        payload.customer_id   = customerId;
        payload.manual_amount = amount ? parseFloat(amount) : 0;

    } else if (invoiceType === 'new_member') {
        const customerId = document.getElementById('inv-customer').value;
        const planId     = document.getElementById('inv-plan').value;
        const qty        = parseInt(document.getElementById('inv-qty').value) || 1;
        if (!customerId) { alert('Velg en kunde'); return; }
        if (!planId)     { alert('Velg en plan'); return; }

        if (customerId === '__manual__') {
            const fullName = document.getElementById('inv-manual-name').value.trim();
            if (!fullName) { alert('Navn er påkrevd'); return; }
            payload.customer_id     = '__manual__';
            payload.manual_customer = {
                first_name: fullName,
                last_name:  '',
                email:      document.getElementById('inv-manual-email').value.trim()
            };
        } else {
            payload.customer_id = customerId;
        }
        payload.plan_id  = planId;
        payload.quantity = qty;

    } else if (invoiceType === 'renewal') {
        const customerId = document.getElementById('inv-customer-renewal').value;
        if (!customerId) { alert('Velg en kunde å fornye'); return; }
        const rc = ecRenewalData.find(x => x.id == customerId);
        if (!rc) { alert('Kunde ikke funnet i fornyelseslisten'); return; }
        payload.membership_member_id = rc.mm_id;
    }

    btn.disabled  = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Oppretter...';
    try {
        const res  = await fetch('/api/create_invoice.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);
        ecToggleCreateForm();
        await ecLoadInvoices();
        ecShowToast(`Faktura ${data.invoice_number} opprettet`);
    } catch (err) {
        alert('Feil: ' + err.message);
    } finally {
        btn.disabled  = false;
        btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Opprett faktura';
    }
}

function ecShowToast(message, type = 'success') {
    const toastId = 'ec-toast-' + Date.now();
    const bg = type === 'success' ? 'bg-success' : 'bg-danger';
    const html = `<div id="${toastId}" class="toast align-items-center text-white ${bg} border-0 position-fixed bottom-0 end-0 m-3" style="z-index:9999" role="alert">
        <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>`;
    document.body.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById(toastId);
    const t  = new bootstrap.Toast(el, {delay: 6000});
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

async function ecMarkPaid(invoiceId, btn) {
    if (!confirm('Marker faktura som betalt?')) return;
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
    try {
        const res  = await fetch('/api/mark_invoice_paid.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({invoice_id: invoiceId})
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);

        const inv = ecAllInvoices.find(i => i.invoice_id === invoiceId);
        if (inv) { inv.status = 'paid'; inv.computed_overdue = false; }
        ecRenderBothLists();

        if (data.action === 'new_member_activated') {
            ecShowToast(`Nytt membre aktivert — kode: <strong>${data.member_code}</strong> — utløper ${ecFmtDate(data.expiration_date)}`);
        } else if (data.action === 'renewal_completed') {
            ecShowToast(`Fornyelse registrert — nytt utløp: <strong>${ecFmtDate(data.new_expiration_date)}</strong>`);
        }
    } catch (err) {
        alert('Feil: ' + err.message);
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Betalt'; }
    }
}

async function ecVoidInvoice(invoiceId, btn) {
    if (!confirm('Ugyldiggjør faktura? Handlingen kan ikke angres.')) return;
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
    try {
        const res  = await fetch('/api/void_invoice.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({invoice_id: invoiceId})
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);
        ecAllInvoices = ecAllInvoices.filter(i => i.invoice_id !== invoiceId);
        ecRenderBothLists();
    } catch (err) {
        alert('Feil: ' + err.message);
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-x-lg"></i>'; }
    }
}

async function loadTermsConditions() {
    try {
        const response = await fetch('/api/get_terms_conditions.php');
        const result = await response.json();
        
        const tbody = document.querySelector('#terms-table tbody');
        
        if (result.success && result.data && result.data.length > 0) {
            tbody.innerHTML = result.data.map(term => `
                <tr>
                    <td>${term.title || 'Kjøpsbetingelser'}</td>
                    <td>${term.version || '1.0'}</td>
                    <td>
                        <span class="badge ${term.is_active ? 'bg-success' : 'bg-secondary'}">
                            ${term.is_active ? 'Aktiv' : 'Inaktiv'}
                        </span>
                    </td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="editTerms('${term.id}')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-outline-info" onclick="viewTermsAcceptances('${term.id}')">
                                <i class="bi bi-people"></i> ${term.acceptance_count || 0}
                            </button>
                            <button class="btn btn-outline-danger" onclick="deleteTerms('${term.id}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">Ingen betingelser registrert</td></tr>';
        }
    } catch (error) {
        console.error('Load terms error:', error);
    }
}

function showCreateTermsModal() {
    document.getElementById('termsModalTitle').textContent = 'Nye kjøpsbetingelser';
    document.getElementById('terms-id').value = '';
    document.getElementById('terms-title').value = '';
    document.getElementById('terms-content').value = '';
    document.getElementById('terms-version').value = '1.0';
    document.getElementById('terms-effective-date').value = new Date().toISOString().split('T')[0];
    document.getElementById('terms-acceptance-type').value = 'checkbox';
    document.getElementById('terms-requires-acceptance').checked = true;
    
    new bootstrap.Modal(document.getElementById('termsModal')).show();
}

async function editTerms(id) {
    try {
        const response = await fetch(`/api/get_terms_conditions.php?id=${id}`);
        const result = await response.json();
        
        if (result.success && result.data) {
            const term = result.data;
            document.getElementById('termsModalTitle').textContent = 'Rediger kjøpsbetingelser';
            document.getElementById('terms-id').value = term.id;
            document.getElementById('terms-title').value = term.title || '';
            document.getElementById('terms-content').value = term.content || '';
            document.getElementById('terms-version').value = term.version || '1.0';
            document.getElementById('terms-effective-date').value = term.effective_date || '';
            document.getElementById('terms-acceptance-type').value = term.acceptance_type || 'checkbox';
            document.getElementById('terms-requires-acceptance').checked = term.requires_customer_acceptance == 1;
            
            new bootstrap.Modal(document.getElementById('termsModal')).show();
        } else {
            alert('Kunne ikke laste kjøpsbetingelser');
        }
    } catch (error) {
        console.error('Edit terms error:', error);
        alert('Feil ved lasting av kjøpsbetingelser');
    }
}

async function saveTerms() {
    const id = document.getElementById('terms-id').value;
    const title = document.getElementById('terms-title').value.trim();
    const content = document.getElementById('terms-content').value.trim();
    const version = document.getElementById('terms-version').value.trim() || '1.0';
    const effectiveDate = document.getElementById('terms-effective-date').value;
    const acceptanceType = document.getElementById('terms-acceptance-type').value;
    const requiresAcceptance = document.getElementById('terms-requires-acceptance').checked;
    
    if (!title || !content || !effectiveDate) {
        alert('Vennligst fyll ut alle påkrevde felt');
        return;
    }
    
    try {
        const response = await fetch('/api/create_update_terms.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                id: id || null,
                title,
                content,
                version,
                effective_date: effectiveDate,
                acceptance_type: acceptanceType,
                requires_customer_acceptance: requiresAcceptance
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('termsModal')).hide();
            loadTermsConditions();
        } else {
            alert('Feil: ' + result.message);
        }
    } catch (error) {
        console.error('Save terms error:', error);
        alert('Feil ved lagring av kjøpsbetingelser');
    }
}

async function viewTermsAcceptances(termsId) {
    try {
        const modal = new bootstrap.Modal(document.getElementById('termsAcceptancesModal'));
        modal.show();
        
        document.getElementById('acceptances-list').innerHTML = '<tr><td colspan="4" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div> Laster...</td></tr>';
        
        const response = await fetch(`/api/get_terms_acceptances.php?terms_id=${termsId}`);
        const result = await response.json();
        
        const tbody = document.getElementById('acceptances-list');
        
        if (result.success && result.data && result.data.length > 0) {
            tbody.innerHTML = result.data.map(acc => `
                <tr>
                    <td>${acc.last_name || '-'}</td>
                    <td>${acc.first_name || '-'}</td>
                    <td>${acc.email || acc.phone || '-'}</td>
                    <td>${formatDate(acc.accepted_at)}</td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="4" class="text-center py-3 text-muted">Ingen har akseptert ennå</td></tr>';
        }
    } catch (error) {
        console.error('Load acceptances error:', error);
        document.getElementById('acceptances-list').innerHTML = '<tr><td colspan="4" class="text-center py-3 text-danger">Feil ved lasting</td></tr>';
    }
}

async function deleteTerms(id) {
    if (!confirm('Er du sikker på at du vil slette disse kjøpsbetingelsene?')) return;
    
    try {
        const response = await fetch('/api/create_update_terms.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ action: 'delete', id })
        });
        
        const result = await response.json();
        
        if (result.success) {
            loadTermsConditions();
        } else {
            alert('Feil: ' + result.message);
        }
    } catch (error) {
        console.error('Delete terms error:', error);
        alert('Feil ved sletting');
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Load economy overview when economy module is shown
    const economyTab = document.querySelector('[data-module="economy"]');
    if (economyTab) {
        economyTab.addEventListener('click', function() {
            setTimeout(loadEconomyOverview, 100);
        });
    }
    
    // Also trigger on subtab navigation within economy
    const economyResultsTab = document.querySelector('[href="#economy-results"]');
    if (economyResultsTab) {
        economyResultsTab.addEventListener('click', function() {
            setTimeout(loadEconomyOverview, 100);
        });
    }
    
    // Initial load - wait briefly then load if economy is visible
    setTimeout(function() {
        const economyContent = document.getElementById('economy-results');
        if (economyContent) {
            // Check if the economy module is currently active/visible
            const isVisible = economyContent.offsetWidth > 0 && economyContent.offsetHeight > 0;
            if (isVisible) {
                loadEconomyOverview();
            }
        }
    }, 200);
});

// ── Fornyelser ────────────────────────────────────────────────────────────────

let renewalPage       = 1;
let renewalTotalPages = 1;

const renewalStatusColors = {
    completed:       'rgba(25,135,84,0.12)',
    pending:         'rgba(255,193,7,0.2)',
    payment_pending: 'rgba(13,202,240,0.15)',
    cancelled:       'rgba(220,53,69,0.12)',
    expired:         'rgba(108,117,125,0.12)',
};

function renewalStatusBg(status) {
    return renewalStatusColors[status] || '';
}

async function lookupRenewal() {
    const order_id = document.getElementById('renewal-lookup-input').value.trim();
    if (!order_id) return;
    const res = document.getElementById('renewal-lookup-result');
    res.style.display = 'none';
    document.getElementById('rl-save-msg').textContent = '';
    try {
        const resp = await fetch('/api/get_membership_renewals.php?lookup=' + encodeURIComponent(order_id));
        const data = await resp.json();
        if (!data.success) {
            alert(data.message || 'Ikke funnet');
            return;
        }
        const r = data.row;
        document.getElementById('rl-name').textContent       = (r.first_name || '') + ' ' + (r.last_name || '');
        document.getElementById('rl-renewal-id').value       = r.id;
        document.getElementById('rl-member-id').value        = r.membership_member_id;
        document.getElementById('rl-renewal-status').value   = r.renewal_status || 'pending';
        document.getElementById('rl-payment-status').value   = r.payment_status  || 'unpaid';
        document.getElementById('rl-expiration-date').value  = r.expiration_date  || '';
        res.style.display = 'block';
    } catch(e) {
        console.error('Lookup error', e);
    }
}

async function saveRenewalLookup() {
    const renewal_id   = document.getElementById('rl-renewal-id').value;
    const member_id    = document.getElementById('rl-member-id').value;
    const rnStatus     = document.getElementById('rl-renewal-status').value;
    const payStatus    = document.getElementById('rl-payment-status').value;
    const expDate      = document.getElementById('rl-expiration-date').value;
    const msgEl        = document.getElementById('rl-save-msg');
    msgEl.textContent  = '';
    msgEl.className    = 'small';

    try {
        const [r1, r2] = await Promise.all([
            fetch('/api/update_renewal_status.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({id: renewal_id, status: rnStatus})
            }).then(r => r.json()),
            fetch('/api/update_member_fields.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({membership_member_id: member_id, payment_status: payStatus, expiration_date: expDate})
            }).then(r => r.json()),
        ]);

        if (r1.success && r2.success) {
            msgEl.textContent = '✓ Lagret';
            msgEl.className   = 'small text-success';
            loadRenewalList(renewalPage);
        } else {
            msgEl.textContent = (r1.message || r2.message || 'Feil ved lagring');
            msgEl.className   = 'small text-danger';
        }
    } catch(e) {
        msgEl.textContent = 'Nettverksfeil';
        msgEl.className   = 'small text-danger';
    }
}

async function loadRenewalList(page) {
    page = page || 1;
    renewalPage = page;
    const search = encodeURIComponent(document.getElementById('renewal-search')?.value?.trim() || '');
    const tbody  = document.getElementById('renewal-list-body');
    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Laster…</td></tr>';

    try {
        const resp = await fetch(`/api/get_membership_renewals.php?page=${page}&per_page=10&search=${search}`);
        const data = await resp.json();
        if (!data.success) { tbody.innerHTML = '<tr><td colspan="5" class="text-danger text-center py-2">Feil ved lasting</td></tr>'; return; }

        renewalTotalPages = Math.max(1, Math.ceil(data.total / data.per_page));

        if (!data.rows || data.rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">Ingen fornyelser funnet</td></tr>';
        } else {
            tbody.innerHTML = data.rows.map(r => {
                const dateStr = r.created_at ? r.created_at.substring(0, 10) : '–';
                const amount  = r.amount ? 'kr ' + parseFloat(r.amount).toFixed(2) : '–';
                const bg      = renewalStatusBg(r.renewal_status);
                const options = ['pending','payment_pending','completed','cancelled','expired']
                    .map(s => `<option value="${s}"${s===r.renewal_status?' selected':''}>${s}</option>`).join('');
                return `<tr>
                    <td>
                        <span data-bs-toggle="tooltip" data-bs-placement="right" title="order_id: ${r.order_id}">
                            ${r.member_name || '–'}
                        </span>
                        <span class="ms-1 text-muted" style="cursor:pointer;" title="Kopier order_id"
                              onclick="navigator.clipboard.writeText('${r.order_id}').then(()=>showRenewalCopyToast())">
                            <i class="bi bi-clipboard" style="font-size:.75rem;"></i>
                        </span>
                    </td>
                    <td class="text-muted small">${r.plan_name || '–'}</td>
                    <td class="text-end">${amount}</td>
                    <td>
                        <select class="form-select form-select-sm border-0 px-1"
                                style="background:${bg}; width:auto; min-width:130px;"
                                onchange="updateRenewalStatus('${r.id}', this.value, this)">
                            ${options}
                        </select>
                    </td>
                    <td class="text-muted small">${dateStr}</td>
                </tr>`;
            }).join('');

            // Init Bootstrap tooltips for new rows
            tbody.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
        }

        // Pagination
        const pageInfo = document.getElementById('renewal-page-info');
        const prevBtn  = document.getElementById('renewal-prev');
        const nextBtn  = document.getElementById('renewal-next');
        const pagDiv   = document.getElementById('renewal-pagination');

        pageInfo.textContent     = `Side ${renewalPage} av ${renewalTotalPages}`;
        prevBtn.disabled         = renewalPage <= 1;
        nextBtn.disabled         = renewalPage >= renewalTotalPages;
        pagDiv.style.display     = data.total > data.per_page ? 'flex' : 'none';

    } catch(e) {
        console.error('loadRenewalList error', e);
        tbody.innerHTML = '<tr><td colspan="5" class="text-danger text-center py-2">Nettverksfeil</td></tr>';
    }
}

async function updateRenewalStatus(id, status, selectEl) {
    try {
        const resp = await fetch('/api/update_renewal_status.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id, status})
        });
        const data = await resp.json();
        if (data.success) {
            if (selectEl) selectEl.style.background = renewalStatusBg(status);
            showRenewalCopyToast('Status oppdatert');
        } else {
            alert(data.message || 'Feil ved oppdatering');
        }
    } catch(e) {
        console.error('updateRenewalStatus error', e);
    }
}

function showRenewalCopyToast(msg) {
    msg = msg || 'order_id kopiert';
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    const id = 'rt' + Date.now();
    container.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast align-items-center text-bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`);
    const el = document.getElementById(id);
    new bootstrap.Toast(el, {delay:2000}).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

document.getElementById('renewal-lookup-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') lookupRenewal();
});
document.getElementById('renewal-search')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') loadRenewalList(1);
});
</script>
