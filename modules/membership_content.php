<div class="module-content">
    <h3><i class="bi bi-people me-2"></i>Medlemskap</h3>
    <p class="text-muted mb-4">Administrer medlemskap og medlemmer</p>
    
    <ul class="nav nav-pills mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#membership-plans" type="button" role="tab">
                <i class="bi bi-card-list me-1"></i>Dine medlemskapsplaner
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#membership-members" type="button" role="tab">
                <i class="bi bi-people-fill me-1"></i>Medlemmer
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#membership-income" type="button" role="tab">
                <i class="bi bi-graph-up me-1"></i>Medlemsinntekter
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="membership-plans" role="tabpanel">
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

        <div class="tab-pane fade" id="membership-members" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Medlemsoversikt</h5>
                    <div class="mb-3">
                        <input type="text" class="form-control" placeholder="Søk etter medlem...">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Navn</th>
                                    <th>E-post</th>
                                    <th>Medlemstype</th>
                                    <th>Status</th>
                                    <th>Gyldig til</th>
                                    <th>Handling</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Ingen medlemmer funnet
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="membership-income" role="tabpanel">
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
</div>
