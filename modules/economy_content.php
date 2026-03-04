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
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0"><i class="bi bi-receipt me-2"></i>Fakturaer</h5>
                    <button class="btn btn-primary btn-sm" onclick="ecToggleCreateForm()">
                        <i class="bi bi-plus me-1"></i>Ny faktura
                    </button>
                </div>

                <div id="invoice-create-form" style="display:none;" class="border rounded p-3 mb-3 bg-light">
                    <h6 class="mb-3">Opprett ny faktura</h6>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm fw-semibold">Fakturatype</label>
                            <select id="inv-type" class="form-select form-select-sm" onchange="ecToggleFormByType(this.value)">
                                <option value="general">Generell</option>
                                <option value="new_member">Nytt medlemskap</option>
                                <option value="renewal">Fornyelse</option>
                            </select>
                        </div>
                    </div>

                    <div id="inv-section-general" class="row g-2 mt-1">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Kunde</label>
                            <select id="inv-customer-general" class="form-select form-select-sm">
                                <option value="">Laster kunder...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Beløp (NOK)</label>
                            <input type="number" id="inv-manual-amount" class="form-control form-control-sm" min="0" step="0.01" placeholder="0.00">
                        </div>
                    </div>

                    <div id="inv-section-new-member" class="row g-2 mt-1" style="display:none;">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Kunde</label>
                            <select id="inv-customer" class="form-select form-select-sm">
                                <option value="">Laster kunder...</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Vare (medlemskapsplan)</label>
                            <select id="inv-plan" class="form-select form-select-sm" onchange="ecUpdateAmountPreview()">
                                <option value="">Laster planer...</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label form-label-sm">Antall</label>
                            <input type="number" id="inv-qty" class="form-control form-control-sm" value="1" min="1" onchange="ecUpdateAmountPreview()">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div id="inv-amount-preview" class="fw-bold text-success fs-6 pb-1"></div>
                        </div>
                    </div>

                    <div id="inv-section-renewal" class="row g-2 mt-1" style="display:none;">
                        <div class="col-md-6">
                            <label class="form-label form-label-sm">Velg membre å fornye</label>
                            <select id="inv-member" class="form-select form-select-sm" onchange="ecMemberSelected(this.value)">
                                <option value="">Laster membres...</option>
                            </select>
                        </div>
                        <div id="inv-renewal-info" class="col-12" style="display:none;">
                            <div class="p-2 rounded border bg-white small">
                                <div class="row g-1">
                                    <div class="col-6"><span class="text-muted">Kunde:</span> <span id="inv-ri-name"></span></div>
                                    <div class="col-6"><span class="text-muted">Plan:</span> <span id="inv-ri-plan"></span></div>
                                    <div class="col-6"><span class="text-muted">Nåværende utløp:</span> <span id="inv-ri-expiry" class="text-danger fw-semibold"></span></div>
                                    <div class="col-6"><span class="text-muted">Nytt utløp:</span> <span id="inv-ri-new-expiry" class="text-success fw-semibold"></span></div>
                                    <div class="col-6"><span class="text-muted">Beløp:</span> <span id="inv-ri-amount" class="fw-semibold"></span></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 mt-1">
                        <div class="col-md-4">
                            <label class="form-label form-label-sm">Forfallsdato</label>
                            <input type="date" id="inv-due" class="form-control form-control-sm">
                        </div>
                        <div class="col-12">
                            <label class="form-label form-label-sm">Betalingsbetingelser / notat</label>
                            <textarea id="inv-notes" class="form-control form-control-sm" rows="2"></textarea>
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-success btn-sm" onclick="ecCreateInvoice()">
                            <i class="bi bi-check2 me-1"></i>Opprett faktura
                        </button>
                        <button class="btn btn-outline-secondary btn-sm" onclick="ecToggleCreateForm()">Avbryt</button>
                    </div>
                </div>

                <div class="mb-2 d-flex gap-1" id="invoice-filter-btns">
                    <button class="btn btn-sm btn-primary filter-btn active" onclick="ecFilterInvoices('all', this)">Alle</button>
                    <button class="btn btn-sm btn-outline-warning filter-btn" onclick="ecFilterInvoices('sent', this)">Sendt</button>
                    <button class="btn btn-sm btn-outline-danger filter-btn" onclick="ecFilterInvoices('overdue', this)">Forfalt</button>
                    <button class="btn btn-sm btn-outline-success filter-btn" onclick="ecFilterInvoices('paid', this)">Betalt</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="invoice-table">
                        <thead class="table-light">
                            <tr>
                                <th>Fakturanr</th>
                                <th>Type</th>
                                <th>Kunde</th>
                                <th>Beløp</th>
                                <th>Utstedt</th>
                                <th>Forfall</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="invoice-list-body">
                            <tr><td colspan="8" class="text-center py-3 text-muted">Laster...</td></tr>
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
    
    tbody.innerHTML = transactions.map(t => `
        <tr class="transaction-row income">
            <td>${formatDate(t.transaction_date)}</td>
            <td>${t.item_name || '-'}</td>
            <td><span class="badge bg-secondary module-badge">${moduleLabels[t.category] || t.category}</span></td>
            <td>${paymentLabels[t.payment_method] || t.payment_method || '-'}</td>
            <td class="text-end text-success">+${formatCurrency(t.selling_price)}</td>
        </tr>
    `).join('');
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
    'cash': 'Kontant betaling',
    'vipps': 'Norsk mobilbetaling',
    'stripe': 'Kort og nettbetaling',
    'paypal': 'Internasjonal betaling',
    'klarna': 'Betal senere',
    'bank': 'Bankoverføring'
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
    else if (subtab === 'economy-income') loadIncomeData();
    else if (subtab === 'economy-expense') loadExpenseData();
    else if (subtab === 'economy-pending') loadPendingPayments();
    else if (subtab === 'economy-payment') loadPaymentGateways();
    else if (subtab === 'economy-terms') loadTermsConditions();
    else if (subtab === 'economy-invoices') ecLoadInvoices();
});

