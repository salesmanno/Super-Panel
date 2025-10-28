<div class="module-content">
    <div id="sales-varesalg" class="subtab-content active">
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
                            
                            <div class="mb-3">
                                <label class="form-label small">Kundenavn (valgfritt)</label>
                                <input type="text" class="form-control form-control-sm" id="checkout-customer-name">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small">E-post</label>
                                <input type="email" class="form-control form-control-sm" id="checkout-customer-email">
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="send-receipt-checkbox">
                                    <label class="form-check-label small" for="send-receipt-checkbox">
                                        Send kvittering til e-post
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small">Betalingsmetode</label>
                                <select class="form-select form-select-sm" id="checkout-payment-method">
                                    <option value="cash">Kontant</option>
                                    <option value="card">Kort</option>
                                    <option value="online">Nettbank</option>
                                    <option value="on_arrival">Oppmøte</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small">Notater</label>
                                <textarea class="form-control form-control-sm" id="checkout-notes" rows="2"></textarea>
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

    <div id="sales-service" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-gear-fill me-2"></i>Service-salg</h5>
                <p class="text-muted">Oversikt over tjenestesalg (events, medlemskap, utleie)</p>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dato</th>
                                <th>Tjeneste</th>
                                <th>Type</th>
                                <th>Kunde</th>
                                <th>E-post</th>
                                <th>Beløp</th>
                                <th>Betalingsmetode</th>
                            </tr>
                        </thead>
                        <tbody id="service-sales-list">
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
                                <th>Status</th>
                                <th>Handlinger</th>
                            </tr>
                        </thead>
                        <tbody id="sales-items-list">
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
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
                    
                    <div class="mb-3">
                        <label class="form-label">Antall <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="service-quantity" min="1" value="1" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Betalingsmetode</label>
                        <select class="form-select" id="service-payment-method">
                            <option value="cash">Kontant</option>
                            <option value="card">Kort</option>
                            <option value="online">Nettbank</option>
                            <option value="on_arrival">Oppmøte</option>
                        </select>
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

<script>
console.log('=== SALES MODULE SCRIPT LOADED ===');
console.log('Checking for sales elements:');
console.log('- #tab-sales exists:', !!document.getElementById('tab-sales'));
console.log('- #content-sales exists:', !!document.getElementById('content-sales'));
console.log('- #products-list exists:', !!document.querySelector('#products-list'));
console.log('- #services-list exists:', !!document.querySelector('#services-list'));

