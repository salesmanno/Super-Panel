<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/tenant_audit.php';

if (empty($_SESSION['tenant_admin_id']) || empty($_SESSION['company_id'])) {
    header('Location: ../index.php');
    exit;
}

$companyId = (string) $_SESSION['company_id'];
$db = getDBConnection();
if (!tenantAuditCanRead($db, $companyId)) {
    http_response_code(403);
    exit('Du har ikke tilgang til aktivitetsloggen.');
}
?>
<!doctype html>
<html lang="no">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>Aktivitetslogg – Salesman.no</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/tenant-audit.css" rel="stylesheet">
</head>
<body>
    <header class="topbar">
        <div class="container-fluid px-3 px-lg-4 topbar-inner">
            <div>
                <a class="brand" href="company_dashboard.php" aria-label="Salesman.no – tilbake til bedriftsoversikten">salesman<span>.no</span></a>
                <span class="portal-label">Bedriftsportal</span>
            </div>
            <nav class="top-links" aria-label="Bedriftsadministrasjon">
                <a href="company_dashboard.php">Oversikt</a>
                <a href="admin_sessions.php">Innloggingshistorikk</a>
                <a href="company_audit.php" aria-current="page">Aktivitetslogg</a>
            </nav>
        </div>
    </header>

    <main class="page-wrap">
        <nav class="crumb" aria-label="Brødsmulesti">
            <a href="company_dashboard.php">Bedriftsoversikt</a>
            <span class="crumb-sep" aria-hidden="true">/</span>
            <span aria-current="page">Aktivitetslogg</span>
        </nav>

        <section class="page-heading" aria-labelledby="page-title">
            <div>
                <p class="eyebrow">Sporbarhet · Administrasjon</p>
                <h1 id="page-title">Aktivitetslogg</h1>
                <p class="heading-copy">Se hvem som endret bedriftens opplysninger, hva som ble endret og når.</p>
            </div>
            <div class="scope-note"><span class="scope-dot" aria-hidden="true"></span>Kun hendelser for denne bedriften</div>
        </section>

        <section class="filter-panel" aria-labelledby="filter-heading">
            <div class="filter-title-line">
                <h2 class="filter-title" id="filter-heading">Avgrens hendelser</h2>
                <span class="filter-hint" id="filter-date-hint">Datoformat: DD.MM.YYYY. Til dato gjelder hele den valgte dagen.</span>
            </div>
            <form id="filter-form">
                <div class="filter-grid">
                    <div>
                        <label class="form-label" for="filter-from">Fra dato</label>
                        <input class="form-control audit-date-input" id="filter-from" name="from" type="text" inputmode="numeric" autocomplete="off" maxlength="10" placeholder="DD.MM.YYYY" aria-describedby="filter-date-hint">
                    </div>
                    <div>
                        <label class="form-label" for="filter-to">Til dato</label>
                        <input class="form-control audit-date-input" id="filter-to" name="to" type="text" inputmode="numeric" autocomplete="off" maxlength="10" placeholder="DD.MM.YYYY" aria-describedby="filter-date-hint">
                    </div>
                    <div>
                        <label class="form-label" for="filter-admin">Administrator</label>
                        <select class="form-select" id="filter-admin" name="admin_id">
                            <option value="">Alle administratorer</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="filter-module">Område</label>
                        <select class="form-select" id="filter-module" name="module">
                            <option value="">Alle områder</option>
                        </select>
                    </div>
                    <div>
                        <label class="form-label" for="filter-action">Hendelse</label>
                        <select class="form-select" id="filter-action" name="action">
                            <option value="">Alle hendelser</option>
                        </select>
                    </div>
                    <div class="filter-actions">
                        <button class="btn btn-primary" type="submit">Vis hendelser</button>
                        <button class="btn btn-outline-secondary" id="reset-filters" type="button">Nullstill</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="results-panel" aria-labelledby="results-heading">
            <div class="results-head">
                <div class="results-title">
                    <h2 id="results-heading">Hendelser</h2>
                    <span class="result-count" id="result-count" aria-live="polite"></span>
                </div>
                <button class="btn btn-outline-secondary refresh-btn" type="button" id="refresh-button" aria-label="Oppdater hendelseslisten">
                    <span aria-hidden="true">↻</span> Oppdater
                </button>
            </div>
            <div id="results-content" aria-live="polite" aria-busy="true">
                <div class="loading-state" aria-label="Henter aktivitetslogg">
                    <div class="skeleton-row"><span class="skeleton-line"></span><span class="skeleton-line"></span><span class="skeleton-line"></span><span class="skeleton-line"></span><span class="skeleton-line"></span></div>
                    <div class="skeleton-row"><span class="skeleton-line"></span><span class="skeleton-line"></span><span class="skeleton-line"></span><span class="skeleton-line"></span><span class="skeleton-line"></span></div>
                    <div class="skeleton-row"><span class="skeleton-line"></span><span class="skeleton-line"></span><span class="skeleton-line"></span><span class="skeleton-line"></span><span class="skeleton-line"></span></div>
                </div>
            </div>
        </section>
        <p class="page-footnote">Dette er bedriftens endringslogg. Se <a href="admin_sessions.php">innloggingshistorikk</a> for administratorenes innlogginger.</p>
    </main>

    <dialog class="audit-dialog" id="audit-dialog" aria-labelledby="dialog-title">
        <div class="dialog-head">
            <div>
                <p class="dialog-kicker">Hendelsesdetaljer</p>
                <h2 id="dialog-title">Laster hendelse</h2>
            </div>
            <button class="icon-close" type="button" id="dialog-close" aria-label="Lukk detaljer">×</button>
        </div>
        <div class="dialog-body" id="dialog-content" aria-live="polite">
            <div class="loading-state"><div class="skeleton-row"><span class="skeleton-line"></span><span class="skeleton-line"></span><span class="skeleton-line"></span></div></div>
        </div>
    </dialog>

    <script src="../assets/tenant-audit.js" defer></script>
</body>
</html>