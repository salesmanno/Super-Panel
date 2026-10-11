// Real rental markup, shared editor and Bootstrap, with disposable API data.
// No application login, database or write endpoint is contacted by this test.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawn, spawnSync} = require('node:child_process');
if (typeof WebSocket === 'undefined') {
    const run = spawnSync(process.execPath, ['--experimental-websocket', __filename], {stdio:'inherit'});
    process.exit(run.status ?? 1);
}
const root = path.join(__dirname, '..');
const dir = fs.mkdtempSync(path.join(os.tmpdir(), 'rental-dashboard-'));
function download(url) {
    const run = spawnSync('curl', ['--fail','--silent','--show-error','--max-time','30',url], {encoding:'utf8',maxBuffer:2*1024*1024});
    assert.equal(run.status,0,run.stderr);
    return run.stdout;
}
const bootstrapCss = download('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
const bootstrapJs = download('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js');
const render = spawnSync('php', ['-r','session_start(); $_SESSION["admin"]=["id"=>"fixture","company_id"=>"a"]; include "modules/rental_content.php";'], {cwd:root,encoding:'utf8',maxBuffer:2*1024*1024});
assert.equal(render.status,0,render.stderr);
let moduleHtml = render.stdout.replace(/<script src="(\/assets\/[^"]+)"[^>]*><\/script>/g,
    (_,src)=>'<script>'+fs.readFileSync(path.join(root,src),'utf8')+'</script>');
moduleHtml = moduleHtml.replace(/<link[^>]+href="(\/assets\/[^"]+)"[^>]*>/g,
    (_,src)=>'<style>'+fs.readFileSync(path.join(root,src),'utf8')+'</style>');
