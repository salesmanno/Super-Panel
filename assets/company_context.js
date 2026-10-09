/* Bind requests and caches to the HTML's authenticated context, not whichever
 * company a different tab most recently selected with the shared session cookie. */
(() => {
    const context = document.currentScript.dataset.context;
    const marker = 'portalCompanyContext';
    try {
        if (localStorage.getItem(marker) !== context) sessionStorage.clear();
        localStorage.setItem(marker, context);
    } catch (_) {}
    let stopped = false;
    function current() {
        try { return !stopped && localStorage.getItem(marker) === context; }
        catch (_) { return !stopped; }
    }
    function changed() {
        if (current()) return;
        stopped = true;
        document.documentElement.style.visibility = 'hidden';
        location.replace('/pages/login_transition.php');
    }
    addEventListener('storage', e => { if (e.key === marker) changed(); });
    addEventListener('pageshow', changed);
    function scoped(url) {
        const target = new URL(url, location.href);
        if (target.origin === location.origin && target.pathname.endsWith('.php')) {
            target.searchParams.set('_company_context', context);
        }
        return target.href;
    }
    const nativeFetch = window.fetch.bind(window);
    window.fetch = async (input, options = {}) => {
        if (!current()) { changed(); throw new Error('Bedriftskonteksten er endret.'); }
        const request = new Request(input, options);
        if (new URL(request.url).origin === location.origin) request.headers.set('X-Company-Context', context);
        const response = await nativeFetch(request);
        if (!current()) { changed(); throw new Error('Bedriftskonteksten er endret.'); }
        return response;
    };
    const open = XMLHttpRequest.prototype.open;
    XMLHttpRequest.prototype.open = function(method, url, ...args) {
        return open.call(this, method, scoped(url), ...args);
    };
    const send = XMLHttpRequest.prototype.send;
    XMLHttpRequest.prototype.send = function(...args) {
        if (!current()) { changed(); throw new Error('Bedriftskonteksten er endret.'); }
        return send.apply(this,args);
    };
    if (window.EventSource) {
        const Native = window.EventSource;
        window.EventSource = class extends Native { constructor(url, options) { super(scoped(url),options); } };
    }
    function scopeForm(form) {
        if (new URL(form.action, location.href).origin !== location.origin) return;
        let field = form.querySelector('input[name="_company_context"]');
        if (!field) { field = document.createElement('input'); field.type='hidden'; field.name='_company_context'; form.append(field); }
        field.value=context;
    }
    document.addEventListener('submit',event=>{
        if (!current()) { event.preventDefault(); changed(); return; }
        scopeForm(event.target);
    },true);
    const submit=HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit=function() { if (!current()) return changed(); scopeForm(this); return submit.call(this); };
    document.addEventListener('click',event=>{
        const link=event.target.closest('a[href]');
        if (link && new URL(link.href,location.href).pathname.startsWith('/api/')) link.href=scoped(link.href);
    },true);
    const windowOpen=window.open.bind(window);
    window.open=(url,...args)=>windowOpen(url?scoped(url):url,...args);
})();
