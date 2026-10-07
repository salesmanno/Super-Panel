<script src="/assets/active-payment-methods.js"></script>
<style>
.editable-member-cell, .editable-plan-cell {
    cursor: pointer;
    position: relative;
    display: block;
    padding: 2px 4px;
    border-radius: 3px;
    transition: background-color 0.2s;
}
.editable-member-cell:hover, .editable-plan-cell:hover {
    background-color: rgba(13, 110, 253, 0.1);
}
.editable-member-cell:hover::after, .editable-plan-cell:hover::after {
    content: '\F4C4';
    font-family: 'bootstrap-icons';
    position: absolute;
    right: 4px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.65rem;
    color: #0d6efd;
    opacity: 0.7;
}
.editable-member-cell input {
    width: 100%;
    border: 2px solid #0d6efd;
    border-radius: 4px;
    padding: 2px 6px;
}
.editable-plan-cell select {
    width: 100%;
    border: 2px solid #0d6efd;
    border-radius: 4px;
    padding: 2px 6px;
    font-size: 0.875rem;
}
.member-tx-row td {
    background: #f8f9fa;
    padding: 8px 12px !important;
}
.member-tx-row .tx-table {
    font-size: 0.8rem;
    margin-bottom: 0;
}
.member-tx-row .tx-table th {
    font-weight: 600;
    color: #6b7280;
    border-bottom: 1px solid #dee2e6;
    padding: 4px 8px;
}
.member-tx-row .tx-table td {
    padding: 4px 8px;
    background: transparent !important;
}
</style>
<div class="module-content">
    <div id="membership-plans" class="subtab-content">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">Medlemskapsplaner</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createPlanModal">
                        <i class="bi bi-plus-circle me-1"></i>Ny plan
                    </button>
                </div>
                <div class="row" id="membership-plans-list">
                    <div class="col-12 text-center text-muted py-4">
                        <i class="bi bi-card-list fs-1 d-block mb-3"></i>
                        Ingen medlemskapsplaner opprettet ennå
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
                    <button class="btn btn-primary btn-sm" id="add-member-btn" data-bs-toggle="modal" data-bs-target="#createMemberModal">
                        <i class="bi bi-plus-circle me-1"></i>Legg til medlem
                    </button>
                </div>
                <div id="member-stats-row" class="row g-2 mb-3">
                    <div class="col-4">
                        <div class="border rounded p-2 text-center">
                            <div class="fs-5 fw-semibold" id="stat-total">—</div>
                            <div class="text-muted" style="font-size:0.75rem;">Aktive betalende</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2 text-center">
                            <div class="fs-5 fw-semibold" id="stat-expired">—</div>
                            <div class="text-muted" style="font-size:0.75rem;">Utløpte</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded p-2 text-center">
                            <div class="fs-5 fw-semibold" id="stat-renewed">—</div>
                            <div class="text-muted" style="font-size:0.75rem;">Fornyede i år</div>
                        </div>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <input type="text" class="form-control" id="member-search-input" placeholder="Søk etter fornavn, etternavn, e-post eller telefon...">
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="member-plan-filter">
                            <option value="">Alle medlemstyper</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="member-status-filter">
                            <option value="">Alle statuser</option>
                            <option value="active">Aktiv</option>
                            <option value="expired">Utløpt</option>
                            <option value="pending">Venter</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <button class="btn btn-outline-success btn-sm" type="button" id="excel-export-toggle" onclick="toggleExcelExportPanel()">
                        <i class="bi bi-file-earmark-excel me-1"></i>Last ned Excel
                    </button>
                    <div id="excel-export-panel" style="display:none;" class="mt-2 border rounded p-3 bg-light">
                        <p class="mb-2 small fw-semibold">Velg felt som skal inkluderes i eksporten:</p>
                        <div class="row g-2 mb-3">
                            <div class="col-sm-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input excel-field-check" type="checkbox" value="first_name" id="ef-first_name" checked>
                                    <label class="form-check-label small" for="ef-first_name">Fornavn</label>
                                </div>
                            </div>
                            <div class="col-sm-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input excel-field-check" type="checkbox" value="last_name" id="ef-last_name" checked>
                                    <label class="form-check-label small" for="ef-last_name">Etternavn</label>
                                </div>
                            </div>
                            <div class="col-sm-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input excel-field-check" type="checkbox" value="email" id="ef-email" checked>
                                    <label class="form-check-label small" for="ef-email">E-post</label>
                                </div>
                            </div>
                            <div class="col-sm-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input excel-field-check" type="checkbox" value="phone" id="ef-phone" checked>
                                    <label class="form-check-label small" for="ef-phone">Telefon</label>
                                </div>
                            </div>
                            <div class="col-sm-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input excel-field-check" type="checkbox" value="address" id="ef-address" checked>
                                    <label class="form-check-label small" for="ef-address">Adresse</label>
                                </div>
                            </div>
                            <div class="col-sm-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input excel-field-check" type="checkbox" value="member_code" id="ef-member_code" checked>
                                    <label class="form-check-label small" for="ef-member_code">Medlemsnummer</label>
                                </div>
                            </div>
                            <div class="col-sm-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input excel-field-check" type="checkbox" value="membership_type" id="ef-membership_type" checked>
                                    <label class="form-check-label small" for="ef-membership_type">Medlemskapsplan</label>
                                </div>
                            </div>
                            <div class="col-sm-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input excel-field-check" type="checkbox" value="join_date" id="ef-join_date" checked>
                                    <label class="form-check-label small" for="ef-join_date">Innmeldingsdato</label>
                                </div>
                            </div>
                            <div class="col-sm-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input excel-field-check" type="checkbox" value="expiry_date" id="ef-expiry_date" checked>
                                    <label class="form-check-label small" for="ef-expiry_date">Utløpsdato</label>
                                </div>
                            </div>
                            <div class="col-sm-4 col-6">
                                <div class="form-check">
                                    <input class="form-check-input excel-field-check" type="checkbox" value="payment_status" id="ef-payment_status" checked>
                                    <label class="form-check-label small" for="ef-payment_status">Betalingsstatus</label>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-success btn-sm" onclick="downloadMembersExcel()">
                            <i class="bi bi-download me-1"></i>Last ned
                        </button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Medlemsnr</th>
                                <th>Navn</th>
                                <th>Kontaktinformasjon</th>
                                <th>Periode</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody id="members-table-body">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Ingen medlemmer funnet
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <small class="text-muted">Viser 0 av 0 medlemmer</small>
                </div>
            </div>
        </div>
    </div>

    <div id="membership-cancelled" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3"><i class="bi bi-x-circle me-2 text-danger"></i>Kansellerte medlemskap</h5>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Navn</th>
                                <th>E-post</th>
                                <th>Medlemstype</th>
                                <th>Begrunnelse</th>
                                <th>Handling</th>
                            </tr>
                        </thead>
                        <tbody id="cancelled-members-tbody">
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">Ingen kansellerte medlemmer</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="membership-income" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-secondary" onclick="changeIncomeYear(-1)" title="Forrige år"><i class="bi bi-chevron-left"></i></button>
                            <span class="btn btn-outline-secondary disabled fw-semibold px-3" id="income-year-display"><?php echo date('Y'); ?></span>
                            <button class="btn btn-outline-secondary" onclick="changeIncomeYear(1)" title="Neste år"><i class="bi bi-chevron-right"></i></button>
                        </div>
                        <span class="text-muted small">Innmeldte og fornyede medlemmer</span>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <small id="income-backfill-result" class="text-muted" style="display:none;"></small>
                        <button class="btn btn-outline-primary btn-sm" id="income-backfill-btn" onclick="runLedgerBackfill()" title="Synkroniser fornyelser uten ledger-oppføring">
                            <i class="bi bi-arrow-repeat me-1"></i>Synkroniser fornyelser
                        </button>
                    </div>
                </div>

                <div class="row g-2" id="income-calendar-grid">
                    <div class="col-12 text-center text-muted py-4">
                        <div class="spinner-border spinner-border-sm me-2"></div>Laster...
                    </div>
                </div>

                <div id="income-month-detail" style="display:none;" class="mt-3 border rounded p-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong id="income-month-title"></strong>
                        <button class="btn btn-sm btn-outline-secondary" onclick="closeIncomeMonthDetail()"><i class="bi bi-x"></i></button>
                    </div>
                    <div id="income-month-body"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fornyelse subtab -->
    <div id="membership-renewals" class="subtab-content" style="display: none;">
        <div class="card">
            <div class="card-body">

                <!-- Oppslagspanel + statistikk -->
                <div class="mb-4 d-flex gap-4 align-items-start">

                    <!-- Venstre: oppslagspanel -->
                    <div class="flex-grow-1">
                    <h6 class="fw-semibold mb-2"><i class="bi bi-search me-2 text-primary"></i>Slå opp fornyelse eller medlem</h6>
                    <div class="input-group input-group-sm" style="max-width:420px;">
                        <input type="text" class="form-control" id="mb-lookup-input" placeholder="Order-ID eller medlemskode…" onkeydown="if(event.key==='Enter'){event.preventDefault();mbLookupRenewal();}">
                        <button class="btn btn-outline-secondary" id="mb-lookup-button" onclick="mbLookupRenewal()" aria-label="Søk"><i class="bi bi-search"></i></button>
                    </div>
                    <div id="mb-lookup-feedback" class="small mt-1" role="status" aria-live="polite"></div>
                    <div id="mb-lookup-result" class="mt-2 p-3 border rounded bg-light" style="display:none; max-width:560px;">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <span class="text-muted small">Navn</span>
                                <div class="fw-semibold" id="mb-rl-name">–</div>
                                <div class="small text-muted" id="mb-rl-plan">–</div>
                            </div>
                            <div class="text-end">
                                <span class="badge text-bg-light border" id="mb-rl-match-type">Fornyelse</span>
                                <div class="small text-muted mt-1" id="mb-rl-member-code">–</div>
                            </div>
                        </div>
                        <div class="row g-2 mb-2">
                            <div class="col-6" id="mb-rl-renewal-field">
                                <label class="form-label small mb-1">Fornyelse status</label>
                                <select class="form-select form-select-sm" id="mb-rl-renewal-status">
                                    <option value="pending">pending</option>
                                    <option value="payment_pending">payment_pending</option>
                                    <option value="completed">completed</option>
                                    <option value="cancelled">cancelled</option>
                                    <option value="expired">expired</option>
                                </select>
                                <div class="form-text" id="mb-rl-renewal-help"></div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small mb-1">Betalingsstatus (membership)</label>
                                <select class="form-select form-select-sm" id="mb-rl-payment-status">
                                    <option value="paid">paid</option>
                                    <option value="unpaid">unpaid</option>
                                    <option value="pending">pending</option>
                                    <option value="refunded">refunded</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small mb-1">Utløpsdato (membership)</label>
                            <input type="date" class="form-control form-control-sm" id="mb-rl-expiration-date" style="max-width:200px;">
                        </div>
                        <input type="hidden" id="mb-rl-renewal-id">
                        <input type="hidden" id="mb-rl-member-id">
                        <div class="d-flex align-items-center gap-2">
                            <button class="btn btn-sm btn-primary" id="mb-rl-save-button" onclick="mbSaveRenewalLookup()">
                                <i class="bi bi-floppy me-1"></i>Lagre
                            </button>
                            <span id="mb-rl-save-msg" class="small"></span>
                        </div>
                    </div>
                    </div>
                    <!-- /Venstre -->

                    <!-- Høyre: statistikk -->
                    <div class="text-end" style="min-width:220px; flex-shrink:0;">
                        <div class="mb-3">
                            <div class="text-muted small">Sum completed &amp; paid</div>
                            <div class="fs-5 fw-semibold text-dark" id="mb-stat-sum">–</div>
                        </div>
                        <div class="mb-3">
                            <div class="text-muted small">Fornyede medlemmer</div>
                            <div class="fs-5 fw-semibold text-dark" id="mb-stat-renewed">–</div>
                        </div>
                        <div>
                            <div class="text-muted small">Gyldige transaksjoner</div>
                            <div class="fs-5 fw-semibold text-success" id="mb-stat-valid">–</div>
                        </div>
                    </div>
                    <!-- /Høyre -->

                </div>
                <!-- /Oppslagspanel + statistikk -->

                <!-- Paginert liste -->
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-semibold mb-0">Alle fornyelser</h6>
                    <div class="input-group input-group-sm" style="max-width:300px;">
                        <input type="text" class="form-control" id="mb-renewal-search" placeholder="Søk order_id…">
                        <button class="btn btn-outline-secondary" onclick="mbLoadRenewalList(1)"><i class="bi bi-search"></i></button>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="min-width:160px;">Navn</th>
                                <th>Plan</th>
                                <th class="text-end">Beløp</th>
                                <th style="min-width:145px;">Fornyelse</th>
                                <th style="min-width:130px;">Betaling</th>
                                <th style="min-width:130px;">Utløper</th>
                                <th class="text-center" style="min-width:90px;">Kontrollert</th>
                                <th>Dato</th>
                            </tr>
                        </thead>
                        <tbody id="mb-renewal-list-body">
                            <tr><td colspan="8" class="text-center text-muted py-3">Laster…</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-2" id="mb-renewal-pagination" style="display:none;">
                    <button class="btn btn-sm btn-outline-secondary" id="mb-renewal-prev" onclick="mbLoadRenewalList(mbRenewalPage - 1)" disabled>&#8592;</button>
                    <span class="small text-muted" id="mb-renewal-page-info">Side 1 av 1</span>
                    <button class="btn btn-sm btn-outline-secondary" id="mb-renewal-next" onclick="mbLoadRenewalList(mbRenewalPage + 1)" disabled>&#8594;</button>
                </div>

            </div>
        </div>
    </div>
    <!-- /Fornyelse subtab -->