let ecAllInvoices = [];
let ecPlanData = [];
let ecMemberData = [];
let ecInvConfig = {};

async function ecLoadInvoices() {
    try {
        const res = await fetch('/api/get_invoices.php');
        const data = await res.json();
        if (!data.success) throw new Error(data.error);
        ecAllInvoices = data.invoices || [];
        ecInvConfig = data.invoice_config || {};
        ecApplyConfig();
        ecRenderInvoices(ecAllInvoices);
        await Promise.all([ecPopulateCustomers(), ecPopulatePlans(), ecPopulateMembers()]);
    } catch (err) {
        document.getElementById('invoice-list-body').innerHTML =
            `<tr><td colspan="8" class="text-center text-danger py-3">${err.message}</td></tr>`;
    }
}

function ecApplyConfig() {
    if (ecInvConfig.invoice_due_days) {
        const due = new Date();
        due.setDate(due.getDate() + parseInt(ecInvConfig.invoice_due_days));
        document.getElementById('inv-due').value = due.toISOString().split('T')[0];
    }
    if (ecInvConfig.invoice_terms) {
        document.getElementById('inv-notes').value = ecInvConfig.invoice_terms;
    }
}

function ecInvoiceTypeBadge(type) {
    if (type === 'new_member') return '<span class="badge bg-primary"><i class="bi bi-person-plus"></i> Nytt</span>';
    if (type === 'renewal')    return '<span class="badge" style="background:#6f42c1"><i class="bi bi-arrow-repeat"></i> Fornyelse</span>';
    return '<span class="badge bg-secondary">Generell</span>';
}

function ecRenderInvoices(invoices) {
    const tbody = document.getElementById('invoice-list-body');
    if (!invoices.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center py-3 text-muted">Ingen fakturaer</td></tr>';
        return;
    }
    tbody.innerHTML = invoices.map(inv => {
        let statusBadge;
        if (inv.status === 'paid') {
            statusBadge = '<span class="badge bg-success">Betalt</span>';
        } else if (inv.computed_overdue) {
            statusBadge = '<span class="badge bg-danger">Forfalt</span>';
        } else {
            statusBadge = '<span class="badge bg-warning text-dark">Sendt</span>';
        }
        const markBtn = inv.status !== 'paid'
            ? `<button class="btn btn-outline-success btn-sm" onclick="ecMarkPaid('${inv.invoice_id}', this)">Marker betalt</button>`
            : '';
        return `<tr id="inv-row-${inv.invoice_id}">
            <td class="fw-semibold">${inv.invoice_number}</td>
            <td>${ecInvoiceTypeBadge(inv.invoice_type || 'general')}</td>
            <td>${inv.customer_name || '<span class="text-muted">—</span>'}</td>
            <td>${parseFloat(inv.total_amount).toLocaleString('nb-NO', {minimumFractionDigits:2})} ${inv.currency}</td>
            <td>${ecFmtDate(inv.invoice_date)}</td>
            <td>${ecFmtDate(inv.due_date)}</td>
            <td id="inv-status-${inv.invoice_id}">${statusBadge}</td>
            <td id="inv-action-${inv.invoice_id}">${markBtn}</td>
        </tr>`;
    }).join('');
}

