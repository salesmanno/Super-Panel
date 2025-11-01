<style>
.barcode-scanner-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
}

.barcode-scanner-card .card-body {
    padding: 2rem;
}

.barcode-icon {
    font-size: 4rem;
    opacity: 0.9;
}

.barcode-scanner-card h5, 
.barcode-scanner-card p {
    color: white;
}

.barcode-input {
    background: rgba(255, 255, 255, 0.95);
    border: 3px solid rgba(255, 255, 255, 0.3);
    font-size: 1.25rem;
    font-weight: 600;
    letter-spacing: 2px;
    padding: 1rem 1.5rem;
    max-width: 500px;
    margin: 0 auto;
    transition: all 0.3s ease;
}

.barcode-input:focus {
    background: white;
    border-color: #10b981;
    box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);
    outline: none;
}

.barcode-scanner-card.scanning {
    animation: pulse-scan 1s ease-in-out;
}

@keyframes pulse-scan {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.02); }
}

.scan-feedback {
    min-height: 30px;
    font-weight: 600;
    font-size: 0.95rem;
}

.purchase-option-card {
    transition: all 0.3s ease;
    cursor: pointer;
}

.purchase-option-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}

.scan-feedback.success {
    color: #10b981;
}

.scan-feedback.error {
    color: #ef4444;
}

/* Fullscreen Sales Mode */
#fullscreen-sales-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: #f8f9fa;
    z-index: 9999;
    overflow-y: auto;
    display: none;
}

#fullscreen-sales-overlay.active {
    display: block;
}

.fullscreen-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 2rem;
    position: sticky;
    top: 0;
    z-index: 10;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
</style>

<div class="module-content">
    <div id="sales-varesalg" class="subtab-content active">
        <!-- Fullscreen Button -->
        <div class="mb-3 text-end">
            <button class="btn btn-primary" id="open-fullscreen-sales">
                <i class="bi bi-arrows-fullscreen me-2"></i>Fullskjerm-modus
            </button>
        </div>
        
        <!-- Barcode Scanner Header (Full Width) -->
        <div class="card mb-3 barcode-scanner-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-upc-scan text-white" style="font-size: 2rem;"></i>
                    <div style="flex: 1;">
                        <input type="text" 
                               class="form-control barcode-input" 
                               id="barcode-scanner-input" 
                               placeholder="Skann strekkode..."
                               autocomplete="off">
                    </div>
                    <div class="scan-feedback text-white" id="scan-feedback" style="min-width: 200px;"></div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Products with Shopping Cart -->
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-box-seam me-2"></i>Produkter</h5>
                        <p class="text-muted">Legg produkter i handlekurv</p>
                        
                        <div id="products-list" class="row">
                            <div class="col-12 text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Laster...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Services (sold separately) -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-gear me-2"></i>Tjenester</h5>
                        <p class="text-muted">Selges individuelt med kundeinformasjon</p>
                        
                        <div id="services-list" class="row">
                            <div class="col-12 text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Laster...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shopping Cart -->
            <div class="col-md-4">

                <div class="card sticky-top" style="top: 20px;">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-cart3 me-2"></i>Handlekurv
                            <span class="badge bg-primary ms-2" id="cart-count">0</span>
                        </h5>
                        
                        <div id="cart-items" class="mb-3">
                            <p class="text-muted text-center py-3">Handlekurven er tom</p>
                        </div>
                        
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong><span id="cart-total">0</span> NOK</strong>
                            </div>
                            
                            <!-- Quick Cash Buttons -->
                            <div class="mb-3">
                                <label class="form-label small">Kontant betaling</label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-outline-success flex-fill" onclick="applyCashPayment(50)" style="position: relative; overflow: hidden;">
                                        <span style="font-size: 1.2em;">💵</span><br><small>50</small>
                                    </button>
                                    <button type="button" class="btn btn-outline-success flex-fill" onclick="applyCashPayment(100)" style="position: relative; overflow: hidden;">
                                        <span style="font-size: 1.2em;">💵</span><br><small>100</small>
                                    </button>
                                    <button type="button" class="btn btn-outline-success flex-fill" onclick="applyCashPayment(200)" style="position: relative; overflow: hidden;">
                                        <span style="font-size: 1.2em;">💵</span><br><small>200</small>
                                    </button>
                                    <button type="button" class="btn btn-outline-success flex-fill" onclick="applyCashPayment(500)" style="position: relative; overflow: hidden;">
                                        <span style="font-size: 1.3em;">💶</span><br><small>500</small>
                                    </button>
                                    <button type="button" class="btn btn-outline-success flex-fill" onclick="applyCashPayment(1000)" style="position: relative; overflow: hidden;">
                                        <span style="font-size: 1.4em;">💷</span><br><small>1000</small>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Payment Status Display -->
                            <div id="payment-status" class="alert alert-info mb-3" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span id="payment-status-text"></span>
                                    <button type="button" class="btn-close btn-close-sm" onclick="resetPayment()"></button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small">Betalingsmetode</label>
                                <select class="form-select form-select-sm" id="checkout-payment-method">
                                    <option value="cash">Kontant</option>
                                    <option value="vipps">Vipps (ukonfigurert)</option>
                                    <option value="on_arrival">Oppmøte</option>
                                    <option value="sponsored">Spons</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small">Notater</label>
                                <textarea class="form-control form-control-sm" id="checkout-notes" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small">Knytt til arrangement (valgfri)</label>
                                <select class="form-select form-select-sm" id="checkout-linked-event">
                                    <option value="">Ingen arrangement</option>
                                </select>
                                <small class="text-muted d-block mt-1">Salget vises i arrangementets resultat</small>
                            </div>
                            
                            <button id="checkout-btn" class="btn btn-success w-100" disabled>
                                <i class="bi bi-check-circle me-2"></i>Gjennomfør salg
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="sales-transactions" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-receipt me-2"></i>Transaksjoner</h5>
                <p class="text-muted">Oversikt over alle gjennomførte varesalg</p>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Transaksjon ID</th>
                                <th>Dato</th>
                                <th>Kunde</th>
                                <th>Produkter</th>
                                <th>Betalingsmetode</th>
                                <th>Totalbeløp</th>
                                <th>Handlinger</th>
                            </tr>
                        </thead>
                        <tbody id="transactions-list">
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Laster...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="sales-items" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h5 class="card-title mb-1">Salgsprodukter</h5>
                        <p class="text-muted mb-0">Administrer produkter og tjenester</p>
                    </div>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSalesItemModal">
                        <i class="bi bi-plus-circle me-1"></i>Nytt produkt
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Produktnavn</th>
                                <th>SKU</th>
                                <th>Kategori</th>
                                <th>Kostpris</th>
                                <th>Salgspris</th>
                                <th>Profitt</th>
                                <th>Lager</th>
                                <th>Status</th>
                                <th>Handlinger</th>
                            </tr>
                        </thead>
                        <tbody id="sales-items-list">
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="visually-hidden">Laster...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="sales-orders" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Ordrer</h5>
                <p class="text-muted">Oversikt over alle salgsordrer</p>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Ordrenr</th>
                                <th>Kunde</th>
                                <th>Dato</th>
                                <th>Produkter</th>
                                <th>Totalbeløp</th>
                                <th>Status</th>
                                <th>Handlinger</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Ingen ordrer funnet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="sales-statistics" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Salgsstatistikk</h5>
                <p class="text-muted">Oversikt over salgsresultater</p>
                
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <small>Total omsetning</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <small>Antall ordrer</small>
                                <h4 class="mb-0">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <small>Gjennomsnittlig ordre</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <small>Denne måneden</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="bi bi-info-circle me-2"></i>
                    Statistikk oppdateres når du har registrert salgsordrer
                </div>
            </div>
        </div>
    </div>

    <div id="sales-purchases" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="bi bi-cart-dash me-2"></i>Innkjøp</h5>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card h-100 border-primary cursor-pointer purchase-option-card" onclick="showPurchaseHistory()">
                            <div class="card-body text-center">
                                <i class="bi bi-clock-history display-4 text-primary mb-3"></i>
                                <h6 class="card-title">Se innkjøpshistorikk</h6>
                                <p class="text-muted small mb-0">Vis alle tidligere innkjøp</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-success cursor-pointer purchase-option-card" onclick="showPurchaseExisting()">
                            <div class="card-body text-center">
                                <i class="bi bi-box-seam display-4 text-success mb-3"></i>
                                <h6 class="card-title">Gjør innkjøp på eksisterende produkt</h6>
                                <p class="text-muted small mb-0">Fyll på lager av registrerte varer</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100 border-info cursor-pointer purchase-option-card" onclick="showPurchaseNew()">
                            <div class="card-body text-center">
                                <i class="bi bi-plus-circle display-4 text-info mb-3"></i>
                                <h6 class="card-title">Gjør innkjøp av ikke-registrerte varer</h6>
                                <p class="text-muted small mb-0">Registrer nye produkter eller andre utgifter</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div id="purchase-history-view" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6><i class="bi bi-clock-history me-2"></i>Innkjøpshistorikk</h6>
                        <button class="btn btn-sm btn-secondary" onclick="resetPurchaseView()">
                            <i class="bi bi-arrow-left me-1"></i>Tilbake
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead>
                                <tr>
                                    <th>Dato</th>
                                    <th>Leverandør</th>
                                    <th>Produkt</th>
                                    <th>Antall</th>
                                    <th>Enhetspris</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody id="purchase-history-list">
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-3">Laster...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div id="purchase-existing-view" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6><i class="bi bi-box-seam me-2"></i>Innkjøp på eksisterende produkt</h6>
                        <button class="btn btn-sm btn-secondary" onclick="resetPurchaseView()">
                            <i class="bi bi-arrow-left me-1"></i>Tilbake
                        </button>
                    </div>
                    <form id="purchaseExistingForm">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Velg produkt <span class="text-danger">*</span></label>
                                <select class="form-select" id="existing-product-select" required>
                                    <option value="">Velg produkt...</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Leverandør</label>
                                <input type="text" class="form-control" id="existing-supplier" placeholder="Leverandørnavn">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Antall <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="existing-quantity" min="1" value="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Total innkjøpspris (NOK) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="existing-total-cost" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stykkpris (auto)</label>
                                <input type="text" class="form-control" id="existing-unit-cost" readonly>
                            </div>
                        </div>
                        <div class="alert alert-info small">
                            <i class="bi bi-info-circle me-1"></i>
                            <strong>Vektet gjennomsnitt:</strong> Kostprisen vil bli oppdatert basert på eksisterende lager og ny innkjøpspris.
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Registrer innkjøp
                        </button>
                    </form>
                </div>
                
                <div id="purchase-new-view" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6><i class="bi bi-plus-circle me-2"></i>Innkjøp av ikke-registrerte varer</h6>
                        <button class="btn btn-sm btn-secondary" onclick="resetPurchaseView()">
                            <i class="bi bi-arrow-left me-1"></i>Tilbake
                        </button>
                    </div>
                    <form id="purchaseNewForm">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Produktnavn <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="new-product-name" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Kategori</label>
                                <input type="text" class="form-control" id="new-product-category" placeholder="F.eks: Elektronikk, Mat, etc.">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Leverandør</label>
                                <input type="text" class="form-control" id="new-supplier" placeholder="Leverandørnavn">
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Antall <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="new-quantity" min="1" value="1" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Total innkjøpspris (NOK) <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="new-total-cost" step="0.01" min="0" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Stykkpris (auto)</label>
                                <input type="text" class="form-control" id="new-unit-cost" readonly>
                            </div>
                        </div>
                        <div class="alert alert-success small">
                            <i class="bi bi-info-circle me-1"></i>
                            Dette produktet vil bli opprettet i systemet og lagt til lageret.
                        </div>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle me-1"></i>Opprett produkt og registrer innkjøp
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Purchase Modal -->
<div class="modal fade" id="addPurchaseModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-cart-dash me-2"></i>Registrer innkjøp
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPurchaseForm">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Leverandør <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="purchase-supplier" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dato <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="purchase-date" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Produkt/Vare <span class="text-danger">*</span></label>
                            <select class="form-select" id="purchase-product" required>
                                <option value="">Velg produkt...</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Antall <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="purchase-quantity" value="1" min="1" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Kostpris per enhet (NOK) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="purchase-unit-cost" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total kostnad (NOK)</label>
                            <input type="number" class="form-control" id="purchase-total-cost" step="0.01" readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Betalingsmetode</label>
                            <select class="form-select" id="purchase-payment-method">
                                <option value="cash">Kontant</option>
                                <option value="card">Kort</option>
                                <option value="invoice">Faktura</option>
                                <option value="vipps">Vipps</option>
                                <option value="sponsored">Spons</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Fakturanummer</label>
                            <input type="text" class="form-control" id="purchase-invoice-number">
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notater</label>
                        <textarea class="form-control" id="purchase-notes" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-success" onclick="savePurchase()">
                    <i class="bi bi-check-circle me-1"></i>Lagre innkjøp
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Sales Item Modal -->
<div class="modal fade" id="addSalesItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-plus-circle me-2"></i>Legg til nytt produkt
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addSalesItemForm">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Produktnavn <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Enhet</label>
                            <select class="form-select" name="unit">
                                <option value="stk">Stk</option>
                                <option value="kg">Kg</option>
                                <option value="liter">Liter</option>
                                <option value="meter">Meter</option>
                                <option value="pakke">Pakke</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kostpris (NOK)</label>
                            <input type="number" class="form-control" name="cost_price" step="0.01" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Salgspris (NOK) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="selling_price" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Produktbilde URL</label>
                            <input type="url" class="form-control" name="img_product" placeholder="https://...">
                            <small class="text-muted">Legg inn direkte lenke til produktbildet</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">MVA-sats (%)</label>
                            <select class="form-select" name="tax_rate">
                                <option value="25" selected>25% (Standard)</option>
                                <option value="15">15% (Mat)</option>
                                <option value="12">12% (Transport, kino)</option>
                                <option value="0">0% (Fritatt)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bestillingspunkt (reorder)</label>
                            <input type="number" class="form-control" name="reorder_level" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" name="description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="productActive" checked>
                                <label class="form-check-label" for="productActive">
                                    Aktiv (tilgjengelig for salg)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="item_taxable" id="productTaxable" checked>
                                <label class="form-check-label" for="productTaxable">
                                    MVA-pliktig produkt
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="submitSalesItem()">
                    <i class="bi bi-save me-1"></i>Lagre produkt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Sales Item Modal -->
