<div class="module-content">
    <h3><i class="bi bi-envelope me-2"></i>Webmail</h3>
    <p class="text-muted mb-4">E-post administrasjon</p>
    
    <ul class="nav nav-pills mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#webmail-client" type="button" role="tab">
                <i class="bi bi-inbox-fill me-1"></i>E-post klient
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#webmail-history" type="button" role="tab">
                <i class="bi bi-clock-history me-1"></i>E-post historikk
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="webmail-client" role="tabpanel">
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

        <div class="tab-pane fade" id="webmail-history" role="tabpanel">
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
</div>