function ecFmtDate(d) {
    if (!d) return '—';
    const parts = d.split('-');
    return parts.length === 3 ? `${parts[2]}.${parts[1]}.${parts[0]}` : d;
}

function ecFilterInvoices(status, btn) {
    document.querySelectorAll('#invoice-filter-btns .filter-btn').forEach(b => {
        b.classList.remove('btn-primary','btn-warning','btn-danger','btn-success');
        b.classList.add('btn-outline-secondary');
    });
    if (btn) {
        btn.classList.remove('btn-outline-secondary');
        btn.classList.add('btn-primary');
    }
    let filtered = ecAllInvoices;
    if (status === 'paid')    filtered = ecAllInvoices.filter(i => i.status === 'paid');
    else if (status === 'sent')    filtered = ecAllInvoices.filter(i => i.status === 'sent' && !i.computed_overdue);
    else if (status === 'overdue') filtered = ecAllInvoices.filter(i => i.computed_overdue);
    ecRenderInvoices(filtered);
}

function ecToggleCreateForm() {
    const form = document.getElementById('invoice-create-form');
    const isHidden = form.style.display === 'none';
    form.style.display = isHidden ? 'block' : 'none';
    if (isHidden) {
        document.getElementById('inv-type').value = 'general';
        ecToggleFormByType('general');
        ecApplyConfig();
    }
}

function ecToggleFormByType(type) {
    document.getElementById('inv-section-general').style.display    = type === 'general'    ? 'flex' : 'none';
    document.getElementById('inv-section-new-member').style.display = type === 'new_member' ? 'flex' : 'none';
    document.getElementById('inv-section-renewal').style.display    = type === 'renewal'    ? 'flex' : 'none';
}

async function ecPopulateCustomers() {
    try {
        const res = await fetch('/api/get_customers_simple.php');
        const data = await res.json();
        const opts = data.success && data.customers.length
            ? '<option value="">Velg kunde...</option>' + data.customers.map(c =>
                `<option value="${c.id}">${c.name}${c.email ? ' — ' + c.email : ''}</option>`).join('')
            : '<option value="">Ingen kunder funnet</option>';
        document.getElementById('inv-customer').innerHTML = opts;
        document.getElementById('inv-customer-general').innerHTML = opts;
    } catch (e) {
        const err = '<option value="">Feil ved lasting</option>';
        document.getElementById('inv-customer').innerHTML = err;
        document.getElementById('inv-customer-general').innerHTML = err;
    }
}

async function ecPopulatePlans() {
    try {
        const res = await fetch('/api/get_membership_plans.php');
        const data = await res.json();
        ecPlanData = data.plans || [];
        const sel = document.getElementById('inv-plan');
        if (ecPlanData.length) {
            sel.innerHTML = '<option value="">Velg plan...</option>' +
                ecPlanData.map(p =>
                    `<option value="${p.id}" data-price="${p.price}" data-currency="${p.currency || 'NOK'}" data-cycle="${p.billing_cycle || 'yearly'}">${p.name} — ${parseFloat(p.price).toLocaleString('nb-NO', {minimumFractionDigits:2})} ${p.currency || 'NOK'}</option>`
                ).join('');
        } else {
            sel.innerHTML = '<option value="">Ingen planer funnet</option>';
        }
        ecUpdateAmountPreview();
    } catch (e) {
        document.getElementById('inv-plan').innerHTML = '<option value="">Feil ved lasting</option>';
    }
}

async function ecPopulateMembers() {
    try {
        const res = await fetch('/api/get_members_simple.php');
        const data = await res.json();
        ecMemberData = data.members || [];
        const sel = document.getElementById('inv-member');
        if (ecMemberData.length) {
            sel.innerHTML = '<option value="">Velg membre...</option>' +
                ecMemberData.map(m => {
                    const expiry = m.expiration_date ? ' — utløper ' + ecFmtDate(m.expiration_date) : '';
                    return `<option value="${m.id}">${m.customer_name} (${m.member_code})${expiry}</option>`;
                }).join('');
        } else {
            sel.innerHTML = '<option value="">Ingen aktive membres funnet</option>';
        }
    } catch (e) {
        document.getElementById('inv-member').innerHTML = '<option value="">Feil ved lasting</option>';
    }
}

function ecBillingCycleToMonths(cycle) {
    return {monthly: 1, quarterly: 3, yearly: 12}[cycle] ?? 12;
}