<div class="modal fade" id="editSalesItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil me-2"></i>Rediger produkt
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editSalesItemForm">
                    <input type="hidden" name="id" id="edit-product-id">
                    <div class="row">
                        <div class="col-md-8 mb-3">
                            <label class="form-label">Produktnavn <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" id="edit-name" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Enhet</label>
                            <select class="form-select" name="unit" id="edit-unit">
                                <option value="stk">Stk</option>
                                <option value="kg">Kg</option>
                                <option value="liter">Liter</option>
                                <option value="meter">Meter</option>
                                <option value="pakke">Pakke</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kostpris (NOK)</label>
                            <input type="number" class="form-control" name="cost_price" id="edit-cost-price" step="0.01" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Salgspris (NOK) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="selling_price" id="edit-selling-price" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Produktbilde URL</label>
                            <input type="url" class="form-control" name="img_product" id="edit-img-product" placeholder="https://...">
                            <small class="text-muted">Legg inn direkte lenke til produktbildet</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">MVA-sats (%)</label>
                            <select class="form-select" name="tax_rate" id="edit-tax-rate">
                                <option value="25">25% (Standard)</option>
                                <option value="15">15% (Mat)</option>
                                <option value="12">12% (Transport, kino)</option>
                                <option value="0">0% (Fritatt)</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Bestillingspunkt (reorder)</label>
                            <input type="number" class="form-control" name="reorder_level" id="edit-reorder-level" value="0">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" name="description" id="edit-description" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="edit-is-active">
                                <label class="form-check-label" for="edit-is-active">
                                    Aktiv (tilgjengelig for salg)
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="item_taxable" id="edit-item-taxable">
                                <label class="form-check-label" for="edit-item-taxable">
                                    MVA-pliktig produkt
                                </label>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="submitEditProduct()">
                    <i class="bi bi-save me-1"></i>Oppdater produkt
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Service Purchase Modal -->
<div class="modal fade" id="servicePurchaseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Selg tjeneste</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info mb-3">
                    <strong id="service-modal-name"></strong><br>
                    <span id="service-modal-type" class="badge bg-info"></span>
                    <span class="ms-2"><strong id="service-modal-price"></strong> NOK</span>
                </div>
                
                <form id="servicePurchaseForm">
                    <input type="hidden" id="service-id">
                    <input type="hidden" id="service-type">
                    <input type="hidden" id="service-price-value">
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Fornavn <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="service-customer-first-name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Etternavn <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="service-customer-last-name" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">E-post <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="service-customer-email" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Telefon</label>
                        <input type="tel" class="form-control" id="service-customer-phone">
                    </div>
                    
                    <!-- Reservation-specific fields -->
                    <div id="reservation-specific-fields" style="display: none;">
                        <hr class="my-3">
                        <h6 class="text-primary mb-3"><i class="bi bi-calendar-check me-2"></i>Booking-informasjon</h6>
                        
                        <input type="hidden" id="reservation-service-id">
                        <input type="hidden" id="reservation-duration">
                        <input type="hidden" id="reservation-assigned-staff">
                        <input type="hidden" id="reservation-date">
                        <input type="hidden" id="reservation-time">
                        <select class="form-select" id="reservation-staff-select" required style="display: none;"></select>
                        
                        <div class="alert alert-info small mb-3">
                            <i class="bi bi-clock me-1"></i>Varighet: <strong id="reservation-duration-display">-</strong> minutter<br>
                            <i class="bi bi-people me-1"></i>Tilgjengelige ansatte: <span id="reservation-staff-names">-</span>
                        </div>
                        
                        <!-- Calendar Navigation -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="changeCalendarWeek(-1)">
                                <i class="bi bi-chevron-left"></i> Forrige uke
                            </button>
                            <h6 class="mb-0" id="calendar-week-label">Velg dato og tidspunkt</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="changeCalendarWeek(1)">
                                Neste uke <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                        
                        <div class="mb-3">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="toggleManualTimeInput()">
                                <i class="bi bi-clock-history me-1"></i>Velg annen tid (utenfor 06:00-22:00)
                            </button>
                        </div>
                        
                        <!-- Manual time input (for times outside calendar range) -->
                        <div id="manual-time-input" class="card mb-3" style="display: none;">
                            <div class="card-body">
                                <h6 class="card-title">Manuell tidsvalg</h6>
                                <div class="row">
                                    <div class="col-md-4 mb-2">
                                        <label class="form-label small">Dato</label>
                                        <input type="date" class="form-control form-control-sm" id="manual-date">
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label small">Tid</label>
                                        <input type="time" class="form-control form-control-sm" id="manual-time">
                                    </div>
                                    <div class="col-md-5 mb-2">
                                        <label class="form-label small">Ansatt</label>
                                        <select class="form-select form-select-sm" id="manual-staff"></select>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-success" onclick="applyManualTime()">
                                    <i class="bi bi-check me-1"></i>Bruk denne tiden
                                </button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="toggleManualTimeInput()">
                                    Avbryt
                                </button>
                            </div>
                        </div>
                        
                        <!-- Calendar Grid -->
                        <div id="reservation-calendar" class="mb-3" style="max-height: 400px; overflow-y: auto;">
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-hourglass-split me-2"></i>Laster tilgjengelige tider...
                            </div>
                        </div>
                        
                        <!-- Selected slot display -->
                        <div id="selected-slot-display" class="alert alert-success" style="display: none;">
                            <i class="bi bi-check-circle me-2"></i>
                            <strong>Valgt tid:</strong> <span id="selected-slot-text"></span>
                        </div>
                    </div>
                    
                    <!-- Membership-specific fields -->
                    <div id="membership-specific-fields" style="display: none;">
                        <hr class="my-3">
                        <h6 class="text-primary mb-3"><i class="bi bi-person-badge me-2"></i>Medlemskapsinformasjon</h6>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Startdato</label>
                                <input type="date" class="form-control" id="service-join-date" value="<?= date('Y-m-d') ?>" onchange="updateExpiryDate()">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Utløpsdato (beregnes automatisk)</label>
                                <input type="date" class="form-control" id="service-expiry-date" readonly style="background-color: #f0f0f0;">
                            </div>
                        </div>
                        <input type="hidden" id="service-plan-duration" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Fødselsdato (valgfri)</label>
                            <input type="date" class="form-control" id="service-customer-birth-date">
                            <small class="text-muted">Brukes for aldersbaserte tilbud og statistikk</small>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Adresse (valgfri)</label>
                            <input type="text" class="form-control" id="service-customer-address" placeholder="Gateadresse">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Postnummer</label>
                                <input type="text" class="form-control" id="service-customer-postal-code" placeholder="0000">
                            </div>
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Poststed</label>
                                <input type="text" class="form-control" id="service-customer-city" placeholder="By">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="service-auto-renew">
                                <label class="form-check-label" for="service-auto-renew">
                                    Skal fornyes automatisk
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Non-membership quantity field -->
                    <div class="mb-3" id="service-quantity-field">
                        <label class="form-label">Antall <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="service-quantity" min="1" value="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Betalingsmetode</label>
                        <select class="form-select" id="service-payment-method">
                            <option value="cash">Kontant</option>
                            <option value="vipps">Vipps (ukonfigurert)</option>
                            <option value="on_arrival">Oppmøte</option>
                            <option value="sponsored">Spons</option>
                        </select>
                    </div>
                    
                    <!-- Cash payment calculator for services -->
                    <div class="mb-3" id="service-cash-calculator" style="display: none;">
                        <label class="form-label">Mottatt beløp</label>
                        <input type="number" class="form-control" id="service-cash-received" placeholder="0.00" step="0.01" min="0">
                        <div id="service-cash-change" class="mt-2"></div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notater</label>
                        <textarea class="form-control" id="service-notes" rows="2"></textarea>
                    </div>
                    
                    <div class="alert alert-secondary">
                        <strong>Totalsum:</strong> <span id="service-total">0</span> NOK
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-success" onclick="processServicePurchase()">
                    <i class="bi bi-check-circle me-1"></i>Gjennomfør salg
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Fullscreen Sales Overlay -->
<div id="fullscreen-sales-overlay">
    <div class="fullscreen-header d-flex justify-content-between align-items-center">
        <h4 class="mb-0"><i class="bi bi-cart3 me-2"></i>Varesalg - Fullskjerm</h4>
        <button class="btn btn-light btn-sm" id="close-fullscreen-sales">
            <i class="bi bi-x-lg me-1"></i>Lukk fullskjerm
        </button>
    </div>
    
    <div class="container-fluid p-4">
        <!-- Barcode Scanner Header (Full Width) -->
        <div class="card mb-3 barcode-scanner-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="card-body py-3">
                <div class="d-flex align-items-center gap-3">
                    <i class="bi bi-upc-scan text-white" style="font-size: 2rem;"></i>
                    <div style="flex: 1;">
                        <input type="text" 
                               class="form-control barcode-input" 
                               id="barcode-scanner-input-fullscreen" 
                               placeholder="Skann strekkode..."
                               autocomplete="off">
                    </div>
                    <div class="scan-feedback text-white" id="scan-feedback-fullscreen" style="min-width: 200px;"></div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Products with Shopping Cart -->
            <div class="col-md-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-box-seam me-2"></i>Produkter</h5>
                        <p class="text-muted">Legg produkter i handlekurv</p>
                        
                        <div id="products-list-fullscreen" class="row">
                            <div class="col-12 text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Laster...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Services (sold separately) -->
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><i class="bi bi-gear me-2"></i>Tjenester</h5>
                        <p class="text-muted">Selges individuelt med kundeinformasjon</p>
                        
                        <div id="services-list-fullscreen" class="row">
                            <div class="col-12 text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Laster...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Shopping Cart -->
            <div class="col-md-4">
                <div class="card sticky-top" style="top: 80px;">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-cart3 me-2"></i>Handlekurv
                            <span class="badge bg-primary ms-2" id="cart-count-fullscreen">0</span>
                        </h5>
                        
                        <div id="cart-items-fullscreen" class="mb-3">
                            <p class="text-muted text-center py-3">Handlekurven er tom</p>
                        </div>
                        
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong><span id="cart-total-fullscreen">0</span> NOK</strong>
                            </div>
                            
                            <!-- Quick Cash Buttons -->
                            <div class="mb-3">
                                <label class="form-label small">Kontant betaling</label>
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-outline-success flex-fill" onclick="applyCashPaymentFullscreen(50)" style="position: relative; overflow: hidden;">
                                        <span style="font-size: 1.2em;">💵</span><br><small>50</small>
                                    </button>
                                    <button type="button" class="btn btn-outline-success flex-fill" onclick="applyCashPaymentFullscreen(100)" style="position: relative; overflow: hidden;">
                                        <span style="font-size: 1.2em;">💵</span><br><small>100</small>
                                    </button>
                                    <button type="button" class="btn btn-outline-success flex-fill" onclick="applyCashPaymentFullscreen(200)" style="position: relative; overflow: hidden;">
                                        <span style="font-size: 1.2em;">💵</span><br><small>200</small>
                                    </button>
                                    <button type="button" class="btn btn-outline-success flex-fill" onclick="applyCashPaymentFullscreen(500)" style="position: relative; overflow: hidden;">
                                        <span style="font-size: 1.3em;">💶</span><br><small>500</small>
                                    </button>
                                    <button type="button" class="btn btn-outline-success flex-fill" onclick="applyCashPaymentFullscreen(1000)" style="position: relative; overflow: hidden;">
                                        <span style="font-size: 1.4em;">💷</span><br><small>1000</small>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Payment Status Display -->
                            <div id="payment-status-fullscreen" class="alert alert-info mb-3" style="display: none;">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span id="payment-status-text-fullscreen"></span>
                                    <button type="button" class="btn-close btn-close-sm" onclick="resetPaymentFullscreen()"></button>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small">Betalingsmetode</label>
                                <select class="form-select form-select-sm" id="checkout-payment-method-fullscreen">
                                    <option value="cash">Kontant</option>
                                    <option value="vipps">Vipps (ukonfigurert)</option>
                                    <option value="on_arrival">Oppmøte</option>
                                    <option value="sponsored">Spons</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small">Notater</label>
                                <textarea class="form-control form-control-sm" id="checkout-notes-fullscreen" rows="2"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small">Knytt til arrangement (valgfri)</label>
                                <select class="form-select form-select-sm" id="checkout-linked-event-fullscreen">
                                    <option value="">Ingen arrangement</option>
                                </select>
                                <small class="text-muted d-block mt-1">Salget vises i arrangementets resultat</small>
                            </div>
                            
                            <button id="checkout-btn-fullscreen" class="btn btn-success w-100" disabled>
                                <i class="bi bi-check-circle me-2"></i>Gjennomfør salg
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
console.log('=== SALES MODULE SCRIPT LOADED ===');
console.log('Checking for sales elements:');
console.log('- #tab-sales exists:', !!document.getElementById('tab-sales'));
console.log('- #content-sales exists:', !!document.getElementById('content-sales'));
console.log('- #products-list exists:', !!document.querySelector('#products-list'));
console.log('- #services-list exists:', !!document.querySelector('#services-list'));

