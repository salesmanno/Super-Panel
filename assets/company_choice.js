(() => {
    'use strict';

    // A pending login must never retain data from a previously selected company.
    try { sessionStorage.clear(); } catch (error) { /* Storage may be disabled. */ }

    const root = document.getElementById('company-choice');
    if (!root) return;
    const list = document.getElementById('company-list');
    const status = document.getElementById('choice-status');
    const retry = document.getElementById('choice-retry');
    const defaultLogo = root.dataset.defaultLogo;
    let csrfToken = '';
    let submitting = false;
    let loading = false;

    function safeLogo(value) {
        if (typeof value !== 'string' || !value.trim()) return defaultLogo;
        try {
            const url = new URL(value, window.location.origin);
            return ['http:', 'https:'].includes(url.protocol) ? url.href : defaultLogo;
        } catch (error) { return defaultLogo; }
    }

    function lockCards(locked) {
        list.querySelectorAll('button').forEach(button => { button.disabled = locked; });
        list.setAttribute('aria-busy', String(locked));
    }

    async function chooseCompany(company, button) {
        if (submitting || loading || !csrfToken) return;
        submitting = true;
        lockCards(true);
        retry.hidden = true;
        status.textContent = 'Logger inn i valgt bedrift …';
        let navigating = false;
        try {
            const response = await fetch('/api/company_choice.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                body: JSON.stringify({ company_id: company.id, csrf_token: csrfToken })
            });
            const result = await response.json();
            if (!response.ok || result.success !== true ||
                result.redirect !== '/pages/login_transition.php') {
                throw new Error('choice_failed');
            }
            window.location.assign(result.redirect);
            navigating = true;
        } catch (error) {
            status.textContent = 'Kunne ikke velge bedriften. Prøv igjen, eller hent listen på nytt.';
            retry.hidden = false;
        } finally {
            if (!navigating) {
                submitting = false;
                lockCards(false);
                button.focus();
            }
        }
    }

    function renderCompany(company) {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'company-card';
        const image = document.createElement('img');
        image.alt = '';
        image.addEventListener('error', () => {
            if (image.dataset.fallback) {
                image.hidden = true;
                return;
            }
            image.dataset.fallback = 'true';
            image.src = defaultLogo;
        });
        image.src = safeLogo(company.logo_url);
        const name = document.createElement('span');
        name.className = 'company-card-name';
        name.textContent = company.name;
        const action = document.createElement('span');
        action.className = 'company-card-action';
        action.textContent = 'Velg bedrift';
        button.append(image, name, action);
        button.addEventListener('click', () => chooseCompany(company, button));
        return button;
    }

    async function loadCompanies() {
        if (loading || submitting) return;
        loading = true;
        csrfToken = '';
        retry.hidden = true;
        list.replaceChildren();
        list.setAttribute('aria-busy', 'true');
        for (let i = 0; i < 2; i++) {
            const skeleton = document.createElement('div');
            skeleton.className = 'choice-skeleton';
            skeleton.setAttribute('aria-hidden', 'true');
            list.append(skeleton);
        }
        status.textContent = 'Henter tilgjengelige bedrifter …';
        try {
            const response = await fetch('/api/company_choice.php', {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });
            const result = await response.json();
            if (!response.ok || result.success !== true || !Array.isArray(result.companies) ||
                typeof result.csrf_token !== 'string' || !result.csrf_token ||
                !result.companies.every(company => company &&
                    (typeof company.id === 'number' || typeof company.id === 'string') &&
                    typeof company.name === 'string')) {
                throw new Error('list_failed');
            }
            csrfToken = result.csrf_token;
            list.replaceChildren(...result.companies.map(renderCompany));
            status.textContent = result.companies.length
                ? 'Ingen bedrift er valgt. Velg en bedrift for å fortsette.'
                : 'Ingen aktive bedrifter er tilgjengelige. Kontakt administrator, eller prøv igjen.';
            retry.hidden = result.companies.length > 0;
        } catch (error) {
            list.replaceChildren();
            status.textContent = 'Kunne ikke hente bedriftene. Sjekk tilkoblingen og prøv igjen.';
            retry.hidden = false;
        } finally {
            loading = false;
            list.setAttribute('aria-busy', 'false');
        }
    }

    retry.addEventListener('click', loadCompanies);
    loadCompanies();
})();
