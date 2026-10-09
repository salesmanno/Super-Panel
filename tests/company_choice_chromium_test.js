'use strict';

// No PHP execution, auth cookies, database, server, or outbound requests.
// Render the real pending template fragment and exercise the real browser assets.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');

function loadPlaywright() {
    const candidates = [
        process.env.PLAYWRIGHT_MODULE,
        'playwright',
        '/mnt/pid2/node_modules/playwright',
    ].filter(Boolean);
    for (const candidate of candidates) {
        try { return require(candidate); } catch (_) {}
    }
    throw new Error('Install playwright or set PLAYWRIGHT_MODULE.');
}

const root = path.resolve(__dirname, '..');
let phase = 'browser setup';
const origin = 'http://company-choice.test';
const externalOrigin = 'http://external-choice.test';
const defaultLogo = '/attached_assets/salesman-removebg-preview_1761762992662.png';
const template = fs.readFileSync(path.join(root, 'pages/login_transition.php'), 'utf8');
const styles = template.match(/<style>([\s\S]*?)<\/style>/);
const fragment = template.match(/<main class="transition-container company-choice"[\s\S]*?<\/main>/);
assert.ok(styles && fragment, 'Pending company-choice markup is present');
assert.ok(!fragment[0].includes('<?'), 'Pending markup can be rendered without PHP');
const choiceHTML = `<!doctype html><html lang="no"><head><meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>${styles[1]}</style></head><body class="company-choice-page">
${fragment[0]}<script src="/assets/company_choice.js"></script></body></html>`;

// Synthetic fixtures only. Never include fixture context/CSRF values in logs.
const fixtures = {
    first: 'isolated-context-a',
    second: 'isolated-context-b',
    csrf: 'isolated-csrf',
};

function contextHTML(context) {
    return `<!doctype html><meta charset="UTF-8">
<script src="/assets/company_context.js" data-context="${context}"></script>
<script>
window.cacheObservedByPage = {
    data: sessionStorage.getItem('dashboardPreload'),
    context: sessionStorage.getItem('dashboardPreloadContext'),
    time: sessionStorage.getItem('dashboardPreloadTime')
};
</script>
<h1>Isolated full-auth fixture</h1>
<iframe name="form-result" title="Form result"></iframe>
<form id="normal-form" action="/api/form.php" method="post" target="form-result">
<input name="action" value="save"><button type="submit">Save</button></form>
<form id="programmatic-form" action="/api/form.php" method="post" target="form-result">
<input name="action" value="programmatic"></form>
<form id="external-form" action="${externalOrigin}/form.php" method="post"></form>`;
}