</div>

<!-- Modal for legg til medlem -->
<div class="modal fade" id="createMemberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bi bi-person-plus me-2"></i>Legg til nytt medlem</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="create-member-form">
                    <!-- Kompakt plan-velger øverst -->
                    <div class="row mb-3 align-items-end">
                        <div class="col-md-7">
                            <label class="form-label fw-semibold mb-1"><i class="bi bi-card-checklist me-1"></i>Medlemskapsplan</label>
                            <select class="form-select" id="plan-select-dropdown" onchange="onPlanDropdownChange(this)">
                                <option value="">Laster planer...</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <small class="text-muted" id="plan-select-hint"></small>
                        </div>
                    </div>
                    <input type="hidden" name="membership_plan_id" id="selected-plan-id">

                    <!-- Personinformasjon -->
                    <div class="card mb-3">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="bi bi-person me-2"></i>Personinformasjon</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Fornavn *</label>
                                    <input type="text" class="form-control" name="first_name" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Etternavn *</label>
                                    <input type="text" class="form-control" name="last_name" required>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">E-post</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Telefon</label>
                                    <input type="tel" class="form-control" name="phone">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Gateadresse</label>
                                    <input type="text" class="form-control" name="address_street">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Poststed</label>
                                    <input type="text" class="form-control" name="address_city">
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label">Postkode</label>
                                    <input type="text" class="form-control" name="address_zip" maxlength="10">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Fødselsdato</label>
                                    <input type="date" class="form-control" name="dateofbirth">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Medlemskapsdetaljer -->
                    <div class="card mb-3">
                        <div class="card-header bg-light py-2">
                            <h6 class="mb-0"><i class="bi bi-calendar-check me-2"></i>Medlemskapsdetaljer</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="member-payment-status">Betalingsstatus</label>
                                <select class="form-select" name="payment_status" id="member-payment-status">
                                    <option value="unpaid">Ikke betalt</option>
                                    <option value="paid">Betalt</option>
                                </select>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Startdato</label>
                                    <input type="date" class="form-control" name="join_date" id="member-join-date" oninput="updateExpirationPreview()">
                                    <small class="text-muted">Standard: i dag</small>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Betalingsmetode</label>
                                    <select class="form-select" name="paymentMethod" id="member-payment-method" onchange="onPaymentMethodChange(this)">
                                        <option value="">— Velg —</option>
                                    </select>
                                    <small class="text-muted" id="payment-status-hint" role="status"></small>
                                    <button type="button" class="btn btn-sm btn-link" onclick="loadMemberPaymentMethods()">Last betalingsmetoder på nytt</button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Utløpsdato <small class="text-muted">(beregnes automatisk)</small></label>
                                    <input type="date" class="form-control bg-light" id="member-expiration-preview" disabled>
                                    <small class="text-muted" id="member-expiration-hint">Velg plan og startdato</small>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Notater</label>
                                <textarea class="form-control" name="notes" rows="2" placeholder="Interne notater om medlemmet..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="submitMember()" id="submit-member-btn">
                    <i class="bi bi-check-circle me-1"></i>Legg til medlem
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for opprett medlemskapsplan -->
<div class="modal fade" id="createPlanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Opprett medlemskapsplan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="create-plan-form" autocomplete="off">
                    <div class="mb-3">
                        <label class="form-label">Plan navn *</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pris (NOK) *</label>
                            <input type="number" step="0.01" class="form-control" name="price" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Faktureringsperiode *</label>
                            <select class="form-select" name="billing_cycle" required>
                                <option value="monthly">Månedlig</option>
                                <option value="quarterly">Kvartalsvis</option>
                                <option value="yearly">Årlig</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fordeler</label>
                        <textarea class="form-control" name="benefits" rows="4" placeholder="- Tilgang til arrangement&#10;- Medlemsrabatt&#10;- Nyhetsbrev"></textarea>
                        <small class="text-muted">Skriv en fordel per linje</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bilde-URL</label>
                        <input type="url" class="form-control" name="plan_image_url" placeholder="https://example.com/plan-bilde.jpg">
                        <small class="text-muted">URL til bilde for denne planen</small>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="for_sale" id="for_sale" checked>
                        <label class="form-check-label" for="for_sale">
                            Til salgs / tilbys
                        </label>
                        <small class="text-muted d-block">Hvis avmerket, kan denne planen selges og tilbys til kunder</small>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_internal" id="is_internal">
                        <label class="form-check-label" for="is_internal">
                            Intern medlemskapsplan
                        </label>
                        <small class="text-muted d-block">Hvis avmerket, vises ikke denne planen i tjenester som kan kjøpes</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="submitMembershipPlan()">Opprett plan</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for rediger medlemskapsplan -->
<div class="modal fade" id="editPlanModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rediger medlemskapsplan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="edit-plan-form" autocomplete="off">
                    <input type="hidden" name="plan_id" id="edit-plan-id">
                    <div class="mb-3">
                        <label class="form-label">Plan navn *</label>
                        <input type="text" class="form-control" name="name" id="edit-plan-name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Beskrivelse</label>
                        <textarea class="form-control" name="description" id="edit-plan-description" rows="3"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Pris (NOK) *</label>
                            <input type="number" step="0.01" class="form-control" name="price" id="edit-plan-price" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Faktureringsperiode *</label>
                            <select class="form-select" name="billing_cycle" id="edit-plan-billing" required>
                                <option value="monthly">Månedlig</option>
                                <option value="quarterly">Kvartalsvis</option>
                                <option value="yearly">Årlig</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fordeler</label>
                        <textarea class="form-control" name="benefits" id="edit-plan-benefits" rows="4"></textarea>
                        <small class="text-muted">Skriv en fordel per linje</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bilde-URL</label>
                        <input type="url" class="form-control" name="plan_image_url" id="edit-plan-image" placeholder="https://example.com/plan-bilde.jpg">
                        <small class="text-muted">URL til bilde for denne planen</small>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="for_sale" id="edit-for-sale">
                        <label class="form-check-label" for="edit-for-sale">
                            Til salgs / tilbys
                        </label>
                        <small class="text-muted d-block">Hvis avmerket, kan denne planen selges og tilbys til kunder</small>
                    </div>
                    <hr>
                    <h6 class="text-muted mb-3"><i class="bi bi-calendar-event me-1"></i>Planlagt prisendring</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ny pris fra dato</label>
                            <input type="date" class="form-control" name="future_price_change_date" id="edit-future-price-date">
                            <small class="text-muted">Dato for når ny pris trer i kraft</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ny pris (NOK)</label>
                            <input type="number" step="0.01" class="form-control" name="future_price_new" id="edit-future-price-new" placeholder="0.00">
                            <small class="text-muted">Prisen som gjelder fra denne datoen</small>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Avbryt</button>
                <button type="button" class="btn btn-primary" onclick="submitEditPlan()">
                    <i class="bi bi-check-circle me-1"></i>Lagre endringer
                </button>
            </div>
        </div>
    </div>
