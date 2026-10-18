const assert = require('node:assert/strict');
const {spawnSync} = require('node:child_process');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const chromiumFixture = require('./chromium_fixture');

if (typeof WebSocket === 'undefined') {
    const child = spawnSync(process.execPath, ['--experimental-websocket', __filename], {stdio:'inherit'});
    process.exit(child.status ?? 1);
}

const projectRoot = path.resolve(__dirname, '..');
const rendered = spawnSync('php', ['-r', "include 'modules/economy_content.php';"], {
    cwd: projectRoot,
    encoding: 'utf8'
});
assert.equal(rendered.status, 0, rendered.stderr || 'PHP could not render economy_content.php');

const testDir = fs.mkdtempSync(path.join(os.tmpdir(), 'economy-ledger-browser-'));
const fixturePath = path.join(testDir, 'economy-module.html');
const bootstrapAndFetchMock = `
window.__economyRequests = [];
window.__economyFailFilter = null;
window.__economyDelayAttempts = false;
window.__economyResolveDelayed = null;
const makeRow = (id, name, {status='completed', ledger='active', paid=true, selling=40, income=40, expense=0, cost=0, profit=10}={}) => ({
    ledger_id: id, item_name: name, module_source: 'sales', payment_method: 'card',
    payment_status: status, ledger_status: ledger, counts_as_paid: paid,
    income_amount: income, expense_amount: expense, selling_price: selling,
    cost_price: cost, profit, transaction_date: '2025-02-03 12:15:00',
    metadata: {}
});
const dataset = (filter, page) => {
    const completedPageOne = [
        makeRow(1, 'Positiv utgift', {selling:25, income:0, expense:25, cost:25, profit:0}),
        ...Array.from({length:9}, (_,i) => makeRow(i+2, 'Betalt rad '+(i+2)))
    ];
    const completedPageTwo = [makeRow(11,'Betalt side to'),makeRow(12,'Betalt side to B')];
    const attempts = [
        makeRow(201,'Betalingsforsøk feil',{status:'failed',paid:false,selling:47, income:0, expense:0, profit:null}),
        makeRow(202,'Historisk refundert',{status:'completed',ledger:'refunded',paid:true,selling:83, income:83, expense:0, profit:58}),
        makeRow(203,'Historisk avbrutt',{status:'completed',ledger:'cancelled',paid:true,selling:29, income:29, expense:0, profit:12})
    ];
    const rows = filter === 'completed' ? (page === 2 ? completedPageTwo : completedPageOne)
        : filter === 'attempts' ? attempts : [makeRow(301,'Alle filter aktiv'), ...attempts];
    const total = filter === 'completed' ? 12 : rows.length;
    return {success:true, data:{
        total_income:900, total_expenses:145, total_profit:755, month_income:90,
        month_expenses:15, year_income:900, year_expenses:145, transaction_count:37,
        transactions:rows, recent_transactions:rows, page, per_page:10, total,
        total_pages:Math.max(1,Math.ceil(total/10)), payment_filter:filter
    }};
};
window.fetch = (input) => {
    const url = new URL(input, location.href);
    const filter = url.searchParams.get('payment_filter') || 'completed';
    const page = Number(url.searchParams.get('page') || 1);
    window.__economyRequests.push({filter,page,url:url.href});
    if (window.__economyFailFilter === filter) return Promise.reject(new Error('simulated fetch failure'));
    const response = {json: async () => dataset(filter,page)};
    if (filter === 'attempts' && window.__economyDelayAttempts) {
        window.__economyDelayAttempts = false;
        return new Promise(resolve => {
            window.__economyResolveDelayed = () => resolve(response);
        });
    }
    return Promise.resolve(response);
};
window.bootstrap = {Modal: {getOrCreateInstance: () => ({
    show() { document.getElementById('economyTransactionModal').classList.add('show'); },
    hide() { document.getElementById('economyTransactionModal').classList.remove('show'); }
})}};
`;
const fixtureCss = `
<style>
*{box-sizing:border-box} body{margin:0;padding:12px;font:14px system-ui,sans-serif;color:#263238}
.module-content{width:100%;max-width:1200px;margin:auto}.card{border:1px solid #d5dadd;border-radius:6px;background:#fff}
.card-body{padding:12px}.card-header{padding:10px;border-bottom:1px solid #d5dadd;display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap}
.d-flex{display:flex}.justify-content-between{justify-content:space-between}.align-items-center{align-items:center}.gap-2{gap:8px}
.table-responsive{width:100%;overflow-x:auto}table{border-collapse:collapse;width:100%}th,td{padding:5px;border-bottom:1px solid #e1e5e7;text-align:left}
.text-end{text-align:right}.btn{padding:4px 8px}.badge{padding:3px 6px}.economy-transaction-modal-details{max-width:100%}
@media(max-width:575px){body{padding:6px}.card-header{align-items:flex-start}.card-header select{max-width:calc(100vw - 72px)!important}}
</style>`;
const moduleHtml = rendered.stdout.replace('<script>', `<script>${bootstrapAndFetchMock}</script><script>`);
fs.writeFileSync(fixturePath, `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1">${fixtureCss}</head><body>${moduleHtml}</body></html>`);

