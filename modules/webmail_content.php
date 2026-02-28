<?php
$smtpConfigured = false;
$webmailConfigured = false;

try {
    if (isset($db) && isset($_SESSION['company_id'])) {
        $chkStmt = $db->prepare("SELECT smtp_address, smtp_username FROM email_configs WHERE company_id = ? LIMIT 1");
        $chkStmt->execute([$_SESSION['company_id']]);
        $chkConfig = $chkStmt->fetch(PDO::FETCH_ASSOC);
        $smtpConfigured = !empty($chkConfig['smtp_address']) && !empty($chkConfig['smtp_username']);
        $webmailConfigured = $smtpConfigured;
    }
} catch (Exception $e) {}
?>
<style>
.wm-shell {
    display: flex;
    height: 70vh;
    min-height: 540px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}
.wm-sidebar {
    width: 220px;
    min-width: 200px;
    background: #f8f9fa;
    border-right: 1px solid #dee2e6;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    flex-shrink: 0;
}
.wm-sidebar .wm-compose-btn {
    margin: 14px 12px 10px;
}
.wm-sidebar .wm-nav { list-style: none; padding: 0; margin: 0; }
.wm-sidebar .wm-nav li a {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 9px 16px;
    color: #343a40;
    text-decoration: none;
    font-size: 0.9rem;
    border-left: 3px solid transparent;
    transition: background 0.15s;
}
.wm-sidebar .wm-nav li a:hover { background: #e9ecef; }
.wm-sidebar .wm-nav li a.active {
    background: #e7f1ff;
    color: #0d6efd;
    border-left-color: #0d6efd;
    font-weight: 600;
}
.wm-sidebar .wm-nav li a .badge { margin-left: auto; }
.wm-sidebar .wm-divider { border-top: 1px solid #dee2e6; margin: 8px 0; }
.wm-main {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.wm-list-pane {
    width: 300px;
    min-width: 240px;
    border-right: 1px solid #dee2e6;
    overflow-y: auto;
    flex-shrink: 0;
    background: #fff;
}
.wm-content-pane {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
}
.wm-main-inner {
    flex: 1;
    display: flex;
    overflow: hidden;
}
.wm-email-item {
    padding: 12px 14px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.12s;
}
.wm-email-item:hover { background: #f8f9fa; }
.wm-email-item.unread { background: #f0f6ff; }
.wm-email-item .wm-from { font-weight: 600; font-size: 0.88rem; }
.wm-email-item .wm-subject { font-size: 0.85rem; color: #495057; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.wm-email-item .wm-date { font-size: 0.75rem; color: #adb5bd; }
.wm-automailer-accordion .accordion-button { font-size: 0.82rem; padding: 8px 12px; }
.wm-automailer-accordion .accordion-body { font-size: 0.82rem; padding: 10px 12px; }

/* Status badges */
.badge-queued { background: #6c757d; }
.badge-sending { background: #fd7e14; }
.badge-sent { background: #198754; }
.badge-failed { background: #dc3545; }

/* Recipient tags */
.recipient-tag {
    display: inline-flex; align-items: center; gap: 4px;
    background: #e7f1ff; color: #0d6efd; border-radius: 20px;
    padding: 2px 10px; font-size: 0.82rem; margin: 2px;
}
.recipient-tag .rm { cursor: pointer; font-weight: bold; color: #6c757d; }
.recipient-tag .rm:hover { color: #dc3545; }

@media (max-width: 768px) {
    .wm-shell { flex-direction: column; height: auto; min-height: 0; }
    .wm-sidebar { width: 100%; min-width: 0; border-right: none; border-bottom: 1px solid #dee2e6; flex-direction: row; flex-wrap: wrap; overflow-x: auto; }
    .wm-sidebar .wm-nav { display: flex; flex-direction: row; flex-wrap: wrap; }
    .wm-sidebar .wm-nav li a { border-left: none; border-bottom: 3px solid transparent; padding: 8px 12px; font-size: 0.8rem; }
    .wm-sidebar .wm-nav li a.active { border-left: none; border-bottom-color: #0d6efd; }
    .wm-sidebar .wm-compose-btn { width: 100%; }
    .wm-main-inner { flex-direction: column; }
    .wm-list-pane { width: 100%; border-right: none; border-bottom: 1px solid #dee2e6; max-height: 280px; }
    .wm-content-pane { min-height: 300px; }
    .wm-automailer-accordion { display: none; }
}
</style>

<?php if (!$webmailConfigured): ?>
<div class="alert alert-warning d-flex align-items-center gap-3 mb-3">
    <i class="bi bi-exclamation-triangle-fill fs-4"></i>
    <div>
        <strong>E-postklient ikke konfigurert</strong> — Legg til SMTP-innstillinger (smtp_address, smtp_port, smtp_username, passord) i <code>email_configs</code> for å aktivere sending.
    </div>
</div>
<?php endif; ?>

<div class="wm-shell">

    <!-- SIDEBAR -->
    <div class="wm-sidebar">
        <button class="btn btn-primary btn-sm wm-compose-btn" onclick="wmShow('compose')">
            <i class="bi bi-pencil-square me-1"></i>Ny e-post
        </button>
        <ul class="wm-nav">
            <li><a href="#" class="active" id="wm-nav-inbox" onclick="wmShow('inbox', this)">
                <i class="bi bi-inbox"></i> Innboks
                <span class="badge bg-primary" id="wm-inbox-count" style="display:none;"></span>
            </a></li>
            <li><a href="#" id="wm-nav-queue" onclick="wmShow('queue', this)">
                <i class="bi bi-hourglass-split"></i> Mail-kø
                <span class="badge bg-secondary" id="wm-queue-count" style="display:none;"></span>
            </a></li>
            <li><a href="#" id="wm-nav-sent" onclick="wmShow('sent', this)">
                <i class="bi bi-send-check"></i> Sendte
            </a></li>
        </ul>

        <div class="wm-divider"></div>

        <!-- AutoMailer som accordion i sidepanelet -->
        <div class="accordion wm-automailer-accordion border-0" id="wmAutomailerAccordion">
            <div class="accordion-item border-0 bg-transparent">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed bg-transparent" type="button"
                            data-bs-toggle="collapse" data-bs-target="#wmAutomailerBody">
                        <i class="bi bi-envelope-paper me-2 text-muted"></i>
                        <small>AutoMailer</small>
                    </button>
                </h2>
                <div id="wmAutomailerBody" class="accordion-collapse collapse" data-bs-parent="#wmAutomailerAccordion">
                    <div class="accordion-body p-2">
                        <div id="wm-automailer-content">
                            <div class="text-center text-muted py-2"><small>Laster...</small></div>
                        </div>
                        <div class="mt-2">
                            <button class="btn btn-primary btn-sm w-100" onclick="saveAutomailerSettings()">
                                <i class="bi bi-save me-1"></i>Lagre
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN -->
    <div class="wm-main">

        <!-- COMPOSE VIEW -->
        <div id="wm-view-compose" class="wm-content-pane" style="display:none;">
            <h6 class="mb-3"><i class="bi bi-pencil-square me-2"></i>Ny e-post</h6>
            <div class="mb-2">
                <label class="form-label fw-semibold small mb-1">Til</label>
                <div id="wm-to-tags" class="border rounded p-2 mb-1 d-flex flex-wrap gap-1" style="min-height:38px;cursor:text;" onclick="document.getElementById('wm-to-input').focus()"></div>
                <div class="input-group input-group-sm">
                    <input type="email" class="form-control" id="wm-to-input" placeholder="Skriv e-postadresse og trykk Enter...">
                    <button class="btn btn-outline-secondary" onclick="openRecipientPicker()">
                        <i class="bi bi-people"></i> Velg
                    </button>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label fw-semibold small mb-1">Emne</label>
                <input type="text" class="form-control form-control-sm" id="wm-subject">
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold small mb-1">Melding</label>
                <textarea class="form-control" id="wm-body" rows="14" style="resize:vertical;font-size:0.9rem;"></textarea>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-primary" onclick="wmSend()">
                    <i class="bi bi-send me-1"></i>Legg i kø og send
                </button>
                <button class="btn btn-outline-secondary" onclick="wmShow('inbox', document.getElementById('wm-nav-inbox'))">Avbryt</button>
            </div>
            <div id="wm-compose-alert" class="mt-3" style="display:none;"></div>
        </div>

        <!-- INBOX VIEW -->
        <div id="wm-view-inbox" class="wm-main-inner">
            <div class="wm-list-pane" id="wm-inbox-list">
                <div class="p-3 text-center text-muted">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <div class="small mt-2">Laster innboks...</div>
                </div>
            </div>
            <div class="wm-content-pane" id="wm-inbox-detail">
                <div class="text-center text-muted py-5">
                    <i class="bi bi-envelope-open fs-1 d-block mb-3 text-secondary"></i>
                    <p>Velg en e-post for å lese den</p>
                </div>
            </div>
        </div>

        <!-- QUEUE VIEW -->
        <div id="wm-view-queue" class="wm-content-pane" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>Mail-kø</h6>
                <button class="btn btn-sm btn-outline-secondary" onclick="wmLoadQueue()">
                    <i class="bi bi-arrow-clockwise"></i> Oppdater
                </button>
            </div>
            <div class="btn-group btn-group-sm mb-3" id="wm-queue-filter">
                <button class="btn btn-outline-secondary active" onclick="wmQueueFilter('all', this)">Alle</button>
                <button class="btn btn-outline-secondary" onclick="wmQueueFilter('queued', this)">Venter</button>
                <button class="btn btn-outline-secondary" onclick="wmQueueFilter('sent', this)">Sendt</button>
                <button class="btn btn-outline-secondary" onclick="wmQueueFilter('failed', this)">Feilet</button>
            </div>
            <div id="wm-queue-table"></div>
        </div>

        <!-- SENT VIEW -->
        <div id="wm-view-sent" class="wm-content-pane" style="display:none;">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="mb-0"><i class="bi bi-send-check me-2"></i>Sendte e-poster</h6>
                <button class="btn btn-sm btn-outline-secondary" onclick="wmLoadLogs()">
                    <i class="bi bi-arrow-clockwise"></i> Oppdater
                </button>
            </div>
            <div id="wm-logs-table"></div>
        </div>

    </div>
</div>

<!-- MOTTAKERPICKER MODAL -->
<div class="modal fade" id="wmRecipientModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title"><i class="bi bi-people me-2"></i>Velg mottakere</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <ul class="nav nav-tabs px-3 pt-2" id="wmRecipientTabs">
                    <li class="nav-item">
                        <a class="nav-link active" href="#" onclick="wmLoadRecipients('customers', this); return false;">
                            <i class="bi bi-person me-1"></i>Kunder
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" onclick="wmLoadRecipients('members', this); return false;">
                            <i class="bi bi-people me-1"></i>Aktive medlemmer
                        </a>
                    </li>
                </ul>
                <div class="px-3 pt-2 pb-1">
                    <input type="text" class="form-control form-control-sm" id="wm-recipient-search" placeholder="Søk navn eller e-post..." oninput="wmFilterRecipients(this.value)">
                </div>
                <div class="px-3 pb-1">
                    <div class="form-check form-check-sm">
                        <input class="form-check-input" type="checkbox" id="wm-select-all" onchange="wmSelectAllRecipients(this.checked)">
                        <label class="form-check-label small" for="wm-select-all">Velg alle synlige</label>
                    </div>
                </div>
                <div id="wm-recipient-list" style="max-height:380px;overflow-y:auto;padding:0 12px 12px;"></div>
            </div>
            <div class="modal-footer py-2">
                <span class="text-muted small me-auto" id="wm-selected-count">0 valgt</span>
                <button class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Avbryt</button>
                <button class="btn btn-primary btn-sm" onclick="wmAddSelectedRecipients()">
                    <i class="bi bi-plus me-1"></i>Legg til valgte
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let wmCurrentView = 'inbox';
let wmRecipients = [];
let wmQueueStatusFilter = 'all';
let wmInboxFolder = 'inbox';
let wmCurrentInboxEmails = [];

function fmtDateWm(d) {
    if (!d) return '-';
    const dt = new Date(d);
    return isNaN(dt) ? d : dt.toLocaleDateString('nb-NO', {day:'2-digit', month:'2-digit', year:'numeric'});
}

function fmtDateTimeWm(d) {
    if (!d) return '-';
    const dt = new Date(d);
    return isNaN(dt) ? d : dt.toLocaleString('nb-NO', {day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'});
}

function wmShow(view, navEl) {
    document.querySelectorAll('.wm-nav li a').forEach(a => a.classList.remove('active'));
    if (navEl) navEl.classList.add('active');

    document.getElementById('wm-view-compose').style.display = 'none';
    document.getElementById('wm-view-inbox').style.display = 'none';
    document.getElementById('wm-view-queue').style.display = 'none';
    document.getElementById('wm-view-sent').style.display = 'none';

    wmCurrentView = view;

    if (view === 'inbox') {
        document.getElementById('wm-view-inbox').style.display = 'flex';
        wmLoadInbox();
    } else if (view === 'queue') {
        document.getElementById('wm-view-queue').style.display = 'block';
        wmLoadQueue();
    } else if (view === 'sent') {
        document.getElementById('wm-view-sent').style.display = 'block';
        wmLoadLogs();
    } else if (view === 'compose') {
        document.getElementById('wm-view-compose').style.display = 'block';
    }
}

/* ===== INNBOKS (IMAP) ===== */
async function wmLoadInbox() {
    const listEl = document.getElementById('wm-inbox-list');
    listEl.innerHTML = '<div class="p-3 text-center text-muted"><div class="spinner-border spinner-border-sm"></div><div class="small mt-2">Laster...</div></div>';
    document.getElementById('wm-inbox-detail').innerHTML = '<div class="text-center text-muted py-5"><i class="bi bi-envelope-open fs-1 d-block mb-3 text-secondary"></i><p>Velg en e-post</p></div>';

    try {
        const resp = await fetch('/api/get_emails.php?folder=inbox', {credentials:'same-origin'});
        const text = await resp.text();
        let data;
        try { data = JSON.parse(text); } catch(e) { throw new Error('Ugyldig respons: ' + text.substring(0,100)); }

        if (data.success && data.emails && data.emails.length > 0) {
            wmCurrentInboxEmails = data.emails;
            const unread = data.unread || 0;
            if (unread > 0) {
                const badge = document.getElementById('wm-inbox-count');
                badge.textContent = unread;
                badge.style.display = '';
            }
            listEl.innerHTML = data.emails.map(e => `
                <div class="wm-email-item ${e.seen ? '' : 'unread'}" onclick="wmOpenEmail(${e.uid})">
                    <div class="wm-from">${!e.seen ? '<i class="bi bi-circle-fill text-primary me-1" style="font-size:8px;"></i>' : ''}${escapeH(e.from_name)}</div>
                    <div class="wm-subject">${escapeH(e.subject)}</div>
                    <div class="wm-date">${e.date}</div>
                </div>
            `).join('');
        } else if (data.error) {
            listEl.innerHTML = `<div class="p-3 text-muted small"><i class="bi bi-exclamation-triangle text-warning me-1"></i>${escapeH(data.error)}</div>`;
        } else {
            listEl.innerHTML = '<div class="p-3 text-center text-muted small"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Innboksen er tom</div>';
        }
    } catch(err) {
        listEl.innerHTML = `<div class="p-3 text-muted small text-danger">${escapeH(err.message)}</div>`;
    }
}

async function wmOpenEmail(uid) {
    const detailEl = document.getElementById('wm-inbox-detail');
    detailEl.innerHTML = '<div class="text-center py-5"><div class="spinner-border"></div></div>';
    try {
        const resp = await fetch(`/api/get_email_content.php?uid=${uid}&folder=inbox`, {credentials:'same-origin'});
        const data = JSON.parse(await resp.text());
        if (data.success) {
            const e = data.email;
            detailEl.innerHTML = `
                <h6>${escapeH(e.subject)}</h6>
                <div class="text-muted small mb-3">
                    <strong>Fra:</strong> ${escapeH(e.from)}<br>
                    <strong>Til:</strong> ${escapeH(e.to)}<br>
                    <strong>Dato:</strong> ${e.date}
                </div>
                <hr>
                <div style="font-size:0.9rem;">${e.body}</div>
            `;
        } else {
            detailEl.innerHTML = `<div class="alert alert-danger">${escapeH(data.error || 'Feil ved henting')}</div>`;
        }
    } catch(err) {
        detailEl.innerHTML = `<div class="alert alert-danger">${escapeH(err.message)}</div>`;
    }
}

/* ===== COMPOSE ===== */
let wmToList = [];

document.addEventListener('DOMContentLoaded', function() {
    const toInput = document.getElementById('wm-to-input');
    if (toInput) {
        toInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const val = this.value.trim().replace(/,$/, '');
                if (val && val.includes('@')) {
                    wmAddTag(val);
                    this.value = '';
                }
            }
        });
    }

    wmShow('inbox', document.getElementById('wm-nav-inbox'));
    loadAutomailerSettings();
});

function wmAddTag(email) {
    if (!wmToList.includes(email)) {
        wmToList.push(email);
        renderTags();
    }
}

function wmRemoveTag(email) {
    wmToList = wmToList.filter(e => e !== email);
    renderTags();
}

function renderTags() {
    const container = document.getElementById('wm-to-tags');
    container.innerHTML = wmToList.map(e => `
        <span class="recipient-tag">
            ${escapeH(e)}
            <span class="rm" onclick="wmRemoveTag('${escapeH(e)}')">&times;</span>
        </span>
    `).join('');
}

async function wmSend() {
    const input = document.getElementById('wm-to-input').value.trim().replace(/,$/, '');
    if (input && input.includes('@')) {
        wmAddTag(input);
        document.getElementById('wm-to-input').value = '';
    }

    const subject = document.getElementById('wm-subject').value.trim();
    const body    = document.getElementById('wm-body').value.trim();
    const alertEl = document.getElementById('wm-compose-alert');

    if (wmToList.length === 0) {
        alertEl.style.display = '';
        alertEl.innerHTML = '<div class="alert alert-warning">Legg til minst én mottaker</div>';
        return;
    }
    if (!subject) {
        alertEl.style.display = '';
        alertEl.innerHTML = '<div class="alert alert-warning">Emne er påkrevd</div>';
        return;
    }

    alertEl.style.display = '';
    alertEl.innerHTML = '<div class="alert alert-info"><div class="spinner-border spinner-border-sm me-2"></div>Legger i kø...</div>';

    try {
        const resp = await fetch('/api/queue_email.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({to: wmToList, subject, body})
        });
        const data = JSON.parse(await resp.text());

        if (data.success) {
            alertEl.innerHTML = `<div class="alert alert-success"><i class="bi bi-check-circle me-2"></i>${data.message}</div>`;
            document.getElementById('wm-subject').value = '';
            document.getElementById('wm-body').value = '';
            wmToList = [];
            renderTags();
        } else {
            alertEl.innerHTML = `<div class="alert alert-danger">${escapeH(data.error)}</div>`;
        }
    } catch(err) {
        alertEl.innerHTML = `<div class="alert alert-danger">Feil: ${escapeH(err.message)}</div>`;
    }
}

/* ===== MOTTAKERPICKER ===== */
let wmAllRecipients = [];

function openRecipientPicker() {
    document.getElementById('wm-recipient-search').value = '';
    wmLoadRecipients('customers', document.querySelector('#wmRecipientTabs .nav-link'));
    new bootstrap.Modal(document.getElementById('wmRecipientModal')).show();
}

async function wmLoadRecipients(source, navEl) {
    document.querySelectorAll('#wmRecipientTabs .nav-link').forEach(a => a.classList.remove('active'));
    if (navEl) navEl.classList.add('active');

    const listEl = document.getElementById('wm-recipient-list');
    listEl.innerHTML = '<div class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></div>';

    try {
        const resp = await fetch(`/api/get_recipients.php?source=${source}`, {credentials:'same-origin'});
        const data = JSON.parse(await resp.text());
        if (data.success) {
            wmAllRecipients = data.recipients;
            wmRenderRecipients(data.recipients);
        } else {
            listEl.innerHTML = `<div class="text-danger small p-2">${escapeH(data.error)}</div>`;
        }
    } catch(err) {
        listEl.innerHTML = `<div class="text-danger small p-2">${escapeH(err.message)}</div>`;
    }
}

function wmRenderRecipients(list) {
    const listEl = document.getElementById('wm-recipient-list');
    if (!list.length) {
        listEl.innerHTML = '<div class="text-muted small p-2">Ingen mottakere funnet</div>';
        return;
    }
    listEl.innerHTML = list.map(r => `
        <div class="form-check py-1 border-bottom wm-recipient-row" data-email="${escapeH(r.email)}" data-name="${escapeH((r.first_name||'') + ' ' + (r.last_name||''))}">
            <input class="form-check-input wm-rec-chk" type="checkbox" id="rec_${escapeH(r.id)}" value="${escapeH(r.email)}" ${wmToList.includes(r.email)?'checked':''}>
            <label class="form-check-label small w-100" for="rec_${escapeH(r.id)}">
                <strong>${escapeH(r.first_name||'')} ${escapeH(r.last_name||'')}</strong>
                <span class="text-muted ms-2">${escapeH(r.email)}</span>
            </label>
        </div>
    `).join('');
    updateSelectedCount();
}

function wmFilterRecipients(query) {
    const q = query.toLowerCase();
    const rows = document.querySelectorAll('.wm-recipient-row');
    rows.forEach(row => {
        const match = row.dataset.email.toLowerCase().includes(q) || row.dataset.name.toLowerCase().includes(q);
        row.style.display = match ? '' : 'none';
    });
}

function wmSelectAllRecipients(checked) {
    document.querySelectorAll('.wm-rec-chk').forEach(cb => {
        if (cb.closest('.wm-recipient-row').style.display !== 'none') cb.checked = checked;
    });
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.wm-rec-chk:checked').length;
    document.getElementById('wm-selected-count').textContent = count + ' valgt';
}

document.addEventListener('change', function(e) {
    if (e.target.classList.contains('wm-rec-chk')) updateSelectedCount();
});

function wmAddSelectedRecipients() {
    document.querySelectorAll('.wm-rec-chk:checked').forEach(cb => {
        wmAddTag(cb.value);
    });
    bootstrap.Modal.getInstance(document.getElementById('wmRecipientModal')).hide();
}

/* ===== MAIL-KØ ===== */
async function wmLoadQueue() {
    const el = document.getElementById('wm-queue-table');
    el.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div></div>';

    try {
        const resp = await fetch(`/api/get_email_queue.php?status=${wmQueueStatusFilter}&limit=100`, {credentials:'same-origin'});
        const data = JSON.parse(await resp.text());

        if (!data.success) {
            el.innerHTML = `<div class="alert alert-danger">${escapeH(data.error)}</div>`;
            return;
        }

        const badge = document.getElementById('wm-queue-count');
        const pending = (data.queue || []).filter(r => r.status === 'queued').length;
        if (pending > 0) { badge.textContent = pending; badge.style.display = ''; }
        else badge.style.display = 'none';

        if (!data.queue || !data.queue.length) {
            el.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-inbox fs-2 d-block mb-2"></i>Ingen e-poster i køen</div>';
            return;
        }

        el.innerHTML = `
            <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Status</th>
                        <th>Mottaker</th>
                        <th>Emne</th>
                        <th>Forsøk</th>
                        <th>Sendt</th>
                        <th>Feil</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.queue.map(r => `
                        <tr>
                            <td><span class="badge badge-${r.status}">${wmStatusLabel(r.status)}</span></td>
                            <td class="small">${escapeH(r.recipient_email)}</td>
                            <td class="small">${escapeH(r.subject)}</td>
                            <td class="small text-center">${r.attempts}/${r.max_attempts||3}</td>
                            <td class="small">${fmtDateTimeWm(r.sent_at)}</td>
                            <td class="small text-danger">${r.error_message ? escapeH(r.error_message.substring(0,60)) : '-'}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
            <div class="text-muted small mt-2 px-1">Totalt ${data.total} poster</div>
        `;
    } catch(err) {
        el.innerHTML = `<div class="alert alert-danger">${escapeH(err.message)}</div>`;
    }
}

function wmQueueFilter(status, btn) {
    wmQueueStatusFilter = status;
    document.querySelectorAll('#wm-queue-filter .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    wmLoadQueue();
}

function wmStatusLabel(s) {
    const labels = {queued:'Venter', sending:'Sender', sent:'Sendt', failed:'Feilet'};
    return labels[s] || s;
}

/* ===== SENDTE (LOGS) ===== */
async function wmLoadLogs() {
    const el = document.getElementById('wm-logs-table');
    el.innerHTML = '<div class="text-center py-4"><div class="spinner-border spinner-border-sm"></div></div>';

    try {
        const resp = await fetch('/api/get_email_logs.php?limit=100', {credentials:'same-origin'});
        const data = JSON.parse(await resp.text());

        if (!data.success) {
            el.innerHTML = `<div class="alert alert-danger">${escapeH(data.error)}</div>`;
            return;
        }

        if (!data.logs || !data.logs.length) {
            el.innerHTML = '<div class="text-center text-muted py-4"><i class="bi bi-send-check fs-2 d-block mb-2"></i>Ingen sendte e-poster</div>';
            return;
        }

        el.innerHTML = `
            <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Fra</th>
                        <th>Til</th>
                        <th>Emne</th>
                        <th>Status</th>
                        <th>Dato</th>
                    </tr>
                </thead>
                <tbody>
                    ${data.logs.map(r => `
                        <tr>
                            <td class="small">${escapeH(r.from_email)}</td>
                            <td class="small">${escapeH(r.to_email)}</td>
                            <td class="small">${escapeH(r.subject)}</td>
                            <td><span class="badge ${r.status==='sent'?'bg-success':'bg-danger'}">${r.status==='sent'?'Sendt':'Feil'}</span></td>
                            <td class="small">${fmtDateTimeWm(r.sent_at)}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
            </div>
            <div class="text-muted small mt-2 px-1">Totalt ${data.total} poster</div>
        `;
    } catch(err) {
        el.innerHTML = `<div class="alert alert-danger">${escapeH(err.message)}</div>`;
    }
}

/* ===== AUTOMAILER ===== */
async function loadAutomailerSettings() {
    try {
        const resp = await fetch('/api/get_automailer_settings.php', {credentials:'same-origin'});
        const data = JSON.parse(await resp.text());
        if (!data.success) return;

        const modules = data.modules || [];
        const settings = data.settings || {};

        const moduleConfig = [
            {key:'rental',       label:'Utleie',        icon:'key',         checks:[
                {col:'automail_rental_confirm', label:'Bekreftelse ved ny booking'},
                {col:'automail_rental_return',  label:'Returpåminnelse'}
            ]},
            {key:'events',       label:'Arrangement',   icon:'calendar-event', checks:[
                {col:'automail_events_confirm',    label:'Bekreftelse ved påmelding'},
                {col:'automail_events_reminder_3d',label:'Påminnelse 3 dager før'},
                {col:'automail_events_thankyou',   label:'Takkebrev etter arrangement'}
            ]},
            {key:'membership',   label:'Medlemskap',    icon:'people',      checks:[
                {col:'automail_membership_welcome', label:'Velkomst til nye medlemmer'},
                {col:'automail_membership_renewal', label:'Fornyelsespåminnelse'}
            ]},
            {key:'reservations', label:'Reservasjoner', icon:'calendar-check', checks:[
                {col:'automail_reservation_confirm',  label:'Bekreftelse ved reservasjon'},
                {col:'automail_reservation_reminder', label:'Påminnelse før reservasjon'}
            ]},
            {key:'sales',        label:'Salg',          icon:'cart',        checks:[
                {col:'automail_sales_order', label:'Ordrebekreftelse ved kjøp'}
            ]}
        ];

        const html = moduleConfig
            .filter(m => modules.includes(m.key))
            .map(m => `
                <div class="mb-2">
                    <div class="fw-semibold small mb-1"><i class="bi bi-${m.icon} me-1 text-muted"></i>${m.label}</div>
                    ${m.checks.map(c => `
                        <div class="form-check ms-2 mb-1">
                            <input class="form-check-input automail-setting" type="checkbox"
                                   id="am_${c.col}" data-column="${c.col}"
                                   ${(settings[c.col]==1||settings[c.col]==='1')?'checked':''}>
                            <label class="form-check-label small" for="am_${c.col}">${c.label}</label>
                        </div>
                    `).join('')}
                </div>
            `).join('');

        document.getElementById('wm-automailer-content').innerHTML = html || '<div class="text-muted small">Ingen aktive moduler</div>';

    } catch(err) {
        document.getElementById('wm-automailer-content').innerHTML = '<div class="text-muted small">Kunne ikke laste</div>';
    }
}

async function saveAutomailerSettings() {
    const settings = {};
    document.querySelectorAll('.automail-setting').forEach(cb => {
        settings[cb.dataset.column] = cb.checked ? 1 : 0;
    });

    try {
        const resp = await fetch('/api/save_automailer_settings.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify(settings)
        });
        const data = JSON.parse(await resp.text());
        if (data.success) {
            const btn = document.querySelector('[onclick="saveAutomailerSettings()"]');
            if (btn) { btn.textContent = '✓ Lagret'; setTimeout(() => { btn.innerHTML = '<i class="bi bi-save me-1"></i>Lagre'; }, 2000); }
        }
    } catch(err) {}
}

function escapeH(t) {
    const d = document.createElement('div');
    d.textContent = t || '';
    return d.innerHTML;
}
</script>