</div>

<script>
async function editMembershipPlan(planId) {
    try {
        const response = await fetch(`/api/get_membership_plans.php?plan_id=${planId}`);
        const result = await response.json();
        
        if (result.success && result.plans && result.plans.length > 0) {
            const plan = result.plans[0];
            document.getElementById('edit-plan-id').value = plan.id || plan.plan_id;
            document.getElementById('edit-plan-name').value = plan.name || '';
            document.getElementById('edit-plan-description').value = plan.description || '';
            document.getElementById('edit-plan-price').value = plan.price || '';
            document.getElementById('edit-plan-billing').value = plan.billing_cycle || 'yearly';
            document.getElementById('edit-plan-benefits').value = plan.benefits || '';
            document.getElementById('edit-plan-image').value = plan.image_url || '';
            document.getElementById('edit-for-sale').checked = plan.for_sale ? true : false;
            document.getElementById('edit-future-price-date').value = plan.future_price_change_date || '';
            document.getElementById('edit-future-price-new').value = plan.future_price_new || '';
            
            const modal = new bootstrap.Modal(document.getElementById('editPlanModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Error loading plan:', error);
        toast.error('Feil', 'Kunne ikke laste medlemskapsplan');
    }
}

async function submitEditPlan() {
    const form = document.getElementById('edit-plan-form');
    const planId = document.getElementById('edit-plan-id').value;
    
    const futurePriceDate = document.getElementById('edit-future-price-date').value;
    const futurePriceNew = document.getElementById('edit-future-price-new').value;
    
    const planData = {
        plan_id: planId,
        name: document.getElementById('edit-plan-name').value,
        description: document.getElementById('edit-plan-description').value,
        price: parseFloat(document.getElementById('edit-plan-price').value),
        billing_cycle: document.getElementById('edit-plan-billing').value,
        benefits: document.getElementById('edit-plan-benefits').value,
        image_url: document.getElementById('edit-plan-image').value || null,
        for_sale: document.getElementById('edit-for-sale').checked ? 1 : 0,
        future_price_change_date: futurePriceDate || null,
        future_price_new: futurePriceNew ? parseFloat(futurePriceNew) : null
    };
    
    const toastId = toast.loading('Lagrer endringer...');
    
    try {
        const response = await fetch('/api/update_membership_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(planData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success(toastId, 'Medlemskapsplan', 'Planen er oppdatert');
            bootstrap.Modal.getInstance(document.getElementById('editPlanModal')).hide();
            loadMembershipPlans();
        } else {
            toast.error(toastId, 'Oppdatering av medlemskapsplan', result.message || 'Kunne ikke lagre endringer');
        }
    } catch (error) {
        console.error('Error updating plan:', error);
        toast.error(toastId, 'Oppdatering av medlemskapsplan', 'Kunne ikke lagre endringer');
    }
}

async function submitMembershipPlan() {
    const form = document.getElementById('create-plan-form');
    const formData = new FormData(form);
    
    const planData = {
        name: formData.get('name'),
        description: formData.get('description'),
        price: parseFloat(formData.get('price')),
        billing_cycle: formData.get('billing_cycle'),
        benefits: formData.get('benefits'),
        image_url: formData.get('plan_image_url') || null,
        for_sale: document.getElementById('for_sale')?.checked ? 1 : 0,
        is_internal: document.getElementById('is_internal')?.checked ? 1 : 0
    };
    
    const toastId = toast.loading('Oppretter medlemskapsplan');
    
    try {
        const response = await fetch('/api/create_membership_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(planData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success(toastId, 'Medlemskapsplan opprettet', 'Planen er nå tilgjengelig for medlemmer');
            bootstrap.Modal.getInstance(document.getElementById('createPlanModal')).hide();
            form.reset();
            loadMembershipPlans();
        } else {
            toast.error(toastId, 'Opprettelse av medlemskapsplan', result.message || 'Kunne ikke opprette plan');
        }
    } catch (error) {
        console.error('Error creating plan:', error);
        toast.error(toastId, 'Opprettelse av medlemskapsplan', 'Kunne ikke opprette plan');
    }
}

async function loadMembershipPlans() {
    try {
        const response = await fetch('../api/get_membership_plans.php');
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        const result = await response.json();
        
        const plansList = document.getElementById('membership-plans-list');
        
        if (result.success && result.plans && result.plans.length > 0) {
            plansList.innerHTML = result.plans.map(plan => {
                // Support both billing_cycle (MySQL) and duration_months (PostgreSQL)
                let billingLabel = '';
                if (plan.billing_cycle) {
                    billingLabel = plan.billing_cycle === 'monthly' ? 'Månedlig' : 
                                  plan.billing_cycle === 'quarterly' ? 'Kvartalsvis' : 
                                  plan.billing_cycle === 'yearly' ? 'Årlig' : plan.billing_cycle;
                } else if (plan.duration_months) {
                    const durationMonths = plan.duration_months;
                    billingLabel = durationMonths === 1 ? 'Månedlig' : 
                                  durationMonths === 3 ? 'Kvartalsvis' : 
                                  durationMonths === 12 ? 'Årlig' : 
                                  `${durationMonths} måneder`;
                } else {
                    billingLabel = 'Årlig';
                }
                
                const planId = plan.id || plan.plan_id;
                const forSaleChecked = plan.for_sale ? 'checked' : '';
                return `
                    <div class="col-md-4 mb-3">
                        <div class="card border h-100 d-flex flex-column">
                            <div class="card-body d-flex flex-column">
                                <h6 class="mb-1">${plan.name}</h6>
                                <p class="text-muted small mb-2">${billingLabel}</p>
                                <h4 class="text-primary mb-2">${plan.price} NOK</h4>
                                <div class="flex-grow-1">
                                    ${plan.benefits ? '<div class="small text-muted">' + plan.benefits.split('\n').slice(0, 2).join('<br>') + '</div>' : '<div class="small text-muted" style="min-height: 2.5em;"></div>'}
                                </div>
                                <div class="mt-auto pt-2">
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" id="for_sale_${planId}" ${forSaleChecked} onchange="togglePlanForSale('${planId}', this.checked)">
                                        <label class="form-check-label small" for="for_sale_${planId}">
                                            Til salgs / tilbys
                                        </label>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-outline-secondary btn-sm" onclick="goToMembershipShareLink('${planId}')" title="Gå til delingslenke">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </button>
                                        <button class="btn btn-outline-primary btn-sm flex-fill" onclick="editMembershipPlan('${planId}')">Rediger</button>
                                        <button class="btn btn-outline-danger btn-sm" onclick="deleteMembershipPlan('${planId}')">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        } else {
            plansList.innerHTML = `
                <div class="col-12 text-center text-muted py-4">
                    <i class="bi bi-card-list fs-1 d-block mb-3"></i>
                    Ingen medlemskapsplaner opprettet ennå
                </div>
            `;
        }
    } catch (error) {
        console.error('Error loading plans:', error);
        const plansList = document.getElementById('membership-plans-list');
        plansList.innerHTML = `
            <div class="col-12 text-center text-danger py-4">
                <i class="bi bi-exclamation-triangle fs-1 d-block mb-3"></i>
                <p class="mb-0">Feil ved lasting av medlemskapsplaner</p>
                <small class="text-muted">${error.message}</small>
            </div>
        `;
    }
}

// Last inn planer når medlemskap-tab vises
document.getElementById('tab-membership')?.addEventListener('shown.bs.tab', loadMembershipPlans);

async function togglePlanForSale(planId, forSale) {
    const toastId = toast.loading(forSale ? 'Aktiverer salg...' : 'Deaktiverer salg...');
    
    try {
        const response = await fetch('/api/update_membership_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ plan_id: planId, for_sale: forSale ? 1 : 0 })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success(toastId, '', forSale ? 'Planen er nå til salgs' : 'Planen er nå bare intern');
        } else {
            toast.error(toastId, 'Oppdatering av plan', result.message || 'Kunne ikke oppdatere');
            loadMembershipPlans(); // Reload to reflect actual state
        }
    } catch (error) {
        console.error('Error toggling plan for_sale:', error);
        toast.error(toastId, 'Oppdatering av plan', 'Kunne ikke oppdatere');
        loadMembershipPlans(); // Reload to reflect actual state
    }
}

async function goToMembershipShareLink(planId) {
    try {
        const response = await fetch(`/api/get_share_link.php?type=membership&item_id=${planId}`);
        const result = await response.json();
        
        if (result.success && result.share_link) {
            window.open(result.share_link, '_blank');
        } else {
            toast.quickError('Feil', result.message || 'Kunne ikke hente delingslenke');
        }
    } catch (error) {
        console.error('Error opening share link:', error);
        toast.quickError('Feil', 'Kunne ikke åpne delingslenke');
    }
}

async function deleteMembershipPlan(planId) {
    if (!confirm('Er du sikker på at du vil slette denne medlemskapsplanen?')) {
        return;
    }
    
    const toastId = toast.loading('Sletter medlemskapsplan');
    
    try {
        const response = await fetch('/api/delete_membership_plan.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ plan_id: planId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success(toastId, 'Medlemskapsplan slettet', 'Planen er nå fjernet fra systemet');
            loadMembershipPlans();
        } else {
            toast.error(toastId, 'Sletting av medlemskapsplan', result.message || 'Kunne ikke slette plan');
        }
    } catch (error) {
        console.error('Error deleting plan:', error);
        toast.error(toastId, 'Sletting av medlemskapsplan', 'Kunne ikke slette plan');
    }
}

async function submitMember() {
    const form = document.getElementById('create-member-form');
    const formData = new FormData(form);
    const methodSelect = document.getElementById('member-payment-method');
    const selectedPlan = window.membershipPlans?.find(plan=>plan.id===formData.get('membership_plan_id'));
    const price = Number(selectedPlan?.price);
    if ((formData.get('payment_status') === 'paid' && (price > 0 || !Number.isFinite(price))) || formData.get('paymentMethod')) {
        if (methodSelect.dataset.paymentState !== 'ready' || !methodSelect.value || methodSelect.selectedOptions[0]?.disabled) {
            toast.quickWarning('Betaling', 'Velg en aktivert betalingsmetode. Last listen på nytt dersom den mangler.');
            return;
        }
    }
    
    const memberData = {
        first_name: formData.get('first_name'),
        last_name: formData.get('last_name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        address_street: formData.get('address_street'),
        address_city: formData.get('address_city'),
        address_zip: formData.get('address_zip'),
        dateofbirth: formData.get('dateofbirth'),
        membership_plan_id: formData.get('membership_plan_id') || null,
        notes: formData.get('notes'),
        join_date: formData.get('join_date') || null,
        payment_status: formData.get('payment_status') || null,
        paymentMethod: formData.get('paymentMethod') || null
    };
    
    if (!memberData.first_name || !memberData.last_name) {
        toast.quickWarning('Manglende informasjon', 'Vennligst fyll ut fornavn og etternavn');
        return;
    }
    
    if (!memberData.membership_plan_id) {
        toast.quickWarning('Manglende plan', 'Vennligst velg en medlemskapsplan');
        return;
    }
    
    const toastId = toast.loading('Legger til nytt medlem');
    
    try {
        const response = await fetch('../api/create_member.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(memberData)
        });
        
        const result = await response.json();
        
        if (result.success) {
            toast.success(toastId, 'Medlem lagt til', `${memberData.first_name} ${memberData.last_name} er nå registrert`);
            bootstrap.Modal.getInstance(document.getElementById('createMemberModal')).hide();
            form.reset();
            document.getElementById('selected-plan-id').value = '';
            const planHint = document.getElementById('plan-select-hint');
            if (planHint) planHint.textContent = '';
            const planDrop = document.getElementById('plan-select-dropdown');
            if (planDrop) planDrop.value = '';
            const payStatus = document.getElementById('member-payment-status');
            if (payStatus) payStatus.value = 'paid';
            loadMembers();
        } else {
            toast.error(toastId, 'Registrering av medlem', result.message || 'Kunne ikke legge til medlem');
        }
    } catch (error) {
        console.error('Error creating member:', error);
        toast.error(toastId, 'Registrering av medlem', 'Kunne ikke legge til medlem');
    }
}

async function loadMembers() {
    loadMemberStats();
    try {
        const response = await fetch('../api/get_members.php');
        const result = await response.json();
        
        if (result.success && result.members) {
            allMembers = result.members;
            memberSubdomain = result.subdomain || '';
            renderMembersTable(allMembers);
        } else {
            allMembers = [];
            renderMembersTable([]);
        }
    } catch (error) {
        console.error('Error loading members:', error);
        const tableBody = document.getElementById('members-table-body');
        tableBody.innerHTML = `
            <tr>
                <td colspan="11" class="text-center text-danger py-4">
                    Feil ved lasting av medlemmer
                </td>
            </tr>
        `;
    }
}

async function loadPlansForSelect() {
    const dropdown = document.getElementById('plan-select-dropdown');
    const hintEl = document.getElementById('plan-select-hint');
    const addMemberBtn = document.getElementById('add-member-btn');

    try {
        const response = await fetch('../api/get_membership_plans.php');
        const result = await response.json();

        // Set default join date to today
        const joinDateInput = document.getElementById('member-join-date');
        if (joinDateInput && !joinDateInput.value) {
            joinDateInput.value = new Date().toISOString().split('T')[0];
        }

        if (result.success && result.plans && result.plans.length > 0) {
            if (addMemberBtn) addMemberBtn.style.display = '';
            window.membershipPlans = result.plans;

            const billingMap = { monthly: 'mnd', quarterly: 'kvartal', yearly: 'år' };
            let opts = '<option value="">— Ingen plan —</option>';
            result.plans.forEach((plan, index) => {
                const label = billingMap[plan.billing_cycle] || 'periode';
                opts += `<option value="${plan.plan_id}" data-index="${index}">${plan.name || plan.plan_name} — ${plan.price} kr/${label}</option>`;
            });
            if (dropdown) dropdown.innerHTML = opts;

            // Auto-select first plan always
            if (dropdown) dropdown.value = result.plans[0].plan_id;
            selectMembershipPlan(result.plans[0].plan_id, 0);
        } else {
            if (addMemberBtn) addMemberBtn.style.display = 'none';
            if (dropdown) {
                dropdown.innerHTML = '<option value="">Ingen planer registrert</option>';
                dropdown.disabled = true;
            }
            if (hintEl) hintEl.textContent = 'Opprett en medlemskapsplan først.';
        }
    } catch (error) {
        console.error('Error loading plans for select:', error);
        if (dropdown) dropdown.innerHTML = '<option value="">Feil ved lasting</option>';
    }
}

function onPlanDropdownChange(select) {
    const idx = select.selectedIndex;
    const opt = select.options[idx];
    const planId = select.value;
    const planIndex = planId ? parseInt(opt.dataset.index ?? -1) : -1;
    selectMembershipPlan(planId, planIndex);
}

function selectMembershipPlan(planId, planIndex) {
    const hiddenInput = document.getElementById('selected-plan-id');
    const hintEl = document.getElementById('plan-select-hint');

    if (hiddenInput) hiddenInput.value = planId || '';

    if (planId && planIndex >= 0 && window.membershipPlans && window.membershipPlans[planIndex]) {
        const plan = window.membershipPlans[planIndex];
        const billingLabel = { monthly: 'Månedlig', quarterly: 'Kvartalsvis', yearly: 'Årlig' }[plan.billing_cycle] || plan.billing_cycle;
        if (hintEl) hintEl.innerHTML = `<i class="bi bi-check-circle-fill text-success me-1"></i>${plan.price} kr &mdash; ${billingLabel} fakturering`;
    } else {
        if (hintEl) hintEl.textContent = '';
    }

    updateExpirationPreview();
}

function updateExpirationPreview() {
    const joinDateInput = document.getElementById('member-join-date');
    const expirationInput = document.getElementById('member-expiration-preview');
    const hintEl = document.getElementById('member-expiration-hint');
    const planId = document.getElementById('selected-plan-id') ? document.getElementById('selected-plan-id').value : null;
    
    if (!expirationInput) return;
    
    const joinDate = joinDateInput && joinDateInput.value
        ? new Date(joinDateInput.value)
        : new Date();
    
    const daysMap = { monthly: 30, quarterly: 90, yearly: 365 };
    
    if (planId && window.membershipPlans) {
        const plan = window.membershipPlans.find(p => (p.plan_id || p.id) == planId);
        if (plan) {
            const days = daysMap[plan.billing_cycle] || 365;
            const expDate = new Date(joinDate);
            expDate.setDate(expDate.getDate() + days);
            expirationInput.value = expDate.toISOString().split('T')[0];
            if (hintEl) hintEl.textContent = '';
            return;
        }
    }
    
    expirationInput.value = '';
    if (hintEl) hintEl.textContent = 'Velg plan og startdato';
}

function onPaymentMethodChange(select) {
    const statusInput = document.getElementById('member-payment-status');
    const hintEl = document.getElementById('payment-status-hint');
    const val = select.value;
    if (val) {
        statusInput.value = 'paid';
        if (hintEl) hintEl.textContent = 'Medlemskapet settes som aktivt';
        select.classList.remove('text-warning');
    } else {
        if (hintEl) hintEl.textContent = 'Velg betalingsmetode ved registrering av en betaling.';
        select.classList.remove('text-warning');
    }
}

async function loadMemberPaymentMethods() {
    const select=document.getElementById('member-payment-method');
    const hint=document.getElementById('payment-status-hint');
    const revision=String(Number(select.dataset.loadRevision || 0)+1);
    select.dataset.loadRevision=revision;
    const previous=select.value;
    select.dataset.paymentState='loading';select.disabled=true;
    select.replaceChildren(new Option('Laster…',''));hint.textContent='Laster aktiverte betalingsmetoder…';
    try {
        const methods=await ActivePaymentMethods.load();
        if (select.dataset.loadRevision!==revision) return;
        select.replaceChildren(new Option('— Velg —',''));
        for (const method of methods) select.append(new Option(ActivePaymentMethods.label(method),method.method_type));
        if (methods.some(m=>m.method_type===previous)) select.value=previous;
        select.disabled=false;select.dataset.paymentState='ready';
        hint.textContent=methods.length ? 'Bare betalingsmetoder aktivert i Økonomi kan velges.' : 'Ingen betalingsmetoder er aktivert. Medlemskap kan fortsatt registreres som ikke betalt eller gratis.';
    } catch (error) {
        if (select.dataset.loadRevision!==revision) return;
        select.replaceChildren(new Option('Kunne ikke laste betalingsmetoder',''));
        select.dataset.paymentState='error';hint.textContent=error.message;
    }
}
document.getElementById('createMemberModal').addEventListener('show.bs.modal',loadMemberPaymentMethods);

async function deleteMember(memberId, memberName) {
    if (!confirm(`Er du sikker på at du vil slette medlemmet "${memberName}"?\n\nDette kan ikke angres.`)) {
        return;
    }
    
    try {
        const response = await fetch('/api/delete_member.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ member_id: memberId })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('Medlem slettet');
            await loadMembers();
        } else {
            alert('Feil ved sletting: ' + result.message);
        }
    } catch (error) {
        console.error('Delete error:', error);
        alert('Feil ved sletting av medlem');
    }
}

// Load membership plans into filter dropdown
async function loadPlansForFilter() {
    try {
        const response = await fetch('../api/get_membership_plans.php');
        const result = await response.json();
        
        const select = document.getElementById('member-plan-filter');
        
        if (result.success && result.plans && result.plans.length > 0) {
            const options = result.plans.map(plan => 
                `<option value="${plan.plan_id || plan.id}">${plan.name || plan.plan_name}</option>`
            ).join('');
            select.innerHTML = '<option value="">Alle medlemstyper</option>' + options;
        }
    } catch (error) {
        console.error('Error loading plans for filter:', error);
    }
}

// Filter members based on search, plan, and status
let allMembers = [];
let memberSubdomain = '';

function applyMemberFilters() {
    const searchTerm = document.getElementById('member-search-input').value.toLowerCase();
    const planFilter = document.getElementById('member-plan-filter').value;
    const statusFilter = document.getElementById('member-status-filter').value;
    
    let filtered = allMembers.filter(member => {
        // Search filter
        const matchesSearch = !searchTerm || 
            (member.first_name?.toLowerCase().includes(searchTerm)) ||
            (member.last_name?.toLowerCase().includes(searchTerm)) ||
            (member.email?.toLowerCase().includes(searchTerm)) ||
            (member.phone?.includes(searchTerm));
        
        // Plan filter
        const matchesPlan = !planFilter || member.membership_plan_id == planFilter;
        
        // Status filter
        const matchesStatus = !statusFilter || member.status === statusFilter;
        
        return matchesSearch && matchesPlan && matchesStatus;
    });
    
    renderMembersTable(filtered);
}

function renderMembersTable(members) {
    const tableBody = document.getElementById('members-table-body');
    
    if (members && members.length > 0) {
        tableBody.innerHTML = members.map(member => {
            // Check if membership is expired based on expiry_date
            const today = new Date();
            const expiryDate = member.expiry_date ? new Date(member.expiry_date) : null;
            const isExpired = expiryDate && expiryDate < today;
            
            const statusBadge = member.status === 'active' && !isExpired ? 'bg-success' :
                               member.status === 'inactive' || isExpired  ? 'bg-danger'  : 'bg-warning';
            const statusText  = member.status === 'active' && !isExpired ? 'Aktiv' :
                               member.status === 'inactive' || isExpired  ? 'Ikke betalt' : 'Venter';
            
            // Generate shorter member reference (last 6 chars of member_id)
            const shortRef = member.member_id ? String(member.member_id).substring(0, 8).toUpperCase() : '-';
            
            return `
                <tr data-member-id="${member.member_id}" data-plan-id="${member.membership_plan_id || ''}" data-address="${escapeH(member.address || '')}" data-poststed="${escapeH(member.poststed || '')}">
                    <td>
                        <code class="d-block">${member.member_code || shortRef}</code>
                        <span class="badge ${statusBadge} mt-1" style="font-size:0.65rem;">${statusText}</span>
                    </td>
                    <td>
                        <div class="editable-member-cell" data-field="first_name">${escapeH(member.first_name)}</div>
                        <div class="editable-member-cell text-muted" data-field="last_name">${escapeH(member.last_name)}</div>
                    </td>
                    <td>
                        <div class="editable-member-cell small" data-field="phone">
                            <i class="bi bi-telephone me-1 text-muted"></i>${escapeH(member.phone || '-')}
                        </div>
                        <div class="editable-member-cell small" data-field="email">
                            <i class="bi bi-envelope me-1 text-muted"></i>${escapeH(member.email)}
                        </div>
                    </td>
                    <td class="small text-nowrap">
                        <div class="text-muted">Fra: ${fmtDate(member.join_date)}</div>
                        <div class="text-muted">Til: ${fmtDate(member.expiry_date)}</div>
                        ${parseInt(member.Automailer_Sent) === 1 ? `<span class="badge bg-warning text-dark mt-1" style="font-size:0.6rem;" title="Utløpsvarsel sendt"><i class="bi bi-envelope-check me-1"></i>Varsel sendt</span>` : ''}
                        ${parseInt(member.Automailer_Sent) === 2 ? `<span class="badge bg-danger mt-1" style="font-size:0.6rem;" title="Purring sendt"><i class="bi bi-envelope-exclamation me-1"></i>Purring sendt</span>` : ''}
                    </td>
                    <td class="text-nowrap">
                        <button class="btn btn-sm btn-success" onclick="renewMembership('${member.member_id}')" title="Forny medlemskap">
                            <i class="bi bi-arrow-clockwise"></i>
                        </button>
                        ${member.share_code && member.member_code ? `
                        <button class="btn btn-sm btn-outline-secondary ms-1"
                                onclick="copyRenewalLink('${member.share_code}','${member.member_code}')"
                                title="Kopier fornyelseslenke">
                            <i class="bi bi-clipboard"></i>
                        </button>` : ''}
                        <button class="btn btn-sm btn-outline-info ms-1"
                                onclick="toggleMemberTransactions('${member.member_id}', this)"
                                title="Kontigentstatus">
                            <i class="bi bi-cash-coin"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary ms-1"
                                onclick="toggleMemberAddress('${member.member_id}', this)"
                                title="Adresse">
                            <i class="bi bi-geo-alt"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    } else {
        tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center text-muted py-4">
                    Ingen medlemmer funnet
                </td>
            </tr>
        `;
    }
}

// Inline transaction expand for kontigentstatus
async function toggleMemberTransactions(memberId, btn) {
    const row = btn.closest('tr');
    const existingTxRow = row.nextElementSibling;

    if (existingTxRow && existingTxRow.classList.contains('member-tx-row')) {
        existingTxRow.remove();
        btn.classList.remove('active');
        return;
    }

    btn.classList.add('active');
    const txRow = document.createElement('tr');
    txRow.className = 'member-tx-row';
    txRow.innerHTML = `<td colspan="5"><div class="text-muted small py-1"><i class="bi bi-hourglass-split me-1"></i>Henter transaksjoner...</div></td>`;
    row.insertAdjacentElement('afterend', txRow);

    try {
        const resp = await fetch(`/api/get_member_transactions.php?member_id=${encodeURIComponent(memberId)}`, {credentials:'same-origin'});
        const data = await resp.json();

        if (!data.success) {
            txRow.innerHTML = `<td colspan="5"><div class="alert alert-danger py-1 small mb-0">${escapeH(data.error)}</div></td>`;
            return;
        }

        if (!data.transactions || data.transactions.length === 0) {
            txRow.innerHTML = `<td colspan="5"><div class="text-muted small fst-italic py-1">
                <i class="bi bi-info-circle me-1"></i>Ingen betalingsdata registrert — tildeles automatisk ved neste fornyelse.
            </div></td>`;
            return;
        }

        const rows = data.transactions.map(tx => {
            const statusBadge = tx.payment_status === 'completed' ? 'bg-success' :
                                tx.payment_status === 'pending'   ? 'bg-warning text-dark' : 'bg-secondary';
            const amount = tx.selling_price !== null ? 'kr ' + parseFloat(tx.selling_price).toLocaleString('nb-NO', {minimumFractionDigits: 2}) : '-';
            const date = tx.transaction_date ? tx.transaction_date.substring(0, 10).split('-').reverse().join('.') : '-';
            return `<tr>
                <td>${date}</td>
                <td>${escapeH(tx.item_name || '-')}</td>
                <td>${amount}</td>
                <td class="text-capitalize">${escapeH(tx.payment_method || '-')}</td>
                <td><span class="badge ${statusBadge}">${escapeH(tx.payment_status || '-')}</span></td>
            </tr>`;
        }).join('');

        txRow.innerHTML = `<td colspan="5">
            <table class="table tx-table table-borderless">
                <thead><tr>
                    <th>Dato</th><th>Beskrivelse</th><th>Beløp</th><th>Betalingsmåte</th><th>Status</th>
                </tr></thead>
                <tbody>${rows}</tbody>
            </table>
        </td>`;

    } catch (err) {
        txRow.innerHTML = `<td colspan="5"><div class="alert alert-danger py-1 small mb-0">Feil: ${escapeH(err.message)}</div></td>`;
    }
}

function toggleMemberAddress(memberId, btn) {
    const row = btn.closest('tr');
    const existing = row.nextElementSibling;

    if (existing && existing.classList.contains('member-addr-row')) {
        existing.remove();
        btn.classList.remove('active');
        return;
    }

    btn.classList.add('active');

    const addr    = row.dataset.address  || '';
    const poststed = row.dataset.poststed || '';

    const addrRow = document.createElement('tr');
    addrRow.className = 'member-addr-row';
    addrRow.dataset.memberId = memberId;
    addrRow.innerHTML = `
        <td colspan="5" style="background:#f8f9fa;padding:10px 16px;">
            <div class="d-flex gap-4 align-items-start flex-wrap">
                <div style="min-width:180px;">
                    <small class="text-muted d-block mb-1"><i class="bi bi-signpost me-1"></i>Gateadresse</small>
                    <div class="editable-member-cell" data-field="address" style="min-width:160px;">${addr || '-'}</div>
                </div>
                <div style="min-width:130px;">
                    <small class="text-muted d-block mb-1"><i class="bi bi-building me-1"></i>Poststed</small>
                    <div class="editable-member-cell" data-field="poststed" style="min-width:110px;">${poststed || '-'}</div>
                </div>
                <div class="ms-auto align-self-center">
                    <button class="btn btn-sm btn-link text-muted p-0" onclick="this.closest('.member-addr-row').remove(); document.querySelector('[data-member-id=\'${memberId}\'] .btn[title=\'Adresse\']')?.classList.remove('active');" title="Lukk">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            </div>
        </td>
    `;

    row.insertAdjacentElement('afterend', addrRow);
}

// Auto-load members when Membership tab is shown
document.getElementById('tab-membership')?.addEventListener('shown.bs.tab', function() {
    console.log('Membership tab shown - loading members and plans');
    loadMembers();
    loadPlansForFilter();
    loadPlansForSelect();
});

// Last inn medlemmer når medlemmer-subtab vises
const memberTabLink = document.querySelector('a[href="#membership-members"]');
if (memberTabLink) {
    memberTabLink.addEventListener('click', function() {
        setTimeout(() => {
            console.log('Members subtab clicked - loading data');
            loadMembers();
            loadPlansForFilter();
        }, 100);
    });
}

// Also load on initial page load if members tab is active
if (window.location.hash === '#membership-members') {
    console.log('Initial load - members subtab active');
    loadMembers();
    loadPlansForFilter();
}

// Add filter event listeners
document.getElementById('member-search-input')?.addEventListener('input', applyMemberFilters);
document.getElementById('member-plan-filter')?.addEventListener('change', applyMemberFilters);
document.getElementById('member-status-filter')?.addEventListener('change', applyMemberFilters);

// Last inn planer for dropdown når modal åpnes
document.getElementById('createMemberModal')?.addEventListener('show.bs.modal', loadPlansForSelect);

// Auto-load membership overview data on page load
setTimeout(() => {
    const membershipTab = document.getElementById('content-membership');
    if (membershipTab && membershipTab.classList.contains('active')) {
        console.log('Membership module active - auto-loading all data');
        loadMembershipPlans();
        loadMembers();
        loadPlansForFilter();
    }
}, 500);

// Inline editing for member fields using event delegation
document.addEventListener('DOMContentLoaded', function() {
    const membersTableBody = document.getElementById('members-table-body');
    if (!membersTableBody) return;
    
    membersTableBody.addEventListener('click', async function(e) {
        const cell = e.target.closest('.editable-member-cell');
        if (!cell || cell.querySelector('input')) return; // Already editing
        
        const row = cell.closest('tr');
        const memberId = row.dataset.memberId;
        const field = cell.dataset.field;
        const currentValue = cell.textContent.trim() === '-' ? '' : cell.textContent.trim();
        
        // Create input
        const input = document.createElement('input');
        input.type = field === 'email' ? 'email' : field === 'phone' ? 'tel' : 'text';
        input.className = 'form-control form-control-sm';
        input.value = currentValue;
        input.style.width = '100%';
        
        // Replace content
        const originalHTML = cell.innerHTML;
        cell.innerHTML = '';
        cell.appendChild(input);
        input.focus();
        input.select();
        
        // Save function
        const saveEdit = async () => {
            const newValue = input.value.trim();
            if (newValue !== currentValue) {
                const updateData = {
                    member_id: memberId,
                    [field]: newValue
                };
                
                try {
                    const response = await fetch('/api/update_member.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(updateData)
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        await loadMembers();
                    } else {
                        cell.innerHTML = originalHTML;
                        alert('❌ Feil ved oppdatering: ' + result.message);
                    }
                } catch (error) {
                    cell.innerHTML = originalHTML;
                    console.error('Update error:', error);
                    alert('❌ Feil ved oppdatering av medlem');
                }
            } else {
                cell.innerHTML = originalHTML;
            }
        };
        
        input.addEventListener('blur', saveEdit);
        input.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                saveEdit();
            } else if (e.key === 'Escape') {
                cell.innerHTML = originalHTML;
            }
        });
    });
    
    // Handle plan type inline editing with dropdown
    membersTableBody.addEventListener('click', async function(e) {
        const cell = e.target.closest('.editable-plan-cell');
        if (!cell || cell.querySelector('select')) return; // Already editing
        
        const row = cell.closest('tr');
        const memberId = row.dataset.memberId;
        const currentPlanId = cell.dataset.currentPlan || '';
        const originalHTML = cell.innerHTML;
        
        // Get available plans from window.membershipPlans (loaded by loadPlansForSelect)
        let plans = window.membershipPlans || [];
        
        // If plans not loaded, fetch them
        if (plans.length === 0) {
            try {
                const response = await fetch('../api/get_membership_plans.php');
                const result = await response.json();
                if (result.success && result.plans) {
                    plans = result.plans;
                    window.membershipPlans = plans;
                }
            } catch (error) {
                console.error('Error fetching plans:', error);
                return;
            }
        }
        
        if (plans.length === 0) {
            alert('Ingen medlemsplaner funnet. Opprett en plan først.');
            return;
        }
        
        // Create select dropdown
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        select.innerHTML = plans.map(plan => 
            `<option value="${plan.id}" ${plan.id === currentPlanId ? 'selected' : ''}>${plan.name}</option>`
        ).join('');
        
        cell.innerHTML = '';
        cell.appendChild(select);
        select.focus();
        
        // Save function
        const savePlanChange = async () => {
            const newPlanId = select.value;
            if (newPlanId !== currentPlanId) {
                const updateData = {
                    member_id: memberId,
                    membership_plan_id: newPlanId
                };
                
                try {
                    const response = await fetch('/api/update_member.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(updateData)
                    });
                    
                    const result = await response.json();
                    if (result.success) {
                        await loadMembers();
                    } else {
                        cell.innerHTML = originalHTML;
                        alert('Feil ved oppdatering: ' + result.message);
                    }
                } catch (error) {
                    cell.innerHTML = originalHTML;
                    console.error('Update error:', error);
                    alert('Feil ved oppdatering av medlemstype');
                }
            } else {
                cell.innerHTML = originalHTML;
            }
        };
        
        select.addEventListener('blur', savePlanChange);
        select.addEventListener('change', savePlanChange);
        select.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                cell.innerHTML = originalHTML;
            }
        });
    });
});

// Copy renewal link to clipboard
function copyRenewalLink(shareCode, memberCode) {
    if (!memberSubdomain || !shareCode || !memberCode) {
        toast.quickError('Mangler data', 'Kunne ikke generere fornyelseslenken.');
        return;
    }
    const url = `https://${memberSubdomain}.salesman.no/${shareCode}/${memberCode}`;
    navigator.clipboard.writeText(url).then(() => {
        toast.quickSuccess('Lenke kopiert', url);
    }).catch(() => {
        prompt('Kopier lenken manuelt:', url);
    });
}

// Renew membership function
async function renewMembership(memberId) {
    const member = allMembers.find(m => m.member_id == memberId);
    if (!member) {
        alert('❌ Medlem ikke funnet');
        return;
    }
    
    const startDate = prompt('Fornyingsdato (YYYY-MM-DD):', new Date().toISOString().split('T')[0]);
    if (!startDate) return;
    
    try {
        const price=Number(member.plan_price);
        if (member.plan_price===null || !Number.isFinite(price)) throw new Error('Medlemskapets pris kunne ikke hentes. Last medlemslisten på nytt.');
        const paymentMethod=price>0 ? await ActivePaymentMethods.choose() : null;
        if (price>0 && !paymentMethod) return;
        const response = await fetch('/api/renew_membership.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                member_id: memberId,
                renewal_date: startDate,
                payment_method: paymentMethod
            })
        });
        
        const result = await response.json();
        if (result.success) {
            alert('✅ Medlemskap fornyet! Ny utløpsdato: ' + result.new_expiry_date);
            await loadMembers();
        } else {
            alert('❌ Feil: ' + result.message);
        }
    } catch (error) {
        console.error('Renewal error:', error);
        alert('Feil ved fornyelse av medlemskap: '+error.message);
    }
}

// ===== MEMBER STATS =====
async function loadMemberStats() {
    try {
        const resp = await fetch('/api/get_members.php?stats=1');
        const data = await resp.json();
        if (data.success) {
            const s = document.getElementById('stat-total');
            const e = document.getElementById('stat-expired');
            const r = document.getElementById('stat-renewed');
            if (s) s.textContent = data.total ?? '—';
            if (e) e.textContent = data.expired ?? '—';
            if (r) r.textContent = data.renewed_this_year ?? '—';
        }
    } catch(err) {
        console.error('Feil ved henting av stats:', err);
    }
}

// ===== MEMBERSHIP CALENDAR =====
let currentIncomeYear = new Date().getFullYear();
const incomeMonthNames = ['Januar','Februar','Mars','April','Mai','Juni',
                          'Juli','August','September','Oktober','November','Desember'];

function changeIncomeYear(delta) {
    currentIncomeYear += delta;
    document.getElementById('income-year-display').textContent = currentIncomeYear;
    loadMembershipCalendar(currentIncomeYear);
}

async function loadMembershipCalendar(year) {
    const grid = document.getElementById('income-calendar-grid');
    if (!grid) return;
    grid.innerHTML = '<div class="col-12 text-center text-muted py-4"><div class="spinner-border spinner-border-sm me-2"></div>Laster...</div>';
    closeIncomeMonthDetail();

    try {
        const resp = await fetch(`/api/get_membership_calendar.php?year=${year}`);
        const data = await resp.json();

        if (!data.success) {
            grid.innerHTML = `<div class="col-12 text-center text-danger py-3"><i class="bi bi-exclamation-circle me-2"></i>${data.error || 'Feil ved lasting'}</div>`;
            return;
        }

        // Group by month index (0-11)
        const byMonth = { new: {}, renewals: {} };
        (data.new || []).forEach(r => {
            const m = new Date(r.d).getMonth();
            if (!byMonth.new[m]) byMonth.new[m] = [];
            byMonth.new[m].push(r);
        });
        (data.renewals || []).forEach(r => {
            const m = new Date(r.d).getMonth();
            if (!byMonth.renewals[m]) byMonth.renewals[m] = [];
            byMonth.renewals[m].push(r);
        });

        const now = new Date();
        const isCurrentYear = year === now.getFullYear();

        grid.innerHTML = incomeMonthNames.map((name, idx) => {
            const newList  = byMonth.new[idx]      || [];
            const renList  = byMonth.renewals[idx] || [];
            const total    = newList.length + renList.length;
            const isCur    = isCurrentYear && idx === now.getMonth();
            const border   = isCur ? 'border-primary' : 'border';

            const newBadge = newList.length  ? `<span class="badge bg-success me-1" title="Innmeldte">${newList.length} ny</span>` : '';
            const renBadge = renList.length  ? `<span class="badge bg-primary" title="Fornyede">${renList.length} fornyet</span>` : '';
            const empty    = !total          ? '<span class="text-muted" style="font-size:0.75rem;">—</span>' : '';

            return `<div class="col-6 col-md-4 col-lg-3 mb-2">
                <div class="${border} rounded p-2 h-100 ${total > 0 ? 'cursor-pointer' : ''}" style="${total>0?'cursor:pointer;':''}"
                     onclick="${total > 0 ? `showIncomeMonthDetail(${idx}, '${name}', ${JSON.stringify(newList).replace(/"/g,'&quot;')}, ${JSON.stringify(renList).replace(/"/g,'&quot;')})` : ''}">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <small class="fw-semibold ${isCur ? 'text-primary' : ''}">${name}</small>
                        ${isCur ? '<span class="badge bg-primary" style="font-size:0.6rem;">nå</span>' : ''}
                    </div>
                    <div>${newBadge}${renBadge}${empty}</div>
                </div>
            </div>`;
        }).join('');

    } catch(err) {
        grid.innerHTML = `<div class="col-12 text-center text-danger py-3"><i class="bi bi-exclamation-circle me-2"></i>${err.message}</div>`;
    }
}

function showIncomeMonthDetail(monthIdx, monthName, newList, renList) {
    const detail = document.getElementById('income-month-detail');
    const title  = document.getElementById('income-month-title');
    const body   = document.getElementById('income-month-body');
    if (!detail) return;

    title.textContent = `${monthName} ${currentIncomeYear}`;

    let html = '';

    if (newList.length) {
        html += `<div class="mb-2"><span class="badge bg-success mb-1">Innmeldte (${newList.length})</span><ul class="list-unstyled mb-0 ms-1">`;
        newList.forEach(r => {
            html += `<li class="small"><i class="bi bi-person-plus text-success me-1"></i>${r.name} <span class="text-muted">— ${r.plan} — ${r.d}</span></li>`;
        });
        html += '</ul></div>';
    }

    if (renList.length) {
        html += `<div><span class="badge bg-primary mb-1">Fornyede (${renList.length})</span><ul class="list-unstyled mb-0 ms-1">`;
        renList.forEach(r => {
            html += `<li class="small"><i class="bi bi-arrow-repeat text-primary me-1"></i>${r.name} <span class="text-muted">— ${r.plan} — ${r.d}</span></li>`;
        });
        html += '</ul></div>';
    }

    body.innerHTML = html || '<span class="text-muted small">Ingen hendelser denne måneden</span>';
    detail.style.display = 'block';
    detail.scrollIntoView({behavior:'smooth', block:'nearest'});
}

function closeIncomeMonthDetail() {
    const d = document.getElementById('income-month-detail');
    if (d) d.style.display = 'none';
}

async function runLedgerBackfill() {
    const btn = document.getElementById('income-backfill-btn');
    const result = document.getElementById('income-backfill-result');
    if (!btn) return;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Kjører...';
    if (result) { result.textContent = ''; result.style.display = 'none'; }

    try {
        const resp = await fetch('/api/run_backfill_renewal_ledger.php', {
            method: 'POST', credentials: 'same-origin'
        });
        const data = await resp.json();

        if (data.success) {
            if (result) {
                result.textContent = data.message;
                result.style.display = 'inline';
                setTimeout(() => { result.style.display = 'none'; }, 8000);
            }
        } else {
            alert('Feil: ' + (data.error || 'Ukjent feil'));
        }
    } catch(err) {
        alert('Feil ved kjøring: ' + err.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Synkroniser fornyelser';
    }
}

// Income tab trigger
const incomeTabLink = document.querySelector('a[href="#membership-income"]');
if (incomeTabLink) {
    incomeTabLink.addEventListener('click', function() {
        setTimeout(() => loadMembershipCalendar(currentIncomeYear), 100);
    });
}


// ===== DATOFORMAT HJELPER =====
function fmtDate(d) {
    if (!d) return '-';
    const dt = new Date(d);
    if (isNaN(dt.getTime())) return d;
    return dt.toLocaleDateString('nb-NO', {day:'2-digit', month:'2-digit', year:'numeric'});
}

// ===== KANSELLERTE MEDLEMMER =====
async function loadCancelledMembers() {
    const tbody = document.getElementById('cancelled-members-tbody');
    if (!tbody) return;
    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><div class="spinner-border spinner-border-sm"></div></td></tr>';

    try {
        const resp = await fetch('../api/get_members.php?cancelled=1');
        const data = await resp.json();

        if (data.success && data.members && data.members.length > 0) {
            tbody.innerHTML = data.members.map(m => `
                <tr>
                    <td>${m.first_name || ''} ${m.last_name || ''}</td>
                    <td>${m.email || '-'}</td>
                    <td>${m.membership_type || '-'}</td>
                    <td><span class="text-muted fst-italic small">${m.cancellation_reasoning || 'Ingen begrunnelse'}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-success" onclick="reactivateMember('${m.member_id}')" title="Reaktiver">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        } else {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Ingen kansellerte medlemmer</td></tr>';
        }
    } catch(err) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-danger text-center py-3">${err.message}</td></tr>`;
    }
}

// Laster kansellerte ved klikk på subtab
const cancelledTabLink = document.querySelector('a[href="#membership-cancelled"]');
if (cancelledTabLink) {
    cancelledTabLink.addEventListener('click', function() {
        setTimeout(loadCancelledMembers, 100);
    });
}

async function reactivateMember(memberId) {
    const btn = event?.currentTarget;
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>'; }
    try {
        const resp = await fetch('../api/cancel_member.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            credentials: 'same-origin',
            body: JSON.stringify({member_id: memberId, reactivate: true})
        });
        const data = await resp.json();
        if (data.success) {
            loadMembers();
            loadCancelledMembers();
        } else {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i>'; }
            alert('Feil: ' + (data.error || 'Ukjent feil'));
        }
    } catch(err) {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i>'; }
    }
}

if (typeof escapeH !== 'function') {
    function escapeH(t) {
        const d = document.createElement('div');
        d.textContent = t || '';
        return d.innerHTML;
    }
}

// ── Fornyelsesliste (membership-income) ──────────────────────────────────────

let mbRenewalPage       = 1;
let mbRenewalTotalPages = 1;
let mbLookupRevision    = 0;
let mbLookupHasRenewal  = false;

const mbRenewalStatusColors = {
    completed:       'rgba(25,135,84,0.12)',
    pending:         'rgba(255,193,7,0.2)',
    payment_pending: 'rgba(13,202,240,0.15)',
    cancelled:       'rgba(220,53,69,0.12)',
    expired:         'rgba(108,117,125,0.12)',
};

async function mbLookupRenewal() {
    const lookup = document.getElementById('mb-lookup-input').value.trim();
    if (!lookup) return;
    const revision = ++mbLookupRevision;
    const resultEl = document.getElementById('mb-lookup-result');
    const feedbackEl = document.getElementById('mb-lookup-feedback');
    const lookupButton = document.getElementById('mb-lookup-button');
    resultEl.style.display = 'none';
    feedbackEl.textContent = 'Søker…';
    feedbackEl.className = 'small mt-1 text-muted';
    lookupButton.disabled = true;
    mbLookupHasRenewal = false;
    document.getElementById('mb-rl-renewal-id').value = '';
    document.getElementById('mb-rl-member-id').value = '';
    document.getElementById('mb-rl-save-msg').textContent = '';
    try {
        const resp = await fetch('/api/get_membership_renewals.php?lookup=' + encodeURIComponent(lookup));
        const data = await resp.json();
        if (revision !== mbLookupRevision) return;
        if (!resp.ok || !data.success) {
            feedbackEl.textContent = data.message || 'Ikke funnet';
            feedbackEl.className = 'small mt-1 text-danger';
            return;
        }
        const r = data.row;
        mbLookupHasRenewal = Boolean(r.has_renewal && r.id);
        document.getElementById('mb-rl-name').textContent           = ((r.first_name || '') + ' ' + (r.last_name || '')).trim() || '–';
        document.getElementById('mb-rl-plan').textContent           = r.plan_name || 'Ingen medlemsplan';
        document.getElementById('mb-rl-member-code').textContent    = r.member_code || 'Ingen medlemskode';
        document.getElementById('mb-rl-match-type').textContent     = r.lookup_type === 'member' ? 'Medlem' : 'Fornyelse';
        document.getElementById('mb-rl-renewal-id').value           = r.id || '';
        document.getElementById('mb-rl-member-id').value            = r.membership_member_id || '';
        document.getElementById('mb-rl-renewal-status').value       = r.renewal_status  || 'pending';
        document.getElementById('mb-rl-payment-status').value       = r.payment_status   || 'unpaid';
        document.getElementById('mb-rl-expiration-date').value      = r.expiration_date ? r.expiration_date.substring(0, 10) : '';
        document.getElementById('mb-rl-renewal-status').disabled    = !mbLookupHasRenewal;
        document.getElementById('mb-rl-renewal-field').classList.toggle('opacity-50', !mbLookupHasRenewal);
        document.getElementById('mb-rl-renewal-help').textContent   = mbLookupHasRenewal
            ? (r.lookup_type === 'member' ? 'Viser siste registrerte fornyelse.' : '')
            : 'Medlemmet har ingen registrert fornyelse.';
        feedbackEl.textContent = r.lookup_type === 'member' ? 'Medlem funnet' : 'Fornyelse funnet';
        feedbackEl.className = 'small mt-1 text-success';
        resultEl.style.display = 'block';
    } catch(e) {
        if (revision !== mbLookupRevision) return;
        console.error('mbLookupRenewal error', e);
        feedbackEl.textContent = 'Kunne ikke gjennomføre søket';
        feedbackEl.className = 'small mt-1 text-danger';
    } finally {
        if (revision === mbLookupRevision) lookupButton.disabled = false;
    }
}

async function mbSaveRenewalLookup() {
    const renewal_id = document.getElementById('mb-rl-renewal-id').value;
    const member_id  = document.getElementById('mb-rl-member-id').value;
    const rnStatus   = document.getElementById('mb-rl-renewal-status').value;
    const payStatus  = document.getElementById('mb-rl-payment-status').value;
    const expDate    = document.getElementById('mb-rl-expiration-date').value;
    const msgEl      = document.getElementById('mb-rl-save-msg');
    const saveButton = document.getElementById('mb-rl-save-button');
    msgEl.textContent = ''; msgEl.className = 'small';
    if (!member_id) {
        msgEl.textContent = 'Medlem mangler';
        msgEl.className = 'small text-danger';
        return;
    }
    saveButton.disabled = true;
    try {
        const response = await fetch('/api/update_membership_lookup.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                membership_member_id: member_id,
                renewal_id: mbLookupHasRenewal ? renewal_id : '',
                renewal_status: mbLookupHasRenewal ? rnStatus : '',
                payment_status: payStatus,
                expiration_date: expDate
            })
        });
        const data = await response.json();
        if (response.ok && data.success) {
            msgEl.textContent = '✓ Lagret'; msgEl.className = 'small text-success';
            if (mbLookupHasRenewal) mbLoadRenewalList(mbRenewalPage);
        } else {
            msgEl.textContent = data.message || 'Feil ved lagring';
            msgEl.className = 'small text-danger';
        }
    } catch(e) {
        msgEl.textContent = 'Nettverksfeil';
        msgEl.className = 'small text-danger';
    } finally {
        saveButton.disabled = false;
    }
}

function mbCopyOrder(el) {
    navigator.clipboard.writeText(el.dataset.orderId || '').then(() => mbCopyToast());
}

async function mbUpdateRenewalStatus(id, status, selectEl) {
    try {
        const resp = await fetch('/api/update_renewal_status.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({id, status})
        });
        const data = await resp.json();
        if (data.success) {
            if (selectEl) selectEl.style.background = mbRenewalStatusColors[status] || '';
            mbCopyToast('Status oppdatert');
        } else { alert(data.message || 'Feil ved oppdatering'); }
    } catch(e) { console.error('mbUpdateRenewalStatus error', e); }
}

async function mbSaveField(membershipMemberId, paymentStatus, expirationDate) {
    try {
        const resp = await fetch('/api/update_member_fields.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                membership_member_id: membershipMemberId,
                payment_status:       paymentStatus,
                expiration_date:      expirationDate
            })
        });
        const data = await resp.json();
        if (!data.success) { console.error('mbSaveField:', data.message); }
        else { mbCopyToast('Felt lagret'); }
    } catch(e) { console.error('mbSaveField error', e); }
}

