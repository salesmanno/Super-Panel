<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">📢 Kunngjøringer</h4>
            <p class="text-muted mb-0">Administrer firmaets kunngjøringer og meldinger</p>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#announcementModal" onclick="openNewAnnouncementModal()">
            <i class="bi bi-plus-lg me-2"></i>Ny kunngjøring
        </button>
    </div>

    <!-- Filters -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Type</label>
                    <select class="form-select form-select-sm" id="filter-type" onchange="loadAnnouncements()">
                        <option value="">Alle typer</option>
                        <option value="info">Info</option>
                        <option value="warning">Advarsel</option>
                        <option value="event">Arrangement</option>
                        <option value="update">Oppdatering</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Prioritet</label>
                    <select class="form-select form-select-sm" id="filter-priority" onchange="loadAnnouncements()">
                        <option value="">Alle prioriteter</option>
                        <option value="low">Lav</option>
                        <option value="normal">Normal</option>
                        <option value="high">Høy</option>
                        <option value="urgent">Haster</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">&nbsp;</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="filter-unpublished" onchange="loadAnnouncements()">
                        <label class="form-check-label" for="filter-unpublished">
                            Vis upubliserte
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Announcements List -->
    <div id="announcements-list"></div>
</div>

<!-- Announcement Modal -->
<div class="modal fade" id="announcementModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="announcementModalTitle">Ny kunngjøring</h5>
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
                        <textarea class="form-control" id="announcement-content" rows="5" required></textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select class="form-select" id="announcement-type">
                                <option value="info">Info</option>
                                <option value="warning">Advarsel</option>
                                <option value="event">Arrangement</option>
                                <option value="update">Oppdatering</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Prioritet</label>
                            <select class="form-select" id="announcement-priority">
                                <option value="low">Lav</option>
                                <option value="normal" selected>Normal</option>
                                <option value="high">Høy</option>
                                <option value="urgent">Haster</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Målgruppe</label>
                            <select class="form-select" id="announcement-audience">
                                <option value="all" selected>Alle</option>
                                <option value="members">Medlemmer</option>
                                <option value="staff">Ansatte</option>
                                <option value="customers">Kunder</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Publiseres</label>
                            <input type="datetime-local" class="form-control" id="announcement-publish-at">
                            <small class="text-muted">La stå tom for umiddelbar publisering</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Utløper</label>
                            <input type="datetime-local" class="form-control" id="announcement-expire-at">
                            <small class="text-muted">Valgfri utløpsdato</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="announcement-is-published">
                            <label class="form-check-label" for="announcement-is-published">
                                Publiser umiddelbart
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="announcement-is-pinned">
                            <label class="form-check-label" for="announcement-is-pinned">
                                Fest øverst
                            </label>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="saveAnnouncement()">Lagre</button>
            </div>
        </div>
    </div>
</div>

<script>
let currentAnnouncements = [];

