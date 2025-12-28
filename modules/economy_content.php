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
});

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
    alert('Opprett nye kjøpsbetingelser - funksjonalitet kommer snart');
}

function editTerms(id) {
    alert('Rediger kjøpsbetingelser - funksjonalitet kommer snart');
}

function viewTermsAcceptances(id) {
    alert('Vis akseptanser - funksjonalitet kommer snart');
}

function deleteTerms(id) {
    if (!confirm('Er du sikker på at du vil slette disse kjøpsbetingelsene?')) return;
    alert('Slett kjøpsbetingelser - funksjonalitet kommer snart');
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