async function mbToggleKontrollert(membershipMemberId, checked, rowEl) {
    try {
        const resp = await fetch('/api/update_member_fields.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({membership_member_id: membershipMemberId, kontrollert: checked})
        });
        const data = await resp.json();
        if (data.success) {
            rowEl.classList.toggle('table-success', checked);
        } else { console.error('mbToggleKontrollert:', data.message); }
    } catch(e) { console.error('mbToggleKontrollert error', e); }
}

async function mbLoadRenewalList(page) {
    page = page || 1;
    mbRenewalPage = page;
    const search = encodeURIComponent(document.getElementById('mb-renewal-search')?.value?.trim() || '');
    const tbody  = document.getElementById('mb-renewal-list-body');
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Laster…</td></tr>';
    try {
        const resp = await fetch(`/api/get_membership_renewals.php?page=${page}&per_page=10&search=${search}`);
        const data = await resp.json();
        if (!data.success) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-danger text-center py-2">Feil ved lasting</td></tr>';
            return;
        }

        mbRenewalTotalPages = Math.max(1, Math.ceil(data.total / data.per_page));

        if (!data.rows || data.rows.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted py-3">Ingen fornyelser</td></tr>';
        } else {
            const mbPayColors = {paid:'#d4edda', unpaid:'#fff3cd', pending:'#fff3cd', refunded:'#e2e3e5'};
            tbody.innerHTML = data.rows.map(r => {
                const dateStr   = r.created_at ? r.created_at.substring(0, 10) : '–';
                const amount    = r.amount ? 'kr ' + parseFloat(r.amount).toFixed(2) : '–';
                const bgRen     = mbRenewalStatusColors[r.renewal_status] || '';
                const bgPay     = mbPayColors[r.payment_status] || '';
                const expVal    = r.expiration_date ? r.expiration_date.substring(0,10) : '';
                const kontrolEl = parseInt(r.kontrollert) === 1;
                const rowBg     = kontrolEl ? 'class="table-success"' : '';

                const renewOpts = ['pending','payment_pending','completed','cancelled','expired']
                    .map(s => `<option value="${s}"${s===r.renewal_status?' selected':''}>${s}</option>`).join('');
                const payOpts = ['paid','unpaid','pending','refunded']
                    .map(s => `<option value="${s}"${s===r.payment_status?' selected':''}>${s}</option>`).join('');

                const safeRenId  = escapeH(r.id);
                const safeMemId  = escapeH(r.membership_member_id || '');
                const safeName   = escapeH(r.member_name || '–');
                const safeOrder  = escapeH(r.order_id || '');
                const safePlan   = escapeH(r.plan_name || '–');

                return `<tr ${rowBg}>
                    <td>
                        <span data-name="${safeName}" data-order="${safeOrder}"
                              onmouseover="this.textContent=this.dataset.order"
                              onmouseout="this.textContent=this.dataset.name"
                              style="cursor:default;">${safeName}</span>
                        <i class="bi bi-clipboard ms-1 text-muted"
                           style="cursor:pointer;font-size:.75rem;"
                           data-order-id="${safeOrder}"
                           onclick="mbCopyOrder(this)"></i>
                    </td>
                    <td class="text-muted small">${safePlan}</td>
                    <td class="text-end small">${amount}</td>
                    <td>
                        <select class="form-select form-select-sm border-0 px-1"
                                style="background:${bgRen};width:auto;min-width:130px;"
                                onchange="mbUpdateRenewalStatus('${safeRenId}', this.value, this)">
                            ${renewOpts}
                        </select>
                    </td>
                    <td>
                        <select class="form-select form-select-sm border-0 px-1"
                                style="background:${bgPay};width:auto;min-width:100px;"
                                onchange="mbSaveField('${safeMemId}', this.value, this.closest('tr').querySelector('input[type=date]').value)">
                            ${payOpts}
                        </select>
                    </td>
                    <td>
                        <input type="date" class="form-control form-control-sm border-0 px-1"
                               style="min-width:120px;"
                               value="${expVal}"
                               onchange="mbSaveField('${safeMemId}', this.closest('tr').querySelector('select:nth-of-type(2)').value, this.value)">
                    </td>
                    <td class="text-center">
                        <input type="checkbox" class="form-check-input" ${kontrolEl ? 'checked' : ''}
                               onchange="mbToggleKontrollert('${safeMemId}', this.checked, this.closest('tr'))">
                    </td>
                    <td class="text-muted small">${dateStr}</td>
                </tr>`;
            }).join('');
        }

        const pagDiv   = document.getElementById('mb-renewal-pagination');
        const pageInfo = document.getElementById('mb-renewal-page-info');
        const prevBtn  = document.getElementById('mb-renewal-prev');
        const nextBtn  = document.getElementById('mb-renewal-next');
        pageInfo.textContent = `Side ${mbRenewalPage} av ${mbRenewalTotalPages}`;
        prevBtn.disabled     = mbRenewalPage <= 1;
        nextBtn.disabled     = mbRenewalPage >= mbRenewalTotalPages;
        pagDiv.style.display = data.total > data.per_page ? 'flex' : 'none';

    } catch(e) {
        console.error('mbLoadRenewalList error', e);
        document.getElementById('mb-renewal-list-body').innerHTML =
            '<tr><td colspan="8" class="text-danger text-center py-2">Nettverksfeil</td></tr>';
    }
}