// Barcode scanner functionality
let productsCache = [];
let scanTimeout = null;

function initBarcodeScanner() {
    const barcodeInput = document.getElementById('barcode-scanner-input');
    const scannerCard = document.querySelector('.barcode-scanner-card');
    const scanFeedback = document.getElementById('scan-feedback');
    
    if (!barcodeInput) return;
    
    barcodeInput.addEventListener('input', function(e) {
        if (scanTimeout) clearTimeout(scanTimeout);
        
        const barcode = e.target.value.trim();
        
        if (barcode.length >= 8) {
            scanTimeout = setTimeout(() => {
                handleBarcodeScanned(barcode, scannerCard, scanFeedback, barcodeInput);
            }, 300);
        }
    });
    
    barcodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const barcode = e.target.value.trim();
            if (barcode) {
                handleBarcodeScanned(barcode, scannerCard, scanFeedback, barcodeInput);
            }
        }
    });
}

async function handleBarcodeScanned(barcode, scannerCard, scanFeedback, inputField) {
    scannerCard.classList.add('scanning');
    scanFeedback.textContent = 'Søker etter produkt...';
    scanFeedback.className = 'scan-feedback';
    
    const product = productsCache.find(p => p.barcode === barcode || p.id === barcode);
    
    if (product) {
        await addToCart(product.id);
        scanFeedback.textContent = `✓ ${product.name} lagt til!`;
        scanFeedback.classList.add('success');
        inputField.value = '';
    } else {
        scanFeedback.textContent = `✗ Ingen produkt funnet med strekkode: ${barcode}`;
        scanFeedback.classList.add('error');
    }
    
    setTimeout(() => {
        scannerCard.classList.remove('scanning');
        if (product) {
            scanFeedback.textContent = '';
            scanFeedback.className = 'scan-feedback';
        }
    }, 2000);
}

// Load products and services
async function loadSalesProducts() {
    console.log('loadSalesProducts() called');
    try {
        const response = await fetch('/api/get_sales_items.php');
        const data = await response.json();
        
        if (data.success && data.items) {
            const products = data.items.filter(item => item.product_type === 'product');
            const services = data.items.filter(item => item.product_type !== 'product');
            
            productsCache = products;
            
            displayProducts(products);
            displayServices(services);
        } else {
            // Show error message when API fails
            displayProducts([]);
            displayServices([]);
            
            const salesModule = document.getElementById('content-sales');
            if (salesModule) {
                const productsContainer = salesModule.querySelector('#products-list');
                const servicesContainer = salesModule.querySelector('#services-list');
                
                const errorHtml = '<div class="col-12"><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Database ikke tilgjengelig. Produkter kan ikke lastes.</div></div>';
                
                if (productsContainer) productsContainer.innerHTML = errorHtml;
                if (servicesContainer) servicesContainer.innerHTML = errorHtml;
            }
        }
    } catch (error) {
        console.error('Error loading products:', error);
        
        // Show error message when fetch fails
        const salesModule = document.getElementById('content-sales');
        if (salesModule) {
            const productsContainer = salesModule.querySelector('#products-list');
            const servicesContainer = salesModule.querySelector('#services-list');
            
            const errorHtml = '<div class="col-12"><div class="alert alert-danger"><i class="bi bi-exclamation-triangle me-2"></i>Kunne ikke laste produkter. Sjekk tilkoblingen.</div></div>';
            
            if (productsContainer) productsContainer.innerHTML = errorHtml;
            if (servicesContainer) servicesContainer.innerHTML = errorHtml;
        }
    }
    
    await loadCart();
}

function displayProducts(products) {
    // Scope to active sales module to avoid duplicate IDs
    const salesModule = document.getElementById('content-sales');
    if (!salesModule) return;
    
    const container = salesModule.querySelector('#products-list');
    if (!container) return;
    
    if (products.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted py-4">Ingen produkter tilgjengelig</div>';
        return;
    }
    
    container.innerHTML = products.map(product => {
        const imgUrl = product.img_product || 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="200"%3E%3Crect fill="%23f0f0f0" width="400" height="200"/%3E%3Ctext fill="%23999" x="50%25" y="50%25" text-anchor="middle" dy=".3em" font-size="30"%3EIngen bilde%3C/text%3E%3C/svg%3E';
        
        return `
        <div class="col-md-3 col-sm-4 col-6 mb-2">
            <div class="card h-100 position-relative overflow-hidden" 
                 style="cursor: pointer; border-radius: 12px; border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" 
                 onclick="addToCart('${product.id}')">
                <img src="${imgUrl}" 
                     class="card-img-top" 
                     style="height: 150px; width: 100%; object-fit: cover; border-radius: 12px 12px 0 0;" 
                     alt="${product.item_name}">
                <div class="position-absolute bottom-0 start-0 end-0 p-2" 
                     style="background: linear-gradient(to top, rgba(0,0,0,0.9) 0%, rgba(0,0,0,0.6) 70%, transparent 100%); border-radius: 0 0 12px 12px;">
                    <h6 class="text-white mb-0 fw-bold" style="font-size: 0.75rem; line-height: 1.2;">${product.item_name}</h6>
                    <p class="text-white mb-0 fw-bold" style="font-size: 0.9rem;">${product.price} NOK</p>
                </div>
            </div>
        </div>
        `;
    }).join('');
}

function displayServices(services) {
    const salesModule = document.getElementById('content-sales');
    if (!salesModule) return;
    
    const container = salesModule.querySelector('#services-list');
    
    if (services.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted py-4">Ingen tjenester tilgjengelig</div>';
        return;
    }
    
    const colors = {
        'event': 'primary',
        'membership': 'success',
        'rental': 'warning'
    };
    
    container.innerHTML = '<div class="d-flex flex-wrap gap-2">' + services.map(service => {
        const color = colors[service.product_type] || 'secondary';
        const durationMonths = service.duration_months || (service.billing_cycle ? 
            (service.billing_cycle === 'monthly' ? 1 : service.billing_cycle === 'quarterly' ? 3 : 12) : null);
        
        // Escape single quotes in name to prevent JavaScript errors
        const safeName = (service.item_name || '').replace(/'/g, "\\'");
        
        return `
            <div class="badge bg-${color} p-2 text-start" 
                 style="cursor: pointer; min-width: 180px; max-width: 250px;"
                 data-bs-toggle="modal" 
                 data-bs-target="#servicePurchaseModal" 
                 onclick="setServiceData('${service.id}', '${service.product_type}', '${safeName}', ${service.price}, ${durationMonths})">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="flex-grow-1">
                        <div style="font-size: 0.9rem; font-weight: 600;">${service.item_name}</div>
                        <small style="opacity: 0.9;">${service.price} NOK • ${getServiceTypeName(service.product_type)}</small>
                    </div>
                    <i class="bi bi-bag-check ms-2"></i>
                </div>
            </div>
        `;
    }).join('') + '</div>';
}

function getServiceTypeName(type) {
    const types = {
        'event': 'Arrangement',
        'membership': 'Medlemskap',
        'rental': 'Utleie',
        'reservation': 'Reservasjon'
    };
    return types[type] || type;
}

async function addToCart(productId) {
    const quantity = 1; // Always add 1 item when clicking product card
    
    try {
        const response = await fetch('/api/add_to_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({output_product_id: productId, quantity: quantity})
        });
        
        const result = await response.json();
        
        if (result.success) {
            await loadCart();
            // Visual feedback
            showToast('✅ Lagt til i handlekurv');
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Add to cart error:', error);
    }
}

function showToast(message) {
    const existingToast = document.querySelector('.cart-toast');
    if (existingToast) existingToast.remove();
    
    const toast = document.createElement('div');
    toast.className = 'cart-toast position-fixed top-0 start-50 translate-middle-x mt-3';
    toast.style.cssText = 'z-index: 9999; padding: 12px 24px; background: #28a745; color: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.3);';
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => toast.remove(), 2000);
}

