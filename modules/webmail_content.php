<?php if (!$webmailConfigured): ?>
<div class="module-content">
    <div class="alert alert-warning border-warning">
        <div class="d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill fs-1 me-3"></i>
            <div>
                <h5 class="alert-heading mb-2">Webmail ikke konfigurert</h5>
                <p class="mb-2">E-postklient er ikke konfigurert for din bedrift. Du må opprette en webmail-konto før du kan bruke denne modulen.</p>
                <hr class="my-3">
                <p class="mb-0">
                    <strong>Hva må gjøres:</strong>
                </p>
                <ul class="mb-0">
                    <li>Kontakt systemadministrator for å konfigurere e-post server (IMAP/SMTP)</li>
                    <li>Opprett webmail-konto med e-postadresse og innstillinger</li>
                    <li>Konfigurer domeneoppsett for e-post (domeneshop.no eller lignende)</li>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="module-content">
    <div id="webmail-client" class="subtab-content">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
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
                                <i class="bi bi-trash me-2"></i>Papirkurv
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Innboks</h5>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-envelope-open fs-1 d-block mb-3"></i>
                            <p>Ingen e-poster i innboksen</p>
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
</div>
<?php endif; ?>

