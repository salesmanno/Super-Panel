<div class="module-content position-relative">
<?php if (!isset($webmailConfigured) || !$webmailConfigured): ?>
    <!-- Overlay melding for ikke-konfigurert -->
    <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(255,255,255,0.95); z-index: 1; display: flex; align-items: center; justify-content: center;">
        <div class="text-center" style="max-width: 600px; padding: 3rem;">
            <i class="bi bi-envelope-x" style="font-size: 5rem; color: #ffc107; margin-bottom: 1.5rem;"></i>
            <h3 class="mb-3">E-postklient må konfigureres</h3>
            <p class="text-muted mb-4">E-postklienten er klar til bruk, men krever konfigurasjon før den kan aktiveres.</p>
            
            <div class="alert alert-warning text-start mb-4">
                <h6 class="alert-heading"><i class="bi bi-list-check me-2"></i>Konfigurasjonssjekk:</h6>
                <ul class="mb-0">
                    <?php if (!isset($smtpConfigured) || !$smtpConfigured): ?>
                    <li class="text-danger mb-2">
                        <i class="bi bi-x-circle me-1"></i>
                        <strong>SMTP-server ikke konfigurert</strong> - Host, port, brukernavn og passord mangler
                    </li>
                    <?php else: ?>
                    <li class="text-success mb-2">
                        <i class="bi bi-check-circle me-1"></i>
                        <strong>SMTP-server konfigurert</strong>
                    </li>
                    <?php endif; ?>
                    <li class="text-muted">
                        <i class="bi bi-circle me-1"></i>
                        Webmail-konto må opprettes
                    </li>
                    <li class="text-muted">
                        <i class="bi bi-circle me-1"></i>
                        Domene-konfigurasjon anbefales
                    </li>
                </ul>
            </div>
            
            <button class="btn btn-warning btn-lg" disabled>
                <i class="bi bi-gear me-2"></i>Konfigurasjon kreves
            </button>
        </div>
    </div>
