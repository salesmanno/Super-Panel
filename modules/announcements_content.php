<style>
.news-layout {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 1.5rem;
}
.border-dashed {
    border: 2px dashed #dee2e6 !important;
}
@media (max-width: 992px) {
    .news-layout {
        grid-template-columns: 1fr;
    }
}
.pinned-section {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border-radius: 12px;
    padding: 1rem;
}
.pinned-section h6 {
    color: #92400e;
    font-weight: 600;
    margin-bottom: 1rem;
}
.pinned-card {
    background: white;
    border-radius: 8px;
    padding: 0.75rem;
    margin-bottom: 0.5rem;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.pinned-card:last-child {
    margin-bottom: 0;
}
.pinned-card h6 {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
    color: #1f2937;
}
.pinned-card p {
    font-size: 0.8rem;
    color: #6b7280;
    margin: 0;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.news-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 1rem;
    margin-bottom: 0.75rem;
    background: white;
    transition: all 0.2s;
}
.news-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    border-color: #d1d5db;
}
.news-card.unpublished {
    opacity: 0.7;
    border-style: dashed;
}
.news-card.scheduled {
    border-left: 3px solid #3b82f6;
}
.news-card.expired {
    opacity: 0.5;
}
.news-thumb {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 6px;
}
.news-tabs .nav-link {
    font-size: 0.85rem;
    padding: 0.5rem 1rem;
}
.news-tabs .nav-link.active {
    font-weight: 600;
}
.empty-pinned {
    text-align: center;
    padding: 2rem 1rem;
    color: #92400e;
    opacity: 0.7;
}
</style>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0">Nyheter</h5>
            <small class="text-muted">Administrer firmaets nyheter</small>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openNewAnnouncementModal()">
            <i class="bi bi-plus-lg me-1"></i>Ny nyhet
        </button>
    </div>

    <ul class="nav nav-tabs news-tabs mb-3" id="newsTabs">
        <li class="nav-item">
            <button class="nav-link active" data-filter="published" onclick="filterNews('published')">
                <i class="bi bi-check-circle me-1"></i>Publisert
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-filter="scheduled" onclick="filterNews('scheduled')">
                <i class="bi bi-clock me-1"></i>Planlagt
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link" data-filter="draft" onclick="filterNews('draft')">
                <i class="bi bi-file-earmark me-1"></i>Kladd
            </button>
        </li>
    </ul>

    <div class="news-layout">
        <div class="pinned-section">
            <h6><i class="bi bi-pin-angle-fill me-1"></i>Festede nyheter</h6>
            <div id="pinned-list"></div>
        </div>
        <div id="news-list"></div>
    </div>
</div>

<div class="modal fade" id="announcementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="announcementModalTitle">Ny nyhet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="announcementForm">
                    <input type="hidden" id="announcement-id">
                    
                    <div class="mb-3">
                        <label class="form-label">Tittel *</label>
                        <input type="text" class="form-control" id="announcement-title" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Innhold *</label>
                        <textarea class="form-control" id="announcement-content" rows="4" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Bilde-URL (valgfri)</label>
                        <input type="url" class="form-control" id="announcement-image-url" placeholder="https://eksempel.no/bilde.jpg">
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-12">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="announcement-schedule-toggle" onchange="toggleScheduleOptions()">
                                <label class="form-check-label" for="announcement-schedule-toggle">
                                    Planlegg publisering
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6 schedule-options" style="display: none;">
                            <label class="form-label">Publiseringsdato</label>
                            <input type="datetime-local" class="form-control" id="announcement-publish-at">
                        </div>
                        <div class="col-md-6 schedule-options" style="display: none;">
                            <label class="form-label">Utløpsdato (valgfri)</label>
                            <input type="datetime-local" class="form-control" id="announcement-expire-at">
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="announcement-is-pinned">
                        <label class="form-check-label" for="announcement-is-pinned">
                            <i class="bi bi-pin-angle me-1"></i>Fest øverst
                        </label>
                    </div>
                    
                    <div class="card border-dashed mb-3">
                        <div class="card-header bg-light p-2">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="announcement-product-toggle" onchange="toggleProductSection()">
                                <label class="form-check-label" for="announcement-product-toggle">
                                    <i class="bi bi-tag me-1"></i>Legg til produkttilbud
                                </label>
                            </div>
                        </div>
                        <div class="card-body product-section" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Velg produkt</label>
                                <select class="form-select" id="announcement-product-id">
                                    <option value="">-- Velg produkt/tjeneste --</option>
                                </select>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-4">
                                    <label class="form-label">Rabatt %</label>
                                    <input type="number" class="form-control" id="announcement-discount" min="0" max="100" value="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Maks antall</label>
                                    <input type="number" class="form-control" id="announcement-max-qty" min="1" placeholder="Ubegrenset">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Utløpsdato</label>
                                    <input type="date" class="form-control" id="announcement-product-expiry">
                                </div>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="bi bi-info-circle me-1"></i>Nyhetssaken fjernes automatisk når tilbudet utløper
                            </small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-outline-secondary" onclick="saveAnnouncement(false)">
                    <i class="bi bi-file-earmark me-1"></i>Lagre som kladd
                </button>
                <button type="button" class="btn btn-primary" onclick="saveAnnouncement(true)">
                    <i class="bi bi-send me-1"></i>Publiser
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentAnnouncements = [];
let announcementsFilter = 'published';

