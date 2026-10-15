(function () {
  'use strict';
  const root = document.getElementById('sales-admin');
  if (!root) return;
  const $ = (selector, scope = root) => scope.querySelector(selector);
  const esc = value => String(value ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  const safeUrl = value => {
    try { const url = new URL(String(value || ''), location.origin); return ['http:', 'https:'].includes(url.protocol) ? url.href : ''; } catch (_) { return ''; }
  };
  const money = minor => new Intl.NumberFormat('nb-NO', {style:'currency',currency:'NOK'}).format((Number(minor) || 0) / 100);
  const date = value => value ? new Intl.DateTimeFormat('nb-NO',{dateStyle:'medium',timeStyle:'short'}).format(new Date(value)) : '–';
  const state = {csrf:'',taxRegistered:1,products:[],methods:[],methodError:'',cart:null,pending:null,selectedMethod:'cash',mutationQueue:Promise.resolve(),busy:false,orders:[],pollTimer:0,pollAbort:null,dialog:null,lastFocus:null,productController:null,checkoutUncertain:false,paymentNotice:''};
  const methodName = m => m?.method_type === 'cash' ? 'Kontant' : (m?.method_name || 'Betaling');
  const usableMethods = () => (state.methods || []).filter(m => m.available && ['cash','vipps'].includes(m.method_type));
  function modal(title, content, opts = {}) {
    if (state.dialog?.kind === 'fields' && !closeFields()) return false;
    const dialog = $('#sales-dialog'); state.lastFocus = document.activeElement;
    $('#sales-dialog-title').textContent = title; $('#sales-dialog-kicker').textContent = opts.kicker || 'Salgsrom';
    $('#sales-dialog-body').innerHTML = content; $('#sales-dialog-foot').innerHTML = opts.footer || '';
    dialog.hidden = false; document.body.classList.add('sales-dialog-open'); state.dialog = opts;
    dialog.querySelector('[data-close-dialog]')?.focus(); return true;
  }
  function closeDialog(force = false) {
    if (!force && state.dialog?.kind === 'fields' && !closeFields()) return;
    const dialog = $('#sales-dialog'); dialog.hidden = true; document.body.classList.remove('sales-dialog-open'); state.dialog = null;
    state.productController?.abort(); state.productController = null;
    state.lastFocus?.focus?.();
  }
  async function api(action, {method='GET',data,signal,query={}} = {}) {
    const params = new URLSearchParams({action,...query});
    const response = await fetch(`/api/sales.php?${params}`, {
      method,credentials:'same-origin',signal,
      headers: method === 'POST' ? {'Content-Type':'application/json'} : undefined,
      body: method === 'POST' ? JSON.stringify({...data,csrf_token:state.csrf}) : undefined
    });
    let result;
    try { result = await response.json(); } catch (_) { throw new Error('Serveren svarte med et ugyldig svar. Utkastet er beholdt.'); }
    if (!response.ok || !result.success) {
      const error = new Error(result.message || 'Handlingen kunne ikke fullføres. Utkastet er beholdt.');
      error.status = response.status; error.result = result; throw error;
    }
    return result;
  }
  function setError(message) { const box = $('#sales-cart-error'); box.hidden = !message; box.textContent = message || ''; }
  function emptyCart() { return {id:null,revision:'',items:[],total_minor:0,subtotal_minor:0,tax_minor:0}; }
  function updateCart(cart) {
    if (!cart) return;
    state.cart = cart;
    renderCart();
  }
  function cartCount() { return (state.cart?.items || []).reduce((sum,item) => sum + Number(item.quantity || 0),0); }
  function renderMethods() {
    const select = $('#sales-method'); const methods = usableMethods();
    if (!methods.some(m => m.method_type === 'cash')) {
      select.innerHTML = '<option value="">Ingen betalingsmåte tilgjengelig</option>';
      $('#sales-checkout').disabled = true; $('#sales-method-note').textContent = 'Ingen aktiv betalingsmåte er konfigurert.';
      return;
    }
    if (!methods.some(m => m.method_type === state.selectedMethod)) state.selectedMethod = 'cash';
    select.innerHTML = methods.map(m => `<option value="${esc(m.method_type)}" ${m.method_type === state.selectedMethod ? 'selected' : ''}>${esc(methodName(m))}${m.is_demo ? ' · test' : ''}</option>`).join('');
    const onlineRequested = state.products.some(p => ['online','both'].includes(p.visibility) && Number(p.is_active) === 1);
    $('#sales-method-note').textContent = onlineRequested && (!methods.some(m => m.method_type === 'vipps') || state.methodError) ?
      `Nettbetaling er ikke tilgjengelig: ${state.methodError || 'ingen brukbar Vipps-tilkobling er konfigurert.'}` : '';
  }
  function renderProducts() {
    const term = ($('#sales-search').value || '').trim().toLocaleLowerCase('nb');
    const products = state.products.filter(p => Number(p.is_active) === 1 && ['kiosk','both'].includes(p.visibility || 'kiosk') &&
      (!term || `${p.name} ${p.sku || ''} ${p.barcode || ''}`.toLocaleLowerCase('nb').includes(term)));
    const host = $('#sales-products');
    if (!products.length) {
      host.innerHTML = `<div class="sales-empty sales-grid-empty"><strong>${state.products.length ? 'Ingen treff' : 'Ingen produkter ennå'}</strong><span>${state.products.length ? 'Prøv et annet søkeord.' : 'Legg til varer i Produkter og lager for å starte salget.'}</span></div>`;
      return;
    }
    host.innerHTML = products.map(p => {
      const img = safeUrl(p.img_product); const qty = Number(p.stock_quantity);
      const unavailable = Number(p.is_active) !== 1 || qty <= 0;
      const stock = !Number.isFinite(qty) ? '' : qty <= 0 ? 'Utsolgt' : qty <= Number(p.reorder_level || p.low_stock_threeshold || 0) ? `Lav beholdning · ${qty}` : `${qty} på lager`;
      return `<button type="button" class="sales-product-tile${unavailable?' is-unavailable':''}" data-product="${esc(p.id)}" ${unavailable?'disabled':''} aria-label="${esc(p.name)}, ${esc(money(p.price_minor))}${stock?', '+esc(stock):''}">
        <span class="sales-product-image">${img ? `<img src="${esc(img)}" alt="" loading="lazy">` : '<span class="sales-product-placeholder" aria-hidden="true"><i class="bi bi-box-seam"></i></span>'}${unavailable?'<span class="sales-stock-overlay">Utsolgt</span>':''}</span>
        <span class="sales-product-meta"><strong>${esc(p.name)}</strong><span class="sales-product-price">${esc(money(p.price_minor))}</span>${stock ? `<small class="${qty <= 0?'stock-out':qty <= Number(p.reorder_level || p.low_stock_threeshold || 0)?'stock-low':''}">${esc(stock)}</small>` : ''}</span>
      </button>`;
    }).join('');
  }
  function renderCart() {
    const cart = state.cart || emptyCart(), items = cart.items || [];
    $('#sales-cart-count').textContent = cartCount();
    $('#sales-cart-lines').innerHTML = items.length ? items.map(item => `<article class="sales-cart-line">
      <div class="sales-line-main"><strong>${esc(item.name)}</strong><span>${esc(money(item.unit_price_minor))} / stk.</span>${item.notes ? `<small>${esc(item.notes)}</small>` : ''}${item.form_data && Object.keys(item.form_data).length ? `<small>${Object.entries(item.form_data).map(([k,v])=>`${esc(k)}: ${esc(v)}`).join(' · ')}</small>` : ''}</div>
      <div class="sales-line-actions"><div class="sales-quantity" aria-label="Antall ${esc(item.name)}"><button type="button" data-line-qty="${esc(item.id)}" data-delta="-1" aria-label="Reduser antall">−</button><span>${esc(item.quantity)}</span><button type="button" data-line-qty="${esc(item.id)}" data-delta="1" aria-label="Øk antall">+</button></div><strong>${esc(money(item.total_minor))}</strong><button class="sales-remove" type="button" data-remove-line="${esc(item.id)}" aria-label="Fjern ${esc(item.name)} fra handlekurven"><i class="bi bi-trash3" aria-hidden="true"></i></button></div>
    </article>`).join('') : '<div class="sales-empty"><strong>Ingen varer ennå</strong><span>Velg et produkt for å starte salget.</span></div>';
    $('#sales-subtotal').textContent = money(cart.subtotal_minor); $('#sales-tax').textContent = money(cart.tax_minor); $('#sales-total').textContent = money(cart.total_minor);
    $('#sales-tax-row').hidden = !state.taxRegistered;
    const tender = Math.round((Number($('#sales-tendered').value) || 0) * 100);
    const change = Math.max(0,tender - Number(cart.total_minor || 0));
    $('#sales-change').textContent = money(change);
    const pending = !!state.pending;
    $('#sales-checkout').disabled = !items.length || state.busy || pending || state.checkoutUncertain || !usableMethods().some(m => m.method_type === state.selectedMethod) ||
      (state.selectedMethod === 'cash' && (tender < Number(cart.total_minor || 0) || !$('#sales-tendered').value)) ||
      (state.selectedMethod === 'vipps' && !$('#sales-phone').value.trim());
    $('#sales-checkout').textContent = state.checkoutUncertain ? 'Avventer betalingsstatus' : (state.selectedMethod === 'vipps' ? 'Send Vipps-betalingsforespørsel' : 'Bekreft kontantsalg');
    $('#sales-cash-panel').hidden = state.selectedMethod !== 'cash';
    $('#sales-vipps-panel').hidden = state.selectedMethod !== 'vipps';
    $('#sales-next-customer').hidden = !state.completedOrder;
    updatePaymentStatus();
  }
  function updatePaymentStatus() {
    const box = $('#sales-payment-status'), order = state.pending || (state.completedOrder?.payment_method === 'vipps' ? state.completedOrder : null);
    if (!order) {
      if(state.paymentNotice){box.hidden=false;box.className='sales-payment-status';box.textContent=state.paymentNotice;}
      else box.hidden = true;
      return;
    }
    const status = order.payment_status || order.status || 'unknown';
    box.hidden = false; box.className = `sales-payment-status status-${esc(status)}`;
    box.innerHTML = `<strong>${esc(statusLabel(status))}</strong><span>${esc(order.order_number ? `Ordre ${order.order_number}` : 'Betalingsstatus synkroniseres')}</span>${state.pending ? '<button type="button" id="sales-check-status">Sjekk status</button>' : ''}`;
    $('#sales-cancel-payment').hidden = !state.pending || ['paid','captured'].includes(status);
  }
  function statusLabel(status) {
    return ({pending:'Venter på betaling',unknown:'Ukjent status – sjekk igjen',awaiting:'Venter på Vipps',authorized:'Betaling godkjent – sluttfører',captured:'Betalt',paid:'Betalt',aborted:'Avbrutt',cancelled:'Avbrutt',expired:'Utløpt',declined:'Avvist'})[status] || `Status: ${status}`;
  }
  async function refreshCart() {
    const r = await api('cart'); updateCart(r.cart);
    state.pending = r.pending_order && !isTerminalPayment(r.pending_order) ? r.pending_order : null;
    if (state.pending || !(r.cart?.items || []).length) state.checkoutUncertain = false;
    if (state.pending) startPolling(); else stopPolling();
    renderCart();
  }
  function isTerminalPayment(order) {
    const status=String(order?.payment_status||'').toLowerCase(), orderStatus=String(order?.status||'').toLowerCase();
    return ['cancelled','aborted','declined','expired'].includes(status) ||
      ['cancelled','declined','expired','aborted'].includes(orderStatus);
  }
  function queueMutation(action, data) {
    const mutationId = crypto.randomUUID();
    state.mutationQueue = state.mutationQueue.then(async () => {
      const payload = {...data,revision:state.cart?.revision || '',mutation_id:mutationId};
      state.busy = true; renderCart(); setError('');
      let succeeded = false;
      try {
        const result = await api(action,{method:'POST',data:payload});
        updateCart(result.cart); succeeded = true; setError('');
      } catch (error) {
        setError(error.message);
        try { await refreshCart(); } catch (_) {}
        if (error.status !== 409) {
          // Preserve the exact mutation id/payload for an explicit safe retry.
          $('#sales-cart-error').dataset.retryAction = action;
          $('#sales-cart-error').dataset.retryPayload = JSON.stringify(payload);
          $('#sales-cart-error').innerHTML = `${esc(error.message)} <button type="button" id="sales-retry-mutation">Prøv samme handling igjen</button>`;
        }
      } finally { state.busy = false; renderCart(); }
      return succeeded;
    });
    return state.mutationQueue;
  }
  async function addProduct(product) {
    const controller = new AbortController(); state.productController?.abort(); state.productController = controller;
    let fields = [];
    try { if (product.has_fields) fields = (await api('fields',{query:{product_id:product.id},signal:controller.signal})).fields || []; }
    catch (e) { if (e.name !== 'AbortError') setError(e.message); return; }
    if (state.productController !== controller) return;
    if (!fields.length) { queueMutation('add',{product_id:product.id,quantity:1,notes:'',form_data:{}}); return; }
    const control = field => {
      const name = esc(field.field_name), label = esc(field.field_label || field.field_name), required = Number(field.is_required) ? 'required' : '';
      const opts = Array.isArray(field.options) ? field.options : (typeof field.options === 'string' ? (()=>{try{return JSON.parse(field.options)}catch(_){return []}})() : []);
      if (field.field_type === 'select') return `<label>${label}${required?'<em>Påkrevd</em>':''}<select class="form-select" name="${name}" ${required}><option value="">Velg</option>${opts.map(o=>`<option value="${esc(typeof o==='object'?o.value:o)}">${esc(typeof o==='object'?(o.label||o.value):o)}</option>`).join('')}</select></label>`;
      if (field.field_type === 'textarea') return `<label>${label}<textarea name="${name}" class="form-control" ${required}>${esc(field.default_value)}</textarea></label>`;
      const type = ['email','tel','number','date'].includes(field.field_type) ? field.field_type : 'text';
      return `<label>${label}${required?'<em>Påkrevd</em>':''}<input class="form-control" type="${type}" name="${name}" value="${esc(field.default_value)}" ${required} ${type==='number'?'step="any"':''}></label>`;
    };
    modal(product.name,`<p class="sales-muted">Tilpass varen før den legges i handlekurven.</p><form id="sales-customize" class="sales-form-grid">${fields.map(control).join('')}<label class="sales-span">Merknad <textarea name="notes" class="form-control" rows="2" maxlength="500"></textarea></label><label>Antall <input class="form-control" name="quantity" type="number" min="1" step="1" value="1" required></label></form>`,{kind:'customize',footer:'<button type="button" class="btn btn-outline-secondary" data-close-dialog>Tilbake</button><button type="button" id="sales-customize-add" class="btn btn-primary">Legg i handlekurv</button>'});
    $('#sales-customize-add').addEventListener('click',async event => {
      const form = $('#sales-customize'); if (!form.reportValidity()) return;
      const values = Object.fromEntries(new FormData(form).entries()), quantity = Math.max(1,Math.floor(Number(values.quantity) || 1)); delete values.quantity;
      const notes = values.notes || ''; delete values.notes;
      event.currentTarget.disabled=true;
      const ok=await queueMutation('add',{product_id:product.id,quantity,notes,form_data:values});
      if(ok)closeDialog(true);else if(event.currentTarget.isConnected)event.currentTarget.disabled=false;
    });
  }
  function productForm(product = null) {
    const p = product || {};
    const visibility = ['kiosk','online','both'].includes(p.visibility) ? p.visibility : 'kiosk';
    const onlineUsable = usableMethods().some(m=>m.method_type==='vipps') && !state.methodError;
    const onlineConflict = !!product && !onlineUsable && ['online','both'].includes(visibility);
    const localDateTime = value => {
      if(!value)return '';
      const d=new Date(String(value).replace(' ','T'));
      if(Number.isNaN(d.getTime()))return '';
      return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}T${String(d.getHours()).padStart(2,'0')}:${String(d.getMinutes()).padStart(2,'0')}`;
    };
    const tax = state.taxRegistered ? `<details class="sales-form-details"><summary>Mva og avgift</summary><div class="sales-form-grid"><label>Mva-klasse<select class="form-select" name="tax_class"><option value="standard">Standard</option><option value="exempt">Unntatt</option><option value="zero">Nullsats</option></select></label><label>Mva-sats (%)<input class="form-control" name="tax_rate" type="number" min="0" max="100" step=".01" value="${esc(p.tax_rate ?? '')}"></label><label class="sales-check"><input type="checkbox" name="item_taxable" ${Number(p.item_taxable) ? 'checked' : ''}> Mva-pliktig</label></div></details>` : '';
    const body = `<form id="sales-product-form" class="sales-product-form">
      <label class="sales-span">Produktnavn <input class="form-control" name="name" required maxlength="180" value="${esc(p.name)}" autocomplete="off"></label>
      <div class="sales-form-grid"><label>Salgspris inkl. mva <div class="input-group"><input class="form-control" name="selling_price" type="number" min="0" step=".01" required value="${esc(p.selling_price ?? (Number(p.price_minor||0)/100||''))}"><span class="input-group-text">kr</span></div></label><label>Kostpris <div class="input-group"><input class="form-control" name="cost_price" type="number" min="0" step=".01" value="${esc(p.cost_price ?? '')}"><span class="input-group-text">kr</span></div></label>${product ? '' : '<label>Startbeholdning<input class="form-control" name="stock_quantity" type="number" min="0" step="1" value="0"></label>'}</div>
      <details class="sales-form-details"><summary>Flere produktoplysninger</summary><div class="sales-form-grid"><label class="sales-span">Beskrivelse<textarea class="form-control" name="description" rows="2">${esc(p.description)}</textarea></label><label>Bildeadresse<input class="form-control" type="url" name="img_product" placeholder="https://…" value="${esc(p.img_product)}" autocomplete="off"></label><label>Enhet<input class="form-control" name="unit" value="${esc(p.unit || 'stk.')}"></label><label>Varsel ved lav beholdning<input class="form-control" name="reorder_level" type="number" min="0" step="1" value="${esc(p.reorder_level ?? '')}"></label><label>Lavlagergrense<input class="form-control" name="low_stock_threeshold" type="number" min="0" step="1" value="${esc(p.low_stock_threeshold ?? '')}"></label><label>Rabatt (%)<input class="form-control" name="discount_percent" type="number" min="0" max="100" step=".01" value="${esc(p.discount_percent ?? '')}"></label><label>Rabatt starter<input class="form-control" name="discount_starts_at" type="datetime-local" value="${esc(localDateTime(p.discount_starts_at))}"></label><label>Rabatt slutter<input class="form-control" name="discount_ends_at" type="datetime-local" value="${esc(localDateTime(p.discount_ends_at))}"></label></div></details>
      ${onlineConflict?'<div class="sales-error" id="sales-online-warning" role="alert">Nettbetaling er ikke tilgjengelig uten aktiv Vipps. Dette produktet kan ikke lagres før du velger «Kasse og kiosk».</div>':''}
      <label>Synlighet<select class="form-select" name="visibility"><option value="kiosk" ${visibility==='kiosk'?'selected':''}>Kasse og kiosk</option><option value="online" ${visibility==='online'?'selected':''} ${!onlineUsable&&!product?'disabled':''}>Nettbutikk${!onlineUsable?' · Vipps mangler':''}</option><option value="both" ${visibility==='both'?'selected':''} ${!onlineUsable&&!product?'disabled':''}>Kasse og nettbutikk${!onlineUsable?' · Vipps mangler':''}</option></select></label>
      <div class="sales-form-grid"><label class="sales-check"><input type="checkbox" name="is_active" ${p.id ? Number(p.is_active)?'checked':'' : 'checked'}> Aktiv</label></div>
      ${tax}
      ${product ? `<section class="sales-code-panel"><div><span class="sales-kicker">Systemgenerert · skrivebeskyttet</span><div class="sales-code-row"><span>SKU</span><code>${esc(p.sku || 'Opprettes ved lagring')}</code><button type="button" class="btn btn-sm btn-outline-secondary" data-copy="${esc(p.sku || '')}" ${p.sku?'':'disabled'}>Kopier</button></div><div class="sales-code-row"><span>Strekkode</span><code>${esc(p.barcode || 'Opprettes ved lagring')}</code><button type="button" class="btn btn-sm btn-outline-secondary" data-copy="${esc(p.barcode || '')}" ${p.barcode?'':'disabled'}>Kopier</button></div></div>${p.id?`<img class="sales-barcode" src="/api/sales_barcode.php?product_id=${encodeURIComponent(p.id)}" alt="Code 128 strekkode for ${esc(p.name)}" loading="lazy">`:''}<button type="button" class="btn btn-sm btn-outline-primary" data-print-barcode="${esc(p.id || '')}" ${p.id?'':'disabled'}>Skriv ut etikett</button></section>
        <div class="sales-field-tools"><div><strong>Tilpasningsfelt</strong><small>Kundespørsmål før varen legges i kassen.</small></div><button type="button" class="btn btn-sm btn-outline-primary" id="sales-open-fields" ${p.id?'':'disabled'}>Rediger felt</button></div><div id="sales-fields-host" hidden></div>` : '<p class="sales-muted small mb-0">SKU og strekkode opprettes automatisk når produktet lagres.</p>'}
      <div class="sales-error" id="sales-product-error" role="alert" hidden></div>
    </form>`;
    modal(product ? 'Rediger produkt' : 'Nytt produkt',body,{kind:'product',footer:`<button type="button" class="btn btn-outline-secondary" data-close-dialog>Avbryt</button><button type="button" class="btn btn-primary" id="sales-save-product">${product?'Lagre endringer':'Opprett produkt'}</button>`});
    const form = $('#sales-product-form');
    if (product && state.taxRegistered) {form.elements.tax_class.value = product.tax_class || 'standard';}
    form.elements.visibility.addEventListener('change',e => {
      const online = ['online','both'].includes(e.target.value), ok = usableMethods().some(m => m.method_type === 'vipps') && !state.methodError;
      if (online && !ok) { $('#sales-product-error').hidden = false; $('#sales-product-error').textContent = 'Nettbutikk kan ikke aktiveres uten en brukbar Vipps-betalingsmåte. Velg kun kasse eller konfigurer Vipps først.'; e.target.value = product?.visibility || 'kiosk'; }
      else $('#sales-product-error').hidden = true;
    });
    $('#sales-save-product').addEventListener('click', async () => {
      if (!form.reportValidity()) return;
      const button = $('#sales-save-product'), errorBox = $('#sales-product-error');
      if (state.dialog?.kind === 'fields' && (state.dialog.instance?.isBusy?.() || state.dialog.instance?.isDirty?.())) {
        errorBox.hidden=false;
        errorBox.textContent='Lagre eller lukk skjemafeltredigeringen før du lagrer produktet. Utkastene er beholdt.';
        return;
      }
      if (!onlineUsable && ['online','both'].includes(form.elements.visibility.value)) {
        errorBox.hidden=false;errorBox.textContent='Nettbutikk krever en brukbar Vipps-betalingsmåte. Velg «Kasse og kiosk» før du lagrer.';return;
      }
      const fd = new FormData(form), payload = Object.fromEntries(fd.entries());
      ['is_active','item_taxable'].forEach(key => payload[key] = fd.has(key) ? 1 : 0);
      ['selling_price','cost_price','stock_quantity','tax_rate','reorder_level','low_stock_threeshold','discount_percent'].forEach(key => {if (payload[key] !== undefined && payload[key] !== '') payload[key] = Number(payload[key]);});
      ['discount_starts_at','discount_ends_at'].forEach(key=>{if(payload[key])payload[key]=payload[key].replace('T',' ')+':00';});
      if (!state.taxRegistered) ['tax_class','tax_rate','item_taxable'].forEach(k=>delete payload[k]);
      if (product) {
        if(!product.revision){errorBox.hidden=false;errorBox.textContent='Produktrevisjon mangler. Last inn produktet på nytt før du lagrer.';return;}
        payload.id = product.id; payload.expected_revision = String(product.revision);
      }
      button.disabled = true; button.textContent = 'Lagrer…'; errorBox.hidden = true;
      try {
        const result = await api('product_save',{method:'POST',data:payload});
        const index = state.products.findIndex(row=>row.id===result.product.id);
        if(index>=0) state.products[index]=result.product; else state.products.unshift(result.product);
        closeDialog(true); renderProducts(); renderAdminProducts();
      } catch (e) {
        errorBox.hidden=false;
        errorBox.textContent = e.status===409
          ? `Produktet er endret et annet sted. Utkastet er vist i skjemaet og beholdt. Last inn siden på nytt før du prøver igjen. ${e.message}`
          : `${e.message} Utkastet er beholdt.`;
        if(e.status===409) form.querySelector('[name="name"]')?.focus();
      }
      finally { if(button.isConnected){button.disabled=false;button.textContent=product?'Lagre endringer':'Opprett produkt';} }
    });
    if (product) {
      $('#sales-open-fields').addEventListener('click',()=>{
        if(state.dialog?.kind==='fields') {
          if(closeFields()){$('#sales-fields-host').hidden=true;$('#sales-open-fields').textContent='Rediger felt';}
          return;
        }
        openFields(product);
      });
      form.querySelectorAll('[data-copy]').forEach(btn=>btn.addEventListener('click',async()=>{
        try {await navigator.clipboard.writeText(btn.dataset.copy); btn.textContent='Kopiert'; setTimeout(()=>{if(btn.isConnected)btn.textContent='Kopier'},1200);}
        catch(_){btn.textContent='Kopiering utilgjengelig';}
      }));
      form.querySelector('[data-print-barcode]')?.addEventListener('click',()=>printBarcode(product));
    }
  }
  function printBarcode(product) {
    const url = `/api/sales_barcode.php?product_id=${encodeURIComponent(product.id)}`;
    const win = window.open('', '_blank', 'width=440,height=320');
    if (!win) { $('#sales-product-error').hidden=false; $('#sales-product-error').textContent='Etikettvisning ble blokkert av nettleseren. Tillat utskrift for denne siden.'; return; }
    win.opener = null;
    const image = document.createElement('img'); image.src = url; image.alt = `Code 128 ${product.name}`;
    image.style.cssText='display:block;max-width:100%;height:100px;margin:30px auto 12px';
    const title = win.document.createElement('h1'); title.textContent = product.name; title.style.cssText='text-align:center;font:16px sans-serif';
    const code = win.document.createElement('p'); code.textContent = product.barcode || ''; code.style.cssText='text-align:center;font:13px monospace';
    win.document.body.append(title,image,code);
    image.onload = () => {win.focus();win.print();};
  }
  function renderAdminProducts() {
    const term = ($('#sales-admin-search').value||'').trim().toLocaleLowerCase('nb');
    const products = state.products.filter(p=>!term || `${p.name} ${p.sku||''} ${p.barcode||''}`.toLocaleLowerCase('nb').includes(term));
    $('#sales-product-total').textContent = `${products.length} produkt${products.length===1?'':'er'}`;
    const host = $('#sales-admin-list');
    if (!products.length) {host.innerHTML='<div class="sales-empty"><strong>Ingen produkter funnet</strong><span>Opprett et produkt, eller prøv et annet søk.</span></div>';return;}
    host.innerHTML=products.map(p=>`<article class="sales-admin-row"><div class="sales-admin-photo">${safeUrl(p.img_product)?`<img src="${esc(safeUrl(p.img_product))}" alt="" loading="lazy">`:'<i class="bi bi-box-seam" aria-hidden="true"></i>'}</div><div class="sales-admin-name"><strong>${esc(p.name)}</strong><small>${esc(p.sku||'SKU opprettes ved lagring')} ${p.barcode?`· ${esc(p.barcode)}`:''}</small></div><div class="sales-admin-price"><strong>${esc(money(p.price_minor))}</strong><small>Kost ${esc(money(Math.round(Number(p.cost_price||0)*100)))}</small></div><div class="sales-stock-cell"><strong>${esc(p.stock_quantity ?? '—')}</strong><small>på lager</small></div><span class="sales-status-pill ${Number(p.is_active)?'is-active':'is-inactive'}">${Number(p.is_active)?'Aktiv':'Pauset'}</span><div class="sales-admin-actions"><button class="btn btn-sm btn-outline-primary" type="button" data-edit-product="${esc(p.id)}">Rediger</button><button class="btn btn-sm btn-outline-secondary" type="button" data-stock-product="${esc(p.id)}">Juster lager</button></div></article>`).join('');
  }
  function stockDialog(product) {
    modal('Juster lager',`<p class="sales-muted">${esc(product.name)} · nå ${esc(product.stock_quantity)} på lager.</p><form id="sales-stock-form" class="sales-form-grid"><label>Nytt lagerantall<input class="form-control" name="quantity" type="number" min="0" step="1" required value="${esc(product.stock_quantity)}"></label><label class="sales-span">Årsak til justering<textarea class="form-control" name="reason" rows="3" required maxlength="500" placeholder="For eksempel: varetelling 14. juni"></textarea></label><div id="sales-stock-error" class="sales-error sales-span" role="alert" hidden></div></form>`,{kind:'stock',footer:'<button class="btn btn-outline-secondary" type="button" data-close-dialog>Avbryt</button><button class="btn btn-primary" type="button" id="sales-save-stock">Lagre lagerantall</button>'});
    $('#sales-save-stock').addEventListener('click',async()=>{
      const form=$('#sales-stock-form'); if(!form.reportValidity())return;
      const button=$('#sales-save-stock'), error=$('#sales-stock-error');button.disabled=true;
      try {const r=await api('stock',{method:'POST',data:{product_id:product.id,quantity:Number(form.elements.quantity.value),reason:form.elements.reason.value.trim(),expected_quantity:Number(product.stock_quantity)}});const i=state.products.findIndex(p=>p.id===product.id);if(i>=0)state.products[i]=r.product;closeDialog(true);renderProducts();renderAdminProducts();}
      catch(e){error.hidden=false;error.textContent=`${e.message} Utkastet er beholdt.`;}finally{if(button.isConnected)button.disabled=false;}
    });
  }
  async function openFields(product) {
    if (!window.EntityFormFields?.mount) {$('#sales-product-error').hidden=false;$('#sales-product-error').textContent='Skjemafeltredigering er ikke tilgjengelig på denne siden.';return;}
    const host=$('#sales-fields-host'); host.hidden=false;host.innerHTML='';
    const instance=window.EntityFormFields.mount(host,{entityType:'sales_product',entityId:product.id,eventLayout:true,onSaved:()=>{
      host.hidden=true;state.dialog={kind:'product'};$('#sales-open-fields').textContent='Rediger felt';
      const status=document.createElement('p');status.className='sales-muted small mt-2';status.setAttribute('role','status');status.textContent='Skjemafeltene er lagret.';host.parentElement.insertBefore(status,host);
    }});
    $('#sales-open-fields').textContent='Lukk feltredigering';
    state.dialog={kind:'fields',instance};
    try {await instance.refresh();} catch (_) {}
  }
  function closeFields() {
    if(state.dialog?.kind!=='fields') return true;
    const instance=state.dialog.instance;
    if(instance?.isBusy?.())return false;
    if(instance?.isDirty?.() && !window.confirm('Du har ulagrede feltendringer. Forkaste dem og lukke?'))return false;
    state.dialog={kind:'product'};
    // Editor intentionally remains open until its save callback or explicit product dialog close.
    return true;
  }
  function receipt(order) {
    const items=order.items||[];
    modal(`Kvittering ${order.order_number||''}`,`<div class="sales-receipt"><div class="sales-receipt-top"><strong>${esc(order.order_number||'Salg')}</strong><span>${esc(date(order.created_at))}</span></div><div class="sales-receipt-lines">${items.map(item=>`<div><span>${esc(item.description)} <small>× ${esc(item.quantity)}</small>${item.sku?`<small class="d-block">${esc(item.sku)}</small>`:''}${item.notes?`<small class="d-block">${esc(item.notes)}</small>`:''}${item.form_data?`<small class="d-block">${Object.entries(item.form_data).map(([k,v])=>`${esc(k)}: ${esc(v)}`).join(' · ')}</small>`:''}</span><strong>${esc(money(item.total_minor))}</strong></div>`).join('')}</div><div class="sales-receipt-sums"><div><span>Delsum</span><strong>${esc(money(order.subtotal_minor))}</strong></div>${state.taxRegistered?`<div><span>Mva</span><strong>${esc(money(order.tax_minor))}</strong></div>`:''}<div class="total"><span>Sum</span><strong>${esc(money(order.total_minor))}</strong></div>${order.received_minor!=null?`<div><span>Mottatt</span><strong>${esc(money(order.received_minor))}</strong></div><div><span>Vekslepenger</span><strong>${esc(money(order.change_minor))}</strong></div>`:''}</div><p class="sales-receipt-foot">${esc(methodName({method_type:order.payment_method,method_name:order.payment_method}))} · ${esc(statusLabel(order.payment_status))}</p></div>`,{kind:'receipt',footer:'<button class="btn btn-outline-secondary" type="button" data-close-dialog>Lukk</button><button class="btn btn-primary" type="button" id="sales-print-receipt">Skriv ut</button>'});
    if(order.tenant_admin_id) {
      const actor=document.createElement('p');actor.className='sales-receipt-foot';
      actor.style.overflowWrap='anywhere';
      actor.textContent=`Registrert av: ${order.tenant_admin_name || order.tenant_admin_id}`;
      $('.sales-receipt').append(actor);
    }
    $('#sales-print-receipt').addEventListener('click',()=>window.print());
    if(order.payment_status==='paid') {
      const send=document.createElement('button');send.type='button';send.className='btn btn-outline-primary';
      send.id='sales-email-receipt';
      send.textContent=order.receipt_email_enabled?'E-postkvittering':'E-post ikke aktivert';
      send.disabled=!order.receipt_email_enabled;
      send.title=order.receipt_email_reason || 'Send kvittering ved behov';
      $('#sales-dialog-foot').append(send);
      send.addEventListener('click',()=>{
        if($('#sales-receipt-email-form'))return;
        const form=document.createElement('form');form.id='sales-receipt-email-form';form.className='sales-form-grid sales-receipt-email-form';
        form.innerHTML='<label class="sales-span">Mottakerens e-postadresse<input class="form-control" type="email" name="sales_receipt_recipient" autocomplete="off" maxlength="254" required></label><button class="btn btn-primary" type="submit">Send kvittering</button><p class="sales-span sales-muted" role="status" aria-live="polite"></p>';
        $('#sales-dialog-body').append(form);form.elements.sales_receipt_recipient.focus();
        const mutation=crypto.randomUUID(),submit=form.querySelector('button'),notice=form.querySelector('[role="status"]');
        form.addEventListener('submit',async event=>{
          event.preventDefault();if(!form.reportValidity() || submit.disabled)return;
          submit.disabled=true;form.elements.sales_receipt_recipient.disabled=true;notice.textContent='Legger kvitteringen i e-postkøen …';
          try {
            const result=await api('receipt_email',{method:'POST',data:{order_id:order.id,email:form.elements.sales_receipt_recipient.value.trim(),mutation_id:mutation}});
            notice.textContent=result.message || 'Kvitteringen er lagt i e-postkøen';submit.textContent='Lagt i kø';
          } catch(error) {
            notice.textContent=error.message;submit.disabled=false;form.elements.sales_receipt_recipient.disabled=false;
          }
        });
      });
    }
  }
  async function checkout() {
    const cart=state.cart||emptyCart(), method=state.selectedMethod, total=Number(cart.total_minor||0);
    if(method==='cash'){
      const received=Math.round((Number($('#sales-tendered').value)||0)*100);
      if(received<total || !$('#sales-tendered').value)return;
      const change=received-total;
      modal('Bekreft kontantsalg',`<div class="sales-confirm-total"><span>Å betale</span><strong>${esc(money(total))}</strong></div><div class="sales-confirm-row"><span>Mottatt</span><strong>${esc(money(received))}</strong></div><div class="sales-confirm-row"><span>Vekslepenger</span><strong>${esc(money(change))}</strong></div><p class="sales-muted mt-3">Kontroller beløpene før salget registreres.</p>`,{kind:'confirm-cash',footer:'<button type="button" class="btn btn-outline-secondary" data-close-dialog>Tilbake</button><button type="button" class="btn btn-success" id="sales-confirm-cash">Registrer kontantsalg</button>'});
      $('#sales-confirm-cash').addEventListener('click',()=>sendCheckout({revision:cart.revision,payment_method:'cash',received_minor:received,expected_total_minor:total}));
    } else {
      const methodConfig=usableMethods().find(m=>m.method_type==='vipps');
      if(!methodConfig)return;
      const phone=$('#sales-phone').value.trim();
      if(!phone)return;
      modal('Send Vipps-forespørsel',`<p>En betalingsforespørsel på <strong>${esc(money(total))}</strong> sendes til <strong>${esc(phone)}</strong>.</p><p class="sales-muted">Betalingsstatus vises her. Ikke lukk kassen mens betalingen avklares.</p>`,{kind:'confirm-vipps',footer:'<button type="button" class="btn btn-outline-secondary" data-close-dialog>Tilbake</button><button type="button" class="btn btn-primary" id="sales-confirm-vipps">Send forespørsel</button>'});
      $('#sales-confirm-vipps').addEventListener('click',()=>sendCheckout({revision:cart.revision,payment_method:'vipps',gateway_id:methodConfig.id,phone,expected_total_minor:total}));
    }
  }
  async function sendCheckout(payload) {
    const button=$('#sales-confirm-cash')||$('#sales-confirm-vipps'); if(button){button.disabled=true;button.textContent='Behandler…';}
    try {
      const r=await api('checkout',{method:'POST',data:payload});
      closeDialog(true);
      state.paymentNotice='';
      if(payload.payment_method==='cash' && r.order?.payment_status==='paid') {
        state.cart=emptyCart();state.completedOrder=r.order;$('#sales-tendered').value='';setError('');renderCart();await refreshOrders();receipt(r.order);
      } else {state.pending=r.order;state.completedOrder=null;setError('');renderCart();startPolling();}
    } catch(e) {
      state.checkoutUncertain=true;
      const body=$('#sales-dialog-body'); body.insertAdjacentHTML('beforeend',`<div class="sales-error" role="alert">${esc(e.message)} Vi undersøker om betalingen ble registrert. Ikke send betalingen på nytt.</div><button type="button" class="btn btn-outline-primary mt-2" id="sales-refresh-after-checkout">Sjekk kasse og betalingsstatus</button>`);
      if(button?.isConnected){button.disabled=true;button.textContent='Venter på avklaring';}
      try { await refreshCart(); } catch (_) {}
      $('#sales-refresh-after-checkout').addEventListener('click',async()=>{try{await refreshCart();if(state.pending){state.checkoutUncertain=false;closeDialog(true);renderCart();}else if(!(state.cart?.items||[]).length){state.checkoutUncertain=false;closeDialog(true);renderCart();await refreshOrders();}else{$('#sales-dialog-body').insertAdjacentHTML('beforeend','<p class="sales-muted mt-2">Salget står fortsatt i kassen. Kontroller Salg før et nytt betalingsforsøk.</p>');}}catch(err){setError(err.message);}});
    }
  }
  function stopPolling() {clearTimeout(state.pollTimer);state.pollTimer=0;state.pollAbort?.abort();state.pollAbort=null;}
  function paymentPanelShown() {
    const view=$('#sales-varesalg');
    return !document.hidden && root.getClientRects().length>0 && !!view && !view.hidden && getComputedStyle(view).display!=='none';
  }
  function startPolling() {
    stopPolling(); if(!state.pending || !paymentPanelShown())return;
    state.pollTimer=setTimeout(pollPayment,2500);
  }
  async function pollPayment() {
    if(!state.pending || !paymentPanelShown() || state.pollAbort)return;
    const controller=new AbortController();state.pollAbort=controller;
    try {await checkPayment(controller.signal);} catch(e){if(e.name!=='AbortError')$('#sales-payment-status').dataset.pollError='1';}
    finally {if(state.pollAbort===controller)state.pollAbort=null;if(state.pending&&paymentPanelShown())state.pollTimer=setTimeout(pollPayment,6000);}
  }
  async function checkPayment(signal) {
    if(!state.pending?.id)return;
    if(state.pollAbort && signal !== state.pollAbort.signal)return;
    const r=await api('payment_status',{method:'POST',data:{order_id:state.pending.id},signal});
    const order=r.order;state.pending=null;
    if(order.payment_status==='paid') {state.paymentNotice='';state.completedOrder=order;await refreshCart();await refreshOrders();receipt(order);}
    else if(isTerminalPayment(order)) {
      state.completedOrder=null;
      await refreshCart();
      state.paymentNotice=`${statusLabel(order.payment_status || order.status)}. Den opprinnelige handlekurven er gjenopprettet; kontroller varene før du prøver igjen.`;
      renderCart();
    }
    else {state.pending=order;renderCart();}
    if(state.pending&&!signal)startPolling();
  }
  async function cancelPayment() {
    if(!state.pending)return;
    stopPolling();
    const button=$('#sales-cancel-payment');button.disabled=true;
    try {
      const r=await api('cancel_payment',{method:'POST',data:{order_id:state.pending.id}});
      if(isTerminalPayment(r.order)) {
        // The backend has definitively cancelled the provider payment and reopened
        // the original lines. Refresh before enabling checkout again.
        state.pending=r.order;
        renderCart();
        await refreshCart();
        state.completedOrder=null;
        state.paymentNotice=`${statusLabel(r.order.payment_status || r.order.status)}. Den opprinnelige handlekurven er gjenopprettet; kontroller varene før du prøver igjen.`;
        renderCart();
      } else {
        state.pending=r.order;renderCart();if(state.pending)startPolling();
      }
    }
    catch(e){$('#sales-payment-status').insertAdjacentHTML('beforeend',`<span role="alert">${esc(e.message)}</span>`);}
    finally{button.disabled=false;}
  }
  async function refreshOrders() {
    const host=$('#sales-orders');host.innerHTML='<tr><td colspan="6"><div class="sales-skeleton"></div></td></tr>';
    try {
      const r=await api('orders');state.orders=r.orders||[];
      host.innerHTML=state.orders.length?state.orders.map(o=>`<tr><td><strong>${esc(o.order_number||'Salg')}</strong></td><td>${esc(date(o.created_at))}</td><td>${esc(methodName({method_type:o.payment_method,method_name:o.payment_method}))}</td><td><span class="sales-ledger-status">${esc(statusLabel(o.payment_status||o.status))}</span></td><td class="text-end"><strong>${esc(money(o.total_minor))}</strong></td><td class="text-end"><button class="btn btn-sm btn-outline-primary" type="button" data-order="${esc(o.id)}">Detaljer</button></td></tr>`).join(''):'<tr><td colspan="6"><div class="sales-empty"><strong>Ingen salg ennå</strong><span>Fullfør et salg for å se oppgjøret her.</span></div></td></tr>';
    } catch(e) {host.innerHTML=`<tr><td colspan="6"><div class="sales-error" role="alert">${esc(e.message)} <button type="button" id="sales-retry-orders">Prøv igjen</button></div></td></tr>`;$('#sales-retry-orders')?.addEventListener('click',refreshOrders);}
  }
  async function showOrder(id) {
    modal('Laster kvittering…','<div class="sales-skeleton"></div>',{kind:'loading'});
    const controller=new AbortController();state.productController=controller;
    try {const r=await api('order',{query:{order_id:id},signal:controller.signal});if(state.productController!==controller)return;receipt(r.order);}
    catch(e){if(e.name!=='AbortError'){$('#sales-dialog-title').textContent='Kvittering kunne ikke lastes';$('#sales-dialog-body').innerHTML=`<div class="sales-error" role="alert">${esc(e.message)}</div><button id="sales-retry-order" class="btn btn-outline-primary" type="button">Prøv igjen</button>`;$('#sales-retry-order').addEventListener('click',()=>showOrder(id));}}
  }
  async function bootstrap() {
    try {
      const r=await api('bootstrap');state.csrf=r.csrf_token||'';state.taxRegistered=Number(r.tax_registered)!==0?1:0;state.methodError=r.method_error||'';
      state.products=r.products||[];state.methods=r.methods||[];state.cart=r.cart||emptyCart();state.pending=r.pending_order&&!isTerminalPayment(r.pending_order)?r.pending_order:null;
      renderMethods();renderProducts();renderAdminProducts();renderCart();
      if(state.pending)startPolling();
      refreshOrders();
    } catch(e) {
      $('#sales-products').innerHTML=`<div class="sales-error sales-grid-empty" role="alert">${esc(e.message)} <button type="button" id="sales-bootstrap-retry">Prøv igjen</button></div>`;
      $('#sales-bootstrap-retry').addEventListener('click',bootstrap);
    }
  }
  root.addEventListener('click',event=>{
    const productTile=event.target.closest('[data-product]');
    if(productTile){const product=state.products.find(p=>p.id===productTile.dataset.product);if(product)addProduct(product);return;}
    const qty=event.target.closest('[data-line-qty]');
    if(qty){const item=state.cart?.items.find(i=>i.id===qty.dataset.lineQty);if(item)queueMutation('quantity',{line_id:item.id,quantity:Math.max(1,Number(item.quantity)+Number(qty.dataset.delta))});return;}
    const remove=event.target.closest('[data-remove-line]');
    if(remove){queueMutation('remove',{line_id:remove.dataset.removeLine});return;}
    const edit=event.target.closest('[data-edit-product]');
    if(edit){const product=state.products.find(p=>p.id===edit.dataset.editProduct);if(product)productForm(product);return;}
    const stock=event.target.closest('[data-stock-product]');
    if(stock){const product=state.products.find(p=>p.id===stock.dataset.stockProduct);if(product)stockDialog(product);return;}
    const order=event.target.closest('[data-order]');
    if(order)showOrder(order.dataset.order);
    if(event.target.closest('[data-close-dialog]'))closeDialog();
  });
  $('#sales-search').addEventListener('input',renderProducts);
  $('#sales-admin-search').addEventListener('input',renderAdminProducts);
  $('#sales-method').addEventListener('change',e=>{state.selectedMethod=e.target.value;renderCart();});
  $('#sales-tendered').addEventListener('input',renderCart);
  $('#sales-phone').addEventListener('input',renderCart);
  $('#sales-checkout').addEventListener('click',checkout);
  $('#sales-cancel-payment').addEventListener('click',cancelPayment);
  $('#sales-next-customer').addEventListener('click',async()=>{
    if(state.pending || (state.cart?.items||[]).length)return;
    const button=$('#sales-next-customer');button.disabled=true;
    try {const r=await api('next_customer',{method:'POST',data:{}});updateCart(r.cart);state.pending=null;state.completedOrder=null;state.paymentNotice='';state.selectedMethod='cash';$('#sales-tendered').value='';$('#sales-phone').value='';renderMethods();renderCart();setError('');}
    catch(e){setError(e.message);try{await refreshCart();}catch(_){}}
    finally{button.disabled=false;}
  });
  root.addEventListener('click',event=>{
    const quick=event.target.closest('[data-cash]');
    if(quick){$('#sales-tendered').value=(Number(quick.dataset.cash)/100).toFixed(2);renderCart();}
    if(event.target.closest('#sales-check-status'))checkPayment().catch(e=>setError(e.message));
    if(event.target.closest('#sales-retry-mutation')){
      const box=$('#sales-cart-error');let payload;try{payload=JSON.parse(box.dataset.retryPayload||'{}')}catch(_){return}
      const action=box.dataset.retryAction;if(action){state.mutationQueue=state.mutationQueue.then(async()=>{state.busy=true;renderCart();let succeeded=false;try{const r=await api(action,{method:'POST',data:payload});updateCart(r.cart);setError('');succeeded=true;}catch(e){setError(e.message)}finally{state.busy=false;renderCart()}if(succeeded&&action==='add'&&state.dialog?.kind==='customize')closeDialog(true);});}
    }
  });
  $('#sales-new-product').addEventListener('click',()=>productForm());
  $('#sales-refresh-products').addEventListener('click',async()=>{try{const r=await api('products');state.products=r.products||[];state.methods=r.methods||state.methods;state.methodError=r.method_error||'';state.taxRegistered=Number(r.tax_registered)!==0?1:0;renderMethods();renderProducts();renderAdminProducts();}catch(e){setError(e.message)}});
  $('#sales-refresh-orders').addEventListener('click',refreshOrders);
  document.addEventListener('shown.bs.tab',event=>{const target=event.target?.getAttribute('href')||event.target?.dataset?.bsTarget;if(target==='#sales-transactions')refreshOrders();if(target==='#sales-varesalg'&&state.pending)startPolling();if(target!=='#sales-varesalg'&&state.pending)stopPolling();});
  document.addEventListener('shown.bs.tab',()=>setTimeout(()=>{if(!state.pending)return;if(paymentPanelShown())startPolling();else stopPolling();},0));
  document.addEventListener('click',event=>{const tab=event.target.closest('a[href="#sales-transactions"],[data-bs-target="#sales-transactions"],a[href="#sales-varesalg"],[data-bs-target="#sales-varesalg"]');if(tab)setTimeout(()=>{const target=tab.getAttribute('href')||tab.dataset.bsTarget;if(target==='#sales-transactions')refreshOrders();if(target==='#sales-varesalg'&&state.pending)startPolling();if(target!=='#sales-varesalg'&&state.pending)stopPolling();},0);});
  document.querySelectorAll('.sales-admin [data-cash]').forEach(()=>{});
  root.addEventListener('click',event=>{if(event.target.closest('#sales-fullscreen'))toggleFullscreen(true);if(event.target.closest('#sales-fullscreen-exit'))toggleFullscreen(false);});
  async function toggleFullscreen(open) {
    try {if(open && root.requestFullscreen)await root.requestFullscreen();else if(!open&&document.fullscreenElement)await document.exitFullscreen();}catch(_){root.classList.toggle('sales-fullscreen',open);}
    syncFullscreen();
  }
  function syncFullscreen() {
    const on=document.fullscreenElement===root||root.classList.contains('sales-fullscreen');
    root.classList.toggle('sales-fullscreen',on);$('#sales-fullscreen').setAttribute('aria-pressed',String(on));$('#sales-fullscreen-exit').classList.toggle('d-none',!on);
  }
  document.addEventListener('fullscreenchange',syncFullscreen);
  document.addEventListener('visibilitychange',()=>{if(document.hidden||!paymentPanelShown()){stopPolling();}else if(state.pending)startPolling();});
  window.addEventListener('beforeunload',event=>{if(state.pending){event.preventDefault();event.returnValue='';}});
  document.addEventListener('keydown',event=>{if(event.key==='Escape'&&!$('#sales-dialog').hidden)closeDialog();});
  root.querySelectorAll('[data-cash]').forEach(button=>button.addEventListener('click',()=>{$('#sales-tendered').value=(Number(button.dataset.cash)/100).toFixed(2);renderCart();}));
  $('#sales-scanner').addEventListener('keydown',event=>{
    if(event.key!=='Enter')return;event.preventDefault();
    const code=event.currentTarget.value.trim();event.currentTarget.value='';
    const product=state.products.find(p=>Number(p.is_active)===1 && (String(p.barcode||'')===code || String(p.sku||'')===code));
    const msg=$('#sales-scan-message');
    if(!product){msg.textContent='Fant ikke en aktiv vare med denne koden.';msg.className='sales-inline-message is-error';return;}
    if(!['kiosk','both'].includes(product.visibility||'kiosk')){msg.textContent='Denne varen er ikke tilgjengelig i kassen.';msg.className='sales-inline-message is-error';return;}
    msg.textContent=`${product.name} valgt`;msg.className='sales-inline-message is-success';addProduct(product);
  });
  $('#sales-scanner').addEventListener('input',()=>{$('#sales-scan-message').textContent='';});
  // Explicitly protect against browser back/navigation while an unresolved provider payment exists.
  bootstrap();
})();