<?php endif; ?>
    <div id="webmail-client" class="subtab-content">
        <div class="row">
            <div class="col-md-3">
                <div class="card" style="min-height: 600px;">
                    <div class="card-body">
                        <button class="btn btn-primary w-100 mb-3" onclick="openComposeModal()">
                            <i class="bi bi-pencil-square me-1"></i>Ny e-post
                        </button>
                        <div class="list-group" id="email-folders">
                            <a href="#" class="list-group-item list-group-item-action active" data-folder="inbox" onclick="loadEmails('inbox', this)">
                                <i class="bi bi-inbox me-2"></i>Innboks <span class="badge bg-primary float-end" id="inbox-count">0</span>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action" data-folder="sent" onclick="loadEmails('sent', this)">
                                <i class="bi bi-send me-2"></i>Sendt
                            </a>
                            <a href="#" class="list-group-item list-group-item-action" data-folder="starred" onclick="loadEmails('starred', this)">
                                <i class="bi bi-star me-2"></i>Stjernemerket
                            </a>
                            <a href="#" class="list-group-item list-group-item-action" data-folder="archive" onclick="loadEmails('archive', this)">
                                <i class="bi bi-archive me-2"></i>Arkiv
                            </a>
                            <a href="#" class="list-group-item list-group-item-action" data-folder="trash" onclick="loadEmails('trash', this)">
                                <i class="bi bi-trash me-2"></i>Papirkurv
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card" style="min-height: 600px;">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="card-title mb-0" id="folder-title"><i class="bi bi-inbox me-2"></i>Innboks</h5>
                            <div>
                                <button class="btn btn-sm btn-outline-secondary me-2" onclick="refreshEmails()">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>
                        <div id="email-list-container" class="flex-grow-1">
                            <div id="email-loading" class="text-center py-5" style="display: none;">
                                <div class="spinner-border text-primary" role="status"></div>
                                <p class="mt-2 text-muted">Laster e-poster...</p>
                            </div>
                            <div id="email-list"></div>
                            <div id="email-empty" class="text-center text-muted py-5">
                                <i class="bi bi-envelope-open fs-1 d-block mb-3"></i>
                                <h6>Ingen e-poster</h6>
                                <p class="small">E-poster vil vises her når de mottas</p>
                            </div>
                        </div>
                        <div id="email-content" style="display: none;">
                            <button class="btn btn-sm btn-outline-secondary mb-3" onclick="backToList()">
                                <i class="bi bi-arrow-left me-1"></i>Tilbake
                            </button>
                            <div id="email-detail"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Compose Email Modal -->
    <div class="modal fade" id="composeModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Ny e-post</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="compose-form">
                        <div class="mb-3">
                            <label class="form-label">Til</label>
                            <input type="email" class="form-control" id="compose-to" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Emne</label>
                            <input type="text" class="form-control" id="compose-subject" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Melding</label>
                            <textarea class="form-control" id="compose-body" rows="10"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                    <button type="button" class="btn btn-primary" onclick="sendEmail()">
                        <i class="bi bi-send me-1"></i>Send
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    let currentFolder = 'inbox';
    let emailsData = [];
    
    const folderNames = {
        'inbox': 'Innboks',
        'sent': 'Sendt',
        'starred': 'Stjernemerket',
        'archive': 'Arkiv',
        'trash': 'Papirkurv'
    };
    
    const folderIcons = {
        'inbox': 'inbox',
        'sent': 'send',
        'starred': 'star',
        'archive': 'archive',
        'trash': 'trash'
    };
    
    async function loadEmails(folder, element) {
        currentFolder = folder;
        
        document.querySelectorAll('#email-folders .list-group-item').forEach(el => el.classList.remove('active'));
        if (element) element.classList.add('active');
        
        document.getElementById('folder-title').innerHTML = `<i class="bi bi-${folderIcons[folder]} me-2"></i>${folderNames[folder]}`;
        document.getElementById('email-loading').style.display = 'block';
        document.getElementById('email-list').innerHTML = '';
        document.getElementById('email-empty').style.display = 'none';
        document.getElementById('email-content').style.display = 'none';
        document.getElementById('email-list-container').style.display = 'block';
        
        try {
            const response = await fetch(`/api/get_emails.php?folder=${folder}`, { credentials: 'same-origin' });
            const text = await response.text();
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                throw new Error('Ugyldig respons fra server: ' + text.substring(0, 100));
            }
            
            document.getElementById('email-loading').style.display = 'none';
            
            if (data.success && data.emails && data.emails.length > 0) {
                emailsData = data.emails;
                renderEmailList(data.emails);
                if (folder === 'inbox') {
                    document.getElementById('inbox-count').textContent = data.unread || 0;
                }
            } else if (data.error) {
                document.getElementById('email-empty').innerHTML = `
                    <i class="bi bi-exclamation-triangle fs-1 d-block mb-3 text-warning"></i>
                    <h6>Feil</h6>
                    <p class="small">${escapeHtml(data.error)}</p>
                `;
                document.getElementById('email-empty').style.display = 'block';
            } else {
                document.getElementById('email-empty').style.display = 'block';
            }
        } catch (error) {
            document.getElementById('email-loading').style.display = 'none';
            document.getElementById('email-empty').innerHTML = `
                <i class="bi bi-exclamation-triangle fs-1 d-block mb-3 text-warning"></i>
                <h6>Kunne ikke laste e-poster</h6>
                <p class="small">${escapeHtml(error.message)}</p>
            `;
            document.getElementById('email-empty').style.display = 'block';
        }
    }
    
    function renderEmailList(emails) {
        const container = document.getElementById('email-list');
        container.innerHTML = emails.map(email => `
            <div class="email-item p-3 border-bottom ${email.seen ? '' : 'bg-light fw-bold'}" 
                 style="cursor: pointer;" 
                 onclick="openEmail(${email.uid})">
                <div class="d-flex justify-content-between">
                    <div class="text-truncate" style="max-width: 200px;">
                        ${email.seen ? '' : '<i class="bi bi-circle-fill text-primary me-2" style="font-size: 8px;"></i>'}
                        ${escapeHtml(email.from_name)}
                    </div>
                    <small class="text-muted">${email.date}</small>
                </div>
                <div class="text-truncate">${escapeHtml(email.subject)}</div>
            </div>
        `).join('');
        document.getElementById('email-empty').style.display = 'none';
    }
    
    async function openEmail(uid) {
        document.getElementById('email-list-container').style.display = 'none';
        document.getElementById('email-content').style.display = 'block';
        document.getElementById('email-detail').innerHTML = '<div class="text-center py-5"><div class="spinner-border"></div></div>';
        
        try {
            const response = await fetch(`/api/get_email_content.php?uid=${uid}&folder=${currentFolder}`, { credentials: 'same-origin' });
            const text = await response.text();
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                throw new Error('Ugyldig respons fra server');
            }
            
            if (data.success) {
                document.getElementById('email-detail').innerHTML = `
                    <div class="mb-3">
                        <h5>${escapeHtml(data.email.subject)}</h5>
                        <div class="text-muted small">
                            <strong>Fra:</strong> ${escapeHtml(data.email.from)}<br>
                            <strong>Til:</strong> ${escapeHtml(data.email.to)}<br>
                            <strong>Dato:</strong> ${data.email.date}
                        </div>
                    </div>
                    <hr>
                    <div class="email-body">${data.email.body}</div>
                `;
            } else {
                document.getElementById('email-detail').innerHTML = `<div class="alert alert-danger">${escapeHtml(data.error || 'Ukjent feil')}</div>`;
            }
        } catch (error) {
            document.getElementById('email-detail').innerHTML = `<div class="alert alert-danger">${escapeHtml(error.message)}</div>`;
        }
    }
    
    function backToList() {
        document.getElementById('email-content').style.display = 'none';
        document.getElementById('email-list-container').style.display = 'block';
    }
    
    function refreshEmails() {
        loadEmails(currentFolder, document.querySelector(`[data-folder="${currentFolder}"]`));
    }
    
    function openComposeModal() {
        document.getElementById('compose-to').value = '';
        document.getElementById('compose-subject').value = '';
        document.getElementById('compose-body').value = '';
        new bootstrap.Modal(document.getElementById('composeModal')).show();
    }
    
    async function sendEmail() {
        const to = document.getElementById('compose-to').value;
        const subject = document.getElementById('compose-subject').value;
        const body = document.getElementById('compose-body').value;
        
        if (!to || !subject) {
            alert('Fyll inn mottaker og emne');
            return;
        }
        
        try {
            const response = await fetch('/api/send_email.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ to, subject, body })
            });
            const text = await response.text();
            
            let data;
            try {
                data = JSON.parse(text);
            } catch (parseError) {
                throw new Error('Ugyldig respons fra server: ' + text.substring(0, 100));
            }
            
            if (data.success) {
                bootstrap.Modal.getInstance(document.getElementById('composeModal')).hide();
                alert('E-post sendt!');
            } else {
                alert('Feil: ' + data.error);
            }
        } catch (error) {
            alert('Kunne ikke sende e-post');
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text || '';
        return div.innerHTML;
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        const webmailTab = document.getElementById('tab-webmail');
        if (webmailTab) {
            webmailTab.addEventListener('shown.bs.tab', function() {
                loadEmails('inbox', document.querySelector('[data-folder="inbox"]'));
            });
        }
        
        if (document.querySelector('#content-webmail.active')) {
            loadEmails('inbox', document.querySelector('[data-folder="inbox"]'));
        }
    });
    </script>

    <div id="webmail-history" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">E-post historikk</h5>
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" placeholder="Søk i e-post...">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" placeholder="Fra dato">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" placeholder="Til dato">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dato</th>
                                <th>Fra</th>
                                <th>Til</th>
                                <th>Emne</th>
                                <th>Status</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">
                                    Ingen e-poster i historikk
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="webmail-automailer" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-envelope-paper me-2"></i>AutoMailer - Automatiske e-poster</h5>
                <p class="text-muted">Aktiver eller deaktiver automatiske e-postutsendelser knyttet til forskjellige moduler</p>
                
                <div class="alert alert-info mb-4">
                    <i class="bi bi-info-circle me-2"></i>
                    Når AutoMailer er aktivert for en modul, vil systemet automatisk sende e-poster ved viktige hendelser.
                </div>
                
                <!-- Utleie AutoMailer -->
                <div class="card mb-3 automailer-module" data-module="rental">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-key me-2"></i>Utleie</h6>
                                <small class="text-muted">Automatiske e-poster for utleiebekreftelser og retur</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="automailer-rental" style="width: 3rem; height: 1.5rem;">
                                <label class="form-check-label ms-2" for="automailer-rental"></label>
                            </div>
                        </div>
                        <div class="mt-3 collapse" id="rental-automailer-settings">
                            <hr>
                            <div class="form-check mb-2">
                                <input class="form-check-input automail-setting" type="checkbox" id="automail_rental_confirm" data-column="automail_rental_confirm">
                                <label class="form-check-label" for="automail_rental_confirm">
                                    Bekreftelse ved ny utleiebooking
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input automail-setting" type="checkbox" id="automail_rental_return" data-column="automail_rental_return">
                                <label class="form-check-label" for="automail_rental_return">
                                    Returpåminnelse
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Arrangement AutoMailer -->
                <div class="card mb-3 automailer-module" data-module="events">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-calendar-event me-2"></i>Arrangement</h6>
                                <small class="text-muted">Automatiske e-poster for arrangementsbekreftelser og påminnelser</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="automailer-events" style="width: 3rem; height: 1.5rem;">
                                <label class="form-check-label ms-2" for="automailer-events"></label>
                            </div>
                        </div>
                        <div class="mt-3 collapse" id="events-automailer-settings">
                            <hr>
                            <div class="form-check mb-2">
                                <input class="form-check-input automail-setting" type="checkbox" id="automail_events_confirm" data-column="automail_events_confirm">
                                <label class="form-check-label" for="automail_events_confirm">
                                    Bekreftelse ved påmelding
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input automail-setting" type="checkbox" id="automail_events_reminder_3d" data-column="automail_events_reminder_3d">
                                <label class="form-check-label" for="automail_events_reminder_3d">
                                    Påminnelse 3 dager før arrangement
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input automail-setting" type="checkbox" id="automail_events_thankyou" data-column="automail_events_thankyou">
                                <label class="form-check-label" for="automail_events_thankyou">
                                    Takkebrev etter arrangement
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Medlemskap AutoMailer -->
                <div class="card mb-3 automailer-module" data-module="membership">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-people me-2"></i>Medlemskap</h6>
                                <small class="text-muted">Automatiske e-poster for medlemskapsbekreftelser og fornyelser</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="automailer-membership" style="width: 3rem; height: 1.5rem;">
                                <label class="form-check-label ms-2" for="automailer-membership"></label>
                            </div>
                        </div>
                        <div class="mt-3 collapse" id="membership-automailer-settings">
                            <hr>
                            <div class="form-check mb-2">
                                <input class="form-check-input automail-setting" type="checkbox" id="automail_membership_welcome" data-column="automail_membership_welcome">
                                <label class="form-check-label" for="automail_membership_welcome">
                                    Velkomst-e-post til nye medlemmer
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input automail-setting" type="checkbox" id="automail_membership_renewal" data-column="automail_membership_renewal">
                                <label class="form-check-label" for="automail_membership_renewal">
                                    Fornyelsespåminnelse
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reservasjoner AutoMailer -->
                <div class="card mb-3 automailer-module" data-module="reservations">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-calendar-check me-2"></i>Reservasjoner</h6>
                                <small class="text-muted">Automatiske e-poster for reservasjonsbekreftelser</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="automailer-reservations" style="width: 3rem; height: 1.5rem;">
                                <label class="form-check-label ms-2" for="automailer-reservations"></label>
                            </div>
                        </div>
                        <div class="mt-3 collapse" id="reservations-automailer-settings">
                            <hr>
                            <div class="form-check mb-2">
                                <input class="form-check-input automail-setting" type="checkbox" id="automail_reservation_confirm" data-column="automail_reservation_confirm">
                                <label class="form-check-label" for="automail_reservation_confirm">
                                    Bekreftelse ved ny reservasjon
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input automail-setting" type="checkbox" id="automail_reservation_reminder" data-column="automail_reservation_reminder">
                                <label class="form-check-label" for="automail_reservation_reminder">
                                    Påminnelse før reservasjon
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Salg AutoMailer -->
                <div class="card mb-3 automailer-module" data-module="sales">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-cart me-2"></i>Salg</h6>
                                <small class="text-muted">Automatiske e-poster for ordrebekreftelser</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="automailer-sales" style="width: 3rem; height: 1.5rem;">
                                <label class="form-check-label ms-2" for="automailer-sales"></label>
                            </div>
                        </div>
                        <div class="mt-3 collapse" id="sales-automailer-settings">
                            <hr>
                            <div class="form-check">
                                <input class="form-check-input automail-setting" type="checkbox" id="automail_sales_order" data-column="automail_sales_order">
                                <label class="form-check-label" for="automail_sales_order">
                                    Ordrebekreftelse ved kjøp
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-end mt-4">
                    <button class="btn btn-primary" onclick="saveAutomailerSettings()">
                        <i class="bi bi-save me-1"></i>Lagre innstillinger
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('[id^="automailer-"]').forEach(toggle => {
    toggle.addEventListener('change', function() {
        const module = this.id.replace('automailer-', '');
        const settingsDiv = document.getElementById(module + '-automailer-settings');
        if (settingsDiv) {
            if (this.checked) {
                settingsDiv.classList.add('show');
            } else {
                settingsDiv.classList.remove('show');
            }
        }
    });
});