async function choiceTests(browser) {
    phase = 'choice UI checks';
    const context = await browser.newContext({ viewport: { width: 390, height: 844 } });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', () => errors.push('Browser script error'));
    let mode = 'list';
    let posts = 0;
    let gets = 0;
    let releasePost;
    let fallbackRequests = 0;
    const reads = [];
    await context.route('**/*', async route => {
        const request = route.request();
        const url = new URL(request.url());
        if (url.origin !== origin) return route.abort();
        if (url.pathname === '/assets/company_choice.js') {
            return route.fulfill({
                contentType: 'application/javascript',
                body: fs.readFileSync(path.join(root, 'assets/company_choice.js'), 'utf8'),
            });
        }
        if (url.pathname === defaultLogo) {
            fallbackRequests++;
            return route.fulfill({ contentType: 'image/png', body: fs.readFileSync(path.join(root, defaultLogo.slice(1))) });
        }
        if (url.pathname === '/broken-logo.png') return route.fulfill({ status: 404, body: '' });
        if (url.pathname === '/api/company_choice.php') {
            if (request.method() === 'POST') {
                posts++;
                const body = request.postDataJSON();
                assert.ok(body.company_id === 17, 'POST carries the explicitly selected company');
                assert.ok(body.csrf_token === fixtures.csrf, 'POST carries the issued CSRF value');
                await new Promise(resolve => { releasePost = resolve; });
                const successful = mode === 'success' || mode === 'unsafe-redirect';
                return route.fulfill({
                    status: successful ? 200 : 403,
                    contentType: 'application/json',
                    body: JSON.stringify(successful
                        ? { success: true, redirect: mode === 'success' ? '/pages/login_transition.php' : externalOrigin }
                        : { success: false }),
                });
            }
            reads.push(url.pathname);
            gets++;
            return route.fulfill({
                status: mode === 'error' ? 503 : 200,
                contentType: 'application/json',
                body: mode === 'malformed' ? '{invalid' : JSON.stringify({
                    success: true, csrf_token: fixtures.csrf,
                    companies: mode === 'empty' ? [] : [
                        { id: 17, name: '<img src=x onerror=alert(1)> Fjordlaget', logo_url: '/broken-logo.png' },
                        { id: 28, name: 'Bergen Verksted', logo_url: null },
                    ],
                }),
            });
        }
        if (url.pathname === '/pages/login_transition.php') {
            return route.fulfill({ contentType: 'text/html', body: '<h1>Selection completed</h1>' });
        }
        assert.ok(url.pathname === '/', 'Pending page makes no preload/dashboard requests');
        return route.fulfill({ contentType: 'text/html; charset=utf-8', body: choiceHTML });
    });
    await context.addInitScript(() => sessionStorage.setItem('old-company-data', 'stale'));
    await page.goto(origin);
    const firstCard = page.getByRole('button', { name: /Fjordlaget/ });
    await firstCard.waitFor();
    assert.ok(await page.evaluate(() => sessionStorage.length === 0), 'Pending entry clears old cached company data');
    assert.ok(posts === 0, 'There is no automatic selection');
    assert.ok(await page.locator('#company-list img').count() === 2, 'Company names do not create DOM markup');
    assert.ok(await page.locator('#company-list').innerText().then(text => text.includes('<img src=x')), 'Names render literally');
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Mobile has no horizontal overflow');
    await page.waitForFunction(logo => [...document.querySelectorAll('.company-card img')]
        .every(image => image.complete && image.naturalWidth > 0 && new URL(image.src).pathname === logo), defaultLogo);
    assert.ok(fallbackRequests > 0, 'Absent and broken logos use the real default logo');
    assert.ok(await page.locator('.choice-cancel').getAttribute('href') === '/auth/logout.php', 'Cancel logs out');

    const screenshotDir = process.env.COMPANY_CHOICE_SCREENSHOT_DIR ||
        path.join(os.tmpdir(), 'company-choice-chromium-test');
    fs.mkdirSync(screenshotDir, { recursive: true });
    const screenshot = path.join(screenshotDir, 'choice-mobile.png');
    await page.screenshot({ path: screenshot, fullPage: true, animations: 'disabled' });
    await page.setViewportSize({ width: 1280, height: 900 });
    assert.ok(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth), 'Desktop has no horizontal overflow');
    await page.screenshot({ path: path.join(screenshotDir, 'choice-desktop.png'), fullPage: true, animations: 'disabled' });
    await page.setViewportSize({ width: 390, height: 844 });

    await page.keyboard.press('Tab');
    assert.ok(await page.evaluate(() => document.activeElement.className === 'company-card'), 'Cards participate in keyboard navigation');
    await page.keyboard.press('Enter');
    await page.waitForFunction(() => document.querySelector('.company-card').disabled);
    await firstCard.dispatchEvent('click');
    assert.ok(posts === 1, 'Duplicate submit is blocked');
    releasePost();
    await page.getByText('Kunne ikke velge bedriften.', { exact: false }).waitFor();
    assert.ok(await page.locator('.company-card:disabled').count() === 0, 'Failed selection unlocks cards');
    assert.ok(await firstCard.evaluate(button => button === document.activeElement), 'Focus returns to the selected card after failure');
    for (const [nextMode, message] of [
        ['empty', 'Ingen aktive bedrifter'],
        ['error', 'Kunne ikke hente bedriftene.'],
        ['malformed', 'Kunne ikke hente bedriftene.'],
        ['list', 'Ingen bedrift er valgt.'],
    ]) {
        mode = nextMode;
        const previousGets = gets;
        await page.getByRole('button', { name: 'Prøv igjen' }).click();
        await page.waitForFunction(() => document.getElementById('company-list').getAttribute('aria-busy') === 'false');
        assert.ok(gets > previousGets, 'Retry issues a fresh list request');
        await page.getByText(message, { exact: false }).waitFor();
    }
    mode = 'unsafe-redirect';
    await firstCard.click();
    await page.waitForFunction(() => document.querySelector('.company-card').disabled);
    releasePost();
    await page.getByText('Kunne ikke velge bedriften.', { exact: false }).waitFor();
    assert.ok(page.url() === origin + '/', 'Unexpected redirect is rejected');
    mode = 'success';
    await firstCard.click();
    await page.waitForFunction(() => document.querySelector('.company-card').disabled);
    releasePost();
    await page.waitForURL(origin + '/pages/login_transition.php');
    assert.ok(errors.length === 0, 'Choice UI has no unhandled script errors');
    assert.ok(reads.every(url => url === '/api/company_choice.php'), 'Pending entry only reads the company-choice endpoint');
    await context.close();
    console.log('PASS: real choice UI, logos, responsive layout, keyboard, safe names, cache clearing, errors/retry, duplicate prevention, redirects.');
    return screenshot;
}

