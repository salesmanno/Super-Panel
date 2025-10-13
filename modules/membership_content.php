<div class="module-content">
    <div id="membership-plans" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Medlemskapsplaner</h5>
                    <button class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>Ny plan
                    </button>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card border">
                            <div class="card-body">
                                <h6>Standardmedlemskap</h6>
                                <p class="text-muted small">Årlig medlemskap</p>
                                <h4 class="text-primary">500 NOK</h4>
                                <button class="btn btn-outline-primary btn-sm w-100 mt-2">Rediger</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="membership-members" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Medlemsoversikt</h5>
                    <button class="btn btn-primary btn-sm">
                        <i class="bi bi-plus-circle me-1"></i>Legg til medlem
                    </button>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" placeholder="Søk etter fornavn, etternavn, e-post eller telefon...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select">
                            <option value="">Alle medlemstyper</option>
                            <option value="standard">Standardmedlemskap</option>
                            <option value="premium">Premium</option>
                            <option value="student">Student</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select">
                            <option value="">Alle statuser</option>
                            <option value="active">Aktiv</option>
                            <option value="expired">Utløpt</option>
                            <option value="pending">Venter</option>
                        </select>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Medlemsnr</th>
                                <th>Fornavn</th>
                                <th>Etternavn</th>
                                <th>E-post</th>
                                <th>Telefon</th>
                                <th>Adresse</th>
                                <th>Medlemstype</th>
                                <th>Status</th>
                                <th>Medlem siden</th>
                                <th>Gyldig til</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="11" class="text-center text-muted py-4">
                                    Ingen medlemmer funnet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">Viser 0 av 0 medlemmer</small>
                    <nav>
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled">
                                <a class="page-link" href="#">Forrige</a>
                            </li>
                            <li class="page-item active">
                                <a class="page-link" href="#">1</a>
                            </li>
                            <li class="page-item disabled">
                                <a class="page-link" href="#">Neste</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <div id="membership-requests" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-person-plus me-2"></i>Medlemsforespørsler</h5>
                <p class="text-muted">Se og behandle innkommende medlemskapsforespørsler</p>
                
                <div class="mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <input type="text" class="form-control" placeholder="Søk i forespørsler...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select">
                                <option value="">Alle medlemstyper</option>
                                <option value="standard">Standardmedlemskap</option>
                                <option value="premium">Premium</option>
                                <option value="student">Student</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select">
                                <option value="">Alle statuser</option>
                                <option value="pending">Venter</option>
                                <option value="approved">Godkjent</option>
                                <option value="rejected">Avvist</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Dato</th>
                                <th>Fornavn</th>
                                <th>Etternavn</th>
                                <th>E-post</th>
                                <th>Telefon</th>
                                <th>Medlemstype</th>
                                <th>Status</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-1 d-block mb-3 text-muted"></i>
                                    Ingen forespørsler å behandle
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-info mt-3">
                    <i class="bi bi-info-circle me-2"></i>
                    Nye medlemsforespørsler vises her når de mottas via nettside eller påmeldingsskjema
                </div>
            </div>
        </div>
    </div>

    <div id="membership-income" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Medlemsinntekter</h5>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Total omsetning</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Aktive medlemmer</small>
                                <h4 class="mb-0">0</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">Denne måneden</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light">
                            <div class="card-body">
                                <small class="text-muted">I år</small>
                                <h4 class="mb-0">0 NOK</h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