async function loadAutomailerSettings() {
    try {
        const response = await fetch('/api/get_automailer_settings.php', { credentials: 'same-origin' });
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Ugyldig JSON:', text);
            return;
        }
        
        if (!data.success) return;
        
        const userModules = data.modules || [];
        
        document.querySelectorAll('.automailer-module').forEach(card => {
            const moduleKey = card.dataset.module;
            if (!userModules.includes(moduleKey)) {
                card.style.display = 'none';
            }
        });
        
        const settings = data.settings || {};
        document.querySelectorAll('.automail-setting').forEach(checkbox => {
            const column = checkbox.dataset.column;
            if (settings[column] === 1 || settings[column] === '1') {
                checkbox.checked = true;
            } else {
                checkbox.checked = false;
            }
        });
        
        document.querySelectorAll('.automailer-module').forEach(card => {
            if (card.style.display !== 'none') {
                const hasChecked = card.querySelectorAll('.automail-setting:checked').length > 0;
                const toggle = card.querySelector('[id^="automailer-"]');
                const settingsDiv = card.querySelector('[id$="-automailer-settings"]');
                if (hasChecked && toggle && settingsDiv) {
                    toggle.checked = true;
                    settingsDiv.classList.add('show');
                }
            }
        });
        
    } catch (error) {
        console.error('Feil ved lasting av automailer-innstillinger:', error);
    }
}

async function saveAutomailerSettings() {
    const settings = {};
    
    document.querySelectorAll('.automail-setting').forEach(checkbox => {
        const column = checkbox.dataset.column;
        const card = checkbox.closest('.automailer-module');
        if (card && card.style.display !== 'none') {
            settings[column] = checkbox.checked ? 1 : 0;
        }
    });
    
    try {
        const response = await fetch('/api/save_automailer_settings.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify(settings)
        });
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            alert('Feil: Ugyldig respons fra server');
            return;
        }
        
        if (data.success) {
            alert('Innstillinger lagret!');
        } else {
            alert('Feil: ' + (data.error || 'Ukjent feil'));
        }
    } catch (error) {
        alert('Kunne ikke lagre innstillinger: ' + error.message);
    }
}

if (document.getElementById('webmail-automailer')) {
    loadAutomailerSettings();
}
</script>