async function loadAnnouncements() {
    try {
        const type = document.getElementById('filter-type').value;
        const priority = document.getElementById('filter-priority').value;
        const showUnpublished = document.getElementById('filter-unpublished').checked;

        const params = new URLSearchParams();
        if (type) params.append('type', type);
        if (priority) params.append('priority', priority);
        if (showUnpublished) params.append('show_unpublished', 'true');

        const response = await fetch(`/api/get_announcements.php?${params.toString()}`);
        const result = await response.json();

        if (result.success) {
            currentAnnouncements = result.announcements;
            displayAnnouncements(result.announcements);
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Load announcements error:', error);
    }
}

function displayAnnouncements(announcements) {
    const container = document.getElementById('announcements-list');

    if (announcements.length === 0) {
        container.innerHTML = `
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="bi bi-megaphone text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-0">Ingen kunngjøringer funnet</p>
                </div>
            </div>
        `;
        return;
    }

    const typeIcons = {
        'info': 'info-circle-fill text-primary',
        'warning': 'exclamation-triangle-fill text-warning',
        'event': 'calendar-event-fill text-success',
        'update': 'arrow-up-circle-fill text-info'
    };

    const priorityBadges = {
        'low': 'secondary',
        'normal': 'primary',
        'high': 'warning',
        'urgent': 'danger'
    };

    container.innerHTML = announcements.map(announcement => {
        const typeIcon = typeIcons[announcement.announcement_type] || 'megaphone text-muted';
        const priorityBadge = priorityBadges[announcement.priority] || 'secondary';
        const isExpired = announcement.expire_at && new Date(announcement.expire_at) < new Date();

        return `
            <div class="card shadow-sm mb-3 ${isExpired ? 'opacity-50' : ''}">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-${typeIcon}"></i>
                                <h5 class="mb-0">${announcement.title}</h5>
                                ${announcement.is_pinned ? '<span class="badge bg-info"><i class="bi bi-pin-angle-fill"></i> Festet</span>' : ''}
                                ${announcement.is_published ? '<span class="badge bg-success">Publisert</span>' : '<span class="badge bg-secondary">Upublisert</span>'}
                                <span class="badge bg-${priorityBadge}">${announcement.priority}</span>
                                ${isExpired ? '<span class="badge bg-dark">Utløpt</span>' : ''}
                            </div>
                            <p class="mb-2">${announcement.content}</p>
                            <div class="d-flex gap-3 text-muted small">
                                <span><i class="bi bi-person"></i> ${announcement.author_name || 'Ukjent'}</span>
                                <span><i class="bi bi-calendar"></i> ${new Date(announcement.publish_at).toLocaleDateString('nb-NO')}</span>
                                ${announcement.expire_at ? `<span><i class="bi bi-hourglass-split"></i> Utløper: ${new Date(announcement.expire_at).toLocaleDateString('nb-NO')}</span>` : ''}
                                <span><i class="bi bi-people"></i> ${getAudienceName(announcement.target_audience)}</span>
                            </div>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-primary" onclick="togglePublish('${announcement.id}')" title="Publiser/Upubliser">
                                <i class="bi bi-${announcement.is_published ? 'eye-slash' : 'eye'}"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="togglePin('${announcement.id}')" title="Fest/Løsne">
                                <i class="bi bi-pin-angle${announcement.is_pinned ? '-fill' : ''}"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="editAnnouncement('${announcement.id}')" title="Rediger">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="deleteAnnouncement('${announcement.id}')" title="Slett">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
}

function getAudienceName(audience) {
    const audiences = {
        'all': 'Alle',
        'members': 'Medlemmer',
        'staff': 'Ansatte',
        'customers': 'Kunder'
    };
    return audiences[audience] || audience;
}

function openNewAnnouncementModal() {
    document.getElementById('announcementModalTitle').textContent = 'Ny kunngjøring';
    document.getElementById('announcementForm').reset();
    document.getElementById('announcement-id').value = '';
    
    // Set default publish time to now
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('announcement-publish-at').value = now.toISOString().slice(0, 16);
}

function editAnnouncement(id) {
    const announcement = currentAnnouncements.find(a => a.id === id);
    if (!announcement) return;

    document.getElementById('announcementModalTitle').textContent = 'Rediger kunngjøring';
    document.getElementById('announcement-id').value = announcement.id;
    document.getElementById('announcement-title').value = announcement.title;
    document.getElementById('announcement-content').value = announcement.content;
    document.getElementById('announcement-type').value = announcement.announcement_type;
    document.getElementById('announcement-priority').value = announcement.priority;
    document.getElementById('announcement-audience').value = announcement.target_audience;
    document.getElementById('announcement-is-published').checked = announcement.is_published;
    document.getElementById('announcement-is-pinned').checked = announcement.is_pinned;

    if (announcement.publish_at) {
        const publishDate = new Date(announcement.publish_at);
        publishDate.setMinutes(publishDate.getMinutes() - publishDate.getTimezoneOffset());
        document.getElementById('announcement-publish-at').value = publishDate.toISOString().slice(0, 16);
    }

    if (announcement.expire_at) {
        const expireDate = new Date(announcement.expire_at);
        expireDate.setMinutes(expireDate.getMinutes() - expireDate.getTimezoneOffset());
        document.getElementById('announcement-expire-at').value = expireDate.toISOString().slice(0, 16);
    }

    new bootstrap.Modal(document.getElementById('announcementModal')).show();
}

async function saveAnnouncement() {
    const id = document.getElementById('announcement-id').value;
    const title = document.getElementById('announcement-title').value.trim();
    const content = document.getElementById('announcement-content').value.trim();

    if (!title || !content) {
        alert('❌ Tittel og innhold er påkrevd');
        return;
    }

    const data = {
        title: title,
        content: content,
        announcement_type: document.getElementById('announcement-type').value,
        priority: document.getElementById('announcement-priority').value,
        target_audience: document.getElementById('announcement-audience').value,
        is_published: document.getElementById('announcement-is-published').checked,
        is_pinned: document.getElementById('announcement-is-pinned').checked,
        publish_at: document.getElementById('announcement-publish-at').value || null,
        expire_at: document.getElementById('announcement-expire-at').value || null
    };

    if (id) {
        data.id = id;
    }

    try {
        const endpoint = id ? '/api/update_announcement.php' : '/api/create_announcement.php';
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            bootstrap.Modal.getInstance(document.getElementById('announcementModal')).hide();
            await loadAnnouncements();
            alert('✅ ' + result.message);
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Save announcement error:', error);
        alert('❌ Kunne ikke lagre kunngjøring');
    }
}

async function togglePublish(id) {
    if (!confirm('Vil du endre publiseringsstatus for denne kunngjøringen?')) return;

    try {
        const response = await fetch('/api/toggle_announcement_publish.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id})
        });

        const result = await response.json();

        if (result.success) {
            await loadAnnouncements();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Toggle publish error:', error);
    }
}

async function togglePin(id) {
    try {
        const response = await fetch('/api/toggle_announcement_pin.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id})
        });

        const result = await response.json();

        if (result.success) {
            await loadAnnouncements();
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Toggle pin error:', error);
    }
}

async function deleteAnnouncement(id) {
    if (!confirm('Er du sikker på at du vil slette denne kunngjøringen? Dette kan ikke angres.')) return;

    try {
        const response = await fetch('/api/delete_announcement.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id: id})
        });

        const result = await response.json();

        if (result.success) {
            await loadAnnouncements();
            alert('✅ ' + result.message);
        } else {
            alert('❌ ' + result.message);
        }
    } catch (error) {
        console.error('Delete announcement error:', error);
    }
}

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    // Only load if the announcements module is visible
    if (document.getElementById('announcements-list')) {
        loadAnnouncements();
    }
});

// Also load when tab becomes visible
document.addEventListener('shown.bs.tab', function(event) {
    if (event.target.getAttribute('data-bs-target') === '#content-announcements') {
        loadAnnouncements();
    }
});
</script>

<style>
#announcements-list .card {
    transition: all 0.2s ease;
}

#announcements-list .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
}
</style>