async function contextTests(browser) {
    phase = 'context cache initialization';
    const context = await browser.newContext();
    const captured = [];
    await context.route('**/*', async route => {
        const request = route.request();
        const url = new URL(request.url());
        assert.ok([origin, externalOrigin].includes(url.origin), 'No live network access');
        if (url.pathname === '/assets/company_context.js') {
            return route.fulfill({
                contentType: 'application/javascript',
                body: fs.readFileSync(path.join(root, 'assets/company_context.js'), 'utf8'),
            });
        }
        if (url.pathname === '/full.php') {
            return route.fulfill({ contentType: 'text/html', body: contextHTML(fixtures.first) });
        }
        if (url.pathname === '/pages/login_transition.php') {
            return route.fulfill({ contentType: 'text/html', body: '<h1>Context invalidated</h1>' });
        }
        if (url.pathname === '/switch') return route.fulfill({ contentType: 'text/html', body: '<h1>Other tab</h1>' });
        captured.push({
            path: url.pathname,
            origin: url.origin,
            headerMatches: request.headers()['x-company-context'] === fixtures.first,
            hasContextHeader: Boolean(request.headers()['x-company-context']),
            queryMatches: url.searchParams.get('_company_context') === fixtures.first,
            preservedHeader: request.headers()['x-test'] === 'preserved',
            formMatches: new URLSearchParams(request.postData() || '').get('_company_context') === fixtures.first,
        });
        return route.fulfill({
            contentType: 'application/json', body: '{"success":true}',
            headers: { 'Access-Control-Allow-Origin': '*', 'Access-Control-Allow-Headers': '*' },
        });
    });
    const page = await context.newPage();
    const errors = [];
    page.on('pageerror', () => errors.push('Context script error'));
    await page.addInitScript(data => {
        if (window !== top || location.pathname !== '/full.php') return;
        localStorage.setItem('portalCompanyContext', data.old);
        sessionStorage.setItem('dashboardPreload', '{"stale":true}');
        sessionStorage.setItem('dashboardPreloadContext', data.old);
        sessionStorage.setItem('dashboardPreloadTime', '123');
    }, { old: fixtures.second });
    await page.goto(origin + '/full.php');
    assert.ok(await page.evaluate(() => Object.values(window.cacheObservedByPage).every(value => value === null)),
        'Changed context discards old preload before page scripts read it');
    await page.evaluate(() => {
        sessionStorage.setItem('dashboardPreload', '{"fresh":true}');
        sessionStorage.setItem('dashboardPreloadTime', '456');
        sessionStorage.setItem('dashboardPreloadContext', localStorage.getItem('portalCompanyContext'));
    });
    // A second page with the same context preserves its own session cache.
    const same = await context.newPage();
    await same.addInitScript(data => {
        if (window !== top || location.pathname !== '/full.php') return;
        localStorage.setItem('portalCompanyContext', data.current);
        sessionStorage.setItem('dashboardPreload', '{"fresh":true}');
    }, { current: fixtures.first });
    await same.goto(origin + '/full.php');
    assert.ok(await same.evaluate(() => window.cacheObservedByPage.data === '{"fresh":true}'),
        'Unchanged context retains matching preload');

    phase = 'fetch and XHR scoping';
    await page.evaluate(async external => {
        await fetch('/api/fetch.php', { headers: { 'X-Test': 'preserved' } });
        await fetch(new Request(location.origin + '/api/request.php', { headers: { 'X-Test': 'preserved' } }));
        await fetch(external + '/api/external.php');
        await new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('GET', '/api/xhr.php?existing=1');
            xhr.onload = resolve;
            xhr.onerror = reject;
            xhr.send();
        });
    }, externalOrigin);
    const fetches = captured.filter(item => ['/api/fetch.php', '/api/request.php'].includes(item.path));
    assert.ok(fetches.length === 2 && fetches.every(item => item.headerMatches && item.preservedHeader),
        'Fetch attaches context and preserves headers for string and Request inputs');
    assert.ok(captured.some(item => item.path === '/api/external.php' && !item.hasContextHeader),
        'Cross-origin fetch does not receive the context header');
    assert.ok(captured.some(item => item.path === '/api/xhr.php' && item.queryMatches),
        'XHR URL is scoped to the page context');
    phase = 'POST form scoping';
    const normalFormResponse = page.waitForResponse(response =>
        new URL(response.url()).pathname === '/api/form.php' && response.request().method() === 'POST');
    await page.evaluate(() => document.getElementById('normal-form').requestSubmit());
    await normalFormResponse;
    await page.waitForFunction(() => document.querySelector('iframe').contentDocument?.body?.textContent.includes('success'));
    assert.ok(captured.filter(item => item.path === '/api/form.php').every(item => item.formMatches),
        'Normal POST form contains the scoped hidden field');
    const previousForms = captured.filter(item => item.path === '/api/form.php').length;
    const programmaticFormResponse = page.waitForResponse(response =>
        new URL(response.url()).pathname === '/api/form.php' && response.request().method() === 'POST');
    await page.evaluate(() => document.getElementById('programmatic-form').submit());
    await programmaticFormResponse;
    await page.waitForFunction(() => document.getElementById('programmatic-form').querySelector('input[name="_company_context"]') !== null);
    const forms = captured.filter(item => item.path === '/api/form.php');
    assert.ok(forms.length === previousForms + 1 && forms.every(item => item.formMatches),
        'Programmatic POST form also contains the scoped field');
    assert.ok(await page.evaluate(() => {
        const form = document.getElementById('external-form');
        form.addEventListener('submit', event => event.preventDefault());
        form.requestSubmit();
        return !form.querySelector('input[name="_company_context"]');
    }), 'External forms do not receive company context');

    phase = 'cross-tab storage invalidation';
    const otherTab = await context.newPage();
    await otherTab.goto(origin + '/switch');
    await otherTab.evaluate(next => localStorage.setItem('portalCompanyContext', next), fixtures.second);
    await Promise.all([
        page.waitForURL(origin + '/pages/login_transition.php'),
        same.waitForURL(origin + '/pages/login_transition.php'),
    ]);
    assert.ok(errors.length === 0, 'Context script has no unhandled errors');
    console.log('PASS: real context asset fetch/Request headers, cross-origin isolation, XHR scope, both POST form paths, cache scoping, cross-tab storage invalidation.');
    await context.close();

    // localStorage changes made by this page do not generate its own storage
    // event: fetch and pageshow must still reject/invalidate stale context.
    for (const trigger of ['fetch', 'pageshow']) {
        phase = 'same-tab stale-page ' + trigger;
        const isolated = await browser.newContext();
        let sent = false;
        await isolated.route('**/*', async route => {
            const url = new URL(route.request().url());
            assert.ok(url.origin === origin, 'Stale-page fixture never accesses live endpoints');
            if (url.pathname === '/assets/company_context.js') return route.fulfill({
                contentType: 'application/javascript',
                body: fs.readFileSync(path.join(root, 'assets/company_context.js'), 'utf8'),
            });
            if (url.pathname === '/full.php') return route.fulfill({ contentType: 'text/html', body: contextHTML(fixtures.first) });
            if (url.pathname === '/api/forbidden.php') sent = true;
            return route.fulfill({ contentType: 'text/html', body: '<h1>Context invalidated</h1>' });
        });
        const stale = await isolated.newPage();
        await stale.goto(origin + '/full.php');
        await stale.evaluate(({ next, trigger }) => {
            localStorage.setItem('portalCompanyContext', next);
            if (trigger === 'fetch') {
                // Handle the deliberate rejection without logging any values.
                fetch('/api/forbidden.php').catch(() => {});
            } else {
                dispatchEvent(new PageTransitionEvent('pageshow', { persisted: true }));
            }
        }, { next: fixtures.second, trigger });
        await stale.waitForURL(origin + '/pages/login_transition.php');
        assert.ok(!sent, 'Stale page sends no company API request');
        await isolated.close();
    }
    console.log('PASS: stale same-tab fetch is blocked and restored pages invalidate on pageshow.');
}

(async () => {
    const { chromium } = loadPlaywright();
    const executablePath = process.env.CHROMIUM_BIN ||
        (fs.existsSync('/repl/tools/bin/chromium') ? '/repl/tools/bin/chromium' : undefined);
    const browser = await chromium.launch({ executablePath, headless: true, args: ['--no-sandbox'] });
    try {
        const screenshot = await choiceTests(browser);
        await contextTests(browser);
        console.log('Choice UI screenshot: ' + screenshot);
    } finally {
        await browser.close();
    }
})().catch(() => {
    // Deliberately do not print Playwright traces or assertion values: request
    // metadata can contain company context and CSRF values.
    console.error('FAIL: ' + phase + '. No request values logged.');
    process.exitCode = 1;
});