const stubs = `
window.testErrors=[];window.fixtureUnexpected=[];window.fixtureWrites=[];window.fixtureObjectUpdates=[];window.fixtureLifecycleWrites=[];
window.addEventListener('error',e=>testErrors.push(e.message));
window.addEventListener('unhandledrejection',e=>testErrors.push(String(e.reason)));
window.confirmAnswer=true;window.confirm=()=>confirmAnswer;
window.toast={loading:()=>null,success:()=>{},quickError:()=>{},error:()=>{}};
window.fixtureFailSave=false;window.fixtureFailFields=false;window.fixtureFailObjects=false;
window.fixtureHold=null;
const tomorrow=new Date();tomorrow.setDate(tomorrow.getDate()+1);
const iso=tomorrow.toISOString().slice(0,10);
const image='https://fixture.invalid/house.svg';
window.fixtureObjects=Array.from({length:16},(_,i)=>({id:'object-'+i,object_name:i===0?'Sal <img src=x onerror="window.injected=1">':'Utleieobjekt '+i,category:i===15?'tool':'venue',rental_price:100+i,weekend_price:0,saturday_price:i===0?175:0,sunday_price:i===0?190:0,images:[{url:image}],is_available:1,display_status:'available',type_data:{access_code:'1234',cleaning_fee:200}}));
window.fixtureRequest={id:'request-a',rental_object_id:'object-0',object_name:'Sal A',category:'venue',status:'pending',approved:0,start_date:iso,end_date:iso,display_customer_name:'Testkunde',display_email:'kunde@example.invalid',customer_name:'Testkunde',customer_email:'kunde@example.invalid',pickup_time:'09:00:00',return_time:'17:30:00',rental_fee:100,decision_version:'v1'};
window.fixtureUpcoming={...fixtureRequest,id:'schedule-a',status:'approved',approved:1,access_code:'0037',is_next_upcoming:1,requires_physical_code_confirmation:1,payment_status:'unpaid'};
window.fixtureFields=['email','phone','city'].map((name,i)=>({field_name:name,field_label:{email:'E-postadresse',phone:'Telefonnummer',city:'Poststed'}[name],field_type:name==='email'?'email':name==='phone'?'tel':'text',is_visible:i===0,is_required:false,sort_order:i,options:[],validation_rules:{}}));
window.fixtureCalls=[];
window.fetch=async function(url,options={}){
 fixtureCalls.push(String(url));
 const payload=options.body?JSON.parse(options.body):null;let result,status=200;
 if(String(url).includes('save_entity_form_fields')){
   fixtureWrites.push(payload);if(fixtureHold)await fixtureHold;
   if(fixtureFailSave){status=409;result={success:false,message:'Oppsettet er endret. Utkastet er beholdt.'};}
   else {fixtureFields=payload.fields;result={success:true,revision:'b'.repeat(64),fields:fixtureFields};}
 }else if(String(url).includes('get_entity_form_fields')){
   if(String(url).includes('csrf_only=1'))result={success:true,csrf_token:'fixture-csrf'};
   else if(fixtureFailFields){status=400;result={success:false,message:'Skjemafeltet E-post (email) har ugyldig JSON i validation_rules.'};}
   else result={success:true,csrf_token:'fixture-csrf',revision:'a'.repeat(64),fields:fixtureFields,presets:[{key:'contact',label:'Kontaktinformasjon',fields:fixtureFields}]};
 }else if(String(url).includes('get_rental_objects')){
   if(fixtureFailObjects){status=500;result={success:false,message:'Kunne ikke hente objekter'};}
   else result={success:true,objects:fixtureObjects,object:fixtureObjects[0]};
  }else if(String(url).includes('update_rental_object')){
   fixtureObjectUpdates.push(payload);const updated=fixtureObjects.find(item=>String(item.id)===String(payload.id));
   if(updated){if(payload.type_data)updated.type_data={...updated.type_data,...payload.type_data};if(payload.is_available!==undefined)updated.is_available=payload.is_available;}
   result={success:true};
  }
  else if(String(url).includes('get_payment_methods'))result={success:true,payment_methods:[{method_type:'cash',method_name:'Kontant'}]};
  else if(String(url).includes('get_customers_simple'))result={success:true,customers:[]};
  else if(String(url).includes('update_rental_schedule_lifecycle')){fixtureLifecycleWrites.push(payload);result={success:true,message:'Betaling registrert'};}
  else if(String(url).includes('get_rental_contracts'))result={success:true,contracts:String(url).includes('status=draft')?[]:[{id:'contract-a',object_id:'object-0',object_name:'Sal A',status:'active',start_date:iso,end_date:iso}]};
 else if(String(url).includes('get_rental_schedule'))result={success:true,requests:[String(url).includes('approved=0')?fixtureRequest:fixtureUpcoming],decision_email_enabled:false};
 else if(String(url).includes('rental_unavailable'))result={success:true,periods:[],today:new Date().toISOString().slice(0,10)};
 else if(String(url).includes('get_rental_results'))result={success:true,summary:{},objects:[],recent:[]};
 else if(String(url).includes('rental_reservation_code'))result={success:true,assigned_count:0,unresolved_ids:[],token:'code-token',code:'0037',assigned:true,matches:false};
 else {fixtureUnexpected.push(String(url));throw new Error('Unexpected API '+url);}
 return {ok:status===200,status,json:async()=>result,text:async()=>JSON.stringify(result)};
};
function showSubTabContent(slug,id){document.querySelectorAll('#rental-module .subtab-content').forEach(e=>e.style.display=e.id===id?'block':'none');}
`;
const nav='<nav id="subtabs-rental"><a class="nav-link active" href="#rental-objects">Utleiedashboard</a><a class="nav-link" href="#rental-requests">Forespurt utleie</a><a class="nav-link" href="#rental-upcoming">Kommende utleie</a></nav>';
const fixture=path.join(dir,'fixture.html');
fs.writeFileSync(fixture,'<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1">'+
    '<style>'+bootstrapCss+'</style><style>'+fs.readFileSync(path.join(root,'assets/dashboard-module-theme.css'),'utf8')+'</style>'+
    '<style>body{background:#f1f6f8;padding:24px}#subtabs-rental{display:flex;gap:20px;margin-bottom:24px}#content-rental{max-width:1240px;margin:auto}@media(max-width:600px){body{padding:10px}#subtabs-rental{font-size:12px;gap:12px}}</style>'+
    '<script>'+bootstrapJs+'</script><script>'+stubs+'</script>'+nav+'<main id="content-rental">'+moduleHtml+'</main>');