// Load products and services
async function loadSalesProducts() {
    console.log('loadSalesProducts() called');
    try {
        const response = await fetch('/api/get_sales_items.php');
        const data = await response.json();
        
        if (data.success && data.items) {
            const products = data.items.filter(item => item.product_type === 'product');
            const services = data.items.filter(item => item.product_type !== 'product');
            
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
        return `
            <div class="badge bg-${color} p-2 text-start" 
                 style="cursor: pointer; min-width: 180px; max-width: 250px;"
                 data-bs-toggle="modal" 
                 data-bs-target="#servicePurchaseModal" 
                 onclick="setServiceData('${service.id}', '${service.product_type}', '${service.item_name}', ${service.price})">
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

// Scope checkout button to sales module
const salesModule = document.getElementById('content-sales');
if (salesModule) {
    const checkoutBtn = salesModule.querySelector('#checkout-btn');
    checkoutBtn?.addEventListener('click', async function() {
        const customerName = salesModule.querySelector('#checkout-customer-name').value;
        const customerEmail = salesModule.querySelector('#checkout-customer-email').value;
        const sendReceipt = salesModule.querySelector('#send-receipt-checkbox').checked;
        const paymentMethod = salesModule.querySelector('#checkout-payment-method').value;
        const notes = salesModule.querySelector('#checkout-notes').value;
    
    try {
        const response = await fetch('/api/checkout_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                customer_name: customerName, 
                customer_email: customerEmail,
                send_receipt: sendReceipt,
                payment_method: paymentMethod, 
                notes: notes
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            let message = `✅ Salg gjennomført!\nAntall varer: ${result.items_sold}\nTotalsum: ${result.total.toFixed(2)} NOK`;
            if (sendReceipt && customerEmail) {
                message += `\n📧 Kvittering sendt til ${customerEmail}`;
            }
            alert(message);
            salesModule.querySelector('#checkout-customer-name').value = '';
            salesModule.querySelector('#checkout-customer-email').value = '';
            salesModule.querySelector('#send-receipt-checkbox').checked = false;
            salesModule.querySelector('#checkout-notes').value = '';
            await loadCart();
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

// Load all sales data in one function
async function loadAllSalesData() {
    console.log('=== loadAllSalesData() called ===');
    await Promise.all([
        loadSalesProducts(),
        loadTransactions(),
        loadProductsWithProfit()
    ]);
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
        container.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-4">Ingen produkter registrert</td></tr>';
        return;
    }
    
    container.innerHTML = products.map(product => {
        const profit = parseFloat(product.profit_margin) || 0;
        const profitClass = profit > 0 ? 'text-success' : 'text-danger';
        const statusBadge = product.is_active == 1 
            ? '<span class="badge bg-success">Aktiv</span>' 
            : '<span class="badge bg-secondary">Inaktiv</span>';
        
        return `
            <tr>
                <td>${product.name}</td>
                <td><code>${product.sku || '-'}</code></td>
                <td>${product.category_id || '-'}</td>
                <td>${parseFloat(product.cost_price || 0).toFixed(2)} NOK</td>
                <td>${parseFloat(product.selling_price).toFixed(2)} NOK</td>
                <td class="${profitClass}"><strong>${profit.toFixed(2)} NOK</strong></td>
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
            bootstrap.Modal.getInstance(document.getElementById('editSalesItemModal')).hide();
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
    
    // TODO: Replace with real API when ready
    container.innerHTML = `
        <tr>
            <td colspan="7" class="text-center text-muted py-4">
                Ingen transaksjoner funnet
            </td>
        </tr>
    `;
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
        member_price: formData.get('member_price') ? parseFloat(formData.get('member_price')) : null,
        tax_rate: parseFloat(formData.get('tax_rate')),
        unit: formData.get('unit'),
        reorder_level: parseInt(formData.get('reorder_level')) || 0,
        is_active: formData.get('is_active') ? true : false
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
            await loadSalesProducts(); // Reload products
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Product creation error:', error);
        alert('❌ Feil ved opprettelse av produkt');
    }
}

// Service purchase functions
function setServiceData(id, type, name, price) {
    document.getElementById('service-id').value = id;
    document.getElementById('service-type').value = type;
    document.getElementById('service-price-value').value = price;
    document.getElementById('service-modal-name').textContent = name;
    document.getElementById('service-modal-type').textContent = getServiceTypeName(type);
    document.getElementById('service-modal-price').textContent = price;
    updateServiceTotal();
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
    
    const purchaseData = {
        output_product_id: document.getElementById('service-id').value,
        service_type: document.getElementById('service-type').value,
        quantity: parseInt(document.getElementById('service-quantity').value),
        customer_first_name: document.getElementById('service-customer-first-name').value,
        customer_last_name: document.getElementById('service-customer-last-name').value,
        customer_email: document.getElementById('service-customer-email').value,
        customer_phone: document.getElementById('service-customer-phone').value,
        payment_method: document.getElementById('service-payment-method').value,
        notes: document.getElementById('service-notes').value
    };
    
    try {
        const response = await fetch('/api/process_service_sale.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(purchaseData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ Tjeneste solgt!\nKunde: ${purchaseData.customer_first_name} ${purchaseData.customer_last_name}\nTotalsum: ${result.total} NOK`);
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
</script>