async function loadCart() {
    try {
        const response = await fetch('/api/get_cart.php');
        const data = await response.json();
        
        if (data.success) {
            displayCart(data.items, data.total, data.count);
        }
    } catch (error) {
        console.error('Load cart error:', error);
    }
}

function displayCart(items, total, count) {
    const salesModule = document.getElementById('content-sales');
    if (!salesModule) return;
    
    const container = salesModule.querySelector('#cart-items');
    const countBadge = salesModule.querySelector('#cart-count');
    const totalElement = salesModule.querySelector('#cart-total');
    const checkoutBtn = salesModule.querySelector('#checkout-btn');
    
    countBadge.textContent = count;
    totalElement.textContent = total.toFixed(2);
    
    if (items.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-3">Handlekurven er tom</p>';
        checkoutBtn.disabled = true;
        return;
    }
    
    checkoutBtn.disabled = false;
    
    container.innerHTML = items.map(item => `
        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
            <div class="flex-grow-1">
                <small class="fw-bold">${item.product_name}</small><br>
                <small class="text-muted">${item.price} NOK × ${item.quantity}</small>
            </div>
            <div class="d-flex align-items-center gap-1">
                <input type="number" class="form-control form-control-sm" style="width: 60px;" value="${item.quantity}" min="1" onchange="updateCartQuantity(${item.cart_id}, this.value)">
                <button class="btn btn-sm btn-outline-danger" onclick="removeFromCart(${item.cart_id})">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `).join('');
}

async function updateCartQuantity(cartId, quantity) {
    try {
        const response = await fetch('/api/update_cart_quantity.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({cart_id: cartId, quantity: parseInt(quantity)})
        });
        
        const result = await response.json();
        if (result.success) await loadCart();
    } catch (error) {
        console.error('Update quantity error:', error);
    }
}

async function removeFromCart(cartId) {
    try {
        const response = await fetch('/api/remove_from_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({cart_id: cartId})
        });
        
        const result = await response.json();
        if (result.success) await loadCart();
    } catch (error) {
        console.error('Remove from cart error:', error);
    }
}

// Cash payment tracking
let cashReceived = 0;
let cartTotal = 0;

async function applyCashPayment(amount) {
    const salesModule = document.getElementById('content-sales');
    if (!salesModule) return;
    
    const totalElement = salesModule.querySelector('#cart-total');
    cartTotal = parseFloat(totalElement.textContent) || 0;
    
    if (cartTotal === 0) {
        alert('❌ Handlekurven er tom');
        return;
    }
    
    cashReceived += amount;
    
    const remaining = cartTotal - cashReceived;
    const paymentStatus = salesModule.querySelector('#payment-status');
    const paymentStatusText = salesModule.querySelector('#payment-status-text');
    const paymentMethodSelect = salesModule.querySelector('#checkout-payment-method');
    
    paymentStatus.style.display = 'block';
    
    if (remaining > 0) {
        // Still need more money
        paymentStatus.className = 'alert alert-warning mb-3';
        paymentStatusText.innerHTML = `
            <div><strong>Mottatt:</strong> ${cashReceived.toFixed(2)} NOK 💵</div>
            <div class="text-danger"><strong>Restbeløp:</strong> ${remaining.toFixed(2)} NOK</div>
            <small class="text-muted">Trykk en annen pengeknapp eller fullfør manuelt</small>
        `;
        paymentMethodSelect.disabled = false;
    } else {
        // Enough money received - auto-complete transaction
        const change = remaining < 0 ? Math.abs(remaining) : 0;
        paymentStatus.className = 'alert alert-success mb-3';
        paymentStatusText.innerHTML = `
            <div><strong>Mottatt:</strong> ${cashReceived.toFixed(2)} NOK 💵</div>
            ${change > 0 ? `<div class="text-success fs-5"><strong>Vekslepenger:</strong> ${change.toFixed(2)} NOK 💰</div>` : '<div class="text-success">✅ Eksakt beløp betalt</div>'}
            <div class="mt-2"><small class="text-muted">Gjennomfører salg...</small></div>
        `;
        paymentMethodSelect.value = 'cash';
        paymentMethodSelect.disabled = true;
        
        // Auto-complete checkout
        await performCheckout(salesModule, change);
    }
}