const checks=`(async()=>{
 const check=(ok,message)=>{if(!ok)throw new Error(message)};
 const delay=(n=50)=>new Promise(r=>setTimeout(r,n));
  const wait=async(fn,label)=>{for(let i=0;i<120;i++){if(fn())return;await delay()}throw new Error('Timed out: '+label+'; visible modals='+[...document.querySelectorAll('.modal.show')].map(m=>m.id).join(',')+'; backdrops='+document.querySelectorAll('.modal-backdrop').length)};
 await wait(()=>document.querySelectorAll('[data-rental-fields]').length>=16,'objects');
 await wait(()=>document.getElementById('rentalMetricUpcoming')?.textContent==='1','metrics');
 check(document.getElementById('rentalMetricRequests').textContent==='1','requests metric');
 check(document.getElementById('rentalMetricContracts').textContent==='1','active contracts metric');
 await loadContracts('draft');
 check(document.getElementById('rentalMetricContracts').textContent==='1','contract filtering does not overwrite active total');
 check(!window.injected,'object names are escaped');
 check(!document.querySelector('#rental-module > .d-flex [onclick*="openRentalCreate"]'),'no redundant header create button');
 check(document.documentElement.scrollWidth<=innerWidth+1,'dashboard fits viewport');
  let firstCard=document.getElementById('rental-card-object-0');
  check(firstCard.textContent.includes('100 NOK/dag')&&firstCard.textContent.includes('Lørdag 175')&&firstCard.textContent.includes('Søndag 190'),'daily and weekend prices displayed on object card');
  check(firstCard.querySelector('[data-rental-access-value] code')?.textContent==='1234','current physical access code is visible directly on venue card');
  check(firstCard.querySelector('.rental-contract-control')&&firstCard.querySelectorAll('.rental-card-menu .dropdown-item').length===3,'contract action remains separate from three-item overflow menu');
  const menuButton=firstCard.querySelector('.rental-menu-trigger');menuButton.click();
  const menuItems=()=>[...document.querySelectorAll('[data-rental-menu-owner="object-0"] .dropdown-item')];
  const menu=()=>document.querySelector('[data-rental-menu-owner="object-0"]');
  check(menuItems().map(x=>x.textContent.trim().replace(/\s+/g,' ')).join('|').includes('Rediger|Skjul|Slett'),'three-dot menu exposes Rediger, Skjul, Slett');
  const menuRect=menu().getBoundingClientRect();
  check(menu().parentElement===document.body&&menuRect.top>=0&&menuRect.bottom<=innerHeight,'overflow menu escapes the scrolling object list and remains tappable');
  menuItems()[0].click();
  await wait(()=>document.getElementById('rentalModal').classList.contains('show'),'edit modal');await delay(350);
  check(!document.querySelector('#formFieldsContainer select[name="category"]')&&document.querySelector('#formFieldsContainer input[type="hidden"][name="category"]')?.value==='venue','edit category is display-only and preserved hidden');
  const editName=document.querySelector('#rentalForm [name="object_name"]');editName.value='Ulagret navn';
  window.confirmAnswer=false;bootstrap.Modal.getOrCreateInstance(document.getElementById('rentalModal')).hide();await delay(100);
  check(document.getElementById('rentalModal').classList.contains('show'),'dirty edit modal prevents accidental close');
  window.confirmAnswer=true;bootstrap.Modal.getOrCreateInstance(document.getElementById('rentalModal')).hide();await wait(()=>!document.getElementById('rentalModal').classList.contains('show'),'confirmed edit modal close');await delay(150);
  menuButton.click();menuItems()[1].click();await delay(80);
  check(fixtureObjectUpdates.at(-1)?.is_available===0,'Skjul reuses existing availability API');
  menuButton.click();
  check(menuItems()[1].textContent.trim()==='Vis igjen','hidden object immediately offers reverse action');
  menuItems()[1].click();await delay(80);
  check(fixtureObjectUpdates.at(-1)?.is_available===1,'Vis igjen reverses visibility without reload');
  menuButton.click();
  check(menuItems()[1].textContent.trim()==='Skjul','shown object again offers hide');
  menuItems()[1].click();await delay(80);
  check(fixtureObjectUpdates.at(-1)?.is_available===0,'same card can hide again without reload');
  check(firstCard.querySelector('.rental-access-control'),'venue has prominent direct access-code action');
  firstCard.querySelector('.rental-access-control').click();
  await wait(()=>document.getElementById('rental-code-object-0')?.value==='1234','access code fetched without opening mutation');
  check(fixtureObjectUpdates.length===3,'opening access-code control does not mutate');
  const codeForm=document.getElementById('rental-code-object-0').closest('form');
  document.getElementById('rental-code-object-0').value='9841';codeForm.requestSubmit();
  await wait(()=>fixtureObjectUpdates.length===4,'access code explicit save');
  check(fixtureObjectUpdates.at(-1).type_data.access_code==='9841','access code saves through existing card API');
  check(firstCard.querySelector('[data-rental-access-value] code')?.textContent==='9841','explicitly saved access code immediately syncs to card');
  await loadRentalObjects();await wait(()=>document.querySelector('#rental-card-object-0 [data-rental-access-value] code')?.textContent==='9841','access code persists after object confirmation reload');
  firstCard=document.getElementById('rental-card-object-0');
  firstCard.querySelector('.rental-contract-control').click();
  await wait(()=>document.querySelector('#cf-payment-method-object-0 option[value="cash"]'),'contract payment methods load');
  check(document.querySelectorAll('#cf-payment-method-object-0 option').length===2,'contract form uses only canonical active payment methods');
  toggleContractForm('object-0');
 const name=id=>document.querySelector('[data-rental-fields="'+id+'"]');
 const panel=id=>document.getElementById('rental-inline-'+id);
 window.scrollTo(0,Math.min(150,document.body.scrollHeight-innerHeight));
 const originalScroll=window.scrollY;
 name('object-0').click();
 await wait(()=>panel('object-0').querySelector('[data-common-field="phone"]'),'inline editor');
 check(!panel('object-0').hidden,'name opens inline editor');
 check(!panel('object-0').querySelector('.entity-field-row'),'individual fields closed initially');
 panel('object-0').querySelector('[data-common-field="phone"]').click();
 window.confirmAnswer=false;
 name('object-1').click();await delay();
 check(!panel('object-0').hidden&&panel('object-1').hidden,'dirty switch cancellation preserves editor');
 window.fixtureFailSave=true;
 panel('object-0').querySelector('[data-save]').click();
 await wait(()=>panel('object-0').textContent.includes('Utkastet er beholdt'),'save failure');
 check(!panel('object-0').hidden,'failed save keeps whole panel open');
 check(panel('object-0').querySelector('[data-common-field="phone"]').checked,'failed save preserves checkbox draft');
 window.fixtureFailSave=false;
 panel('object-0').querySelector('[data-save]').click();
 await wait(()=>panel('object-0').hidden,'successful save closes whole editor');
 check(fixtureWrites.at(-1).entity_type==='rental_object'&&fixtureWrites.at(-1).entity_id==='object-0'&&fixtureWrites.at(-1).csrf_token==='fixture-csrf','save uses object-bound shared fields and CSRF');
 check(Math.abs(window.scrollY-originalScroll)<5,'save restores scroll position');
 const order=[...document.querySelectorAll('[data-rental-fields]')].map(e=>e.dataset.rentalFields);
 check(order[0]==='object-0'&&order.at(-1)==='object-15','natural order retained');
 const list=document.getElementById('rentalObjectsContainer');list.scrollTop=180;
 const originalListScroll=list.scrollTop;
 name('object-3').click();
 await wait(()=>panel('object-3').querySelector('[data-save]')?.disabled===false,'scrolled object fields');
 check(Math.abs(document.getElementById('rental-card-object-3').getBoundingClientRect().top-list.getBoundingClientRect().top)<3,'selected row at top of object list');
 panel('object-3').querySelector('[data-save]').click();await wait(()=>panel('object-3').hidden,'scrolled object save');
 check(Math.abs(list.scrollTop-originalListScroll)<3,'saving restores list position');
 window.confirmAnswer=true;window.fixtureFailFields=true;
 name('object-2').click();await wait(()=>panel('object-2').textContent.includes('validation_rules'),'field loading error');
 check(!panel('object-2').hidden&&panel('object-2').querySelector('[data-save]').disabled,'invalid config shows error with saving disabled');
 await RentalDashboard.closeFieldPanel(false);window.fixtureFailFields=false;
 const search=document.getElementById('rentalObjectSearch');
 search.value='Utleieobjekt 15';search.dispatchEvent(new Event('input'));await delay();
 check(document.querySelectorAll('[data-rental-fields]').length===1,'search filters objects');
 check(document.querySelector('.rental-create-tile'),'create tile remains available with active filter');
 search.value='no-such-rental';search.dispatchEvent(new Event('input'));await delay();
 check(!document.querySelector('[data-rental-fields]')&&document.querySelector('.rental-create-tile'),'create tile remains in filtered empty state');
 search.value='Utleieobjekt 15';search.dispatchEvent(new Event('input'));await delay();
 name('object-15').click();await wait(()=>panel('object-15').querySelector('[data-save]')?.disabled===false,'filtered editor');
 panel('object-15').querySelector('[data-save]').click();await wait(()=>panel('object-15').hidden,'filtered save');
 check(search.value==='Utleieobjekt 15'&&document.querySelectorAll('[data-rental-fields]').length===1,'save retains search and filtered list');
 search.value='';search.dispatchEvent(new Event('input'));await delay();
  document.querySelector('.rental-create-tile')?.click();
  await wait(()=>document.getElementById('rental-create').style.display==='block','create tile opens existing create form');
  check(document.querySelector('#createFormFieldsContainer #createCategorySelect'),'existing category choice preserved in creation form');
  const createFields=document.getElementById('createFormFieldsContainer');
  const createCategory=createFields.querySelector('#createCategorySelect');
  createCategory.value='venue';createCategory.dispatchEvent(new Event('change'));
  check(createFields.querySelector('[name="rental_price"]')&&!createFields.querySelector('[name="rental_price"]').closest('details'),'required daily rate stays visible in first-pass create form');
  check(createFields.querySelector('[name="type_data[access_code]"]')&&!createFields.querySelector('[name="type_data[access_code]"]').closest('details'),'venue access code stays visible in first-pass create form');
  check(createFields.querySelector('[name="description"]')?.closest('details')&&!createFields.querySelector('details.rental-create-advanced')?.open,'optional listing details are grouped in a closed disclosure');
  const createName=document.querySelector('#createRentalForm [name="object_name"]');createName.value='Utkast til lokale';
  document.querySelector('.rental-create-fields-details').open=true;
  const createFieldCount=rentalCreateFieldsEditor.getFields().length;
  document.querySelector('#create-rental-fields-editor [data-add]').click();
  window.confirmAnswer=false;closeRentalCreate();
  check(document.getElementById('rental-create').style.display==='block','dirty create navigation can be cancelled');
  window.confirmAnswer=true;closeRentalCreate();
  check(document.getElementById('rental-create').style.display==='none'&&createName.value==='Utkast til lokale'&&rentalCreateFieldsEditor.getFields().length===createFieldCount+1,'create navigation preserves form and customer-field drafts');
 document.querySelector('[data-rental-open="requests"]').click();
 const overview=document.getElementById('rentalOverviewModal'),details=document.getElementById('detailsModal');
 await wait(()=>overview.classList.contains('show'),'requests modal');await delay(350);
 check(overview.contains(document.getElementById('rentalRequestsContainer')),'existing request workflow moved into modal');
 check(document.querySelectorAll('#rentalRequestsContainer').length===1,'no duplicate request content');
 const requestRow=document.querySelector('#rentalRequestsContainer [data-rental-request-id="request-a"]');
 check(requestRow?.dataset.baseRentalFee==='100'&&requestRow?.dataset.pickupTime==='09:00','request dataset and base pricing remain on original row');
 check(requestRow.querySelector('.rental-request-end-time')&&requestRow.querySelector('.rental-request-adjustment-type')&&requestRow.querySelector('.rental-request-approve'),'date/time, pricing selectors, and approval controls remain available');
 if(innerWidth<768)check(getComputedStyle(requestRow).display==='block'&&getComputedStyle(requestRow.querySelector('[data-label="Til"]')).display==='grid','mobile requests render as labeled stacked cards');
 bootstrap.Modal.getOrCreateInstance(overview).hide();
 await wait(()=>!overview.classList.contains('show')&&!document.querySelector('.modal-backdrop'),'request modal closes');
 await delay(350);
 document.querySelector('#subtabs-rental a[href="#rental-upcoming"]').click();
 await wait(()=>overview.classList.contains('show'),'legacy upcoming link opens modal');await delay(350);
 check(overview.contains(document.getElementById('rentalCalendarHost')),'calendar inside same overview modal');
 check(document.getElementById('rentalUnavailableContent').hidden,'internal planning collapsed');
 showRentalCalDetail('schedule-a');
 await wait(()=>details.classList.contains('show'),'child details opens');await delay(350);
 check(!overview.classList.contains('show')&&document.querySelectorAll('.modal-backdrop').length===1,'parent hidden before child, single backdrop');
 bootstrap.Modal.getOrCreateInstance(details).hide();
 await wait(()=>overview.classList.contains('show'),'parent reopened after child');await delay(350);
 check(document.querySelectorAll('#rental-upcoming').length===1,'upcoming contents restored without duplication');
 check(document.documentElement.scrollWidth<=innerWidth+1,'upcoming modal fits viewport');
 bootstrap.Modal.getOrCreateInstance(overview).hide();await delay(500);
  showRentalCalDetail('schedule-a',false);
  await wait(()=>document.getElementById('detailsModal').classList.contains('show'),'upcoming payment detail modal');
  await wait(()=>document.querySelector('#rentalSchedulePaymentMethod option[value="cash"]'),'enabled payment method loaded');
  check(document.querySelectorAll('#rentalSchedulePaymentMethod option').length===2,'only actual active methods appear');
  check(document.getElementById('rentalSchedulePaymentButton').disabled,'payment requires explicit method');
  document.getElementById('rentalSchedulePaymentMethod').value='cash';toggleRentalPaymentButton('cash');
  check(!document.getElementById('rentalSchedulePaymentButton').disabled,'method enables mark-paid');
  document.getElementById('rentalSchedulePaymentButton').click();
  await wait(()=>fixtureLifecycleWrites.length===1,'mark-paid uses rental lifecycle ledger API');
  check(fixtureLifecycleWrites[0].action==='mark_paid'&&fixtureLifecycleWrites[0].payment_method==='cash','mark-paid sends selected active method');
  await delay(350);
 window.fixtureFailObjects=true;
 await loadRentalObjects();await delay();
 check(document.getElementById('rentalMetricObjects').textContent==='—','load failure not reported as zero');
 check(fixtureUnexpected.length===0,'unexpected APIs: '+fixtureUnexpected.join(','));
 check(testErrors.length===0,'browser errors: '+testErrors.join(','));
  const mobileImage=firstCard.querySelector('.rental-object-image');
  if(innerWidth<768)check(mobileImage.getBoundingClientRect().width<=90,'mobile photo remains compact');
  return {passed:true,width:innerWidth,saves:fixtureWrites.length};
})()`;
let browser,socket;
async function main(){
    browser=spawn('/repl/tools/bin/chromium',['--headless','--no-sandbox','--disable-dev-shm-usage','--remote-debugging-port=0','--user-data-dir='+path.join(dir,'profile'),'about:blank']);
    const url=await new Promise((resolve,reject)=>{
        let output='';const timer=setTimeout(()=>reject(new Error('Chromium timeout')),15000);
        browser.stderr.on('data',data=>{output+=data;const match=output.match(/DevTools listening on (ws:\/\/[^\s]+)/);if(match){clearTimeout(timer);resolve(match[1]);}});
        browser.on('error',reject);
    });
    socket=new WebSocket(url);await new Promise(resolve=>socket.addEventListener('open',resolve,{once:true}));
    let id=0;const pending=new Map();
    socket.addEventListener('message',event=>{const msg=JSON.parse(event.data),cb=pending.get(msg.id);if(cb){pending.delete(msg.id);msg.error?cb.reject(msg.error):cb.resolve(msg.result);}});
    const send=(method,params={},sessionId)=>new Promise((resolve,reject)=>{pending.set(++id,{resolve,reject});socket.send(JSON.stringify({id,method,params,sessionId}));});
    const {targetId}=await send('Target.createTarget',{url:'about:blank'});
    const {sessionId}=await send('Target.attachToTarget',{targetId,flatten:true});
    for(const width of [390,1280]){
        await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false},sessionId);
        await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-reduced-motion',value:'reduce'}]},sessionId);
        await send('Page.navigate',{url:'file://'+fixture},sessionId);
        await new Promise(resolve=>setTimeout(resolve,650));
        const result=await send('Runtime.evaluate',{expression:checks,awaitPromise:true,returnByValue:true},sessionId);
        if(result.exceptionDetails)throw new Error(JSON.stringify(result.exceptionDetails));
        assert.equal(result.result.value?.passed,true);
        console.log('Rental dashboard Chromium',result.result.value);
        await send('Runtime.evaluate',{expression:'fixtureFailObjects=false;loadRentalObjects();window.scrollTo(0,0)'},sessionId);
        await new Promise(resolve=>setTimeout(resolve,250));
        const shot=await send('Page.captureScreenshot',{format:'png'},sessionId);
        fs.writeFileSync(path.join(dir,'dashboard-'+width+'.png'),Buffer.from(shot.data,'base64'));
    }
    console.log('Isolated UI screenshots:',dir);
}
main().catch(error=>{console.error(error);process.exitCode=1;}).finally(()=>{socket?.close();browser?.kill();});