function ecMemberSelected(memberId) {
    const infoPanel = document.getElementById('inv-renewal-info');
    if (!memberId) { infoPanel.style.display = 'none'; return; }
    const m = ecMemberData.find(x => x.id === memberId);
    if (!m) { infoPanel.style.display = 'none'; return; }
    const months   = ecBillingCycleToMonths(m.plan_billing_cycle);
    const baseDate = (m.expiration_date && m.expiration_date > new Date().toISOString().split('T')[0])
                     ? new Date(m.expiration_date) : new Date();
    const newExpiry = new Date(baseDate);
    newExpiry.setMonth(newExpiry.getMonth() + months);
    const fmtNew = `${String(newExpiry.getDate()).padStart(2,'0')}.${String(newExpiry.getMonth()+1).padStart(2,'0')}.${newExpiry.getFullYear()}`;
    document.getElementById('inv-ri-name').textContent   = m.customer_name;
    document.getElementById('inv-ri-plan').textContent   = m.plan_name || '—';
    document.getElementById('inv-ri-expiry').textContent = ecFmtDate(m.expiration_date);
    document.getElementById('inv-ri-new-expiry').textContent = fmtNew;
    document.getElementById('inv-ri-amount').textContent = parseFloat(m.plan_price || 0).toLocaleString('nb-NO', {minimumFractionDigits:2}) + ' ' + (m.currency || 'NOK');
    infoPanel.style.display = 'block';
}

function ecUpdateAmountPreview() {
    const planSel = document.getElementById('inv-plan');
    const qty     = parseInt(document.getElementById('inv-qty').value) || 1;
    const sel     = planSel.options[planSel.selectedIndex];
    const preview = document.getElementById('inv-amount-preview');
    if (sel && sel.dataset.price) {
        const total = parseFloat(sel.dataset.price) * qty;
        preview.textContent = total.toLocaleString('nb-NO', {minimumFractionDigits:2}) + ' ' + (sel.dataset.currency || 'NOK');
    } else {
        preview.textContent = '';
    }
}

async function ecCreateInvoice() {
    const invoiceType = document.getElementById('inv-type').value;
    const dueDate     = document.getElementById('inv-due').value;
    const notes       = document.getElementById('inv-notes').value.trim();
    const btn         = document.querySelector('#invoice-create-form .btn-success');

    let payload = { invoice_type: invoiceType, due_date: dueDate, notes };

    if (invoiceType === 'general') {
        const customerId = document.getElementById('inv-customer-general').value;
        const amount     = document.getElementById('inv-manual-amount').value;
        if (!customerId) { alert('Velg en kunde'); return; }
        payload.customer_id    = customerId;
        payload.manual_amount  = amount ? parseFloat(amount) : 0;

    } else if (invoiceType === 'new_member') {
        const customerId = document.getElementById('inv-customer').value;
        const planId     = document.getElementById('inv-plan').value;
        const qty        = parseInt(document.getElementById('inv-qty').value) || 1;
        if (!customerId) { alert('Velg en kunde'); return; }
        if (!planId)     { alert('Velg en plan'); return; }
        payload.customer_id = customerId;
        payload.plan_id     = planId;
        payload.quantity    = qty;

    } else if (invoiceType === 'renewal') {
        const memberId = document.getElementById('inv-member').value;
        if (!memberId) { alert('Velg et membre å fornye'); return; }
        payload.membership_member_id = memberId;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Oppretter...';
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
    } catch (err) {
        alert('Feil: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check2 me-1"></i>Opprett faktura';
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
    const t  = new bootstrap.Toast(el, {delay: 5000});
    t.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

async function ecMarkPaid(invoiceId, btn) {
    if (!confirm('Marker faktura som betalt?')) return;
    if (btn) { btn.disabled = true; btn.textContent = '...'; }
    try {
        const res  = await fetch('/api/mark_invoice_paid.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({invoice_id: invoiceId})
        });
        const data = await res.json();
        if (!data.success) throw new Error(data.error);

        document.getElementById('inv-status-' + invoiceId).innerHTML = '<span class="badge bg-success">Betalt</span>';
        document.getElementById('inv-action-' + invoiceId).innerHTML = '';
        const inv = ecAllInvoices.find(i => i.invoice_id === invoiceId);
        if (inv) { inv.status = 'paid'; inv.computed_overdue = false; }

        if (data.action === 'new_member_activated') {
            ecShowToast(`Nytt membre aktivert — kode: <strong>${data.member_code}</strong> — utløper ${ecFmtDate(data.expiration_date)}`);
        } else if (data.action === 'renewal_completed') {
            ecShowToast(`Fornyelse registrert — nytt utløp: <strong>${ecFmtDate(data.new_expiration_date)}</strong>`);
        }
    } catch (err) {
        alert('Feil: ' + err.message);
        if (btn) { btn.disabled = false; btn.textContent = 'Marker betalt'; }
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
</script>