async function performCheckout(salesModule, change = 0) {
    const paymentMethod = salesModule.querySelector('#checkout-payment-method').value;
    const notes = salesModule.querySelector('#checkout-notes').value;
    const linkedEvent = salesModule.querySelector('#checkout-linked-event').value;
    
    try {
        const response = await fetch('/api/checkout_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                event_id: linkedEvent || null,
                payment_method: paymentMethod, 
                notes: notes
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            let message = `✅ Salg gjennomført!\nAntall varer: ${result.items_sold}\nTotalsum: ${result.total.toFixed(2)} NOK`;
            
            if (change > 0) {
                message += `\n💰 Vekslepenger: ${change.toFixed(2)} NOK`;
            }
            
            alert(message);
            salesModule.querySelector('#checkout-notes').value = '';
            salesModule.querySelector('#checkout-linked-event').value = '';
            resetPayment();
            await loadCart();
            await loadAllSalesData();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Checkout error:', error);
        alert('❌ Feil ved betaling');
    }
}

function resetPayment() {
    cashReceived = 0;
    const salesModule = document.getElementById('content-sales');
    if (!salesModule) return;
    
    const paymentStatus = salesModule.querySelector('#payment-status');
    const paymentMethodSelect = salesModule.querySelector('#checkout-payment-method');
    
    paymentStatus.style.display = 'none';
    paymentMethodSelect.disabled = false;
}

// Fullscreen cash payment tracking
let cashReceivedFullscreen = 0;
let cartTotalFullscreen = 0;

async function applyCashPaymentFullscreen(amount) {
    const totalElement = document.getElementById('cart-total-fullscreen');
    cartTotalFullscreen = parseFloat(totalElement.textContent) || 0;
    
    if (cartTotalFullscreen === 0) {
        alert('❌ Handlekurven er tom');
        return;
    }
    
    cashReceivedFullscreen += amount;
    
    const remaining = cartTotalFullscreen - cashReceivedFullscreen;
    const paymentStatus = document.getElementById('payment-status-fullscreen');
    const paymentStatusText = document.getElementById('payment-status-text-fullscreen');
    const paymentMethodSelect = document.getElementById('checkout-payment-method-fullscreen');
    
    paymentStatus.style.display = 'block';
    
    if (remaining > 0) {
        // Still need more money
        paymentStatus.className = 'alert alert-warning mb-3';
        paymentStatusText.innerHTML = `
            <div><strong>Mottatt:</strong> ${cashReceivedFullscreen.toFixed(2)} NOK 💵</div>
            <div class="text-danger"><strong>Restbeløp:</strong> ${remaining.toFixed(2)} NOK</div>
            <small class="text-muted">Trykk en annen pengeknapp eller fullfør manuelt</small>
        `;
        paymentMethodSelect.disabled = false;
    } else {
        // Enough money received - auto-complete transaction
        const change = remaining < 0 ? Math.abs(remaining) : 0;
        paymentStatus.className = 'alert alert-success mb-3';
        paymentStatusText.innerHTML = `
            <div><strong>Mottatt:</strong> ${cashReceivedFullscreen.toFixed(2)} NOK 💵</div>
            ${change > 0 ? `<div class="text-success fs-5"><strong>Vekslepenger:</strong> ${change.toFixed(2)} NOK 💰</div>` : '<div class="text-success">✅ Eksakt beløp betalt</div>'}
            <div class="mt-2"><small class="text-muted">Gjennomfører salg...</small></div>
        `;
        paymentMethodSelect.value = 'cash';
        paymentMethodSelect.disabled = true;
        
        // Auto-complete checkout
        await performCheckoutFullscreen(change);
    }
}

async function performCheckoutFullscreen(change = 0) {
    const paymentMethod = document.getElementById('checkout-payment-method-fullscreen').value;
    const notes = document.getElementById('checkout-notes-fullscreen').value;
    const linkedEvent = document.getElementById('checkout-linked-event-fullscreen').value;
    
    try {
        const response = await fetch('/api/checkout_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                event_id: linkedEvent || null,
                payment_method: paymentMethod, 
                notes: notes
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            let message = `✅ Salg gjennomført!\nAntall varer: ${result.items_sold}\nTotalsum: ${result.total.toFixed(2)} NOK`;
            
            if (change > 0) {
                message += `\n💰 Vekslepenger: ${change.toFixed(2)} NOK`;
            }
            
            alert(message);
            document.getElementById('checkout-notes-fullscreen').value = '';
            document.getElementById('checkout-linked-event-fullscreen').value = '';
            resetPaymentFullscreen();
            await loadCart();
            await loadCartFullscreen();
            await loadAllSalesData();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Checkout error:', error);
        alert('❌ Feil ved betaling');
    }
}

function resetPaymentFullscreen() {
    cashReceivedFullscreen = 0;
    const paymentStatus = document.getElementById('payment-status-fullscreen');
    const paymentMethodSelect = document.getElementById('checkout-payment-method-fullscreen');
    
    paymentStatus.style.display = 'none';
    paymentMethodSelect.disabled = false;
}

// Scope checkout button to sales module
const salesModule = document.getElementById('content-sales');
if (salesModule) {
    const checkoutBtn = salesModule.querySelector('#checkout-btn');
    checkoutBtn?.addEventListener('click', async function() {
        const paymentMethod = salesModule.querySelector('#checkout-payment-method').value;
        const notes = salesModule.querySelector('#checkout-notes').value;
        const linkedEvent = salesModule.querySelector('#checkout-linked-event').value;
    
    try {
        const response = await fetch('/api/checkout_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                event_id: linkedEvent || null,
                payment_method: paymentMethod, 
                notes: notes
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            let message = `✅ Salg gjennomført!\nAntall varer: ${result.items_sold}\nTotalsum: ${result.total.toFixed(2)} NOK`;
            
            // Show change if cash payment was used
            if (cashReceived > 0) {
                const change = cashReceived - result.total;
                if (change > 0) {
                    message += `\n💰 Vekslepenger: ${change.toFixed(2)} NOK`;
                }
            }
            
            alert(message);
            salesModule.querySelector('#checkout-notes').value = '';
            salesModule.querySelector('#checkout-linked-event').value = '';
            resetPayment();
            await loadCart();
            await loadAllSalesData(); // Refresh transactions and products
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Checkout error:', error);
        alert('❌ Feil ved betaling');
    }
    });
}

// Initialize when DOM is fully ready
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired in sales module');
    
    const salesTab = document.getElementById('tab-sales');
    const salesContent = document.getElementById('content-sales');
    
    console.log('Sales tab button:', salesTab);
    console.log('Sales content pane:', salesContent);
    
    if (salesTab) {
        // Listen for tab shown event
        salesTab.addEventListener('shown.bs.tab', function(e) {
            console.log('✅ Sales tab shown via event - loading data...');
            loadAllSalesData();
        });
        
        // Check if already active on page load
        if (salesTab.classList.contains('active') && salesContent && salesContent.classList.contains('active')) {
            console.log('✅ Sales is already active tab - loading data immediately...');
            setTimeout(() => {
                loadAllSalesData();
            }, 200);
        }
    } else {
        console.error('❌ #tab-sales button not found!');
    }
});

// Load events for linking sales to events
async function loadEventsForLinking() {
    try {
        const response = await fetch('/api/get_events.php');
        const result = await response.json();
        
        if (result.success && result.events) {
            const eventSelect = document.getElementById('checkout-linked-event');
            if (eventSelect) {
                const eventOptions = result.events
                    .filter(event => new Date(event.start_date) >= new Date()) // Only future events
                    .map(event => `<option value="${event.id}">${event.title} - ${new Date(event.start_date).toLocaleDateString('nb-NO')}</option>`)
                    .join('');
                
                eventSelect.innerHTML = '<option value="">Ingen arrangement</option>' + eventOptions;
            }
        }
    } catch (error) {
        console.error('Error loading events for linking:', error);
    }
}

// Load all sales data in one function
async function loadAllSalesData() {
    console.log('=== loadAllSalesData() called ===');
    await Promise.all([
        loadSalesProducts(),
        loadTransactions(),
        loadProductsWithProfit(),
        loadEventsForLinking()
    ]);
    
    // Setup cash calculators after data loads
    setupCashCalculator();
    setupServiceCashCalculator();
    
    console.log('=== loadAllSalesData() completed ===');
}

async function loadProductsWithProfit() {
    try {
        const response = await fetch('/api/get_sales_products.php');
        const data = await response.json();
        
        if (data.success && data.products) {
            displayProductsWithProfit(data.products);
        }
    } catch (error) {
        console.error('Error loading products with profit:', error);
    }
}

function displayProductsWithProfit(products) {
    const container = document.getElementById('sales-items-list');
    if (!container) return;
    
    if (products.length === 0) {
        container.innerHTML = '<tr><td colspan="9" class="text-center text-muted py-4">Ingen produkter registrert</td></tr>';
        return;
    }
    
    container.innerHTML = products.map(product => {
        const profit = parseFloat(product.profit_margin) || 0;
        const profitClass = profit > 0 ? 'text-success' : 'text-danger';
        const statusBadge = product.is_active == 1 
            ? '<span class="badge bg-success">Aktiv</span>' 
            : '<span class="badge bg-secondary">Inaktiv</span>';
        
        // Stock status with color indicators
        let stockBadge = '<span class="badge bg-secondary">-</span>';
        let stockValue = product.stock_quantity !== null ? parseInt(product.stock_quantity) : 0;
        if (product.stock_quantity !== null) {
            const stock = parseInt(product.stock_quantity);
            const threshold = parseInt(product.low_stock_threshold || 5);
            
            if (stock <= 0) {
                stockBadge = `<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Tom (${stock})</span>`;
            } else if (stock <= threshold) {
                stockBadge = `<span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Lavt (${stock})</span>`;
            } else {
                stockBadge = `<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>${stock} stk</span>`;
            }
        }
        
        return `
            <tr data-product-id="${product.id}">
                <td class="editable-cell" data-field="name" style="cursor:pointer;" title="Klikk for å redigere">${product.name}</td>
                <td><code>${product.sku || '-'}</code></td>
                <td>${product.category_id || '-'}</td>
                <td class="editable-cell" data-field="cost_price" style="cursor:pointer;" title="Klikk for å redigere">${parseFloat(product.cost_price || 0).toFixed(2)} NOK</td>
                <td class="editable-cell" data-field="selling_price" style="cursor:pointer;" title="Klikk for å redigere">${parseFloat(product.selling_price).toFixed(2)} NOK</td>
                <td class="${profitClass}"><strong>${profit.toFixed(2)} NOK</strong></td>
                <td>${stockBadge}</td>
                <td>${statusBadge}</td>
                <td>
                    <button class="btn btn-sm btn-outline-primary" onclick="editProduct('${product.id}')">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteProduct('${product.id}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    }).join('');
}

async function editProduct(productId) {
    try {
        const response = await fetch(`/api/get_single_product.php?id=${productId}`);
        const result = await response.json();
        
        if (result.success) {
            const p = result.product;
            
            document.getElementById('edit-product-id').value = p.id;
            document.getElementById('edit-name').value = p.name || '';
            document.getElementById('edit-unit').value = p.unit || 'stk';
            document.getElementById('edit-cost-price').value = p.cost_price || 0;
            document.getElementById('edit-selling-price').value = p.selling_price || 0;
            document.getElementById('edit-img-product').value = p.img_product || '';
            document.getElementById('edit-tax-rate').value = p.tax_rate || 25;
            document.getElementById('edit-reorder-level').value = p.reorder_level || 0;
            document.getElementById('edit-description').value = p.description || '';
            document.getElementById('edit-is-active').checked = p.is_active == 1;
            document.getElementById('edit-item-taxable').checked = p.item_taxable == 1;
            
            const editModal = new bootstrap.Modal(document.getElementById('editSalesItemModal'));
            editModal.show();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Load product error:', error);
        alert('❌ Feil ved henting av produktdata');
    }
}

async function submitEditProduct() {
    const form = document.getElementById('editSalesItemForm');
    const formData = new FormData(form);
    
    const data = {
        id: formData.get('id'),
        name: formData.get('name'),
        unit: formData.get('unit'),
        cost_price: parseFloat(formData.get('cost_price')) || 0,
        selling_price: parseFloat(formData.get('selling_price')),
        img_product: formData.get('img_product'),
        tax_rate: parseFloat(formData.get('tax_rate')),
        reorder_level: parseInt(formData.get('reorder_level')) || 0,
        description: formData.get('description'),
        is_active: form.querySelector('[name="is_active"]').checked,
        item_taxable: form.querySelector('[name="item_taxable"]').checked
    };
    
    try {
        const response = await fetch('/api/update_sales_product.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Produktet ble oppdatert');
            
            // Properly close modal and remove backdrop
            const modalElement = document.getElementById('editSalesItemModal');
            const modalInstance = bootstrap.Modal.getInstance(modalElement);
            if (modalInstance) {
                modalInstance.hide();
            }
            
            // Remove backdrop manually as fallback
            setTimeout(() => {
                document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('padding-right');
                document.body.style.removeProperty('overflow');
            }, 300);
            
            await loadSalesProducts();
            await loadAllSalesData();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Update product error:', error);
        alert('❌ Feil ved oppdatering av produkt');
    }
}

function deleteProduct(productId) {
    if (confirm('Er du sikker på at du vil slette dette produktet?')) {
        alert('Sletting kommer snart for produkt: ' + productId);
    }
}


async function loadTransactions() {
    const salesModule = document.getElementById('content-sales');
    if (!salesModule) return;
    
    const container = salesModule.querySelector('#transactions-list');
    if (!container) return;
    
    try {
        const response = await fetch('/api/get_transactions.php');
        const data = await response.json();
        
        if (data.success && data.transactions) {
            if (data.transactions.length === 0) {
                container.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Ingen transaksjoner funnet
                        </td>
                    </tr>
                `;
                return;
            }
            
            container.innerHTML = data.transactions.map(tx => `
                <tr>
                    <td><code>#${tx.transaction_id}</code></td>
                    <td>${new Date(tx.created_at).toLocaleString('nb-NO')}</td>
                    <td>${tx.customer_name || '-'}</td>
                    <td><small>${tx.products || '-'}</small></td>
                    <td><span class="badge bg-secondary">${getPaymentMethodName(tx.payment_method)}</span></td>
                    <td><strong>${parseFloat(tx.total_amount).toFixed(2)} NOK</strong></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewTransactionDetails(${tx.transaction_id})">
                            <i class="bi bi-eye"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading transactions:', error);
        container.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-danger py-4">
                    Feil ved lasting av transaksjoner
                </td>
            </tr>
        `;
    }
}

function getPaymentMethodName(method) {
    const methods = {
        'cash': 'Kontant',
        'vipps': 'Vipps',
        'on_arrival': 'Oppmøte',
        'card': 'Kort',
        'online': 'Nettbank'
    };
    return methods[method] || method;
}

// Cash calculator for checkout
function setupCashCalculator() {
    const paymentSelect = document.getElementById('checkout-payment-method');
    const cashCalculator = document.getElementById('cash-payment-calculator');
    const cashReceivedInput = document.getElementById('cash-received');
    const changeDisplay = document.getElementById('cash-change-display');
    
    if (!paymentSelect || !cashCalculator) return;
    
    paymentSelect.addEventListener('change', function() {
        if (this.value === 'cash') {
            cashCalculator.style.display = 'block';
        } else {
            cashCalculator.style.display = 'none';
            cashReceivedInput.value = '';
            changeDisplay.innerHTML = '';
        }
    });
    
    cashReceivedInput.addEventListener('input', function() {
        const total = parseFloat(document.getElementById('checkout-total')?.textContent || 0);
        const received = parseFloat(this.value || 0);
        const change = received - total;
        
        if (received === 0) {
            changeDisplay.innerHTML = '';
        } else if (change < 0) {
            changeDisplay.innerHTML = `<div class="alert alert-danger py-1 px-2 mb-0 small"><i class="bi bi-exclamation-triangle me-1"></i><strong>Mangler:</strong> ${Math.abs(change).toFixed(2)} NOK</div>`;
        } else if (change === 0) {
            changeDisplay.innerHTML = `<div class="alert alert-success py-1 px-2 mb-0 small"><i class="bi bi-check-circle me-1"></i><strong>Passe penger</strong></div>`;
        } else {
            changeDisplay.innerHTML = `<div class="alert alert-info py-1 px-2 mb-0 small"><i class="bi bi-cash-coin me-1"></i><strong>Retur:</strong> ${change.toFixed(2)} NOK</div>`;
        }
    });
}

// Cash calculator for service sales
function setupServiceCashCalculator() {
    const paymentSelect = document.getElementById('service-payment-method');
    const cashCalculator = document.getElementById('service-cash-calculator');
    const cashReceivedInput = document.getElementById('service-cash-received');
    const changeDisplay = document.getElementById('service-cash-change');
    
    if (!paymentSelect || !cashCalculator) return;
    
    paymentSelect.addEventListener('change', function() {
        if (this.value === 'cash') {
            cashCalculator.style.display = 'block';
        } else {
            cashCalculator.style.display = 'none';
            cashReceivedInput.value = '';
            changeDisplay.innerHTML = '';
        }
    });
    
    cashReceivedInput.addEventListener('input', function() {
        const total = parseFloat(document.getElementById('service-total')?.textContent || 0);
        const received = parseFloat(this.value || 0);
        const change = received - total;
        
        if (received === 0) {
            changeDisplay.innerHTML = '';
        } else if (change < 0) {
            changeDisplay.innerHTML = `<div class="alert alert-danger py-2 mb-0"><i class="bi bi-exclamation-triangle me-1"></i><strong>Mangler:</strong> ${Math.abs(change).toFixed(2)} NOK</div>`;
        } else if (change === 0) {
            changeDisplay.innerHTML = `<div class="alert alert-success py-2 mb-0"><i class="bi bi-check-circle me-1"></i><strong>Passe penger</strong></div>`;
        } else {
            changeDisplay.innerHTML = `<div class="alert alert-info py-2 mb-0"><i class="bi bi-cash-coin me-1"></i><strong>Retur:</strong> ${change.toFixed(2)} NOK</div>`;
        }
    });
}

function viewTransactionDetails(transactionId) {
    alert('Detaljer for transaksjon #' + transactionId + ' kommer snart');
}

async function submitSalesItem() {
    const form = document.getElementById('addSalesItemForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const formData = new FormData(form);
    const productData = {
        name: formData.get('name'),
        description: formData.get('description'),
        cost_price: parseFloat(formData.get('cost_price')) || 0,
        selling_price: parseFloat(formData.get('selling_price')),
        img_product: formData.get('img_product') || '',
        member_price: formData.get('member_price') ? parseFloat(formData.get('member_price')) : null,
        tax_rate: parseFloat(formData.get('tax_rate')),
        unit: formData.get('unit'),
        reorder_level: parseInt(formData.get('reorder_level')) || 0,
        is_active: formData.get('is_active') ? true : false,
        item_taxable: formData.get('item_taxable') ? true : false
    };
    
    try {
        const response = await fetch('/api/create_sales_product.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(productData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ Produkt opprettet!\nProdukt ID: ${result.product_id}`);
            bootstrap.Modal.getInstance(document.getElementById('addSalesItemModal')).hide();
            form.reset();
            await loadAllSalesData(); // Reload all sales data
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Product creation error:', error);
        alert('❌ Feil ved opprettelse av produkt');
    }
}

// Service purchase functions
async function setServiceData(id, type, name, price, planDuration = null, reservationData = null) {
    console.log('setServiceData called:', {id, type, name, price, planDuration, reservationData});
    
    document.getElementById('service-id').value = id;
    document.getElementById('service-type').value = type;
    document.getElementById('service-price-value').value = price;
    document.getElementById('service-modal-name').textContent = name;
    document.getElementById('service-modal-type').textContent = getServiceTypeName(type);
    document.getElementById('service-modal-price').textContent = price;
    
    // Get references to field sections
    const membershipFields = document.getElementById('membership-specific-fields');
    const reservationFields = document.getElementById('reservation-specific-fields');
    const quantityField = document.getElementById('service-quantity-field');
    
    // Hide all special fields first
    membershipFields.style.display = 'none';
    reservationFields.style.display = 'none';
    quantityField.style.display = 'block';
    
    if (type === 'membership') {
        membershipFields.style.display = 'block';
        quantityField.style.display = 'none';
        document.getElementById('service-quantity').value = 1;
        
        // Store plan duration
        document.getElementById('service-plan-duration').value = planDuration || 12;
        
        // Set default join date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('service-join-date').value = today;
        
        // Calculate expiry date automatically
        updateExpiryDate();
    } else if (type === 'event') {
        // Event tickets always have quantity = 1
        quantityField.style.display = 'none';
        document.getElementById('service-quantity').value = 1;
    } else if (type === 'service' && reservationData) {
        // This is a reservable service - show reservation fields
        reservationFields.style.display = 'block';
        quantityField.style.display = 'none';
        document.getElementById('service-quantity').value = 1;
        
        // Store reservation data globally for calendar
        window.currentReservationData = reservationData;
        window.currentCalendarWeekOffset = 0;
        
        document.getElementById('reservation-service-id').value = reservationData.service_id;
        document.getElementById('reservation-duration').value = reservationData.duration_minutes;
        document.getElementById('reservation-assigned-staff').value = JSON.stringify(reservationData.assigned_staff_ids);
        document.getElementById('reservation-duration-display').textContent = reservationData.duration_minutes;
        document.getElementById('reservation-staff-names').textContent = reservationData.staff_names || 'Ingen valgt';
        
        // Load calendar
        loadReservationCalendar();
    }
    
    updateServiceTotal();
}

function updateExpiryDate() {
    const joinDateStr = document.getElementById('service-join-date').value;
    const planDuration = parseInt(document.getElementById('service-plan-duration').value) || 12;
    
    if (joinDateStr) {
        const joinDate = new Date(joinDateStr);
        const expiryDate = new Date(joinDate);
        expiryDate.setMonth(expiryDate.getMonth() + planDuration);
        document.getElementById('service-expiry-date').value = expiryDate.toISOString().split('T')[0];
    }
}

// Reservation functions
async function checkAvailability() {
    const serviceId = document.getElementById('reservation-service-id').value;
    const date = document.getElementById('reservation-date').value;
    const time = document.getElementById('reservation-time').value;
    const staffId = document.getElementById('reservation-staff-select').value;
    const duration = parseInt(document.getElementById('reservation-duration').value);
    
    const statusDiv = document.getElementById('availability-status');
    
    if (!serviceId || !date || !time || !staffId || !duration) {
        statusDiv.innerHTML = '';
        return;
    }
    
    statusDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Sjekker...';
    
    try {
        const response = await fetch('/api/check_reservation_availability.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                service_id: serviceId,
                date: date,
                time: time,
                staff_id: staffId,
                duration: duration
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            if (result.available) {
                statusDiv.innerHTML = '<div class="alert alert-success alert-sm mb-0 py-1 px-2"><i class="bi bi-check-circle me-1"></i>Ledig</div>';
            } else {
                statusDiv.innerHTML = `<div class="alert alert-warning alert-sm mb-0 py-1 px-2"><i class="bi bi-exclamation-triangle me-1"></i>Opptatt - ${result.message || 'Velg annet tidspunkt'}</div>`;
            }
        } else {
            statusDiv.innerHTML = `<div class="alert alert-danger alert-sm mb-0 py-1 px-2">Feil: ${result.message}</div>`;
        }
    } catch (error) {
        console.error('Availability check error:', error);
        statusDiv.innerHTML = '<div class="alert alert-danger alert-sm mb-0 py-1 px-2">Feil ved sjekk</div>';
    }
}

async function findFirstAvailable() {
    const serviceId = document.getElementById('reservation-service-id').value;
    const duration = parseInt(document.getElementById('reservation-duration').value);
    const assignedStaff = JSON.parse(document.getElementById('reservation-assigned-staff').value || '[]');
    
    const resultDiv = document.getElementById('first-available-result');
    resultDiv.innerHTML = '<div class="spinner-border spinner-border-sm" role="status"></div> Søker...';
    
    try {
        const response = await fetch('/api/find_first_available.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                service_id: serviceId,
                duration: duration,
                assigned_staff_ids: assignedStaff,
                days_ahead: 14
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.slot) {
            resultDiv.innerHTML = `
                <div class="alert alert-success alert-sm mb-0 py-1 px-2">
                    <strong>Funnet:</strong> ${result.slot.date} kl. ${result.slot.time} med ${result.slot.staff_name}
                    <button type="button" class="btn btn-sm btn-success ms-2" onclick="applyFirstAvailable('${result.slot.date}', '${result.slot.time}', '${result.slot.staff_id}')">
                        <i class="bi bi-check me-1"></i>Bruk
                    </button>
                </div>
            `;
        } else {
            resultDiv.innerHTML = `<div class="alert alert-warning alert-sm mb-0 py-1 px-2">${result.message || 'Ingen ledige tider funnet de neste 14 dagene'}</div>`;
        }
    } catch (error) {
        console.error('First available error:', error);
        resultDiv.innerHTML = '<div class="alert alert-danger alert-sm mb-0 py-1 px-2">Feil ved søk</div>';
    }
}

function applyFirstAvailable(date, time, staffId) {
    document.getElementById('reservation-date').value = date;
    document.getElementById('reservation-time').value = time;
    document.getElementById('reservation-staff-select').value = staffId;
    document.getElementById('first-available-result').innerHTML = '';
    checkAvailability();
}

// New calendar-based reservation system
async function loadReservationCalendar() {
    const reservationData = window.currentReservationData;
    if (!reservationData) return;
    
    const weekOffset = window.currentCalendarWeekOffset || 0;
    const startDate = new Date();
    startDate.setDate(startDate.getDate() + (weekOffset * 7));
    
    // Update week label
    const weekLabel = document.getElementById('calendar-week-label');
    if (weekOffset === 0) {
        weekLabel.textContent = 'Denne uken';
    } else if (weekOffset === 1) {
        weekLabel.textContent = 'Neste uke';
    } else {
        const endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + 6);
        weekLabel.textContent = `${formatDate(startDate)} - ${formatDate(endDate)}`;
    }
    
    // Generate calendar for the week
    const calendar = document.getElementById('reservation-calendar');
    calendar.innerHTML = '<div class="text-center text-muted py-3"><i class="bi bi-hourglass-split me-2"></i>Laster...</div>';
    
    try {
        const slotsData = await generateWeekSlots(startDate, reservationData);
        renderCalendar(slotsData, reservationData);
    } catch (error) {
        console.error('Error loading calendar:', error);
        calendar.innerHTML = '<div class="alert alert-danger">Feil ved lasting av kalender</div>';
    }
}

async function generateWeekSlots(startDate, reservationData) {
    const duration = reservationData.duration_minutes;
    const staffIds = reservationData.assigned_staff_ids || [];
    const staffMap = reservationData.staff_map || {};
    
    // Calculate end date (7 days from start)
    const endDate = new Date(startDate);
    endDate.setDate(endDate.getDate() + 6);
    
    try {
        // Fetch all availability data in ONE API call
        const response = await fetch('/api/get_week_availability.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                start_date: startDate.toISOString().split('T')[0],
                end_date: endDate.toISOString().split('T')[0],
                staff_ids: staffIds,
                duration: duration,
                start_hour: 6,
                end_hour: 22,
                include_weekends: true
            })
        });
        
        const result = await response.json();
        
        if (!result.success) {
            console.error('Error fetching availability:', result.message);
            return {};
        }
        
        // Transform API response into slots format
        const slots = {};
        const availabilityData = result.availability;
        
        for (const date in availabilityData) {
            slots[date] = {};
            
            for (const staffId in availabilityData[date]) {
                slots[date][staffId] = {
                    staffName: staffMap[staffId] || staffId,
                    slots: []
                };
                
                // Convert time -> availability map to slots array
                const staffAvailability = availabilityData[date][staffId];
                for (const time in staffAvailability) {
                    slots[date][staffId].slots.push({
                        time: time.substring(0, 5), // Remove seconds (HH:MM:SS -> HH:MM)
                        available: staffAvailability[time]
                    });
                }
            }
        }
        
        return slots;
    } catch (error) {
        console.error('Error generating week slots:', error);
        return {};
    }
}

