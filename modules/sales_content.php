<div class="module-content">
    <div id="sales-varesalg" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-cart-check me-2"></i>Varesalg</h5>
                <p class="text-muted">Registrer et nytt salg</p>
                
                <form id="quick-sale-form">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Velg produkt</label>
                            <select class="form-select" id="sale-product-id" required>
                                <option value="">-- Velg produkt --</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Antall</label>
                            <input type="number" class="form-control" id="sale-quantity" min="1" value="1" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Pris per stk</label>
                            <input type="number" class="form-control" id="sale-price" step="0.01" readonly>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Kundenavn (valgfritt)</label>
                            <input type="text" class="form-control" id="sale-customer-name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Betalingsmetode</label>
                            <select class="form-select" id="sale-payment-method" required>
                                <option value="cash">Kontant</option>
                                <option value="card">Kort</option>
                                <option value="online">Nettbank</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label class="form-label">Notater (valgfritt)</label>
                            <textarea class="form-control" id="sale-notes" rows="2"></textarea>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <strong>Totalsum:</strong> <span id="sale-total">0.00</span> NOK
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-check-circle me-2"></i>Gjennomfør salg
                    </button>
                </form>
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
                                <th>Pris</th>
                                <th>Lager</th>
                                <th>Status</th>
                                <th>Handlinger</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    Ingen produkter registrert
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
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Produktnavn *</label>
                            <input type="text" class="form-control" name="product_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">SKU / Varenummer *</label>
                            <input type="text" class="form-control" name="sku" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Kategori</label>
                            <select class="form-select" name="category">
                                <option value="">Velg kategori</option>
                                <option value="electronics">Elektronikk</option>
                                <option value="clothing">Klær</option>
                                <option value="food">Mat & Drikke</option>
                                <option value="services">Tjenester</option>
                                <option value="other">Annet</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pris (NOK) *</label>
                            <input type="number" class="form-control" name="price" step="0.01" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Lager antall</label>
                            <input type="number" class="form-control" name="stock" value="0">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="active">Aktiv</option>
                                <option value="inactive">Inaktiv</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
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

<script>
// Load products when sales module is active
async function loadSalesProducts() {
    try {
        const response = await fetch('/api/get_sales_items.php');
        const data = await response.json();
        
        if (data.success && data.items) {
            const select = document.getElementById('sale-product-id');
            select.innerHTML = '<option value="">-- Velg produkt --</option>';
            
            data.items.forEach(item => {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = `${item.item_name} - ${item.price} NOK`;
                option.dataset.price = item.price;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

// Update price when product is selected
document.getElementById('sale-product-id')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const price = selectedOption.dataset.price || 0;
    document.getElementById('sale-price').value = price;
    calculateTotal();
});

// Calculate total when quantity changes
document.getElementById('sale-quantity')?.addEventListener('input', calculateTotal);

function calculateTotal() {
    const price = parseFloat(document.getElementById('sale-price').value) || 0;
    const quantity = parseInt(document.getElementById('sale-quantity').value) || 0;
    const total = (price * quantity).toFixed(2);
    document.getElementById('sale-total').textContent = total;
}

// Handle quick sale submission
document.getElementById('quick-sale-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const saleData = {
        product_id: document.getElementById('sale-product-id').value,
        quantity: parseInt(document.getElementById('sale-quantity').value),
        price: parseFloat(document.getElementById('sale-price').value),
        customer_name: document.getElementById('sale-customer-name').value,
        payment_method: document.getElementById('sale-payment-method').value,
        notes: document.getElementById('sale-notes').value,
        company_id: <?= $_SESSION['company_id'] ?>
    };
    
    try {
        const response = await fetch('/api/process_sale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(saleData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Salg registrert!');
            this.reset();
            document.getElementById('sale-price').value = '';
            document.getElementById('sale-total').textContent = '0.00';
        } else {
            alert('❌ Feil: ' + (result.message || 'Kunne ikke registrere salg'));
        }
    } catch (error) {
        console.error('Error processing sale:', error);
        alert('❌ Feil ved registrering av salg');
    }
});

// Load products when sales tab becomes active
document.querySelector('[href="#tab-sales"]')?.addEventListener('shown.bs.tab', loadSalesProducts);

// Load products immediately if on sales tab
if (document.querySelector('[href="#tab-sales"]')?.classList.contains('active')) {
    loadSalesProducts();
}

function submitSalesItem() {
    const form = document.getElementById('addSalesItemForm');
    const formData = new FormData(form);
    
    // Her kan du legge til AJAX kall til API for å lagre produktet
    console.log('Lagrer produkt:', Object.fromEntries(formData));
    
    // Lukk modal og tilbakestill form
    bootstrap.Modal.getInstance(document.getElementById('addSalesItemModal')).hide();
    form.reset();
    
    alert('Produkt lagret! (Dette er en placeholder - koble til database)');
}
</script>
