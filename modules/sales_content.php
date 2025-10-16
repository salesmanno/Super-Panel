<div class="module-content">
    <div id="sales-varesalg" class="subtab-content">
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
                                <label class="form-label small">Betalingsmetode</label>
                                <select class="form-select form-select-sm" id="checkout-payment-method">
                                    <option value="cash">Kontant</option>
                                    <option value="card">Kort</option>
                                    <option value="online">Nettbank</option>
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
                    
                    <div class="mb-3">
                        <label class="form-label">Kundenavn <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="service-customer-name" required>
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
// Load products and services
async function loadSalesProducts() {
    try {
        const response = await fetch('/api/get_sales_items.php');
        const data = await response.json();
        
        if (data.success && data.items) {
            const products = data.items.filter(item => item.product_type === 'product');
            const services = data.items.filter(item => item.product_type !== 'product');
            
            displayProducts(products);
            displayServices(services);
        }
    } catch (error) {
        console.error('Error loading products:', error);
    }
    
    await loadCart();
}

function displayProducts(products) {
    const container = document.getElementById('products-list');
    
    if (products.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted py-4">Ingen produkter tilgjengelig</div>';
        return;
    }
    
    container.innerHTML = products.map(product => `
        <div class="col-md-6 mb-3">
            <div class="card h-100">
                <div class="card-body">
                    <h6 class="card-title">${product.item_name}</h6>
                    <p class="text-muted mb-2">${product.price} NOK</p>
                    <div class="input-group input-group-sm">
                        <input type="number" class="form-control" id="qty-${product.id}" min="1" value="1" style="max-width: 80px;">
                        <button class="btn btn-primary" onclick="addToCart('${product.id}')">
                            <i class="bi bi-cart-plus"></i> Legg til
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
}

function displayServices(services) {
    const container = document.getElementById('services-list');
    
    if (services.length === 0) {
        container.innerHTML = '<div class="col-12 text-center text-muted py-4">Ingen tjenester tilgjengelig</div>';
        return;
    }
    
    container.innerHTML = services.map(service => `
        <div class="col-md-12 mb-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="card-title mb-1">${service.item_name}</h6>
                            <small class="text-muted">
                                <span class="badge bg-info">${getServiceTypeName(service.product_type)}</span>
                            </small>
                            <p class="mb-2 mt-2"><strong>${service.price} NOK</strong></p>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#servicePurchaseModal" onclick="setServiceData('${service.id}', '${service.product_type}', '${service.item_name}', ${service.price})">
                            <i class="bi bi-bag-check"></i> Selg
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `).join('');
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
    const quantity = parseInt(document.getElementById('qty-' + productId).value) || 1;
    
    try {
        const response = await fetch('/api/add_to_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({output_product_id: productId, quantity: quantity})
        });
        
        const result = await response.json();
        
        if (result.success) {
            await loadCart();
            document.getElementById('qty-' + productId).value = 1;
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Add to cart error:', error);
    }
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
    const container = document.getElementById('cart-items');
    const countBadge = document.getElementById('cart-count');
    const totalElement = document.getElementById('cart-total');
    const checkoutBtn = document.getElementById('checkout-btn');
    
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

document.getElementById('checkout-btn')?.addEventListener('click', async function() {
    const customerName = document.getElementById('checkout-customer-name').value;
    const paymentMethod = document.getElementById('checkout-payment-method').value;
    const notes = document.getElementById('checkout-notes').value;
    
    try {
        const response = await fetch('/api/checkout_cart.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({customer_name: customerName, payment_method: paymentMethod, notes: notes})
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert(`✅ Salg gjennomført!\nAntall varer: ${result.items_sold}\nTotalsum: ${result.total.toFixed(2)} NOK`);
            document.getElementById('checkout-customer-name').value = '';
            document.getElementById('checkout-notes').value = '';
            await loadCart();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Checkout error:', error);
        alert('❌ Feil ved betaling');
    }
});

document.getElementById('tab-sales')?.addEventListener('shown.bs.tab', loadSalesProducts);

if (document.getElementById('tab-sales')?.classList.contains('active')) {
    loadSalesProducts();
}

function submitSalesItem() {
    const form = document.getElementById('addSalesItemForm');
    const formData = new FormData(form);
    console.log('Lagrer produkt:', Object.fromEntries(formData));
    bootstrap.Modal.getInstance(document.getElementById('addSalesItemModal')).hide();
    form.reset();
    alert('Produkt lagret! (Dette er en placeholder - koble til database)');
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
        customer_name: document.getElementById('service-customer-name').value,
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
            alert(`✅ Tjeneste solgt!\nKunde: ${purchaseData.customer_name}\nTotalsum: ${result.total} NOK`);
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
