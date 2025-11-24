<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">📋 Oppføringer</h4>
            <p class="text-muted mb-0">Alle produkter og tjenester fra alle moduler</p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted small mb-2">Totalt antall</div>
                    <div class="fs-3 fw-bold" id="stat-total">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted small mb-2">Aktive</div>
                    <div class="fs-3 fw-bold text-success" id="stat-active">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted small mb-2">Inaktive</div>
                    <div class="fs-3 fw-bold text-warning" id="stat-inactive">0</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm text-center">
                <div class="card-body">
                    <div class="text-muted small mb-2">Moduler</div>
                    <div class="fs-3 fw-bold text-info" id="stat-modules">0</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter etter status</label>
                    <select class="form-select" id="filter-status" onchange="loadInventory()">
                        <option value="">Alle produkter</option>
                        <option value="active">Bare aktive</option>
                        <option value="inactive">Bare inaktive</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Søk på navn</label>
                    <input type="text" class="form-control" id="search-product" placeholder="Produktnavn..." onkeyup="loadInventory()">
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" onclick="loadInventory()">
                        <i class="bi bi-arrow-clockwise me-2"></i>Oppdater
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Products Table -->
    <div class="table-responsive">
        <table class="table table-hover">
            <thead class="table-light">
                <tr>
                    <th>Produktnavn</th>
                    <th>Modul</th>
                    <th>Type</th>
                    <th>Pris</th>
                    <th>Status</th>
                    <th>Opprettet</th>
                    <th>Handlinger</th>
                </tr>
            </thead>
            <tbody id="products-table-body">
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">
                        <i class="bi bi-hourglass-split me-2"></i>Laster...
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Empty State -->
    <div id="empty-state" style="display: none;">
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                <p class="text-muted mt-3 mb-0">Ingen oppføringer funnet</p>
            </div>
        </div>
    </div>
</div>

<script>
let allProducts = [];
let modules = {};

async function loadInventory() {
    try {
        const response = await fetch('/api/get_output_products_inventory.php');
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }
        
        const result = await response.json();
        
        if (result.success) {
            allProducts = result.products || [];
            modules = result.modules || {};
            
            // Update stats
            document.getElementById('stat-total').textContent = result.stats.total;
            document.getElementById('stat-active').textContent = result.stats.active;
            document.getElementById('stat-inactive').textContent = result.stats.inactive;
            document.getElementById('stat-modules').textContent = Object.keys(modules).length;
            
            displayProducts(allProducts);
        } else {
            throw new Error(result.message || 'Kunne ikke laste oppføringer');
        }
    } catch (error) {
        console.error('Error loading inventory:', error);
        toast.quickError('Feil', 'Kunne ikke laste oppføringer');
    }
}

function displayProducts(products) {
    // Apply filters
    const statusFilter = document.getElementById('filter-status').value;
    const searchTerm = document.getElementById('search-product').value.toLowerCase();
    
    let filtered = products.filter(product => {
        // Status filter
        if (statusFilter === 'active' && product.is_visible != 1) return false;
        if (statusFilter === 'inactive' && product.is_visible == 1) return false;
        
        // Search filter
        if (searchTerm && !product.name.toLowerCase().includes(searchTerm)) return false;
        
        return true;
    });
    
    const tbody = document.getElementById('products-table-body');
    const emptyState = document.getElementById('empty-state');
    
    if (filtered.length === 0) {
        tbody.innerHTML = '';
        emptyState.style.display = 'block';
        return;
    }
    
    emptyState.style.display = 'none';
    
    tbody.innerHTML = filtered.map(product => {
        const createdDate = new Date(product.created_at).toLocaleDateString('no-NO');
        const moduleName = modules[product.module_id] || product.module_id || 'Ukjent';
        
        const statusBadge = product.is_visible == 1
            ? '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Aktiv</span>'
            : '<span class="badge bg-warning"><i class="bi bi-exclamation-circle me-1"></i>Inaktiv</span>';
        
        return `
            <tr>
                <td><strong>${product.name || 'Ukjent'}</strong></td>
                <td><small class="text-muted">${moduleName}</small></td>
                <td><small class="badge bg-light text-dark">${product.product_type}</small></td>
                <td>${product.price ? parseFloat(product.price).toFixed(2) + ' kr' : 'Gratis'}</td>
                <td>${statusBadge}</td>
                <td><small>${createdDate}</small></td>
                <td>
                    <div class="btn-group btn-group-sm">
                        ${product.is_visible == 1 
                            ? `<button class="btn btn-outline-warning" onclick="toggleProductStatus('${product.id}', 0)" title="Deaktiver">
                                <i class="bi bi-eye-slash"></i>
                            </button>`
                            : `<button class="btn btn-outline-success" onclick="toggleProductStatus('${product.id}', 1)" title="Reaktiver">
                                <i class="bi bi-eye"></i>
                            </button>`
                        }
                        <button class="btn btn-outline-danger" onclick="deleteProductConfirm('${product.id}')" title="Slett">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

async function toggleProductStatus(productId, newStatus) {
    try {
        if (newStatus === 0) {
            // Deactivate
            const response = await fetch('/api/reactivate_product.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    product_id: productId
                })
            });
            
            const result = await response.json();
            if (result.success) {
                // Note: This just deactivates, we'll reload for clarity
                const product = allProducts.find(p => p.id === productId);
                if (product) {
                    product.is_visible = 0;
                    displayProducts(allProducts);
                    toast.quickSuccess('Deaktivert', 'Produkt er nå inaktiv');
                }
            } else {
                throw new Error(result.message);
            }
        } else {
            // Reactivate
            const response = await fetch('/api/reactivate_product.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    product_id: productId
                })
            });
            
            const result = await response.json();
            if (result.success) {
                const product = allProducts.find(p => p.id === productId);
                if (product) {
                    product.is_visible = 1;
                    displayProducts(allProducts);
                    toast.quickSuccess('Reaktivert', 'Produkt er nå aktivt');
                }
            } else {
                throw new Error(result.message);
            }
        }
    } catch (error) {
        console.error('Error toggling product status:', error);
        toast.quickError('Feil', 'Kunne ikke endre status');
    }
}

function deleteProductConfirm(productId) {
    const product = allProducts.find(p => p.id === productId);
    if (!product) return;
    
    if (confirm(`Er du sikker på at du vil slette "${product.name}" permanent? Dette kan ikke angres.`)) {
        deleteProduct(productId);
    }
}

async function deleteProduct(productId) {
    try {
        const response = await fetch('/api/delete_product.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                product_id: productId
            })
        });
        
        const result = await response.json();
        if (result.success) {
            allProducts = allProducts.filter(p => p.id !== productId);
            displayProducts(allProducts);
            toast.quickSuccess('Slettet', 'Produkt ble slettet permanent');
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        console.error('Error deleting product:', error);
        toast.quickError('Feil', 'Kunne ikke slette produkt');
    }
}

// Load on page load
document.addEventListener('DOMContentLoaded', loadInventory);

// Reload when tab is shown
document.querySelector('[href="#products-inventory"]')?.addEventListener('click', loadInventory);
</script>