function renderCalendar(slotsData, reservationData) {
    const calendar = document.getElementById('reservation-calendar');
    const dates = Object.keys(slotsData).sort();
    
    if (dates.length === 0) {
        calendar.innerHTML = '<div class="alert alert-info">Ingen tilgjengelige dager denne uken (kun hverdager)</div>';
        return;
    }
    
    let html = '<div class="table-responsive">';
    html += '<table class="table table-sm table-bordered">';
    
    // Header row with dates
    html += '<thead><tr><th style="width: 80px;">Tid</th>';
    dates.forEach(date => {
        const dateObj = new Date(date);
        const dayName = ['Søn', 'Man', 'Tir', 'Ons', 'Tor', 'Fre', 'Lør'][dateObj.getDay()];
        const dayMonth = `${dateObj.getDate()}/${dateObj.getMonth() + 1}`;
        html += `<th class="text-center">${dayName}<br><small>${dayMonth}</small></th>`;
    });
    html += '</tr></thead><tbody>';
    
    // Get all unique times
    const allTimes = new Set();
    dates.forEach(date => {
        Object.values(slotsData[date]).forEach(staff => {
            staff.slots.forEach(slot => allTimes.add(slot.time));
        });
    });
    const sortedTimes = Array.from(allTimes).sort();
    
    // For each time slot
    sortedTimes.forEach(time => {
        html += `<tr><td class="fw-bold">${time}</td>`;
        
        dates.forEach(date => {
            html += '<td class="p-1">';
            
            const staffData = slotsData[date];
            const staffIds = Object.keys(staffData);
            
            // Show each staff member's slot
            staffIds.forEach((staffId, index) => {
                const staff = staffData[staffId];
                const slot = staff.slots.find(s => s.time === time);
                
                if (slot) {
                    const btnClass = slot.available ? 'btn-outline-success' : 'btn-outline-secondary';
                    const disabled = !slot.available ? 'disabled' : '';
                    const icon = slot.available ? '✓' : '✗';
                    
                    html += `
                        <button type="button" class="btn ${btnClass} btn-sm w-100 mb-1" 
                                ${disabled}
                                onclick="selectTimeSlot('${date}', '${time}', '${staffId}', '${staff.staffName}')"
                                title="${staff.staffName} - ${slot.available ? 'Ledig' : 'Opptatt'}">
                            <small>${icon} ${staff.staffName}</small>
                        </button>
                    `;
                }
            });
            
            html += '</td>';
        });
        
        html += '</tr>';
    });
    
    html += '</tbody></table></div>';
    calendar.innerHTML = html;
}

