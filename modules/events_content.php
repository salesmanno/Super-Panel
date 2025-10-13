<div class="module-content">
    <div id="events-my" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Dine arrangement</h5>
                <p class="text-muted">Oversikt over alle dine arrangementer</p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Arrangement</th>
                                <th>Dato</th>
                                <th>Påmeldte</th>
                                <th>Status</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Ingen arrangementer funnet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="events-create" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Opprett nytt arrangement</h5>
                <form>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Arrangement navn</label>
                            <input type="text" class="form-control" placeholder="F.eks. Sommerfest 2025">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Dato</label>
                            <input type="date" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" rows="3" placeholder="Beskriv arrangementet..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Pris (NOK)</label>
                            <input type="number" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Medlemspris (NOK)</label>
                            <input type="number" class="form-control" placeholder="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Maks deltakere</label>
                            <input type="number" class="form-control" placeholder="Ubegrenset">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-1"></i>Opprett arrangement
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div id="events-expenses" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Arrangement utgifter</h5>
                <p class="text-muted">Oversikt over alle utgifter knyttet til arrangementer</p>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Arrangement</th>
                                <th>Kategori</th>
                                <th>Beskrivelse</th>
                                <th>Beløp</th>
                                <th>Dato</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Ingen utgifter registrert
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

