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
const dashboardSource = fs.readFileSync(path.join(root, 'pages/company_dashboard.php'), 'utf8');
assert.ok(!dashboardSource.includes('href="#rental-contracts"'), 'contract subtab removed from dashboard');
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
window.fixtureRentalHold=null;window.fixtureFailRentalSave=false;window.fixtureFailRentalCodeSave=false;window.fixtureRentalWrites=[];
const tomorrow=new Date();tomorrow.setDate(tomorrow.getDate()+1);
const iso=tomorrow.toISOString().slice(0,10);
const image='https://fixture.invalid/house.svg';
window.fixtureObjects=Array.from({length:16},(_,i)=>({id:'object-'+i,object_name:i===0?'Sal <img src=x onerror="window.injected=1">':'Utleieobjekt '+i,category:i===15?'tool':'venue',rental_price:100+i,weekday_prices:i===0?JSON.stringify({monday:500,tuesday:500,wednesday:600,thursday:600,friday:800}):i===2?'{"monday":':null,weekday_prices_enabled:i===0||i===2?1:0,weekend_price:i===1?425:0,saturday_price:i===0?175:0,sunday_price:i===0?190:0,deposit_amount:2500,description:'Lyst lokale ved fjorden.',rental_text:'Nøkkel hentes etter avtale.',images:[{url:image,is_primary:true,caption:'Hovedrommet'}],is_available:1,display_status:'available',type_data:{access_code:'1234',age_required:i===0?18:0,cleaning_fee:200,capacity_seated:48,facilities:'Lydanlegg tilgjengelig',pickup_time:'09:30',return_time:'21:00'}}));
window.fixtureObjects[1].weekday_prices=JSON.stringify({monday:500,tuesday:500,wednesday:600,thursday:600,friday:800});
window.fixtureObjects[15].market_price=235;
window.fixtureRequest={id:'request-a',rental_object_id:'object-0',object_name:'Sal A',category:'venue',object_daily_price:100,object_weekend_price:0,object_saturday_price:150,object_sunday_price:125,object_weekday_prices:{monday:500,tuesday:500,wednesday:600,thursday:600,friday:800},object_weekday_prices_enabled:1,status:'pending',approved:0,start_date:iso,end_date:iso,display_customer_name:'Testkunde',display_email:'kunde@example.invalid',customer_name:'Testkunde',customer_email:'kunde@example.invalid',pickup_time:'09:00:00',return_time:'17:30:00',rental_fee:100,decision_version:'v1'};
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
   if(payload&&Object.hasOwn(payload,'object_name')){
    fixtureRentalWrites.push(payload);if(fixtureRentalHold)await fixtureRentalHold;
    if(fixtureFailRentalSave){status=409;result={success:false,message:'Lagring av objekt feilet'};}
    else {const saved=fixtureObjects.find(item=>String(item.id)===String(payload.id));if(saved){Object.assign(saved,payload);saved.type_data={...saved.type_data,...payload.type_data};}result={success:true};}
    }else{
     fixtureObjectUpdates.push(payload);
     if(fixtureFailRentalCodeSave){status=409;result={success:false,message:'Lagring av objekt feilet'};}
     else {const updated=fixtureObjects.find(item=>String(item.id)===String(payload.id));
      if(updated){if(payload.type_data)updated.type_data={...updated.type_data,...payload.type_data};if(payload.is_available!==undefined)updated.is_available=payload.is_available;}
      result={success:true};}
   }
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
  fixtureObjects[0].object_name='Sal Fjordglimt';
  allRentalObjects.find(item=>String(item.id)==='object-0').object_name='Sal Fjordglimt';
  firstCard.querySelector('.rental-object-name').textContent='Sal Fjordglimt';
  check(firstCard.querySelector('.rental-price')?.textContent.trim()==='Ukedagspriser'
    &&firstCard.querySelector('.rental-weekday-price')?.textContent.includes('Fre 800')
    &&!firstCard.querySelector('.rental-price').textContent.includes('100 NOK/dag')
    &&firstCard.textContent.includes('Lørdag 175')&&firstCard.textContent.includes('Søndag 190'),'cards show actual weekday map rather than a misleading flat day price');
  const inactiveMapCard=document.getElementById('rental-card-object-1');
  check(inactiveMapCard.querySelector('.rental-price')?.textContent.includes('101 NOK/dag')
    &&!inactiveMapCard.querySelector('.rental-weekday-price'),'a retained but disabled weekday map does not affect displayed flat pricing');
  check(firstCard.querySelector('[data-rental-access-value] code')?.textContent==='1234','current physical access code is visible directly on venue card');
  check(firstCard.querySelector('.rental-contract-control')&&firstCard.querySelectorAll('.rental-card-menu .dropdown-item').length===4,'contract action remains separate from four-item overflow menu');
  viewDetails('object-0');
  await wait(()=>document.getElementById('detailsModal').classList.contains('show'),'weekday-rate object details');await delay(100);
  check(document.getElementById('detailsModal').textContent.includes('Mandag: 500 NOK')
    &&document.getElementById('detailsModal').textContent.includes('Helgegrunnpris ved manglende helgesats'),'details show all weekday rates and weekend fallback meaning');
  bootstrap.Modal.getOrCreateInstance(document.getElementById('detailsModal')).hide();
  await wait(()=>!document.getElementById('detailsModal').classList.contains('show'),'close weekday-rate details');
  rentalSchemas.venue.fields.pickup_time={type:'time',label:'Hentetid',required:false};
  rentalSchemas.venue.fields.return_time={type:'time',label:'Returtid',required:false};
  rentalSchemas.venue.fields.facilities.required=true;
  let menuButton=firstCard.querySelector('.rental-menu-trigger');menuButton.click();
  const menuItems=()=>[...document.querySelectorAll('[data-rental-menu-owner="object-0"] .dropdown-item')];
  const menuItem=label=>menuItems().find(item=>item.textContent.trim()===label);
  const menu=()=>document.querySelector('[data-rental-menu-owner="object-0"]');
  const menuLabels=menuItems().map(x=>x.textContent.trim());
  check(['Rediger','Rediger skjemafelt','Skjul','Slett'].every(label=>menuLabels.includes(label)),'three-dot menu exposes base editing, form fields, hide, and delete: '+JSON.stringify(menuLabels));
  const menuRect=menu().getBoundingClientRect();
  check(menu().parentElement===document.body&&menuRect.top>=0&&menuRect.bottom<=innerHeight,'overflow menu escapes the scrolling object list and remains tappable');
  menuItem('Rediger').click();
  await wait(()=>document.getElementById('rentalModal').classList.contains('show'),'edit modal');await delay(350);
  check(!document.querySelector('#formFieldsContainer select[name="category"]')&&document.querySelector('#formFieldsContainer input[type="hidden"][name="category"]')?.value==='venue','edit category is display-only and preserved hidden');
  const editForm=document.getElementById('rentalForm');
  const editSections=[...editForm.querySelectorAll('details.rental-edit-section')];
  check(editSections.length>=4&&editSections.every(section=>!section.open),'less-used edit groups start collapsed');
  check(editForm.querySelector('[data-rental-edit-section="pricing"] .rental-edit-section-title').textContent==='Grunnpris og helgepris','pricing disclosure has the requested title and excludes market price for venues');
  check(!editForm.querySelector('[name="market_price"]'),'venue pricing has no market-price control');
  check(editForm.querySelector('#formFieldsContainer_weekdayPricesEnabled').checked
    &&editForm.querySelector('[name="weekday_prices[monday]"]').value==='500'
    &&editForm.querySelector('[name="weekday_prices[tuesday]"]').value==='500'
    &&editForm.querySelector('[name="weekday_prices[wednesday]"]').value==='600'
    &&editForm.querySelector('[name="weekday_prices[thursday]"]').value==='600'
    &&editForm.querySelector('[name="weekday_prices[friday]"]').value==='800','existing JSON weekday map prepopulates all five named controls');
  check([...editForm.querySelectorAll('[name^="weekday_prices["]')].every(input=>input.required&&input.min==='0'&&input.step==='0.01')
    &&!editForm.querySelector('[name="rental_price"]').required,'weekday rates require all five nonnegative cent-precision values while fallback stays optional');
  check(!editForm.querySelector('[name="type_data[access_code]"]').closest('details'),'venue access code remains immediately visible');
  check(editForm.querySelector('[name="type_data[pickup_time]"]')?.type==='time'&&!editForm.querySelector('[name="type_data[pickup_time]"]').closest('details')
    &&editForm.querySelector('[name="type_data[return_time]"]')?.type==='time','type-specific pickup and return controls remain in the visible edit flow');
  const ageToggle=editForm.querySelector('[data-rental-age-required-toggle]');
  const ageInput=editForm.querySelector('[name="type_data[age_required]"]');
  check(ageToggle?.checked&&ageInput?.value==='18'&&!ageInput.disabled&&ageInput.required
    &&ageInput.min==='1'&&ageInput.step==='1','saved venue minimum age enables an integer input');
  ageToggle.click();
  check(!ageToggle.checked&&ageInput.disabled&&!ageInput.required,'disabling minimum age disables its number input');
  ageToggle.click();
  check(ageToggle.checked&&!ageInput.disabled&&ageInput.value==='18','re-enabling preserves the edit draft');
  check(editForm.querySelector('[name="description"]').closest('details')&&editForm.querySelector('[name="rental_text"]').closest('details'),'descriptive copy is progressively disclosed');
  check(editForm.querySelector('.image-caption-input').value==='Hovedrommet'&&editForm.querySelector('.image-row input[type="radio"]').checked,'image caption and primary selection remain in original fields');
  check(editForm.querySelector('[name="weekend_price"]').value==='0'&&editForm.querySelector('[name="saturday_price"]').value==='175'
    &&editForm.querySelector('[name="sunday_price"]').value==='190','separate weekend values, including zero, survive grouping '+JSON.stringify([
      editForm.querySelector('[name="weekend_price"]').value,editForm.querySelector('[name="weekend_price"]').getAttribute('value'),editForm.querySelector('[name="saturday_price"]').value,editForm.querySelector('[name="sunday_price"]').value]));
  check(editForm.querySelector('[name="rental_price"]').value==='100'&&editForm.querySelector('[name="deposit_amount"]').value==='2500'
    &&editForm.querySelector('[data-rental-base-rate-help]').textContent.includes('lørdag og søndag'),'fallback rate remains adjustable and is clearly explained for weekend use');
  window.fixtureEditScreenshotReady=true;
  await new Promise(resolve=>window.fixtureContinueEditTests=resolve);
  const editName=editForm.querySelector('[name="object_name"]');
  editName.value='Sal test';
  window.confirmAnswer=false;bootstrap.Modal.getOrCreateInstance(document.getElementById('rentalModal')).hide();await delay(100);
  check(document.getElementById('rentalModal').classList.contains('show'),'dirty edit modal prevents accidental close');
  const facilities=editForm.querySelector('[name="type_data[facilities]"]');
  facilities.value='';
  window.confirmAnswer=true;
  document.getElementById('rentalSaveButton').click();await delay(100);
  check(document.querySelector('[data-rental-edit-section="specifications"]').open&&document.activeElement===facilities,'invalid field in closed details opens and receives focus');
  facilities.value='Lydanlegg tilgjengelig';
  editForm.querySelector('[name="rental_price"]').value='125';
  editForm.querySelector('[name="deposit_amount"]').value='3200';
  editForm.querySelector('[name="weekday_prices[monday]"]').value='500';
  editForm.querySelector('[name="weekday_prices[tuesday]"]').value='0';
  editForm.querySelector('[name="weekday_prices[wednesday]"]').value='600';
  editForm.querySelector('[name="weekday_prices[thursday]"]').value='600';
  editForm.querySelector('[name="weekday_prices[friday]"]').value='800';
  editForm.querySelector('[name="saturday_price"]').value='0';
  editForm.querySelector('[name="sunday_price"]').value='340';
  editForm.querySelector('[name="type_data[access_code]"]').value='A731';
  editForm.querySelector('[name="type_data[pickup_time]"]').value='10:15';
  editForm.querySelector('[name="type_data[return_time]"]').value='22:45';
  editForm.querySelector('[name="description"]').value='Oppdatert beskrivelse';
  editForm.querySelector('[name="rental_text"]').value='Adgang etter avtale.';
  editForm.querySelector('.image-caption-input').value='Oppdatert sal';
  editForm.querySelector('[name="is_available"]').checked=true;
  window.fixtureFailRentalSave=true;
  document.getElementById('rentalSaveButton').click();
  await wait(()=>fixtureRentalWrites.length===1,'failed edit save');
  check(document.getElementById('rentalModal').classList.contains('show')&&editName.value==='Sal test'
    &&editForm.querySelector('[name="saturday_price"]').value==='0'
    &&editForm.querySelector('[name="weekday_prices[tuesday]"]').value==='0'
    &&editForm.querySelector('[name="type_data[access_code]"]').value==='A731'
    &&editForm.querySelector('.image-caption-input').value==='Oppdatert sal','failed save retains the full edit draft');
  window.fixtureFailRentalSave=false;
  window.fixtureRentalHold=new Promise(resolve=>window.releaseRentalSave=resolve);
  document.getElementById('rentalSaveButton').click();
  document.getElementById('rentalSaveButton').click();
  await wait(()=>fixtureRentalWrites.length===2,'successful edit request');
  check(fixtureRentalWrites.length===2&&document.getElementById('rentalSaveButton').disabled,'duplicate save is blocked while request is in flight');
  window.releaseRentalSave();window.fixtureRentalHold=null;
  await wait(()=>!document.getElementById('rentalModal').classList.contains('show'),'successful edit closes modal');
  const savedVenue=fixtureRentalWrites.at(-1);
  check(savedVenue.category==='venue'&&savedVenue.rental_price===125&&savedVenue.deposit_amount===3200
    &&savedVenue.saturday_price===0&&savedVenue.sunday_price===340&&savedVenue.weekend_price===0,'edit serialization retains locked category and day/weekend pricing values');
  check(JSON.stringify(savedVenue.weekday_prices)===JSON.stringify({monday:500,tuesday:0,wednesday:600,thursday:600,friday:800}),'edit serializes all five weekday rates as numeric English-keyed map including explicit zero');
  check(savedVenue.type_data.access_code==='A731'&&savedVenue.type_data.pickup_time==='10:15'
    &&savedVenue.type_data.return_time==='22:45'&&savedVenue.type_data.facilities==='Lydanlegg tilgjengelig'
    &&savedVenue.type_data.age_required===18,'edit serializes physical access, type-specific details, and enabled minimum age');
  check(savedVenue.description==='Oppdatert beskrivelse'&&savedVenue.rental_text==='Adgang etter avtale.'
    &&savedVenue.images[0].caption==='Oppdatert sal'&&savedVenue.images[0].is_primary&&savedVenue.is_available===true,'hidden copy, image caption/primary, and availability serialize through original API shape');
  await loadRentalObjects();
  check(fixtureObjects[0].weekday_prices.monday===500&&fixtureObjects[0].weekday_prices.tuesday===0,'saved weekday map reloads as an object');
  await editRentalObject('object-0');
  await wait(()=>document.getElementById('rentalModal').classList.contains('show'),'reloaded weekday-map edit');await delay(100);
  check(editForm.querySelector('#formFieldsContainer_weekdayPricesEnabled').checked
    &&editForm.querySelector('[name="weekday_prices[tuesday]"]').value==='0'
    &&editForm.querySelector('[name="rental_price"]').value==='125'
    &&editForm.querySelector('[data-rental-age-required-toggle]').checked
    &&editForm.querySelector('[name="type_data[age_required]"]').value==='18','saved map, zero rate, and minimum age prefill on reload');
  editForm.querySelector('[data-rental-age-required-toggle]').click();
  document.getElementById('rentalSaveButton').click();
  await wait(()=>fixtureRentalWrites.length===3,'save disabled minimum age');
  await wait(()=>!document.getElementById('rentalModal').classList.contains('show'),'save disabled minimum age closes');
  check(fixtureRentalWrites.at(-1).type_data.age_required===0,'unchecked minimum age is explicitly saved as 0');
  await loadRentalObjects();
  await editRentalObject('object-0');
  await wait(()=>document.getElementById('rentalModal').classList.contains('show'),'reload disabled minimum age');await delay(100);
  check(!editForm.querySelector('[data-rental-age-required-toggle]').checked
    &&editForm.querySelector('[name="type_data[age_required]"]').disabled,'saved zero reloads as unchecked with disabled input');
  bootstrap.Modal.getOrCreateInstance(document.getElementById('rentalModal')).hide();
  await wait(()=>!document.getElementById('rentalModal').classList.contains('show'),'close disabled age modal');
  await editRentalObject('object-1');
  await wait(()=>document.getElementById('rentalModal').classList.contains('show'),'unified-price edit modal');await delay(100);
  const unifiedDetails=editForm.querySelector('[data-rental-edit-section="pricing"]');
  check(!unifiedDetails.open&&editForm.querySelector('#formFieldsContainer_modeUnified').checked
    &&editForm.querySelector('[name="weekend_price"]').value==='425','unified weekend price is preserved while its section stays collapsed');
  window.fixtureRentalHold=new Promise(resolve=>window.releaseRentalSave=resolve);
  document.getElementById('rentalSaveButton').click();document.getElementById('rentalSaveButton').click();
  await wait(()=>fixtureRentalWrites.length===4,'unified mode save');
  window.releaseRentalSave();window.fixtureRentalHold=null;
  await wait(()=>!document.getElementById('rentalModal').classList.contains('show'),'unified mode save closes');
  check(fixtureRentalWrites.at(-1).weekend_price===425&&fixtureRentalWrites.at(-1).saturday_price===0
    &&fixtureRentalWrites.at(-1).sunday_price===0,'unified weekend serialization retains zero per-day values');
  check(fixtureRentalWrites.at(-1).weekday_prices_enabled===0
    &&JSON.stringify(fixtureRentalWrites.at(-1).weekday_prices)===JSON.stringify({monday:500,tuesday:500,wednesday:600,thursday:600,friday:800}),
    'flat mode disables map pricing while preserving all saved weekday rates');
  await loadRentalObjects();
  await editRentalObject('object-1');
  await wait(()=>document.getElementById('rentalModal').classList.contains('show'),'reloaded flat-price edit');await delay(100);
  check(!editForm.querySelector('#formFieldsContainer_weekdayPricesEnabled').checked
    &&editForm.querySelector('[name="rental_price"]').value==='101'
    &&!editForm.querySelector('[data-rental-edit-section="pricing"] [name="weekday_prices[monday]"]').required
    &&getComputedStyle(editForm.querySelector('#formFieldsContainer_weekdayPrices')).display==='none','null weekday map reloads as flat-rate mode');
  bootstrap.Modal.getOrCreateInstance(document.getElementById('rentalModal')).hide();
  await wait(()=>!document.getElementById('rentalModal').classList.contains('show'),'close reloaded flat modal');
  await editRentalObject('object-2');
  await wait(()=>document.getElementById('rentalModal').classList.contains('show'),'malformed weekday map edit');await delay(100);
  check(editForm.querySelector('#formFieldsContainer_weekdayPricesEnabled').checked
    &&!editForm.querySelector('[data-weekday-pricing-error]').hidden,'invalid JSON weekday map is explicitly surfaced without choosing a silent mode '+JSON.stringify([
      editForm.querySelector('#formFieldsContainer_weekdayPricesEnabled').checked,
      editForm.querySelector('[data-weekday-pricing-error]').hidden,
      fixtureObjects[2].weekday_prices,
      document.getElementById('rentalModal').textContent]));
  document.getElementById('rentalSaveButton').click();await delay(100);
  check(document.querySelector('[data-rental-edit-section="pricing"]').open,'invalid pricing choice opens its collapsed validation section');
  editForm.querySelector('#formFieldsContainer_weekdayPricesEnabled').click();
  check(editForm.querySelector('[data-weekday-pricing-error]').hidden
    &&getFormData('rentalForm').weekday_prices===undefined
    &&getFormData('rentalForm').weekday_prices_enabled===0,'disabling malformed inactive map permits flat pricing without overwriting the saved map');
  bootstrap.Modal.getOrCreateInstance(document.getElementById('rentalModal')).hide();
  await wait(()=>!document.getElementById('rentalModal').classList.contains('show'),'close malformed-map test modal');
  await editRentalObject('object-15');
  await wait(()=>document.getElementById('rentalModal').classList.contains('show'),'non-venue pricing edit');await delay(100);
  const marketPrice=editForm.querySelector('[name="market_price"]');
  check(marketPrice?.closest('[data-rental-edit-section="specifications"]')
    &&!editForm.querySelector('[data-rental-edit-section="pricing"] [name="market_price"]'),'market-price control stays under non-venue specifications, separate from rate controls');
  bootstrap.Modal.getOrCreateInstance(document.getElementById('rentalModal')).hide();
  await wait(()=>!document.getElementById('rentalModal').classList.contains('show'),'close non-venue edit');
  await loadRentalObjects();firstCard=document.getElementById('rental-card-object-0');menuButton=firstCard.querySelector('.rental-menu-trigger');await delay(150);
  menuButton.click();menuItem('Skjul').click();await delay(80);
  check(fixtureObjectUpdates.at(-1)?.is_available===0,'Skjul reuses existing availability API');
  menuButton.click();
  check(menuItem('Vis igjen')?.textContent.trim()==='Vis igjen','hidden object immediately offers reverse action');
  menuItem('Vis igjen').click();await delay(80);
  check(fixtureObjectUpdates.at(-1)?.is_available===1,'Vis igjen reverses visibility without reload');
  menuButton.click();
  check(menuItem('Skjul')?.textContent.trim()==='Skjul','shown object again offers hide');
  menuItem('Skjul').click();await delay(80);
  check(fixtureObjectUpdates.at(-1)?.is_available===0,'same card can hide again without reload');
  check(firstCard.querySelector('.rental-access-control'),'venue has prominent direct access-code action');
  const accessButton=firstCard.querySelector('.rental-access-control');
  accessButton.click();
  const accessInput=firstCard.querySelector('[data-rental-access-input]');
  await wait(()=>!firstCard.querySelector('.rental-access-edit-form').hidden&&accessInput?.value==='A731','access code is edited in place and prefilled from the card');
  check(fixtureObjectUpdates.length===3,'opening access-code control does not mutate');
  accessInput.value='0000';accessButton.click();await delay(50);
  check(fixtureObjectUpdates.length===3&&!firstCard.querySelector('.rental-access-edit-form').hidden
    &&firstCard.querySelector('[data-rental-access-status]').textContent.includes('0000'),'invalid code is rejected without discarding the inline draft');
  accessInput.value='A732';window.fixtureFailRentalCodeSave=true;accessButton.click();
  await wait(()=>fixtureObjectUpdates.length===4,'failed access-code save');
  check(!firstCard.querySelector('.rental-access-edit-form').hidden&&accessInput.value==='A732'
    &&!accessButton.disabled&&firstCard.querySelector('[data-rental-access-status]').textContent==='Lagring av objekt feilet'
    &&firstCard.querySelector('[data-rental-access-value] code')?.textContent==='A731','failed save preserves the in-place code draft');
  window.fixtureFailRentalCodeSave=false;accessInput.value='9841';accessButton.click();accessButton.click();
  await wait(()=>fixtureObjectUpdates.length===5,'access code toggle save');
  check(fixtureObjectUpdates.length===5&&fixtureObjectUpdates.at(-1).type_data.access_code==='9841','second click saves through the existing card API without duplicate requests');
  check(firstCard.querySelector('.rental-access-edit-form').hidden
    &&firstCard.querySelector('[data-rental-access-value] code')?.textContent==='9841','saved access code immediately returns to the card display');
  await loadRentalObjects();await wait(()=>document.querySelector('#rental-card-object-0 [data-rental-access-value] code')?.textContent==='9841','access code persists after object confirmation reload');
  firstCard=document.getElementById('rental-card-object-0');
  document.querySelector('#rental-card-object-1 .rental-menu-trigger').click();
  document.querySelector('[data-rental-menu-owner="object-1"] [data-rental-edit-fields="object-1"]').click();
  await wait(()=>!document.getElementById('rental-inline-object-1').hidden
    &&document.querySelector('#rental-inline-object-1 [data-common-field="phone"]'),'form-field menu opens the shared editor under its selected object');
  check(document.getElementById('rental-inline-object-0').hidden
    &&!document.getElementById('rentalModal').classList.contains('show'),'field menu targets only the chosen card and does not open basic editing');
  await RentalDashboard.closeFieldPanel(false);
  await wait(()=>document.getElementById('rental-inline-object-1').hidden,'close form-field menu editor');
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
 check(panel('object-0').closest('.rental-object-card')===firstCard.querySelector('.rental-object-card'),'field editor is inside its object card, not a separate panel');
 const checkFieldLayout=()=>{
   const editor=panel('object-0').querySelector('.entity-fields-editor');
   check(editor.scrollWidth<=editor.clientWidth+1,'field editor fits the card without horizontal overflow');
   check(document.documentElement.scrollWidth<=innerWidth+1,'open field editor fits viewport');
   const bounds=firstCard.querySelector('.rental-object-card').getBoundingClientRect();
   const panelBounds=panel('object-0').getBoundingClientRect();
   check(panelBounds.left>=bounds.left-1&&panelBounds.right<=bounds.right+1,'field panel stays within the object card');
 };
 checkFieldLayout();
 check(!panel('object-0').querySelector('.entity-field-row'),'individual fields closed initially');
 panel('object-0').querySelector('[data-edit-field="email"]').click();
 check(panel('object-0').querySelectorAll('.entity-field-row').length===1,'only selected field editor opens inside card');
 checkFieldLayout();
 const advanced=panel('object-0').querySelector('.entity-field-row details');
 if(advanced){advanced.open=true;checkFieldLayout();advanced.open=false;}
 window.fixtureFieldsScreenshotReady=true;
 await new Promise(resolve=>window.fixtureContinueFieldsTests=resolve);
 panel('object-0').querySelector('[data-close-field]').click();
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
 check(search.autocomplete==='off'&&search.spellcheck===false,'inventory search discourages browser autofill');
 const initialInventoryCount=document.querySelectorAll('[data-rental-fields]').length;
 search.value='autofill@example.com';search.dispatchEvent(new Event('input'));await delay();
 check(search.value===''&&document.querySelectorAll('[data-rental-fields]').length===initialInventoryCount,'generic autofill cannot erase inventory');
 search.value='4799999999';search.dispatchEvent(new InputEvent('input',{inputType:'insertReplacementText',data:null}));await delay();
 check(search.value===''&&document.querySelectorAll('[data-rental-fields]').length===initialInventoryCount,'browser replacement autofill cannot filter inventory');
 search.value='autofill@example.com';search.dispatchEvent(new InputEvent('input',{inputType:'insertReplacementText',data:'autofill@example.com'}));await delay();
 check(search.value===''&&document.querySelectorAll('[data-rental-fields]').length===initialInventoryCount,'replacement autofill with data cannot filter inventory');
 search.value='silent-autofill';RentalDashboard.refreshObjects();await delay();
 check(search.value===''&&document.querySelectorAll('[data-rental-fields]').length===initialInventoryCount,'silent autofill ignored on inventory refresh');
 search.value='Utleieobjekt 15';search.dispatchEvent(new InputEvent('input',{inputType:'insertText',data:'5'}));await delay();
 check(document.querySelectorAll('[data-rental-fields]').length===1,'search filters objects');
 check(document.querySelector('.rental-create-tile'),'create tile remains available with active filter');
 search.value='no-such-rental';search.dispatchEvent(new InputEvent('input',{inputType:'insertFromPaste'}));await delay();
 check(!document.querySelector('[data-rental-fields]')&&document.querySelector('.rental-create-tile'),'create tile remains in filtered empty state');
 search.value='Utleieobjekt 15';search.dispatchEvent(new InputEvent('input',{inputType:'insertText',data:'5'}));await delay();
 search.value='wrong-autofill@example.com';search.dispatchEvent(new InputEvent('input',{inputType:'insertReplacementText',data:null}));await delay();
 check(search.value==='Utleieobjekt 15'&&document.querySelectorAll('[data-rental-fields]').length===1,'autofill retains deliberate search');
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
  const zeroPriceDraft=getFormData('createRentalForm');zeroPriceDraft.rental_price=0;
  buildCreateForm('venue',zeroPriceDraft);
  check(createFields.querySelector('[name="rental_price"]').value==='0','edit cleanup preserves zero-rate creation draft');
  check(createFields.querySelector('[name="rental_price"]')&&!createFields.querySelector('[name="rental_price"]').closest('details'),'required daily rate stays visible in first-pass create form');
  check(createFields.querySelector('[name="type_data[access_code]"]')&&!createFields.querySelector('[name="type_data[access_code]"]').closest('details'),'venue access code stays visible in first-pass create form');
   check(!createFields.querySelector('[name="type_data[age_required]"]')&&!createFields.querySelector('[data-rental-age-required-toggle]'),'venue age requirement remains an edit-only setting');
  check(createFields.querySelector('[name="description"]')?.closest('details')&&!createFields.querySelector('details.rental-create-advanced')?.open,'optional listing details are grouped in a closed disclosure');
   check(!createFields.querySelector('#createFormFieldsContainer_weekdayPricesEnabled').checked
     &&getComputedStyle(createFields.querySelector('#createFormFieldsContainer_weekdayPrices')).display==='none'
     &&createFields.querySelector('#createFormFieldsContainer_weekdayPriceNote').hidden,'new create form defaults to flat pricing when no weekday map exists');
  const createName=document.querySelector('#createRentalForm [name="object_name"]');createName.value='Utkast til lokale';
   createFields.querySelector('[name="rental_price"]').value='900';
   const createWeekdays=createFields.querySelector('#createFormFieldsContainer_weekdayPricesEnabled');
   const createRates={monday:'500',tuesday:'0',wednesday:'600',thursday:'600',friday:'800'};
   createFields.querySelector('[name="type_data[facilities]"]').value='Lydanlegg';
   createWeekdays.click();
   Object.entries(createRates).forEach(([day,value])=>createFields.querySelector('[name="weekday_prices['+day+']"]').value=value);
   check([...createFields.querySelectorAll('[name^="weekday_prices["]')].every(input=>input.required)
     &&!createFields.querySelector('#createFormFieldsContainer_weekdayPrices').hidden,'create weekday mode reveals and requires all five rates');
   createWeekdays.click();
   const flatCreateDraft=getFormData('createRentalForm');
   check(flatCreateDraft.weekday_prices_enabled===0&&flatCreateDraft.weekday_prices.tuesday===0&&flatCreateDraft._weekdayPricesDraft.tuesday===0
     &&createFields.querySelector('[name="weekday_prices[tuesday]"]').value==='0','switching create modes serializes null without discarding weekday draft values');
   createWeekdays.click();
   showCreateSummary();
   check(pendingCreateData.weekday_prices.tuesday===0&&pendingCreateData.weekday_prices.friday===800
     &&pendingCreateData.weekday_prices_enabled===1
     &&document.getElementById('createSummaryContent').textContent.includes('Fredag'),'creation summary retains the selected weekday map and displays its daily values');
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
  check(Number(requestRow?.dataset.baseRentalFee)>0&&requestRow?.dataset.pickupTime==='09:00'
    &&requestRow?.dataset.weekdayPricesEnabled==='1'
    &&JSON.parse(requestRow?.dataset.weekdayPrices || '{}').monday===500,'request row carries weekday mode and rates into quote calculation data '+JSON.stringify([
      requestRow?.dataset.baseRentalFee,requestRow?.dataset.pickupTime,requestRow?.dataset.weekdayPricesEnabled,requestRow?.dataset.weekdayPrices]));
  const startDateField=requestRow.querySelector('[data-rental-request-field="start-date"]');
  const endDateField=requestRow.querySelector('[data-rental-request-field="end-date"]');
  startDateField.value='2031-07-07';startDateField.dispatchEvent(new Event('change',{bubbles:true}));
  endDateField.value='2031-07-12';endDateField.dispatchEvent(new Event('change',{bubbles:true}));
  check(requestRow.querySelector('[data-rental-weekday-label="start-date"]').textContent==='Mandag'
    &&requestRow.querySelector('[data-rental-weekday-label="end-date"]').textContent==='Lørdag'
    &&Number(requestRow.dataset.baseRentalFee)===3150,'Changing either request date refreshes Norwegian weekday labels and prices all weekdays plus Saturday');
 check(requestRow.querySelector('.rental-request-end-time')&&requestRow.querySelector('.rental-request-adjustment-type')&&requestRow.querySelector('.rental-request-approve'),'date/time, pricing selectors, and approval controls remain available');
 if(innerWidth<768)check(getComputedStyle(requestRow).display==='block'&&getComputedStyle(requestRow.querySelector('[data-label="Til"]')).display==='grid','mobile requests render as labeled stacked cards');
 bootstrap.Modal.getOrCreateInstance(overview).hide();
 await wait(()=>!overview.classList.contains('show')&&!document.querySelector('.modal-backdrop'),'request modal closes');
 await delay(350);
 document.querySelector('#subtabs-rental a[href="#rental-upcoming"]').click();
 await wait(()=>overview.classList.contains('show'),'legacy upcoming link opens modal');await delay(350);
 check(overview.contains(document.getElementById('rentalCalendarHost')),'calendar inside same overview modal');
 check(document.getElementById('rentalUnavailableContent').hidden,'internal planning collapsed');
 const upcomingCard=document.querySelector('.rental-upcoming-card');
 check(Boolean(upcomingCard),'upcoming card exists');
 check(!upcomingCard.querySelector('.rental-customer-links'),'contact clutter kept in details only');
 check(!upcomingCard.textContent.includes('Innsjekk-e-post'),'email status kept in details only');
 check(upcomingCard.textContent.includes('0037'),'assigned access code visible');
 check(upcomingCard.textContent.includes('Ubetalt'),'payment state visible');
 upcomingCard.click();
 await wait(()=>details.classList.contains('show'),'child details opens');await delay(350);
 check(!overview.classList.contains('show')&&document.querySelectorAll('.modal-backdrop').length===1,'parent hidden before child, single backdrop');
 bootstrap.Modal.getOrCreateInstance(details).hide();
 await wait(()=>overview.classList.contains('show'),'parent reopened after child');await delay(350);
 check(document.querySelectorAll('#rental-upcoming').length===1,'upcoming contents restored without duplication');
 check(document.documentElement.scrollWidth<=innerWidth+1,'upcoming modal fits viewport');
 document.querySelector('.rental-upcoming-card').dispatchEvent(new KeyboardEvent('keydown',{key:'Enter',bubbles:true}));
 await wait(()=>details.classList.contains('show'),'upcoming card keyboard opens details');await delay(350);
 bootstrap.Modal.getOrCreateInstance(details).hide();
 await wait(()=>overview.classList.contains('show'),'overview restored after keyboard details');await delay(350);
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
    for(const width of [320,390,1280]){
        await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false},sessionId);
        await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-reduced-motion',value:'reduce'}]},sessionId);
        await send('Page.navigate',{url:'file://'+fixture},sessionId);
        await new Promise(resolve=>setTimeout(resolve,650));
        const checksPromise=send('Runtime.evaluate',{expression:checks,awaitPromise:true,returnByValue:true},sessionId);
        let screenshotReady=false;
        for(let attempt=0;attempt<100;attempt++){
            const state=await send('Runtime.evaluate',{expression:'window.fixtureEditScreenshotReady===true',returnByValue:true},sessionId);
            if(state.result.value){screenshotReady=true;break;}
            await new Promise(resolve=>setTimeout(resolve,50));
        }
        if(!screenshotReady){
            const failed=await checksPromise;
            throw new Error('Edit screenshot checkpoint failed: '+JSON.stringify(failed.exceptionDetails||failed.result?.value));
        }
        await new Promise(resolve=>setTimeout(resolve,120));
        const editShot=await send('Page.captureScreenshot',{format:'png'},sessionId);
        fs.writeFileSync(path.join(dir,'rental-edit-modal-'+width+'.png'),Buffer.from(editShot.data,'base64'));
        await send('Runtime.evaluate',{expression:'(()=>{const section=document.querySelector("[data-rental-edit-section=\\"pricing\\"]");section.open=true;section.scrollIntoView({block:"start"});return true})()'},sessionId);
        await new Promise(resolve=>setTimeout(resolve,120));
        const pricingShot=await send('Page.captureScreenshot',{format:'png'},sessionId);
        fs.writeFileSync(path.join(dir,'rental-edit-pricing-'+width+'.png'),Buffer.from(pricingShot.data,'base64'));
        await send('Runtime.evaluate',{expression:'(()=>{document.querySelector("[data-rental-edit-section=\\"pricing\\"]").open=false;document.querySelector("#rentalModal .modal-body").scrollTop=0})()'},sessionId);
        await send('Runtime.evaluate',{expression:'window.fixtureContinueEditTests()'},sessionId);
        let fieldsReady=false;
        for(let attempt=0;attempt<160;attempt++){
            const state=await send('Runtime.evaluate',{expression:'window.fixtureFieldsScreenshotReady===true',returnByValue:true},sessionId);
            if(state.result.value){fieldsReady=true;break;}
            await new Promise(resolve=>setTimeout(resolve,50));
        }
        if(!fieldsReady){
            const failed=await checksPromise;
            throw new Error('Inline fields checkpoint failed: '+JSON.stringify(failed.exceptionDetails||failed.result?.value));
        }
        await send('Runtime.evaluate',{expression:'document.getElementById("rental-card-object-0").scrollIntoView({block:"start"})'},sessionId);
        const fieldsShot=await send('Page.captureScreenshot',{format:'png'},sessionId);
        fs.writeFileSync(path.join(dir,'rental-card-fields-'+width+'.png'),Buffer.from(fieldsShot.data,'base64'));
        await send('Runtime.evaluate',{expression:'window.fixtureContinueFieldsTests()'},sessionId);
        const result=await checksPromise;
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