function selectTimeSlot(date, time, staffId, staffName) {
    // Store selected values
    document.getElementById('reservation-date').value = date;
    document.getElementById('reservation-time').value = time;
    document.getElementById('reservation-staff-select').value = staffId;
    
    // Show confirmation
    const display = document.getElementById('selected-slot-display');
    const text = document.getElementById('selected-slot-text');
    const dateObj = new Date(date);
    const dayName = ['Søndag', 'Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag'][dateObj.getDay()];
    
    text.textContent = `${dayName} ${formatDate(dateObj)} kl. ${time} med ${staffName}`;
    display.style.display = 'block';
}

function changeCalendarWeek(offset) {
    window.currentCalendarWeekOffset = (window.currentCalendarWeekOffset || 0) + offset;
    
    // Don't allow going back more than current week
    if (window.currentCalendarWeekOffset < 0) {
        window.currentCalendarWeekOffset = 0;
        return;
    }
    
    loadReservationCalendar();
}

function formatDate(date) {
    const d = new Date(date);
    const day = d.getDate();
    const month = d.getMonth() + 1;
    return `${day}.${month}`;
}

function toggleManualTimeInput() {
    const manualInput = document.getElementById('manual-time-input');
    const isVisible = manualInput.style.display !== 'none';
    
    if (!isVisible) {
        // Show manual input
        manualInput.style.display = 'block';
        
        // Set default date to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('manual-date').value = today;
        
        // Populate staff dropdown
        const reservationData = window.currentReservationData;
        if (reservationData) {
            const manualStaff = document.getElementById('manual-staff');
            manualStaff.innerHTML = '<option value="">-- Velg ansatt --</option>';
            
            if (reservationData.assigned_staff_ids && Array.isArray(reservationData.assigned_staff_ids)) {
                for (let staffId of reservationData.assigned_staff_ids) {
                    const staffName = reservationData.staff_map?.[staffId] || staffId;
                    const option = document.createElement('option');
                    option.value = staffId;
                    option.textContent = staffName;
                    manualStaff.appendChild(option);
                }
            }
        }
    } else {
        // Hide manual input
        manualInput.style.display = 'none';
    }
}

function applyManualTime() {
    const date = document.getElementById('manual-date').value;
    const time = document.getElementById('manual-time').value;
    const staffId = document.getElementById('manual-staff').value;
    
    if (!date || !time || !staffId) {
        alert('❌ Vennligst fyll ut alle felt');
        return;
    }
    
    // Get staff name
    const staffSelect = document.getElementById('manual-staff');
    const staffName = staffSelect.options[staffSelect.selectedIndex].text;
    
    // Store selected values (same as calendar selection)
    document.getElementById('reservation-date').value = date;
    document.getElementById('reservation-time').value = time;
    document.getElementById('reservation-staff-select').value = staffId;
    
    // Show confirmation
    const display = document.getElementById('selected-slot-display');
    const text = document.getElementById('selected-slot-text');
    const dateObj = new Date(date);
    const dayName = ['Søndag', 'Mandag', 'Tirsdag', 'Onsdag', 'Torsdag', 'Fredag', 'Lørdag'][dateObj.getDay()];
    
    text.textContent = `${dayName} ${formatDate(dateObj)} kl. ${time} med ${staffName} (Manuelt valgt)`;
    display.style.display = 'block';
    
    // Hide manual input
    toggleManualTimeInput();
}

document.getElementById('service-quantity')?.addEventListener('input', updateServiceTotal);

function updateServiceTotal() {
    const price = parseFloat(document.getElementById('service-price-value')?.value || 0);
    const quantity = parseInt(document.getElementById('service-quantity')?.value || 1);
    const total = price * quantity;
    document.getElementById('service-total').textContent = total.toFixed(2);
}

async function processServicePurchase() {
    const form = document.getElementById('servicePurchaseForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const serviceType = document.getElementById('service-type').value;
    const reservationFields = document.getElementById('reservation-specific-fields');
    const isReservation = reservationFields && reservationFields.style.display !== 'none';
    
    const purchaseData = {
        output_product_id: document.getElementById('service-id').value,
        service_type: serviceType,
        quantity: (serviceType === 'membership' || isReservation) ? 1 : parseInt(document.getElementById('service-quantity').value),
        customer_first_name: document.getElementById('service-customer-first-name').value,
        customer_last_name: document.getElementById('service-customer-last-name').value,
        customer_email: document.getElementById('service-customer-email').value,
        customer_phone: document.getElementById('service-customer-phone').value,
        payment_method: document.getElementById('service-payment-method').value,
        notes: document.getElementById('service-notes').value
    };
    
    // Add membership-specific fields
    if (serviceType === 'membership') {
        purchaseData.join_date = document.getElementById('service-join-date').value;
        purchaseData.expiry_date = document.getElementById('service-expiry-date').value;
        purchaseData.auto_renew = document.getElementById('service-auto-renew').checked;
        purchaseData.customer_birth_date = document.getElementById('service-customer-birth-date').value;
        purchaseData.customer_address = document.getElementById('service-customer-address').value;
        purchaseData.customer_postal_code = document.getElementById('service-customer-postal-code').value;
        purchaseData.customer_city = document.getElementById('service-customer-city').value;
    }
    
    // Add reservation-specific fields
    if (isReservation) {
        purchaseData.is_reservation = true;
        purchaseData.reservation_service_id = document.getElementById('reservation-service-id').value;
        purchaseData.reservation_date = document.getElementById('reservation-date').value;
        purchaseData.reservation_time = document.getElementById('reservation-time').value;
        purchaseData.reservation_staff_id = document.getElementById('reservation-staff-select').value;
        purchaseData.reservation_duration = parseInt(document.getElementById('reservation-duration').value);
        
        // Validate reservation fields
        if (!purchaseData.reservation_date || !purchaseData.reservation_time || !purchaseData.reservation_staff_id) {
            alert('⚠️ Vennligst fyll ut alle booking-felter');
            return;
        }
    }
    
    try {
        const response = await fetch('/api/process_service_sale.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(purchaseData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            let message = `✅ ${isReservation ? 'Reservasjon booket' : 'Tjeneste solgt'}!\nKunde: ${purchaseData.customer_first_name} ${purchaseData.customer_last_name}`;
            if (isReservation) {
                message += `\nDato: ${purchaseData.reservation_date} kl. ${purchaseData.reservation_time}`;
            }
            message += `\nTotalsum: ${result.total} NOK`;
            alert(message);
            bootstrap.Modal.getInstance(document.getElementById('servicePurchaseModal')).hide();
            form.reset();
            await loadSalesProducts();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Service purchase error:', error);
        alert('❌ Feil ved salg av tjeneste');
    }
}

// Initialize barcode scanner when Sales module loads
document.addEventListener('DOMContentLoaded', function() {
    initBarcodeScanner();
    initFullscreenSales();
});

// Also re-initialize when switching to sales tab
document.querySelector('[href="#content-sales"]')?.addEventListener('shown.bs.tab', function() {
    initBarcodeScanner();
});

// Fullscreen Sales Mode
function initFullscreenSales() {
    const openBtn = document.getElementById('open-fullscreen-sales');
    const closeBtn = document.getElementById('close-fullscreen-sales');
    const overlay = document.getElementById('fullscreen-sales-overlay');
    
    if (!openBtn || !closeBtn || !overlay) return;
    
    openBtn.addEventListener('click', function() {
        overlay.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // Sync data to fullscreen
        syncToFullscreen();
        
        // Focus barcode scanner in fullscreen
        setTimeout(() => {
            document.getElementById('barcode-scanner-input-fullscreen')?.focus();
        }, 100);
    });
    
    closeBtn.addEventListener('click', function() {
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    // ESC key to close
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && overlay.classList.contains('active')) {
            closeBtn.click();
        }
    });
    
    // Initialize fullscreen barcode scanner
    initFullscreenBarcodeScanner();
    
    // Initialize fullscreen checkout button
    const checkoutBtnFullscreen = document.getElementById('checkout-btn-fullscreen');
    if (checkoutBtnFullscreen) {
        checkoutBtnFullscreen.addEventListener('click', async function() {
            const paymentMethod = document.getElementById('checkout-payment-method-fullscreen').value;
            const notes = document.getElementById('checkout-notes-fullscreen').value;
            const linkedEvent = document.getElementById('checkout-linked-event-fullscreen').value;
            
            try {
                const response = await fetch('/api/checkout_cart.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        event_id: linkedEvent || null,
                        payment_method: paymentMethod, 
                        notes: notes
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    let message = `✅ Salg gjennomført!\nAntall varer: ${result.items_sold}\nTotalsum: ${result.total.toFixed(2)} NOK`;
                    
                    // Show change if cash payment was used
                    if (cashReceivedFullscreen > 0) {
                        const change = cashReceivedFullscreen - result.total;
                        if (change > 0) {
                            message += `\n💰 Vekslepenger: ${change.toFixed(2)} NOK`;
                        }
                    }
                    
                    alert(message);
                    document.getElementById('checkout-notes-fullscreen').value = '';
                    document.getElementById('checkout-linked-event-fullscreen').value = '';
                    resetPaymentFullscreen();
                    await loadCart();
                    await loadAllSalesData();
                    syncCartToFullscreen();
                } else {
                    alert('❌ ' + result.message);
                }
            } catch (error) {
                console.error('Checkout error:', error);
                alert('❌ Feil ved betaling');
            }
        });
    }
}

function syncToFullscreen() {
    // Sync products
    const productsList = document.getElementById('products-list')?.innerHTML;
    if (productsList) {
        document.getElementById('products-list-fullscreen').innerHTML = productsList;
    }
    
    // Sync services
    const servicesList = document.getElementById('services-list')?.innerHTML;
    if (servicesList) {
        document.getElementById('services-list-fullscreen').innerHTML = servicesList;
    }
    
    // Sync cart
    syncCartToFullscreen();
}

function syncCartToFullscreen() {
    document.getElementById('cart-items-fullscreen').innerHTML = document.getElementById('cart-items').innerHTML;
    document.getElementById('cart-total-fullscreen').textContent = document.getElementById('cart-total').textContent;
    document.getElementById('cart-count-fullscreen').textContent = document.getElementById('cart-count').textContent;
}

function initFullscreenBarcodeScanner() {
    const barcodeInput = document.getElementById('barcode-scanner-input-fullscreen');
    const scannerCard = document.querySelector('#fullscreen-sales-overlay .barcode-scanner-card');
    const scanFeedback = document.getElementById('scan-feedback-fullscreen');
    
    if (!barcodeInput) return;
    
    let scanTimeout = null;
    
    barcodeInput.addEventListener('input', function(e) {
        if (scanTimeout) clearTimeout(scanTimeout);
        
        const barcode = e.target.value.trim();
        
        if (barcode.length >= 8) {
            scanTimeout = setTimeout(() => {
                handleBarcodeScanned(barcode, scannerCard, scanFeedback, barcodeInput, true);
            }, 300);
        }
    });
    
    barcodeInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const barcode = e.target.value.trim();
            if (barcode) {
                handleBarcodeScanned(barcode, scannerCard, scanFeedback, barcodeInput, true);
            }
        }
    });
}

function updateCashChangeFullscreen() {
    const total = parseFloat(document.getElementById('cart-total-fullscreen').textContent) || 0;
    const received = parseFloat(document.getElementById('cash-received-fullscreen').value) || 0;
    const changeDisplay = document.getElementById('cash-change-display-fullscreen');
    
    if (received === 0) {
        changeDisplay.innerHTML = '';
        return;
    }
    
    const change = received - total;
    
    if (change < 0) {
        changeDisplay.innerHTML = `<div class="alert alert-danger py-2 mb-0"><strong>Mangler:</strong> ${Math.abs(change).toFixed(2)} NOK</div>`;
    } else if (change === 0) {
        changeDisplay.innerHTML = `<div class="alert alert-success py-2 mb-0"><strong>Passe penger</strong></div>`;
    } else {
        changeDisplay.innerHTML = `<div class="alert alert-info py-2 mb-0"><strong>Retur:</strong> ${change.toFixed(2)} NOK</div>`;
    }
}