async function loadAnnouncements() {
    try {
        const response = await fetch('/api/get_announcements.php?show_unpublished=true');
        if (!response.ok) throw new Error('Kunne ikke hente nyheter');
        
        const result = await response.json();
        if (result.success) {
            currentAnnouncements = result.announcements;
            displayAnnouncements();
        }
    } catch (error) {
        console.error('Load announcements error:', error);
    }
}

function filterNews(filter) {
    announcementsFilter = filter;
    document.querySelectorAll('.news-tabs .nav-link').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.filter === filter);
    });
    displayAnnouncements();
}

function displayAnnouncements() {
    const pinnedContainer = document.getElementById('pinned-list');
    const newsContainer = document.getElementById('news-list');
    const now = new Date();
    
    const pinned = currentAnnouncements.filter(a => a.is_pinned && a.is_published);
    
    let filtered = [];
    if (announcementsFilter === 'published') {
        filtered = currentAnnouncements.filter(a => 
            a.is_published && 
            !a.is_pinned &&
            (!a.publish_at || new Date(a.publish_at) <= now)
        );
    } else if (announcementsFilter === 'scheduled') {
        filtered = currentAnnouncements.filter(a => 
            a.is_published && 
            a.publish_at && 
            new Date(a.publish_at) > now
        );
    } else if (announcementsFilter === 'draft') {
        filtered = currentAnnouncements.filter(a => !a.is_published);
    }
    
    filtered.sort((a, b) => new Date(b.publish_at || b.created_at) - new Date(a.publish_at || a.created_at));
    
    if (pinned.length === 0) {
        pinnedContainer.innerHTML = `
            <div class="empty-pinned">
                <i class="bi bi-pin-angle d-block mb-2" style="font-size: 1.5rem;"></i>
                <small>Ingen festede nyheter</small>
            </div>
        `;
    } else {
        pinnedContainer.innerHTML = pinned.map(a => `
            <div class="pinned-card">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <h6>${a.title}</h6>
                        <p>${a.content.substring(0, 80)}${a.content.length > 80 ? '...' : ''}</p>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-sm btn-link text-muted p-0 me-2" onclick="togglePin('${a.id}')" title="Løsne">
                            <i class="bi bi-pin-angle-fill"></i>
                        </button>
                        <button class="btn btn-sm btn-link text-muted p-0" onclick="editAnnouncement('${a.id}')" title="Rediger">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }
    
    if (filtered.length === 0) {
        const messages = {
            published: 'Ingen publiserte nyheter',
            scheduled: 'Ingen planlagte nyheter',
            draft: 'Ingen kladder'
        };
        newsContainer.innerHTML = `
            <div class="text-center py-5 text-muted">
                <i class="bi bi-newspaper d-block mb-2" style="font-size: 2rem;"></i>
                <p>${messages[announcementsFilter]}</p>
            </div>
        `;
        return;
    }
    
    newsContainer.innerHTML = filtered.map(a => {
        const isExpired = a.expire_at && new Date(a.expire_at) < now;
        const isScheduled = a.publish_at && new Date(a.publish_at) > now;
        let cardClass = 'news-card';
        if (!a.is_published) cardClass += ' unpublished';
        if (isScheduled) cardClass += ' scheduled';
        if (isExpired) cardClass += ' expired';
        
        return `
            <div class="${cardClass}">
                <div class="d-flex gap-3">
                    ${a.image_url ? `<img src="${a.image_url}" alt="" class="news-thumb" onerror="this.style.display='none'">` : ''}
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h6 class="mb-1">${a.title}</h6>
                                <p class="text-muted small mb-1" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                    ${a.content}
                                </p>
                                <div class="d-flex gap-2 align-items-center">
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>${formatDate(a.publish_at || a.created_at)}
                                    </small>
                                    ${isScheduled ? '<span class="badge bg-primary">Planlagt</span>' : ''}
                                    ${isExpired ? '<span class="badge bg-dark">Utløpt</span>' : ''}
                                    ${!a.is_published ? '<span class="badge bg-secondary">Kladd</span>' : ''}
                                </div>
                            </div>
                            <div class="btn-group btn-group-sm">
                                ${!a.is_published ? `
                                    <button class="btn btn-outline-success" onclick="togglePublish('${a.id}')" title="Publiser">
                                        <i class="bi bi-send"></i>
                                    </button>
                                ` : `
                                    <button class="btn btn-outline-secondary" onclick="togglePublish('${a.id}')" title="Avpubliser">
                                        <i class="bi bi-eye-slash"></i>
                                    </button>
                                `}
                                <button class="btn btn-outline-info" onclick="togglePin('${a.id}')" title="${a.is_pinned ? 'Løsne' : 'Fest'}">
                                    <i class="bi bi-pin-angle${a.is_pinned ? '-fill' : ''}"></i>
                                </button>
                                <button class="btn btn-outline-primary" onclick="editAnnouncement('${a.id}')" title="Rediger">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="deleteAnnouncement('${a.id}')" title="Slett">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('nb-NO', { day: 'numeric', month: 'short', year: 'numeric' });
}

function toggleScheduleOptions() {
    const isScheduled = document.getElementById('announcement-schedule-toggle').checked;
    document.querySelectorAll('.schedule-options').forEach(el => {
        el.style.display = isScheduled ? '' : 'none';
    });
    if (!isScheduled) {
        document.getElementById('announcement-publish-at').value = '';
        document.getElementById('announcement-expire-at').value = '';
    }
}

function toggleProductSection() {
    const isEnabled = document.getElementById('announcement-product-toggle').checked;
    document.querySelectorAll('.product-section').forEach(el => {
        el.style.display = isEnabled ? '' : 'none';
    });
    if (isEnabled) {
        loadOutputProducts();
    } else {
        document.getElementById('announcement-product-id').value = '';
        document.getElementById('announcement-discount').value = '0';
        document.getElementById('announcement-max-qty').value = '';
        document.getElementById('announcement-product-expiry').value = '';
    }
}

async function loadOutputProducts() {
    const select = document.getElementById('announcement-product-id');
    select.innerHTML = '<option value="">Laster produkter...</option>';
    
    try {
        const response = await fetch('/api/get_output_products.php');
        const responseText = await response.text();
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error('JSON parse error:', responseText);
            select.innerHTML = '<option value="">Feil ved lasting av produkter</option>';
            return;
        }
        
        select.innerHTML = '<option value="">-- Velg produkt/tjeneste --</option>';
        
        if (result.success && result.products && result.products.length > 0) {
            result.products.forEach(p => {
                const option = document.createElement('option');
                option.value = p.id;
                option.textContent = `${p.name || 'Ukjent'} (${p.product_type})`;
                select.appendChild(option);
            });
        } else if (result.success && (!result.products || result.products.length === 0)) {
            select.innerHTML = '<option value="">Ingen produkter tilgjengelig</option>';
        } else {
            select.innerHTML = '<option value="">Feil: ' + (result.message || 'Kunne ikke hente produkter') + '</option>';
        }
    } catch (error) {
        console.error('Load products error:', error);
        select.innerHTML = '<option value="">Nettverksfeil - prøv igjen</option>';
    }
}

function openNewAnnouncementModal() {
    document.getElementById('announcementModalTitle').textContent = 'Ny nyhet';
    document.getElementById('announcementForm').reset();
    document.getElementById('announcement-id').value = '';
    document.getElementById('announcement-schedule-toggle').checked = false;
    document.getElementById('announcement-product-toggle').checked = false;
    toggleScheduleOptions();
    toggleProductSection();
    new bootstrap.Modal(document.getElementById('announcementModal')).show();
}

async function editAnnouncement(id) {
    const a = currentAnnouncements.find(x => x.id === id);
    if (!a) return;

    document.getElementById('announcementModalTitle').textContent = 'Rediger nyhet';
    document.getElementById('announcement-id').value = a.id;
    document.getElementById('announcement-title').value = a.title;
    document.getElementById('announcement-content').value = a.content;
    document.getElementById('announcement-image-url').value = a.image_url || '';
    document.getElementById('announcement-is-pinned').checked = a.is_pinned;
    
    const hasScheduleData = !!a.publish_at || !!a.expire_at;
    document.getElementById('announcement-schedule-toggle').checked = hasScheduleData;
    toggleScheduleOptions();
    
    if (a.publish_at) {
        const d = new Date(a.publish_at);
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        document.getElementById('announcement-publish-at').value = d.toISOString().slice(0, 16);
    }
    if (a.expire_at) {
        const d = new Date(a.expire_at);
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        document.getElementById('announcement-expire-at').value = d.toISOString().slice(0, 16);
    }

    // Load product data if exists
    if (a.product && a.product.output_product_id) {
        document.getElementById('announcement-product-toggle').checked = true;
        toggleProductSection();
        await loadOutputProducts();
        document.getElementById('announcement-product-id').value = a.product.output_product_id;
        document.getElementById('announcement-discount').value = a.product.discount_percent || 0;
        document.getElementById('announcement-max-qty').value = a.product.max_quantity || '';
        document.getElementById('announcement-product-expiry').value = a.product.expiry_date || '';
    } else {
        document.getElementById('announcement-product-toggle').checked = false;
        toggleProductSection();
    }
    
    new bootstrap.Modal(document.getElementById('announcementModal')).show();
}

async function saveAnnouncement(publish = true) {
    const id = document.getElementById('announcement-id').value;
    const title = document.getElementById('announcement-title').value.trim();
    const content = document.getElementById('announcement-content').value.trim();

    if (!title || !content) {
        alert('Tittel og innhold er påkrevd');
        return;
    }

    const isScheduled = document.getElementById('announcement-schedule-toggle').checked;
    const publishAt = document.getElementById('announcement-publish-at').value;
    const hasProduct = document.getElementById('announcement-product-toggle').checked;
    
    const data = {
        title,
        content,
        image_url: document.getElementById('announcement-image-url').value.trim() || null,
        is_published: publish,
        is_pinned: document.getElementById('announcement-is-pinned').checked,
        publish_at: isScheduled && publishAt ? publishAt : null,
        expire_at: document.getElementById('announcement-expire-at').value || null
    };
    
    if (hasProduct) {
        const productId = document.getElementById('announcement-product-id').value;
        if (productId) {
            data.product = {
                output_product_id: productId,
                discount_percent: parseInt(document.getElementById('announcement-discount').value) || 0,
                max_quantity: document.getElementById('announcement-max-qty').value ? parseInt(document.getElementById('announcement-max-qty').value) : null,
                expiry_date: document.getElementById('announcement-product-expiry').value || null
            };
        }
    }

    if (id) data.id = id;

    try {
        const endpoint = id ? '/api/update_announcement.php' : '/api/create_announcement.php';
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const responseText = await response.text();
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON parse error:', parseError, 'Response:', responseText);
            alert('Serverfeil: Ugyldig respons fra server');
            return;
        }
        
        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('announcementModal')).hide();
            await loadAnnouncements();
        } else {
            alert('Feil: ' + (result.message || 'Ukjent feil'));
        }
    } catch (error) {
        console.error('Save error:', error);
        alert('Kunne ikke lagre: ' + error.message);
    }
}

async function togglePublish(id) {
    try {
        const response = await fetch('/api/toggle_announcement_publish.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        const result = await response.json();
        if (result.success) await loadAnnouncements();
    } catch (error) {
        console.error('Toggle error:', error);
    }
}

async function togglePin(id) {
    try {
        const response = await fetch('/api/toggle_announcement_pin.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        const result = await response.json();
        if (result.success) await loadAnnouncements();
    } catch (error) {
        console.error('Toggle pin error:', error);
    }
}

async function deleteAnnouncement(id) {
    if (!confirm('Slette denne nyheten?')) return;
    try {
        const response = await fetch('/api/delete_announcement.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        const result = await response.json();
        if (result.success) await loadAnnouncements();
    } catch (error) {
        console.error('Delete error:', error);
    }
}

document.addEventListener('shown.bs.tab', function(event) {
    if (event.target.getAttribute('data-bs-target') === '#content-announcements') {
        loadAnnouncements();
    }
});
</script>
