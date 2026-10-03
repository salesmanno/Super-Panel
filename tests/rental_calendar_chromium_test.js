// Actual planning/calendar functions and dashboard date enhancer; local data
// only. This fixture never authenticates to or writes to the application's API.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawn, spawnSync} = require('node:child_process');
if (typeof WebSocket === 'undefined') {
    const child = spawnSync(process.execPath, ['--experimental-websocket', __filename], {stdio: 'inherit'});
    process.exit(child.status ?? 1);
}
const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'rental-calendar-'));
const content = fs.readFileSync(path.join(__dirname, '../modules/rental_content.php'), 'utf8');
const enhancement = fs.readFileSync(path.join(__dirname, '../includes/norwegian_datetime_inputs.php'), 'utf8');
const from = content.indexOf('function renderRentalUnavailable() {');
const to = content.indexOf('async function updateRentalScheduleLifecycle(', from);
const htmlFrom = content.indexOf('<div id="rental-upcoming"');
const htmlTo = content.indexOf('<div id="rental-results"', htmlFrom);
assert(from > 0 && to > from && htmlFrom > 0 && htmlTo > htmlFrom);
const colors = content.slice(content.indexOf('const CAL_COLORS ='), content.indexOf("document.addEventListener('DOMContentLoaded'"));
const styles = content.match(/<style>[\s\S]*?<\/style>/)[0];
const stubs = `
const rentalLifecycleCsrf='fixture-csrf';
let allRentalObjects=[{id:'a',object_name:'Sal A'},{id:'b',object_name:'Sal B'}];
let allApprovedRentals=[],allRentalUnavailable=[],calViewStart=null;
let rentalUnavailableToday=null,rentalUnavailableListExpanded=false,rentalUnavailableSaving=false;
window.fixtureErrors=[];window.fixtureWrites=[];window.fixtureToasts=[];
window.fixtureFail=false;window.fixtureReject=false;window.fixtureHold=null;
window.addEventListener('error',e=>fixtureErrors.push(e.message));
window.addEventListener('unhandledrejection',e=>fixtureErrors.push(String(e.reason)));
const escHtml=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const escAttr=escHtml,calcAge=()=>null,contractPaymentBadge=()=>'<span>Ubetalt</span>';
const fmtDate=value=>String(value??'').slice(0,10).split('-').reverse().join('.');
const toast={error:(...args)=>fixtureToasts.push(args)};
const bootstrap={Modal:{getOrCreateInstance:()=>({show:()=>{}})}};
window.confirm=()=>true;
const fixturePeriods=[
 {id:'today',rental_object_id:'a',object_name:'Sal A',start_date:'2028-04-03',end_date:'2028-04-03',notes:'<img src=x onerror="window.injected=1"> Service'},
 {id:'ongoing',rental_object_id:'b',object_name:'Sal B',start_date:'2028-04-02',end_date:'2028-04-04',notes:'Vedlikehold '+ 'n'.repeat(180)},
 {id:'legacy',rental_object_id:'a',object_name:'Sal A',start_date:'2028-04-04',end_date:'2028-04-04',notes:null},
 {id:'expired',rental_object_id:'a',object_name:'Sal A',start_date:'2028-04-02',end_date:'2028-04-02',notes:'Historisk'}];
window.fetch=async (url,options={})=>{
 if(!options.method){
  if(url==='/api/rental_unavailable.php')return {ok:true,json:async()=>({success:true,today:'2028-04-03',periods:fixturePeriods})};
  if(url==='/api/get_rental_schedule.php?approved=1')return {ok:true,json:async()=>({success:true,requests:allApprovedRentals})};
  throw new Error('Unexpected GET: '+url);
 }
 if(!url.startsWith('/api/rental_unavailable.php'))throw new Error('Unexpected write: '+url);
 fixtureWrites.push({url,...options,payload:options.body?JSON.parse(options.body):null});
 if(fixtureHold)await fixtureHold;
 if(fixtureFail)throw new Error('Fixture network error');
 return {ok:!fixtureReject,json:async()=>({success:!fixtureReject,message:'Fixture overlap'})};
};
${colors}
document.addEventListener('DOMContentLoaded',()=>setRentalUnavailableMode('day'));
`;
const checks = `
(async()=>{
 const check=(ok,message)=>{if(!ok)throw new Error(message)};
 const settle=()=>new Promise(resolve=>setTimeout(resolve,20));
 const width=innerWidth;
 const field=id=>document.getElementById('rentalUnavailable'+id);
 const proxy=id=>field(id).previousElementSibling;
 const change=(id,value)=>{
  proxy(id).value=value;
  proxy(id).dispatchEvent(new Event('input',{bubbles:true}));
  proxy(id).dispatchEvent(new Event('change',{bubbles:true}));
 };
 try{
  await loadRentalUnavailable();await loadApprovedSchedule();await settle();
  const manager=field('Manager'),form=field('Form'),list=field('List');
  check(list.hidden&&field('ListToggle').getAttribute('aria-expanded')==='false','List starts collapsed');
  check(field('Count').textContent==='3'&&!list.textContent.includes('Historisk'),'Ended blocks hidden, today and ongoing retained');
  check(document.getElementById('rentalCalendarHost').compareDocumentPosition(manager)&4,'Planning after calendar');
  check(manager.compareDocumentPosition(document.getElementById('upcomingContractsContainer'))&4,'Planning before rental cards');
  check(proxy('Start').dataset.norwegianDateTimeProxy==='1','Actual shared date enhancer active');
  check(proxy('End').disabled&&!proxy('End').required,'Single-day end proxy cannot block validation');
  check(!list.querySelector('img')&&!document.getElementById('rentalCalendarHost').querySelector('img')&&!window.injected,'Notes escaped in list and calendar');
  check(document.querySelector('.rental-unavailable[title*="Service"]')?.textContent.includes('Sal A'),'Calendar label identifies object and note');
  check(list.textContent.includes('Utilgjengelig'),'Legacy blank-note fallback retained');
  check(list.querySelector('[title^="Vedlikehold"]')?.textContent.endsWith('…'),'Long notes truncated with full tooltip');
  toggleRentalUnavailableList();
  field('Objects').options[0].selected=true;field('Notes').value='Utkast';
  change('Start','05.04.2028');
  calNavigate(1);calNavigate(-1);await loadRentalUnavailable();await loadApprovedSchedule();await settle();
  check(field('Manager')===manager&&field('Form')===form,'Same planning DOM retained on redraw');
  check(!list.hidden&&field('Notes').value==='Utkast'&&field('Start').value==='2028-04-05'
   &&field('Objects').options[0].selected,'List expansion, dates, selected objects and notes survive refresh/navigation');
  document.querySelector('.rental-calendar-day:not(:disabled)').click();
  check(field('Start').value==='2028-04-03'&&field('End').value==='2028-04-03','Day click prefills both dates');
  check(field('Notes').value==='Utkast'&&field('Objects').options[0].selected,'Day selection preserves object/note draft');
  await saveRentalUnavailable({preventDefault(){}});await settle();
  const saved=fixtureWrites.at(-1);
  check(saved.method==='POST'&&saved.payload.start_date==='2028-04-03'&&saved.payload.end_date==='2028-04-03'
   &&saved.payload.notes==='Utkast'&&saved.payload.rental_object_ids[0]==='a','Single-day canonical payload saved');
  check(saved.headers['X-CSRF-Token']==='fixture-csrf','CSRF forwarded');
  check(!field('Start').value&&!field('End').value&&!proxy('Start').value&&!field('Notes').value,'Reset clears canonical dates, proxies and notes');
  check(!list.hidden,'Successful save retains list expansion');
  editRentalUnavailable('ongoing');await settle();
  check(field('ModePeriod').checked&&!field('EndField').hidden&&!proxy('End').disabled,'Edit restores period mode');
  check(proxy('Start').value==='02.04.2028'&&proxy('End').value==='04.04.2028'
   &&field('Notes').value===fixturePeriods[1].notes,'Edit restores Norwegian dates and original notes');
  change('End','31.04.2028');
  check(!proxy('End').checkValidity(),'Invalid text has validation error');
  editRentalUnavailable('legacy');await settle();
  check(proxy('End').disabled&&proxy('End').checkValidity()&&!field('Notes').value,'Editing single-day block clears stale hidden-end validation');
  field('Notes').value='Ny årsak';await saveRentalUnavailable({preventDefault(){}});await settle();
  check(fixtureWrites.at(-1).method==='PUT'&&fixtureWrites.at(-1).payload.id==='legacy'
   &&fixtureWrites.at(-1).payload.notes==='Ny årsak','Edit PUT carries notes');
  prefillRentalUnavailableDate('2028-04-06');field('Objects').options[0].selected=true;
  field('Notes').value='Behold dette';fixtureFail=true;
  await saveRentalUnavailable({preventDefault(){}});await settle();
  check(field('Notes').value==='Behold dette'&&field('Start').value==='2028-04-06'
   &&!field('Submit').disabled,'Network error retains usable draft');
  fixtureFail=false;fixtureReject=true;
  await saveRentalUnavailable({preventDefault(){}});
  check(field('Notes').value==='Behold dette'&&!field('Submit').disabled,'Server rejection retains draft');
  fixtureReject=false;
  field('ModePeriod').checked=true;setRentalUnavailableMode('period');
  change('End','05.04.2028');
  const before=fixtureWrites.length;await saveRentalUnavailable({preventDefault(){}});
  check(fixtureWrites.length===before,'Reversed period never submitted');
  change('End','08.04.2028');field('Objects').options[1].selected=true;
  let release;fixtureHold=new Promise(resolve=>release=resolve);
  const pending=saveRentalUnavailable({preventDefault(){}});
  await settle();
  check(field('Submit').disabled&&field('Notes').disabled,'Pending save prevents draft replacement');
  const note=field('Notes').value;
  prefillRentalUnavailableDate('2028-04-10');editRentalUnavailable('today');resetRentalUnavailableForm();
  await saveRentalUnavailable({preventDefault(){}});
  check(fixtureWrites.length===before+1&&field('Notes').value===note&&field('Start').value==='2028-04-06','Repeated submit and pending reset/edit/day click are blocked');
  release();fixtureHold=null;await pending;await settle();
  check(fixtureWrites.at(-1).payload.rental_object_ids.length===2
   &&fixtureWrites.at(-1).payload.end_date==='2028-04-08','Multi-object period submitted once');
  check(!field('Start').value&&!field('End').value&&proxy('End').disabled,'Repeated reset clears both dates and restores one-day mode');
  await deleteRentalUnavailable('today');
  check(fixtureWrites.at(-1).method==='DELETE'&&fixtureWrites.at(-1).url.endsWith('?id=today'),'Delete action submits selected block');
  for(const [code,expected] of [[null,''],[undefined,''],['',''],[' ',''],[0,''],['0',''],['0000',''],
      ['too-long',''],['12!',''],['1234567',''],['0123','0123'],[' AB12 ','AB12'],[4321,'4321']]){
   check(validAssignedRentalCode(code)===expected,'Assigned-code validation: '+String(code));
   allApprovedRentals=[{id:'rental',object_name:'Lokale',start_date:'2028-04-03',end_date:'2028-04-03',
    rental_fee:100,key_box_code:code,object_key_box_code:'SECRET',display_customer_name:'Testkunde'}];
   renderRentalCalendar();showRentalCalDetail('rental');
   const bar=document.querySelector('.rental-calendar-bar:not(.rental-unavailable)');
   const card=document.querySelector('.rental-upcoming-card');
   const detail=document.getElementById('detailsModalBody');
   check(bar.textContent.includes('Kode:')===Boolean(expected),'Calendar suppresses complete invalid-code field');
   check(card.textContent.includes('Tilgangskode:')===Boolean(expected),'Cards suppress complete invalid-code field');
   check(detail.textContent.includes('Tilgangskode')===Boolean(expected),'Details suppress complete invalid-code field');
   check(![bar,card,detail].some(el=>el.textContent.includes('SECRET')),'No fallback to general venue code');
   if(expected)check([bar,card,detail].every(el=>el.textContent.includes(expected)),'Valid leading-zero/alphanumeric codes retained');
  }
  check(fixtureErrors.length===0,'No browser errors: '+fixtureErrors.join('; '));
  return {passed:true,width};
 }catch(error){return {passed:false,error:error.stack||String(error),errors:fixtureErrors}}
})()
`;
let browser, socket;
async function main() {
    const file = path.join(directory, 'fixture.html');
    const markup = content.slice(htmlFrom, htmlTo).replace('style="display: none;"', '');
    fs.writeFileSync(file, `<!doctype html><html lang="nb"><head><meta name="viewport" content="width=device-width,initial-scale=1">${styles}</head><body><div id="rental-module">${markup}</div><div id="detailsModal"><div id="detailsModalTitle"></div><div id="detailsModalBody"></div></div><script>${stubs}</script><script>${content.slice(from, to)}</script>${enhancement}</body></html>`);
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
        const message = JSON.parse(event.data), callbacks = pending.get(message.id);
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
    // Check Norwegian behavior even when the browser is on another continent.
    await send('Emulation.setTimezoneOverride', {timezoneId: 'America/Los_Angeles'}, sessionId);
    for (const width of [390, 768, 1280]) {
        await send('Emulation.setDeviceMetricsOverride', {width, height: 844, deviceScaleFactor: 1, mobile: width === 390}, sessionId);
        await send('Page.navigate', {url: 'file://' + file + '?width=' + width}, sessionId);
        await new Promise(resolve => setTimeout(resolve, 200));
        const result = await send('Runtime.evaluate', {expression: checks, awaitPromise: true, returnByValue: true}, sessionId);
        assert.equal(result.result?.value?.passed, true, JSON.stringify(result));
        assert.equal(result.result.value.width, width);
        console.log('Actual rental planning/calendar with shared date enhancer passed at ' + width + 'px');
    }
}
main().catch(error => {console.error(error);process.exitCode = 1;}).finally(async () => {
    if (browser) {
        const stopped = new Promise(resolve => browser.once('exit', resolve));
        if (socket?.readyState === WebSocket.OPEN) socket.send(JSON.stringify({id: 999999, method: 'Browser.close'}));
        else browser.kill();
        await stopped;
    }
    if (socket) socket.close();
    await fs.promises.rm(directory, {recursive: true, force: true, maxRetries: 10, retryDelay: 200});
});