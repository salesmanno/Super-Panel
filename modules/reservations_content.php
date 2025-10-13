<div class="module-content">
    <h3><i class="bi bi-calendar-check me-2"></i>Reservasjoner</h3>
    <p class="text-muted mb-4">Administrer reservasjoner</p>
    
    <ul class="nav nav-pills mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#reservations-incoming" type="button" role="tab">
                <i class="bi bi-inbox me-1"></i>Reservasjonsmottak
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#reservations-confirmed" type="button" role="tab">
                <i class="bi bi-check-circle me-1"></i>Bekreftede Reservasjoner
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="reservations-incoming" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Ventende reservasjoner</h5>
                    <p class="text-muted">Reservasjoner som venter på bekreftelse</p>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Reservasjonsnr.</th>
                                    <th>Kunde</th>
                                    <th>Type</th>
                                    <th>Dato</th>
                                    <th>Status</th>
                                    <th>Handling</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                        Ingen ventende reservasjoner
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="reservations-confirmed" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Bekreftede reservasjoner</h5>
                    <div class="mb-3">
                        <div class="row">
                            <div class="col-md-4">
                                <input type="date" class="form-control" placeholder="Fra dato">
                            </div>
                            <div class="col-md-4">
                                <input type="date" class="form-control" placeholder="Til dato">
                            </div>
                            <div class="col-md-4">
                                <button class="btn btn-primary w-100">
                                    <i class="bi bi-search me-1"></i>Søk
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Reservasjonsnr.</th>
                                    <th>Kunde</th>
                                    <th>Type</th>
                                    <th>Fra dato</th>
                                    <th>Til dato</th>
                                    <th>Beløp</th>
                                    <th>Handling</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        Ingen bekreftede reservasjoner
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
