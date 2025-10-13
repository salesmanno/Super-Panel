<div class="module-content">
    <h3><i class="bi bi-box-seam me-2"></i>Utleie</h3>
    <p class="text-muted mb-4">Administrer utleieobjekter</p>
    
    <ul class="nav nav-pills mb-4" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#rental-objects" type="button" role="tab">
                <i class="bi bi-box me-1"></i>Se og opprett utleieobjekt
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#rental-contracts" type="button" role="tab">
                <i class="bi bi-file-text me-1"></i>Kontrakt & betingelser
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#rental-upcoming" type="button" role="tab">
                <i class="bi bi-calendar3 me-1"></i>Kommende utleie
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#rental-results" type="button" role="tab">
                <i class="bi bi-graph-up-arrow me-1"></i>Deres utleie resultat
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <div class="tab-pane fade show active" id="rental-objects" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">Utleieobjekter</h5>
                        <button class="btn btn-primary btn-sm">
                            <i class="bi bi-plus-circle me-1"></i>Nytt objekt
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Objekt navn</th>
                                    <th>Serienummer</th>
                                    <th>Tilstand</th>
                                    <th>Status</th>
                                    <th>Pris/dag</th>
                                    <th>Handling</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Ingen utleieobjekter funnet
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="rental-contracts" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Kontrakt & betingelser</h5>
                    <p class="text-muted">Administrer leieavtaler og betingelser</p>
                    <div class="mb-3">
                        <label class="form-label">Last opp kontrakt mal</label>
                        <input type="file" class="form-control" accept=".pdf,.doc,.docx">
                        <small class="text-muted">Støttede formater: PDF, DOC, DOCX</small>
                    </div>
                    <div class="mt-4">
                        <h6>Eksisterende dokumenter</h6>
                        <div class="list-group">
                            <div class="list-group-item text-center text-muted py-4">
                                Ingen dokumenter lastet opp
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="rental-upcoming" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Kommende utleie</h5>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Objekt</th>
                                    <th>Kunde</th>
                                    <th>Fra dato</th>
                                    <th>Til dato</th>
                                    <th>Varighet</th>
                                    <th>Beløp</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="bi bi-calendar-x fs-1 d-block mb-2"></i>
                                        Ingen kommende utleie
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="rental-results" role="tabpanel">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Utleie resultat</h5>
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
                                    <small class="text-muted">Antall utleier</small>
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
                                    <small class="text-muted">Gj.snitt per utleie</small>
                                    <h4 class="mb-0">0 NOK</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle me-2"></i>
                        Statistikk vil vises når du har registrert utleier
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
