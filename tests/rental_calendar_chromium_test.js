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
const formatterFrom = content.indexOf('function formatRentalDisplayDate(');
const formatterTo = content.indexOf('\nfunction ', formatterFrom + 1);
assert(formatterFrom > 0 && formatterTo > formatterFrom, 'Use the real rental date formatter');
const formatter = content.slice(formatterFrom, formatterTo);
const dashboard = fs.readFileSync(path.join(__dirname, '../pages/company_dashboard.php'), 'utf8');
const bootstrapUrl = dashboard.match(/href="(https:\/\/[^"]+bootstrap[^"]+\.min\.css)"/)?.[1];
assert(bootstrapUrl, 'Use the same Bootstrap stylesheet as the real dashboard');
const css = spawnSync('curl', ['-fsSL', '--max-time', '20', bootstrapUrl], {encoding:'utf8', timeout:25000, maxBuffer:2*1024*1024});
assert.equal(css.status, 0, 'Dashboard Bootstrap CSS could not be loaded: ' + css.stderr);
const stubs = `
const rentalLifecycleCsrf='fixture-csrf';
let allRentalObjects=[{id:'a',object_name:'Sal A'},{id:'b',object_name:'Sal B'}];
let allApprovedRentals=[],allPendingRentalRequests=[],allRentalUnavailable=[],calViewStart=null;
let rentalUnavailableToday=null,rentalUnavailableListExpanded=false,rentalUnavailableSaving=false;
window.fixtureErrors=[];window.fixtureWrites=[];window.fixtureToasts=[];
window.fixtureFail=false;window.fixtureReject=false;window.fixtureHold=null;
window.fixtureCodeFailure=false;window.fixtureCodeCalls=[];
window.addEventListener('error',e=>fixtureErrors.push(e.message));
window.addEventListener('unhandledrejection',e=>fixtureErrors.push(String(e.reason)));
const escHtml=value=>String(value??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
const escAttr=escHtml,calcAge=()=>null,contractPaymentBadge=()=>'<span>Ubetalt</span>';
const toast={error:(...args)=>fixtureToasts.push(args)};
window.fixtureModalShows=0;window.fixtureContacts=[];
const bootstrap={Modal:{getOrCreateInstance:()=>({show:()=>{fixtureModalShows++}})}};
document.addEventListener('click',event=>{
 const link=event.target.closest('a[href^="tel:"],a[href^="mailto:"]');
 if(link){fixtureContacts.push(link.getAttribute('href'));event.preventDefault()}
});
window.confirm=()=>true;
const fixturePeriods=[
 {id:'today',rental_object_id:'a',object_name:'Sal A',start_date:'2028-04-03',end_date:'2028-04-03',notes:'<img src=x onerror="window.injected=1"> Service'},
 {id:'ongoing',rental_object_id:'b',object_name:'Sal B',start_date:'2028-04-02',end_date:'2028-04-04',notes:'Vedlikehold '+ 'n'.repeat(180)},
 {id:'legacy',rental_object_id:'a',object_name:'Sal A',start_date:'2028-04-04',end_date:'2028-04-04',notes:null},
 {id:'expired',rental_object_id:'a',object_name:'Sal A',start_date:'2028-04-02',end_date:'2028-04-02',notes:'Historisk'}];
const fixtureRentals=[
 {id:'rental-a',object_name:'Sal A',display_customer_name:'Testkunde A',start_date:'2028-04-03',end_date:'2028-04-05',
  display_email:'kunde@example.invalid',display_phone:'+47 12345678',payment_status:'paid',rental_fee:1250,
  key_box_code:'0123',checkin_email_sent_at:'2028-04-03 09:00:00',logistics:'delivery',delivery_address:'Testadresse',
  notes:'Detaljnotat',cleaning:1,cleaning_fee:250,pickup_time:'08:00:00',end_time:'18:00:00'},
 {id:'rental-b',object_name:'Langt objektnavn som ikke skal presse visningen ut av skjermen',
  display_customer_name:'Testkunde med et langt navn som skal beholde en kompakt visning',
  start_date:'2028-04-04',end_date:'2028-04-04',rental_fee:200},
 {id:'rental-c',object_name:'Sal C',display_customer_name:'Testkunde C',start_date:'2028-04-06',end_date:'2028-04-08',
  display_phone:'87654321',payment_status:'partial',rental_fee:600}
];
const fixturePendingRequests=[
 {id:'pending-april',object_name:'Sal B',status:'pending',approved:0,display_customer_name:'Ventende kunde',start_date:'2028-04-04',end_date:'2028-04-05'},
 {id:'pending-november',object_name:'Sal C',status:'pending',approved:0,display_customer_name:'Novemberkunde',start_date:'2028-11-02',end_date:'2028-11-03'}
];
allApprovedRentals=fixtureRentals;
window.fetch=async (url,options={})=>{
 if(url==='/api/rental_reservation_code.php'){
  const payload=JSON.parse(options.body);fixtureCodeCalls.push(payload);
  if(payload.action==='ensure_upcoming')return {ok:true,json:async()=>({success:true,assigned_count:0,unresolved_ids:[]})};
  const rental=allApprovedRentals.find(r=>r.id===payload.id);
  if(fixtureCodeFailure)return {ok:false,json:async()=>({success:false,message:'Utleien er endret'})};
  if(payload.action==='prepare')return {ok:true,json:async()=>({success:true,token:'fixture-token',
   code:rental.key_box_code||'0037',assigned:!!rental.key_box_code,matches:!!rental.fixtureMatches})};
  rental.key_box_code='0037';rental.fixtureMatches=payload.physical_changed;
  return {ok:true,json:async()=>({success:true,message:'Koden er lagret.'})};
 }
 if(!options.method){
  if(url==='/api/rental_unavailable.php')return {ok:true,json:async()=>({success:true,today:'2028-04-03',periods:fixturePeriods})};
  if(url==='/api/get_rental_schedule.php?approved=1')return {ok:true,json:async()=>({success:true,requests:allApprovedRentals})};
   if(url==='/api/get_rental_schedule.php?approved=0')return {ok:true,json:async()=>({success:true,today:'2028-04-03',requests:fixturePendingRequests})};
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
  check(field('Content').hidden && field('PlanningToggle').getAttribute('aria-expanded')==='false','Entire planning starts collapsed');
  field('PlanningToggle').click();
  check(!field('Content').hidden && field('PlanningToggle').getAttribute('aria-expanded')==='true','Planning header expands controls');
  check(list.hidden&&field('ListToggle').getAttribute('aria-expanded')==='false','List starts collapsed');
  check(field('Count').textContent==='3'&&!list.textContent.includes('Historisk'),'Ended blocks hidden, today and ongoing retained');
  check(document.getElementById('rentalCalendarHost').compareDocumentPosition(manager)&4,'Planning after calendar');
   check(document.getElementById('upcomingContractsContainer').compareDocumentPosition(document.getElementById('rentalCalendarHost'))&4,'Tenant cards before calendar');
  check(proxy('Start').dataset.norwegianDateTimeProxy==='1','Actual shared date enhancer active');
  check(proxy('End').disabled&&!proxy('End').required,'Single-day end proxy cannot block validation');
  check(!list.querySelector('img')&&!document.getElementById('rentalCalendarHost').querySelector('img')&&!window.injected,'Notes escaped in list and calendar');
  check(document.querySelector('.rental-unavailable[title*="Service"]')?.textContent.includes('Sal A'),'Calendar label identifies object and note');
   const pendingBar=document.querySelector('.rental-calendar-request');
   check(pendingBar?.textContent.includes('Forespurt')&&pendingBar.textContent.includes('Ventende kunde')
     &&getComputedStyle(pendingBar).backgroundColor==='rgb(255, 214, 107)','Pending request appears as a clearly labeled yellow calendar bar');
   check(document.querySelector('.rental-calendar-request-key')?.textContent.includes('Forespurte')
     &&document.querySelectorAll('.rental-upcoming-card').length===3,'Request calendar legend is clear without mixing pending requests into approved reservation cards');
   const monthOptions=document.getElementById('rentalUpcomingMonth').options;
   check(monthOptions[monthOptions.length-1]?.value==='2028-11','Pending dates extend the navigable upcoming calendar range');
  check(list.textContent.includes('Utilgjengelig'),'Legacy blank-note fallback retained');
  check(list.querySelector('[title^="Vedlikehold"]')?.textContent.endsWith('…'),'Long notes truncated with full tooltip');
  toggleRentalUnavailableList();
  check(list.textContent.includes('03.04.2028 – 03.04.2028')&&list.textContent.includes('02.04.2028 – 04.04.2028')
    &&!list.textContent.includes('2028.04.03'),'Expanded unavailable cards use DD.MM.YYYY for days and periods');
  const cards=Array.from(document.querySelectorAll('.rental-upcoming-card'));
  check(cards.length===3,'Upcoming tenant cards rendered');
  const first=cards[0],emptyContact=cards[1],phoneOnly=cards[2];
  check(first.textContent.includes('Testkunde A')&&first.textContent.includes('Sal A')
    &&first.querySelector('.rental-period').textContent==='03.04.2028 – 05.04.2028','Full period and identity remain visible');
   check(first.textContent.includes('NOK')&&first.textContent.includes('Tilgangskode:')
     &&first.textContent.includes('Sendt')&&!first.textContent.includes('Detaljnotat'),'Monthly card shows price, code status and check-in status');
  check(first.textContent.includes('Betalt')&&phoneOnly.textContent.includes('Delvis betalt'),'Short payment states preserved');
  check(!emptyContact.querySelector('a')&&!emptyContact.querySelector('.rental-customer-links')
    &&!emptyContact.textContent.includes('Ingen telefon')&&!emptyContact.textContent.includes('Ingen e-post'),'No empty contact actions or large placeholders');
  check(phoneOnly.querySelectorAll('a').length===1&&phoneOnly.querySelector('a').getAttribute('href')==='tel:87654321','Partial contact information stays usable');
  const modalShows=fixtureModalShows;
  first.querySelector('a[href^="tel:"]').click();first.querySelector('a[href^="mailto:"]').click();
  check(fixtureContacts.includes('tel:+47 12345678')&&fixtureContacts.includes('mailto:kunde@example.invalid')
    &&fixtureModalShows===modalShows,'Contact links act directly without opening details');
  check(Array.from(first.querySelectorAll('a,button')).every(el=>el.tabIndex>=0),'Contact and details controls keyboard-focusable');
  const open=first;
  check(open.getAttribute('role')==='button'&&open.tabIndex===0&&!open.querySelector('button'),'Entire card is focusable; details button removed');
  open.click();
  const detailBody=document.getElementById('detailsModalBody');
  check(fixtureModalShows===modalShows+1&&detailBody.textContent.includes('Testkunde A')
    &&detailBody.textContent.includes('Detaljnotat')&&detailBody.textContent.includes('0123')
    &&detailBody.textContent.includes('Innsjekk-e-post')&&detailBody.textContent.includes('Logistikk')
    &&detailBody.textContent.includes('Registrer betaling')===false,'Correct rental details, including secondary information and payment state');
  check(detailBody.textContent.includes('03.04.2028')&&detailBody.textContent.includes('05.04.2028'),'Modal dates stay Norwegian under global formatter collision');
  check(detailBody.querySelector('a[href="tel:+47 12345678"]')
    &&detailBody.querySelector('a[href="mailto:kunde@example.invalid"]'),'Modal contacts are clickable too');
  check(detailBody.querySelector('select')&&Array.from(detailBody.querySelectorAll('button')).some(el=>el.textContent.includes('Avlys leie')),'Existing admin actions retained');
  cards[2].click();
  check(detailBody.textContent.includes('Testkunde C')&&!detailBody.textContent.includes('Testkunde A'),'Details selects the requested rental');
   check(document.getElementById('rentalUpcomingMonth').value==='2028-04','Monthly overview starts in current month');
   rentalChangeMonth(1);
   check(document.querySelectorAll('.rental-upcoming-card').length===0,'Empty next month shown independently of calendar');
   rentalChangeMonth(-1);
  check(document.documentElement.scrollWidth<=width+1,'No page-level horizontal overflow');
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
    check(card.textContent.includes('Tilgangskode:')===Boolean(expected),'Card displays valid assigned access code');
   check(detail.textContent.includes('Tilgangskode')===Boolean(expected),'Details suppress complete invalid-code field');
   check(![bar,card,detail].some(el=>el.textContent.includes('SECRET')),'No fallback to general venue code');
    if(expected)check([bar,card,detail].every(el=>el.textContent.includes(expected)),'Valid leading-zero/alphanumeric codes retained in calendar, cards and details');
  }
   // Actual code-panel asset, with API transport stubbed (server tested in MySQL suite).
   allApprovedRentals=[{id:'next',object_name:'Sal A',start_date:'2028-04-04',end_date:'2028-04-05',
    can_prepare_code:true,key_box_code:'0037',rental_fee:100},
    {id:'later',object_name:'Sal A',start_date:'2028-05-04',end_date:'2028-05-05',can_prepare_code:false}];
   showRentalCalDetail('later');await settle();
   const priorDetailsCalls=fixtureCodeCalls.length;
   check(fixtureCodeCalls.every(call=>call.action==='ensure_upcoming'),'Later rental never asks code questions');
   showRentalCalDetail('next');await settle();
   let codeHost=document.getElementById('rentalReservationCodeHost');
   check(codeHost.textContent.includes('0037')&&codeHost.querySelector('[data-save]').disabled,'Next reservation shows assigned code requiring confirmation');
   codeHost.querySelector('[data-dismiss]').click();
   check(fixtureCodeCalls.slice(priorDetailsCalls).every(call=>call.action==='prepare')&&allApprovedRentals[0].key_box_code==='0037','Dismiss causes no write or code generation');
   showRentalCalDetail('next');await settle();
   codeHost=document.getElementById('rentalReservationCodeHost');
   codeHost.querySelector('[data-save]').click();await settle();await settle();
   check(fixtureCodeCalls.at(-1).action==='prepare'
    &&allApprovedRentals[0].key_box_code==='0037','Unchecked confirmation cannot write');
   showRentalCalDetail('next');await settle();
   codeHost=document.getElementById('rentalReservationCodeHost');
   check(codeHost.querySelector('[data-save]').disabled,'Physical confirmation required for already assigned code');
   const physical=codeHost.querySelector('[data-physical]');physical.checked=true;physical.dispatchEvent(new Event('change'));
   codeHost.querySelector('[data-save]').click();await settle();await settle();
   check(fixtureCodeCalls.some(call=>call.action==='save'&&call.physical_changed===true),'Physical change explicitly sent');
   showRentalCalDetail('next');await settle();
   check(!document.getElementById('rentalReservationCodeHost').querySelector('button'),'Matching codes suppress all questions');
   allApprovedRentals[0].fixtureMatches=false;fixtureCodeFailure=true;
   showRentalCalDetail('next');await settle();
   check(document.getElementById('rentalReservationCodeHost').textContent.includes('Utleien er endret'),'Stale/error response explained without code actions');
   fixtureCodeFailure=false;
   const priorCodeCalls=fixtureCodeCalls.length;
   allApprovedRentals[0].start_date=getRentalOsloToday();
   showRentalCalDetail('next');await settle();
   check(fixtureCodeCalls.length===priorCodeCalls,'Today blocks prompt even with stale eligibility flag');
   allApprovedRentals=fixtureRentals;
   check(fixtureErrors.length===0,'No browser errors: '+fixtureErrors.join('; '));
  return {passed:true,width};
 }catch(error){return {passed:false,error:error.stack||String(error),errors:fixtureErrors}}
})()
`;
let browser, socket;
async function main() {
    const file = path.join(directory, 'fixture.html');
    const markup = content.slice(htmlFrom, htmlTo).replace('style="display: none;"', '');
    fs.writeFileSync(file, `<!doctype html><html lang="nb"><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>${css.stdout}</style>${styles}<style>#detailsModal{display:none}</style></head><body><div id="rental-module">${markup}</div><div id="detailsModal"><div id="detailsModalTitle"></div><div id="detailsModalBody"></div></div><script>${stubs}</script><script>${fs.readFileSync(path.join(__dirname,'../assets/rental_reservation_codes.js'),'utf8')}</script><script>${formatter}\n${content.slice(from, to)}</script><script>window.fmtDate=value=>String(value??'').slice(0,10).replaceAll('-','.');</script>${enhancement}</body></html>`);
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
    await send('Target.activateTarget', {targetId});
    await send('Emulation.setFocusEmulationEnabled', {enabled:true}, sessionId);
    // Check Norwegian behavior even when the browser is on another continent.
    await send('Emulation.setTimezoneOverride', {timezoneId: 'America/Los_Angeles'}, sessionId);
    for (const width of [390, 768, 1280]) {
        await send('Emulation.setDeviceMetricsOverride', {width, height: 844, deviceScaleFactor: 1, mobile: width === 390}, sessionId);
        await send('Page.navigate', {url: 'file://' + file + '?width=' + width}, sessionId);
        await new Promise(resolve => setTimeout(resolve, 200));
        await send('Runtime.evaluate', {expression: 'loadRentalUnavailable().then(()=>loadApprovedSchedule())', awaitPromise: true}, sessionId);
        if (process.env.RENTAL_CALENDAR_SCREENSHOT_DIR) {
            await send('Runtime.evaluate', {expression: "fixturePeriods[0].notes='Service';fixturePeriods[1].notes='Vedlikehold';renderRentalUnavailable();renderRentalCalendar();"}, sessionId);
            const image = await send('Page.captureScreenshot', {format:'png',captureBeyondViewport:false}, sessionId);
            fs.mkdirSync(process.env.RENTAL_CALENDAR_SCREENSHOT_DIR, {recursive:true});
            fs.writeFileSync(path.join(process.env.RENTAL_CALENDAR_SCREENSHOT_DIR, 'rental-'+width+'.png'), Buffer.from(image.data, 'base64'));
            await send('Runtime.evaluate', {expression: `fixturePeriods[0].notes='<img src=x onerror="window.injected=1"> Service';fixturePeriods[1].notes="Vedlikehold "+ "n".repeat(180);renderRentalUnavailable();`}, sessionId);
        }
        const result = await send('Runtime.evaluate', {expression: checks, awaitPromise: true, returnByValue: true}, sessionId);
        assert.equal(result.result?.value?.passed, true, JSON.stringify(result));
        assert.equal(result.result.value.width, width);
        await send('Runtime.evaluate', {expression: 'allApprovedRentals=fixtureRentals;renderRentalCalendar();document.querySelector(".rental-upcoming-card").focus();', returnByValue:true}, sessionId);
        const before = await send('Runtime.evaluate', {expression:'fixtureModalShows',returnByValue:true}, sessionId);
        await send('Input.dispatchKeyEvent', {type:'keyDown',key:'Enter',code:'Enter',windowsVirtualKeyCode:13,nativeVirtualKeyCode:13,text:'\r',unmodifiedText:'\r'}, sessionId);
        await send('Input.dispatchKeyEvent', {type:'keyUp',key:'Enter',code:'Enter',windowsVirtualKeyCode:13}, sessionId);
        const keyboard = await send('Runtime.evaluate', {expression:'({shows:fixtureModalShows,correct:document.getElementById("detailsModalBody").textContent.includes("Testkunde A")})',returnByValue:true}, sessionId);
        assert.equal(keyboard.result.value.shows, before.result.value+1, 'Enter opens details');
        assert.equal(keyboard.result.value.correct, true, 'Keyboard opens correct rental');
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