const checks = `(async () => {
    const pause = ms => new Promise(resolve => setTimeout(resolve,ms));
    const waitFor = async predicate => {
        for (let i=0;i<100;i++) { if (predicate()) return; await pause(10); }
        throw new Error('Timed out waiting for UI update');
    };
    const select = document.getElementById('economy-payment-filter');
    if (!select || select.value !== 'completed' || select.previousElementSibling?.textContent.trim() !== 'Vis') throw new Error('Default accessible payment filter missing');
    await loadEconomyOverview(1);
    if (!window.__economyRequests.some(r => r.filter === 'completed' && r.page === 1)) throw new Error('Default filter not sent');
    const expenseCell = document.querySelector('#recent-transactions tr.transaction-row td:last-child');
    if (!expenseCell || !expenseCell.textContent.includes('25') || expenseCell.textContent.includes('+')) throw new Error('Positive expense amount was not rendered as a 25 expense');
    openEconomyTransaction(0);
    if (!document.getElementById('economyTransactionDetails').innerText.includes('Resultat')) throw new Error('Posted expense details missing profit field');
    if (!document.getElementById('economyTransactionDetails').innerText.includes('Ledger-status')) throw new Error('Raw ledger status missing from details');
    await loadEconomyOverview(1);
    openEconomyTransaction(9);
    await moveEconomyTransaction(1);
    if (!window.__economyRequests.some(r => r.filter === 'completed' && r.page === 2)) throw new Error('Modal page navigation omitted selected payment filter');
    if (!window.__economyRequests.some(r => r.filter === 'completed' && r.page === 2)) throw new Error('Page request not tracked');

    select.value = 'attempts';
    select.dispatchEvent(new Event('change',{bubbles:true}));
    await waitFor(() => document.querySelector('#recent-transactions tr.transaction-row')?.textContent.includes('Betalingsforsøk feil'));
    if (!window.__economyRequests.some(r => r.filter === 'attempts' && r.page === 1)) throw new Error('Filter change did not reset to page 1');
    const failedRow = [...document.querySelectorAll('#recent-transactions tr.transaction-row')].find(row => row.textContent.includes('Betalingsforsøk feil'));
    if (!failedRow?.textContent.includes('Forsøk · ikke betalt') || !failedRow.textContent.includes('Mislykket')) throw new Error('Failed attempt is not neutral and translated');
    openEconomyTransaction(0);
    let details = document.getElementById('economyTransactionDetails').innerText;
    if (!details.includes('Ikke medregnet i inntekter/resultat') || details.includes('Resultat')) throw new Error('Failed payment details show profit or omit exclusion note');

    const refundedRow = [...document.querySelectorAll('#recent-transactions tr.transaction-row')].find(row => row.textContent.includes('Historisk refundert'));
    if (!refundedRow?.textContent.includes('Refundert') || refundedRow.textContent.includes('Gjennomført') || !refundedRow.textContent.includes('Historisk beløp · ikke medregnet')) throw new Error('Refunded ledger row misrepresents its payment status or amount');
    openEconomyTransaction(1);
    details = document.getElementById('economyTransactionDetails').innerText;
    if (!details.includes('Historisk oppføring') || !details.includes('Ledger-status') || !details.includes('refunded') || details.includes('Resultat')) throw new Error('Refunded details include posted profit or omit ledger exclusion');
    const cancelledRow = [...document.querySelectorAll('#recent-transactions tr.transaction-row')].find(row => row.textContent.includes('Historisk avbrutt'));
    if (!cancelledRow?.textContent.includes('Avbrutt') || cancelledRow.textContent.includes('Gjennomført')) throw new Error('Cancelled ledger status not preferred over payment status');

    window.__economyDelayAttempts = true;
    select.value = 'attempts';
    select.dispatchEvent(new Event('change',{bubbles:true}));
    await waitFor(() => typeof window.__economyResolveDelayed === 'function');
    select.value = 'all';
    select.dispatchEvent(new Event('change',{bubbles:true}));
    await waitFor(() => document.querySelector('#recent-transactions tr.transaction-row')?.textContent.includes('Alle filter aktiv'));
    window.__economyResolveDelayed();
    await pause(30);
    if (!document.querySelector('#recent-transactions tr.transaction-row')?.textContent.includes('Alle filter aktiv')) throw new Error('Stale attempts response replaced latest filter data');

    window.__economyFailFilter = 'attempts';
    select.value = 'attempts';
    select.dispatchEvent(new Event('change',{bubbles:true}));
    await waitFor(() => document.querySelector('#recent-transactions td.text-danger')?.textContent.includes('Feil ved lasting'));
    if (document.querySelector('#recent-transactions tr.transaction-row')) throw new Error('Previous filter rows remained visible after filter request failure');
    window.__economyFailFilter = null;
    await loadEconomyOverview(1);
    if (!document.querySelector('#recent-transactions tr.transaction-row')?.textContent.includes('Betalingsforsøk feil')) throw new Error('Retry did not recover filtered results');

    const filterBox = select.getBoundingClientRect();
    if (filterBox.right > window.innerWidth + 1 || filterBox.left < 0) throw new Error('Payment selector clipped at viewport width '+window.innerWidth);
    return {passed:true,width:window.innerWidth,requests:window.__economyRequests.length,selectorRight:filterBox.right};
})()`;

chromiumFixture(fixturePath, checks, [390, 1280], {outputDir:testDir})
    .then(() => new Promise(resolve => setTimeout(() => {
        fs.rmSync(testDir,{recursive:true,force:true});
        resolve();
    },500)))
    .catch(error => {
        fs.rmSync(testDir,{recursive:true,force:true});
        console.error(error);
        process.exitCode = 1;
    });
