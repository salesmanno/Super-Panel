// Uses the actual request rendering/pricing/payload code with the dashboard's
// date enhancer. All data is local; no app API or database is contacted.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawn, spawnSync} = require('node:child_process');
if (typeof WebSocket === 'undefined') {
    const child = spawnSync(process.execPath, ['--experimental-websocket', __filename], {stdio: 'inherit'});
    process.exit(child.status ?? 1);
}
const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'rental-request-dates-'));
const content = fs.readFileSync(path.join(__dirname, '../modules/rental_content.php'), 'utf8');
const from = content.indexOf('function rentalAdjustmentFromRow(row) {');
const to = content.indexOf('function updateRentalRequestInlineTotal(checkbox) {', from);
assert(from >= 0 && to > from);
const helper = fs.readFileSync(path.join(__dirname, '../assets/rental_request_pricing.js'), 'utf8');
const enhancement = fs.readFileSync(path.join(__dirname, '../includes/norwegian_datetime_inputs.php'), 'utf8');
const stubs = `
window.fixtureErrors=[];
window.addEventListener('error',e=>fixtureErrors.push(e.message));
window.addEventListener('unhandledrejection',e=>fixtureErrors.push(String(e.reason)));
const escHtml=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const escAttr=escHtml;
const calcAge=()=>null;
const rentalRequestFieldsHtml=()=>'';
window.fetch=async url=>{
    if(url!=='/api/get_rental_schedule.php?approved=0')throw new Error('Unexpected fetch: '+url);
    return {ok:true,json:async()=>({success:true,requests:[{
        id:1,object_name:'Testobjekt',display_customer_name:'Testkunde',
        start_date:'2028-04-03',end_date:'2028-04-05',end_time:'18:30:00',
        object_daily_price:'100',object_weekend_price:'150',
        object_saturday_price:'175',object_sunday_price:'125',
        cleaning_fee:'75.50',cleaning:0
    }]})};
};
`;
const checks = `
(async()=>{
    const check=(ok,message)=>{if(!ok)throw new Error(message)};
    const settle=()=>new Promise(resolve=>setTimeout(resolve,20));
    // Record the emulated viewport before native validation may focus/zoom it.
    const width=innerWidth;
    try{
        await loadRentalRequests();await settle();
        const row=document.querySelector('[data-rental-request-id="1"]');
        check(Boolean(row),'Actual request row rendered');
        const canonical=field=>row.querySelector('[data-rental-request-field="'+field+'"]');
        const proxy=field=>canonical(field).previousElementSibling;
        const change=(field,value)=>{
            const input=proxy(field);input.value=value;
            input.dispatchEvent(new Event('input',{bubbles:true}));
            input.dispatchEvent(new Event('change',{bubbles:true}));
        };
        check(proxy('start-date').dataset.norwegianDateTimeProxy==='1','Shared date enhancer is active');
        check(proxy('start-date').value==='03.04.2028','User sees Norwegian date');
        check(row.querySelector('.rental-request-start-date')===proxy('start-date'),
            'Legacy class selector would select the display proxy, reproducing the cause');
        check(canonical('start-date').value==='2028-04-03','Canonical date remains SQL formatted');
        check(refreshRentalRequestPrice(row)?.totalCents===30000,'Price still calculates after enhancement');
        check(!row.querySelector('.rental-request-approve').disabled,'Valid quote permits approval');
        const payload=getRentalRequestInlinePayload(1);
        check(payload.start_date==='2028-04-03'&&payload.end_date==='2028-04-05',
            'Save reads canonical dates, not display strings');
        check(payload.end_time==='18:30'&&payload.rental_fee===300,'Save reads canonical time and current quote');
        change('start-date','08.04.2028');change('end-date','09.04.2028');
        check(row.dataset.rentalTotal==='300','Editing Norwegian dates recalculates Saturday/Sunday rates');
        row.querySelector('.rental-request-cleaning').checked=true;
        check(refreshRentalRequestPrice(row)?.totalCents===37550,'Cleaning is retained');
        change('end-time','23:59');
        check(getRentalRequestInlinePayload(1).end_time==='23:59','Edited 24-hour time is saved');
        change('end-date','31.04.2028');
        check(row.dataset.priceError.includes('gyldig start- og sluttdato'),'Impossible calendar date is rejected');
        check(row.querySelector('.rental-request-approve').disabled,'Invalid date prevents approval');
        change('end-date','07.04.2028');
        check(row.dataset.priceError.includes('før startdato'),'Reversed date range remains invalid');
        change('end-date','09.04.2028');
        check(!row.dataset.priceError&&!row.querySelector('.rental-request-approve').disabled,'Correcting date restores calculation and approval');
        change('start-date','');check(Boolean(row.dataset.priceError),'Empty date is rejected');
        change('start-date','08.04.2028');
        const saved=getRentalRequestInlinePayload(1);
        check(saved.start_date==='2028-04-08'&&saved.end_date==='2028-04-09'&&saved.rental_fee===375.5,
            'Edited dates, cleaning and current price reach save payload');
        check(fixtureErrors.length===0,'No browser errors: '+fixtureErrors.join('; '));
        return {passed:true,width};
    }catch(error){return {passed:false,error:error.stack||String(error),errors:fixtureErrors}}
})()
`;
let browser;
let socket;
async function main() {
    const file = path.join(directory, 'fixture.html');
    fs.writeFileSync(file, `<!doctype html><html lang="nb"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>body{margin:0}.table-responsive{overflow-x:auto;width:100%}</style></head><body><div id="rentalRequestsContainer"></div><script>${stubs}</script><script>${helper}</script><script>${content.slice(from, to)}</script>${enhancement}</body></html>`);
    browser = spawn(process.env.CHROMIUM_PATH || '/repl/tools/bin/chromium', [
        '--headless', '--no-sandbox', '--disable-dev-shm-usage', '--remote-debugging-port=0',
        '--user-data-dir=' + path.join(directory, 'profile'), 'about:blank',
    ]);
    const url = await new Promise((resolve, reject) => {
        let output = '';
        const timeout = setTimeout(() => reject(new Error('Chromium debugger timed out')), 15000);
        browser.stderr.on('data', data => {
            output += data;
            const match = output.match(/DevTools listening on (ws:\/\/[^\s]+)/);
            if (match) {clearTimeout(timeout);resolve(match[1]);}
        });
        browser.on('error', reject);
    });
    socket = new WebSocket(url);
    await new Promise(resolve => socket.addEventListener('open', resolve, {once: true}));
    let id = 0;
    const pending = new Map();
    socket.addEventListener('message', event => {
        const message = JSON.parse(event.data);
        const callbacks = pending.get(message.id);
        if (callbacks) {
            pending.delete(message.id);
            message.error ? callbacks.reject(message.error) : callbacks.resolve(message.result);
        }
    });
    const send = (method, params = {}, sessionId) => new Promise((resolve, reject) => {
        pending.set(++id, {resolve, reject});
        socket.send(JSON.stringify({id, method, params, sessionId}));
    });
    const {targetId} = await send('Target.createTarget', {url: 'about:blank'});
    const {sessionId} = await send('Target.attachToTarget', {targetId, flatten: true});
    for (const width of [390, 768, 1280]) {
        await send('Emulation.setDeviceMetricsOverride', {width, height: 844, deviceScaleFactor: 1, mobile: width === 390}, sessionId);
        await send('Page.navigate', {url: 'file://' + file + '?width=' + width}, sessionId);
        await new Promise(resolve => setTimeout(resolve, 200));
        const result = await send('Runtime.evaluate', {expression: checks, awaitPromise: true, returnByValue: true}, sessionId);
        assert.equal(result.result?.value?.passed, true, JSON.stringify(result));
        assert.equal(result.result.value.width, width);
        console.log('Actual rental request dates/pricing passed at ' + width + 'px');
    }
}
main().catch(error => {console.error(error);process.exitCode = 1;}).finally(async () => {
    if (browser) {
        const stopped = new Promise(resolve => browser.once('exit', resolve));
        if (socket?.readyState === WebSocket.OPEN) {
            socket.send(JSON.stringify({id: 999999, method: 'Browser.close'}));
        } else {
            browser.kill();
        }
        await stopped;
    }
    if (socket) socket.close();
    await fs.promises.rm(directory, {recursive: true, force: true, maxRetries: 10, retryDelay: 200});
});