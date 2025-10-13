<?php if (!isset($webmailConfigured) || !$webmailConfigured): ?>
<div class="module-content">
    <div class="alert alert-warning border-warning">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-1 me-3"></i>
            <div>
                <h5 class="alert-heading mb-2">
                    <i class="bi bi-envelope-slash me-2"></i>E-postklient ikke konfigurert
                </h5>
                <p class="mb-2">E-postklient mangler nødvendig konfigurasjon for din bedrift.</p>
                <hr class="my-3">
                <p class="mb-2">
                    <strong>Manglende konfigurasjon:</strong>
                </p>
                <ul class="mb-3">
                    <?php if (!isset($smtpConfigured) || !$smtpConfigured): ?>
                    <li class="text-danger"><strong>SMTP-server ikke konfigurert</strong> - Nødvendig for å sende e-poster</li>
                    <?php endif; ?>
                    <li>Webmail-konto med e-postadresse må opprettes</li>
                    <li>Domeneoppsett for e-post må konfigureres (domeneshop.no eller lignende)</li>
                </ul>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle me-2"></i>
                    <strong>SMTP-konfigurasjon kreves:</strong> SMTP-server (host, port, brukernavn og passord) må legges inn i <code>x_webmailconfiguration</code> tabellen før e-postklienten kan brukes.
                </div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="module-content">
    <div id="webmail-client" class="subtab-content">
        <div class="row">
            <div class="col-md-3">
                <div class="card" style="min-height: 600px;">
                    <div class="card-body">
                        <button class="btn btn-primary w-100 mb-3">
                            <i class="bi bi-pencil-square me-1"></i>Ny e-post
                        </button>
                        <div class="list-group">
                            <a href="#" class="list-group-item list-group-item-action active">
                                <i class="bi bi-inbox me-2"></i>Innboks <span class="badge bg-primary float-end">0</span>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="bi bi-send me-2"></i>Sendt
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="bi bi-star me-2"></i>Stjernemerket
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
                                <i class="bi bi-archive me-2"></i>Arkiv
                            </a>
                            <a href="#" class="list-group-item list-group-item-action">
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
                            <h5 class="card-title mb-0"><i class="bi bi-inbox me-2"></i>Innboks</h5>
                            <div>
                                <button class="btn btn-sm btn-outline-secondary me-2">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-funnel"></i>
                                </button>
                            </div>
                        </div>
                        <div class="flex-grow-1 d-flex align-items-center justify-content-center">
                            <div class="text-center text-muted">
                                <i class="bi bi-envelope-open fs-1 d-block mb-3"></i>
                                <h6>Ingen e-poster i innboksen</h6>
                                <p class="small">E-poster vil vises her når de mottas</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><i class="bi bi-key me-2"></i>Utleie</h6>
                                <small class="text-muted">Automatiske e-poster for utleiebekreftelser, påminnelser og kvitteringer</small>
                            </div>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="automailer-rental" style="width: 3rem; height: 1.5rem;">
                                <label class="form-check-label ms-2" for="automailer-rental"></label>
                            </div>
                        </div>
                        <div class="mt-3 collapse" id="rental-automailer-settings">
                            <hr>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="rental-booking-confirm">
                                <label class="form-check-label" for="rental-booking-confirm">
                                    Bekreftelse ved ny utleiebooking
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="rental-reminder">
                                <label class="form-check-label" for="rental-reminder">
                                    Påminnelse 24 timer før utleie
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="rental-receipt">
                                <label class="form-check-label" for="rental-receipt">
                                    Kvittering etter utleie
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Arrangement AutoMailer -->
                <div class="card mb-3">
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
                                <input class="form-check-input" type="checkbox" id="events-registration-confirm">
                                <label class="form-check-label" for="events-registration-confirm">
                                    Bekreftelse ved påmelding
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="events-reminder">
                                <label class="form-check-label" for="events-reminder">
                                    Påminnelse 3 dager før arrangement
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="events-thank-you">
                                <label class="form-check-label" for="events-thank-you">
                                    Takkebrev etter arrangement
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Medlemskap AutoMailer -->
                <div class="card mb-3">
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
                                <input class="form-check-input" type="checkbox" id="membership-welcome">
                                <label class="form-check-label" for="membership-welcome">
                                    Velkomst-e-post til nye medlemmer
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="membership-renewal">
                                <label class="form-check-label" for="membership-renewal">
                                    Fornyelsespåminnelse 30 dager før utløp
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="membership-expired">
                                <label class="form-check-label" for="membership-expired">
                                    Varsel ved utløpt medlemskap
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reservasjoner AutoMailer -->
                <div class="card mb-3">
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
                                <input class="form-check-input" type="checkbox" id="reservations-confirm">
                                <label class="form-check-label" for="reservations-confirm">
                                    Bekreftelse ved ny reservasjon
                                </label>
                            </div>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" id="reservations-cancellation">
                                <label class="form-check-label" for="reservations-cancellation">
                                    Varsel ved kansellering
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-end mt-4">
                    <button class="btn btn-primary">
                        <i class="bi bi-save me-1"></i>Lagre innstillinger
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
// Toggle AutoMailer settings
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
</script>

