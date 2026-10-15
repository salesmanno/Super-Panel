<link rel="stylesheet" href="/assets/sales-admin.css">
<div class="module-content sales-admin" id="sales-admin">
  <section id="sales-varesalg" class="subtab-content active" aria-label="Kasse">
    <header class="sales-heading">
      <div><span class="sales-kicker">Salgsrom</span><h2>Kasse</h2><p>Raskt salg. Ryddig oppgjør.</p></div>
      <button class="btn btn-outline-primary" type="button" id="sales-fullscreen" aria-pressed="false"><i class="bi bi-arrows-fullscreen" aria-hidden="true"></i> Fullskjerm</button>
    </header>
    <div class="sales-checkout-layout">
      <section class="sales-catalog" aria-label="Produktkatalog">
        <div class="sales-catalog-tools">
          <label class="sales-search"><i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Søk produkter</span><input id="sales-search" type="search" placeholder="Søk produkter" autocomplete="off" autocapitalize="off" spellcheck="false"></label>
          <label class="sales-search sales-scan"><i class="bi bi-upc-scan" aria-hidden="true"></i><span class="visually-hidden">Skann strekkode</span><input id="sales-scanner" type="text" placeholder="Skann strekkode" autocomplete="off" autocapitalize="off" spellcheck="false"></label>
          <button class="btn btn-sm btn-outline-secondary" type="button" id="sales-refresh-products" aria-label="Last produkter på nytt"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i></button>
        </div>
        <div id="sales-scan-message" class="sales-inline-message" role="status" aria-live="polite"></div>
        <div id="sales-products" class="sales-product-grid" aria-live="polite"><div class="sales-skeleton" aria-label="Laster produkter"></div><div class="sales-skeleton"></div><div class="sales-skeleton"></div><div class="sales-skeleton"></div></div>
      </section>
      <aside class="sales-cart-panel" id="sales-cart-panel" aria-label="Handlekurv">
        <div class="sales-cart-head"><div><span class="sales-kicker">Aktivt salg</span><h3>Handlekurv <span id="sales-cart-count" class="sales-count">0</span></h3></div><button id="sales-fullscreen-exit" class="btn btn-sm btn-outline-secondary d-none" type="button">Lukk fullskjerm</button></div>
        <div id="sales-cart-lines" class="sales-cart-lines" aria-live="polite"><div class="sales-empty"><strong>Ingen varer ennå</strong><span>Velg et produkt for å starte salget.</span></div></div>
        <div class="sales-totals"><div><span>Delsum</span><strong id="sales-subtotal">0,00 kr</strong></div><div id="sales-tax-row"><span>Mva</span><strong id="sales-tax">0,00 kr</strong></div><div class="sales-grand-total"><span>Å betale</span><strong id="sales-total">0,00 kr</strong></div></div>
        <div class="sales-payment">
          <label class="form-label" for="sales-method">Betalingsmåte</label><select id="sales-method" class="form-select"></select>
          <div id="sales-method-note" class="form-text"></div>
          <div id="sales-cash-panel">
            <label class="form-label mt-3" for="sales-tendered">Mottatt kontant</label>
            <div class="input-group"><input id="sales-tendered" class="form-control" type="number" inputmode="decimal" min="0" step="0.01" placeholder="0,00" autocomplete="off"><span class="input-group-text">kr</span></div>
            <div class="sales-cash-shortcuts" aria-label="Hurtigbeløp"><button type="button" data-cash="5000">50</button><button type="button" data-cash="10000">100</button><button type="button" data-cash="20000">200</button><button type="button" data-cash="50000">500</button><button type="button" data-cash="100000">1000</button></div>
            <div class="sales-change"><span>Vekslepenger</span><strong id="sales-change">0,00 kr</strong></div>
          </div>
          <div id="sales-vipps-panel" hidden>
            <label class="form-label mt-3" for="sales-phone">Vipps-nummer</label><input id="sales-phone" class="form-control" type="tel" inputmode="tel" placeholder="4xx xx xxx" autocomplete="off">
          </div>
          <div id="sales-payment-status" class="sales-payment-status" role="status" aria-live="polite" hidden></div>
          <button id="sales-checkout" type="button" class="btn btn-success w-100 mt-3" disabled>Bekreft kontantsalg</button>
          <button id="sales-cancel-payment" type="button" class="btn btn-outline-danger w-100 mt-2" hidden>Be om avbryt betaling</button>
          <button id="sales-next-customer" type="button" class="btn btn-outline-secondary w-100 mt-2" hidden>Neste kunde</button>
          <div id="sales-cart-error" class="sales-error" role="alert" hidden></div>
        </div>
      </aside>
    </div>
  </section>

  <section id="sales-items" class="subtab-content" style="display:none" aria-label="Produkter og lager">
    <header class="sales-heading"><div><span class="sales-kicker">Katalog og beholdning</span><h2>Produkter og lager</h2><p>Hold varedata og tilgjengelighet oppdatert.</p></div><button class="btn btn-primary" id="sales-new-product" type="button"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nytt produkt</button></header>
    <div class="sales-admin-tools"><label class="sales-search"><i class="bi bi-search" aria-hidden="true"></i><span class="visually-hidden">Søk i produkter</span><input id="sales-admin-search" type="search" placeholder="Finn et produkt" autocomplete="off"></label><span id="sales-product-total" class="sales-muted"></span></div>
    <div id="sales-admin-list" class="sales-admin-list"><div class="sales-skeleton"></div><div class="sales-skeleton"></div></div>
  </section>

  <section id="sales-transactions" class="subtab-content" style="display:none" aria-label="Salg">
    <header class="sales-heading"><div><span class="sales-kicker">Oppgjørshistorikk</span><h2>Salg</h2><p>Gjennomførte og avventende betalinger.</p></div><button type="button" class="btn btn-outline-primary" id="sales-refresh-orders"><i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Oppdater</button></header>
    <div class="sales-ledger-wrap"><table class="table sales-ledger"><thead><tr><th>Ordre</th><th>Tidspunkt</th><th>Betaling</th><th>Status</th><th class="text-end">Sum</th><th></th></tr></thead><tbody id="sales-orders"><tr><td colspan="6"><div class="sales-skeleton"></div></td></tr></tbody></table></div>
  </section>

  <div class="sales-dialog" id="sales-dialog" hidden role="presentation">
    <div class="sales-dialog-backdrop" data-close-dialog></div>
    <section class="sales-dialog-card" role="dialog" aria-modal="true" aria-labelledby="sales-dialog-title">
      <header class="sales-dialog-head"><div><span class="sales-kicker" id="sales-dialog-kicker">Salg</span><h2 id="sales-dialog-title"></h2></div><button type="button" class="sales-dialog-close" data-close-dialog aria-label="Lukk">×</button></header>
      <div id="sales-dialog-body" class="sales-dialog-body"></div>
      <footer id="sales-dialog-foot" class="sales-dialog-foot"></footer>
    </section>
  </div>
</div>
<script src="/assets/entity-form-fields.js"></script>
<script src="/assets/sales-admin.js" defer></script>