// Purchases functionality
document.addEventListener('DOMContentLoaded', function() {
    // Auto-calculate total cost
    const quantityInput = document.getElementById('purchase-quantity');
    const unitCostInput = document.getElementById('purchase-unit-cost');
    const totalCostInput = document.getElementById('purchase-total-cost');
    
    if (quantityInput && unitCostInput && totalCostInput) {
        [quantityInput, unitCostInput].forEach(input => {
            input.addEventListener('input', function() {
                const qty = parseFloat(quantityInput.value) || 0;
                const unitCost = parseFloat(unitCostInput.value) || 0;
                totalCostInput.value = (qty * unitCost).toFixed(2);
            });
        });
    }
    
    // Set today's date as default
    const purchaseDateInput = document.getElementById('purchase-date');
    if (purchaseDateInput) {
        purchaseDateInput.value = new Date().toISOString().split('T')[0];
    }
    
    // Load products when modal opens
    const purchaseModal = document.getElementById('addPurchaseModal');
    if (purchaseModal) {
        purchaseModal.addEventListener('shown.bs.modal', function() {
            loadProductsForPurchase();
        });
    }
});

async function loadProductsForPurchase() {
    const select = document.getElementById('purchase-product');
    if (!select) return;
    
    try {
        const response = await fetch('/api/get_sales_items.php');
        const result = await response.json();
        
        if (result.success && result.items) {
            select.innerHTML = '<option value="">Velg produkt...</option>';
            result.items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${item.name} (${item.sku || 'Ingen SKU'})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

async function savePurchase() {
    const supplier = document.getElementById('purchase-supplier').value;
    const date = document.getElementById('purchase-date').value;
    const productId = document.getElementById('purchase-product').value;
    const quantity = document.getElementById('purchase-quantity').value;
    const unitCost = document.getElementById('purchase-unit-cost').value;
    const paymentMethod = document.getElementById('purchase-payment-method').value;
    const invoiceNumber = document.getElementById('purchase-invoice-number').value;
    const notes = document.getElementById('purchase-notes').value;
    
    if (!supplier || !date || !productId || !quantity || !unitCost) {
        alert('Vennligst fyll ut alle påkrevde felt');
        return;
    }
    
    const totalCost = parseFloat(quantity) * parseFloat(unitCost);
    
    const purchaseData = {
        supplier,
        date,
        product_id: productId,
        quantity: parseInt(quantity),
        unit_cost: parseFloat(unitCost),
        total_cost: totalCost,
        payment_method: paymentMethod,
        invoice_number: invoiceNumber,
        notes
    };
    
    try {
        const response = await fetch('api/register_purchase.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(purchaseData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ Innkjøp registrert!\nLeverandør: ${supplier}\nTotal: ${totalCost.toFixed(2)} NOK\nNytt lagernivå: ${result.new_stock}`);
            bootstrap.Modal.getInstance(document.getElementById('addPurchaseModal')).hide();
            document.getElementById('addPurchaseForm').reset();
            loadPurchases(); // Reload purchases list
            loadProducts(); // Reload products to show updated stock
        } else {
            alert('❌ ' + (result.message || 'Feil ved registrering av innkjøp'));
        }
    } catch (error) {
        console.error('Error saving purchase:', error);
        alert('❌ Feil ved lagring av innkjøp');
    }
}

// Purchase view management
function showPurchaseHistory() {
    document.getElementById('purchase-history-view').style.display = 'block';
    document.getElementById('purchase-existing-view').style.display = 'none';
    document.getElementById('purchase-new-view').style.display = 'none';
    document.querySelector('.row.g-3.mb-4').style.display = 'none';
    loadPurchaseHistory();
}

function showPurchaseExisting() {
    document.getElementById('purchase-history-view').style.display = 'none';
    document.getElementById('purchase-existing-view').style.display = 'block';
    document.getElementById('purchase-new-view').style.display = 'none';
    document.querySelector('.row.g-3.mb-4').style.display = 'none';
    loadProductsForPurchaseExisting();
}

function showPurchaseNew() {
    document.getElementById('purchase-history-view').style.display = 'none';
    document.getElementById('purchase-existing-view').style.display = 'none';
    document.getElementById('purchase-new-view').style.display = 'block';
    document.querySelector('.row.g-3.mb-4').style.display = 'none';
}

function resetPurchaseView() {
    document.getElementById('purchase-history-view').style.display = 'none';
    document.getElementById('purchase-existing-view').style.display = 'none';
    document.getElementById('purchase-new-view').style.display = 'none';
    document.querySelector('.row.g-3.mb-4').style.display = 'flex';
}

// Auto-calculate unit cost for existing products
document.getElementById('existing-quantity')?.addEventListener('input', calculateExistingUnitCost);
document.getElementById('existing-total-cost')?.addEventListener('input', calculateExistingUnitCost);

function calculateExistingUnitCost() {
    const quantity = parseFloat(document.getElementById('existing-quantity').value) || 0;
    const totalCost = parseFloat(document.getElementById('existing-total-cost').value) || 0;
    const unitCost = quantity > 0 ? (totalCost / quantity).toFixed(2) : '0.00';
    document.getElementById('existing-unit-cost').value = unitCost + ' NOK';
}

// Auto-calculate unit cost for new products
document.getElementById('new-quantity')?.addEventListener('input', calculateNewUnitCost);
document.getElementById('new-total-cost')?.addEventListener('input', calculateNewUnitCost);

function calculateNewUnitCost() {
    const quantity = parseFloat(document.getElementById('new-quantity').value) || 0;
    const totalCost = parseFloat(document.getElementById('new-total-cost').value) || 0;
    const unitCost = quantity > 0 ? (totalCost / quantity).toFixed(2) : '0.00';
    document.getElementById('new-unit-cost').value = unitCost + ' NOK';
}

async function loadProductsForPurchaseExisting() {
    const select = document.getElementById('existing-product-select');
    if (!select) return;
    
    try {
        const response = await fetch('/api/get_sales_items.php');
        const result = await response.json();
        
        if (result.success && result.items) {
            select.innerHTML = '<option value="">Velg produkt...</option>';
            result.items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${item.name} - ${item.sku || 'Ingen SKU'} (Lager: ${item.stock_quantity || 0})`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

async function loadPurchaseHistory() {
    const tbody = document.getElementById('purchase-history-list');
    if (!tbody) return;
    
    try {
        const response = await fetch('/api/get_purchase_history.php');
        const result = await response.json();
        
        if (result.success && result.purchases) {
            if (result.purchases.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">Ingen innkjøp registrert ennå</td></tr>';
                return;
            }
            
            tbody.innerHTML = result.purchases.map(p => `
                <tr>
                    <td>${new Date(p.purchase_date).toLocaleDateString('nb-NO')}</td>
                    <td>${p.supplier || '-'}</td>
                    <td>${p.product_name}</td>
                    <td>${p.quantity}</td>
                    <td>${parseFloat(p.unit_cost).toFixed(2)} NOK</td>
                    <td><strong>${parseFloat(p.total_cost).toFixed(2)} NOK</strong></td>
                </tr>
            `).join('');
        }
    } catch (error) {
        console.error('Error loading purchase history:', error);
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-3">Feil ved lasting av innkjøpshistorikk</td></tr>';
    }
}

// Form submit handlers
document.getElementById('purchaseExistingForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const productId = document.getElementById('existing-product-select').value;
    const supplier = document.getElementById('existing-supplier').value;
    const quantity = parseInt(document.getElementById('existing-quantity').value);
    const totalCost = parseFloat(document.getElementById('existing-total-cost').value);
    
    if (!productId || !quantity || !totalCost) {
        alert('❌ Vennligst fyll ut alle påkrevde felt');
        return;
    }
    
    const unitCost = totalCost / quantity;
    
    try {
        const response = await fetch('/api/purchase_existing_product.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                product_id: productId,
                supplier: supplier,
                quantity: quantity,
                total_cost: totalCost,
                unit_cost: unitCost
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ Innkjøp registrert!\n\nNy beholdning: ${result.new_stock}\nNy kostpris: ${result.new_cost_price} NOK (vektet gjennomsnitt)`);
            this.reset();
            resetPurchaseView();
            await loadSalesProducts();
        } else {
            alert('❌ ' + (result.message || 'Feil ved registrering av innkjøp'));
        }
    } catch (error) {
        console.error('Error purchasing existing product:', error);
        alert('❌ Feil ved registrering av innkjøp');
    }
});

document.getElementById('purchaseNewForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const productName = document.getElementById('new-product-name').value;
    const category = document.getElementById('new-product-category').value;
    const supplier = document.getElementById('new-supplier').value;
    const quantity = parseInt(document.getElementById('new-quantity').value);
    const totalCost = parseFloat(document.getElementById('new-total-cost').value);
    
    if (!productName || !quantity || !totalCost) {
        alert('❌ Vennligst fyll ut alle påkrevde felt');
        return;
    }
    
    const unitCost = totalCost / quantity;
    
    try {
        const response = await fetch('/api/purchase_new_product.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                product_name: productName,
                category: category,
                supplier: supplier,
                quantity: quantity,
                total_cost: totalCost
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            const productId = result.product_id || 'Ukjent';
            const sku = result.sku || 'Ukjent';
            alert(`✅ Produkt opprettet og innkjøp registrert!\n\nProdukt-ID: ${productId}\nSKU: ${sku}\nProdukt: ${productName}\nLagerbeholdning: ${quantity}\nKostpris: ${unitCost.toFixed(2)} NOK`);
            this.reset();
            resetPurchaseView();
            await loadSalesProducts();
        } else {
            alert('❌ ' + (result.message || 'Feil ved opprettelse av produkt'));
        }
    } catch (error) {
        console.error('Error purchasing new product:', error);
        alert('❌ Feil ved opprettelse av produkt');
    }
});

// Inline editing for products - using event delegation for dynamic content
document.addEventListener('DOMContentLoaded', function() {
    const salesItemsList = document.getElementById('sales-items-list');
    if (!salesItemsList) return;
    
    // Event delegation for editable cells
    salesItemsList.addEventListener('click', async function(e) {
        const cell = e.target.closest('.editable-cell');
        if (!cell || cell.querySelector('input')) return; // Already editing
        
        const row = cell.closest('tr');
        const productId = row.dataset.productId;
        const field = cell.dataset.field;
        const currentValue = cell.textContent.replace(' NOK', '').trim();
        
        // Create input
        const input = document.createElement('input');
        input.type = field.includes('price') ? 'number' : 'text';
        input.className = 'form-control form-control-sm';
        input.value = currentValue;
        input.style.width = '100%';
        if (field.includes('price')) {
            input.step = '0.01';
        }
        
        // Replace content
        const originalHTML = cell.innerHTML;
        cell.innerHTML = '';
        cell.appendChild(input);
        input.focus();
        input.select();
        
        // Save function
        const saveEdit = async () => {
            const newValue = input.value;
            if (newValue !== currentValue && newValue) {
                const updateData = {
                    id: productId,
                    [field]: field.includes('price') ? parseFloat(newValue) : newValue
                };
                
                try {
                    const response = await fetch('/api/update_sales_product.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(updateData)
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        await loadProductsWithProfit();
                    } else {
                        cell.innerHTML = originalHTML;
                        alert('❌ Feil ved oppdatering');
                    }
                } catch (error) {
                    cell.innerHTML = originalHTML;
                    console.error('Update error:', error);
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
</script>
