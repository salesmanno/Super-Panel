<?php // Only included after the dashboard has established its current tenant. ?>
<section class="portal-overview" aria-label="Oversikt">
    <div class="container">
        <div class="po-heading"><h1>Hei, <?= htmlspecialchars($adminName) ?> <span aria-hidden="true">●</span></h1>
        <?php if ($companySubdomain): ?><a href="https://<?= htmlspecialchars($companySubdomain) ?>.salesman.no" target="_blank" rel="noopener"><?= htmlspecialchars($companySubdomain) ?>.salesman.no <i class="bi bi-box-arrow-up-right"></i></a><?php endif; ?></div>
        <div class="po-columns">
            <section aria-labelledby="po-notices-label">
                <h2 id="po-notices-label"><i class="bi bi-bell"></i> Varsler <span id="po-notice-count"></span></h2>
                <div id="tenant-notifications" class="po-notice-stack" aria-live="polite" aria-busy="true">
                    <p class="po-empty" data-notification-loading>Henter varsler …</p>
                    <button type="button" class="btn btn-sm btn-outline-primary po-retry-notifications" hidden onclick="if(window.PortalOverview?.loadNotifications) window.PortalOverview.loadNotifications(); else window.location.reload()">Prøv igjen</button>
                </div>
            </section>
            <section aria-labelledby="po-agenda-label">
                <h2 id="po-agenda-label"><i class="bi bi-calendar3"></i> Neste på planen</h2>
                <div id="po-agenda"><p class="po-empty">Henter kommende aktiviteter …</p></div>
            </section>
        </div>
        <?php if ($auditVisible): ?>
        <div class="po-activity" id="po-activity"><strong><i class="bi bi-activity"></i> Aktivitet</strong><span>Henter siste endringer …</span></div>
        <?php endif; ?>
    </div>
</section>
<?php if ($isSupervisor): ?>
<div class="modal fade" id="company-users-modal" tabindex="-1" aria-labelledby="company-users-title" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <div class="modal-content po-users">
            <div class="modal-header"><div><small><?= htmlspecialchars($companyName) ?></small><h2 class="modal-title fs-5" id="company-users-title">Firmabrukere</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Lukk"></button></div>
            <div class="modal-body"><p class="text-muted small">Ansattknyttede brukere i denne bedriften. Kun bedriftens aktiverte moduler kan delegeres.</p>
                <div id="company-users-feedback" role="status" aria-live="polite"></div>
                <div id="company-users-list"></div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<script>
// Keep the notice area recoverable even if its external script never loads.
window.setTimeout(() => {
    const host = document.getElementById('tenant-notifications');
    const loading = host?.querySelector('[data-notification-loading]');
    if (!loading) return;
    loading.textContent = 'Varslene svarer ikke ennå.';
    const retry = host.querySelector('.po-retry-notifications');
    if (retry) retry.hidden = false;
    host.setAttribute('aria-busy', 'false');
}, 8000);
</script>