function mbCopyToast(msg) {
    msg = msg || 'order_id kopiert';
    let container = document.getElementById('mb-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'mb-toast-container';
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    const uid = 'mbt' + Date.now();
    container.insertAdjacentHTML('beforeend', `
        <div id="${uid}" class="toast align-items-center text-bg-success border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${msg}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`);
    const el = document.getElementById(uid);
    new bootstrap.Toast(el, {delay:2000}).show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}

async function mbLoadRenewalStats() {
    try {
        const resp = await fetch('/api/get_renewal_stats.php');
        const data = await resp.json();
        if (!data.success) return;
        document.getElementById('mb-stat-sum').textContent =
            'kr\u00a0' + parseFloat(data.sum_completed).toLocaleString('nb-NO', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('mb-stat-renewed').textContent = data.count_renewed;
        document.getElementById('mb-stat-valid').textContent   = data.count_valid;
    } catch(e) { console.error('mbLoadRenewalStats error', e); }
}

const mbRenewalTabLink = document.querySelector('a[href="#membership-renewals"]');
if (mbRenewalTabLink) {
    mbRenewalTabLink.addEventListener('click', () => setTimeout(() => {
        mbLoadRenewalList(1);
        mbLoadRenewalStats();
    }, 100));
}

document.getElementById('mb-lookup-input')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') mbLookupRenewal();
});
document.getElementById('mb-renewal-search')?.addEventListener('keydown', e => {
    if (e.key === 'Enter') mbLoadRenewalList(1);
});

function toggleExcelExportPanel() {
    const panel = document.getElementById('excel-export-panel');
    if (panel) {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }
}

function downloadMembersExcel() {
    const checks = document.querySelectorAll('.excel-field-check:checked');
    const fields = Array.from(checks).map(cb => cb.value);
    if (fields.length === 0) {
        alert('Velg minst ett felt for eksport.');
        return;
    }
    const url = '../api/export_members_excel.php?fields=' + encodeURIComponent(fields.join(','));
    window.location.href = url;
}
</script>
