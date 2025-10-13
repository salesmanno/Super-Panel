<div class="module-content">
    <div id="reservations-incoming" class="subtab-content">
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

    <div id="reservations-confirmed" class="subtab-content" style="display: none;">
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

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('#subtabs-reservations .nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            
            document.querySelectorAll('#content-reservations .subtab-content').forEach(content => {
                content.style.display = 'none';
            });
            
            document.getElementById(targetId).style.display = 'block';
            
            document.querySelectorAll('#subtabs-reservations .nav-link').forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });
});
</script>
