const fs=require('node:fs'),os=require('node:os'),path=require('node:path');
const {spawn,spawnSync}=require('node:child_process'),assert=require('node:assert/strict');
if(typeof WebSocket==='undefined') {
    process.exit(spawnSync(process.execPath,['--experimental-websocket',__filename],{stdio:'inherit'}).status??1);
}
const root=path.join(__dirname,'..'),dir=fs.mkdtempSync(path.join(os.tmpdir(),'rental-workflow-browser-'));
const rendered=spawnSync('php',['-r','include "modules/rental_content.php";'],{cwd:root,encoding:'utf8'});
assert.equal(rendered.status,0,rendered.stderr);
// Keep the actual CSS cascade: edit modal is a sibling of #rental-module.
// Stripping styles hid a production regression in the old scope-only checks.
let html=rendered.stdout.replace(/<link\b[^>]*>/g,tag=>{
 const href=tag.match(/href="\/assets\/([^"]+)"/);
 if(!href)return '';
 const name=href[1].split('?')[0];
 return '<style data-rental-test-css="'+name+'">'+fs.readFileSync(path.join(root,'assets',name),'utf8')+'</style>';
});
html=html.replace(/<script src="\/assets\/([^"]+)"><\/script>/g,(_,name)=>
    '<script>'+fs.readFileSync(path.join(root,'assets',name.split('?')[0]),'utf8')+'</script>');
const dashboard=fs.readFileSync(path.join(root,'pages/company_dashboard.php'),'utf8');
const subtab=dashboard.slice(dashboard.indexOf('function showSubTabContent('),dashboard.indexOf('async function navigateToDittNettsted('));
const stubs=`
window.errors=[];window.calls=[];window.messages=[];window.failSave=false;window.failDecision=false;
window.failAck=false;window.opened=[];window.refreshes=0;
window.PORTAL_NOTICE_TIMEOUT_MS=60;
window.addEventListener('error',e=>errors.push(e.message));
window.addEventListener('unhandledrejection',e=>errors.push(String(e.reason)));
window.toast={loading:()=>1,error:(...args)=>messages.push(args),success:(...args)=>messages.push(args)};
window.bootstrap={Modal:{getOrCreateInstance:()=>({show(){},hide(){}})},Tab:{getOrCreateInstance:()=>({show(){}})}};
window.requestFixture={id:'request-a',company_id:'a',rental_object_id:'oa',status:'pending',approved:0,
 customer_name:'John <img src=x onerror="window.injected=1">',display_customer_name:'John',
 object_name:'Sal A',object_category:'venue',object_daily_price:100,rental_fee:200,
 start_date:'2031-05-04',end_date:'2031-05-05',end_time:'18:00:00',cleaning:0,cleaning_fee:20,
 created_at:'2031-01-01',decision_version:'v1',price_adjustment_cents:0};
window.pollFixtures=[
 {id:'p1',module_source:'rental',created_at:'2031-05-03 12:00:00',metadata:{event_type:'rental.request.created',resource_type:'rental_schedule',resource_id:'request-a',customer_name:'John <img src=x>',object_name:'Sal A'}},
 {id:'p2',module_source:'rental',created_at:'2031-05-02 12:00:00',action_type:'rental_booking',items_count:4,metadata:null},
 {id:'p3',module_source:'economy',created_at:'2031-05-04 12:00:00',action_type:'payment_received',metadata:null}
];
window.openRentalRequestNotification=async id=>{opened.push(id);return true};
window.PortalOverview={
 moduleNames:{rental:'Rental',events:'Events'},
 tab:module=>document.querySelector('.module-tabs [data-bs-target="#content-'+module+'"]'),
 open:async(module,poll={})=>{
  if(module==='rental'&&poll.metadata?.resource_id)return window.openRentalRequestNotification(poll.metadata.resource_id);
  bootstrap.Tab.getOrCreateInstance(window.PortalOverview.tab(module)).show();return true;
 }
};
window.fetch=async(url,options={})=>{
 const payload=options.body?JSON.parse(options.body):null;calls.push({url:String(url),payload});
 let result={success:true},status=200;
 if(String(url).includes('get_entity_form_fields'))result={success:true,fields:[],presets:[],csrf_token:'fixture'};
 else if(String(url).includes('get_rental_objects'))result={success:true,objects:[]};
 else if(String(url).includes('get_rental_contracts'))result={success:true,contracts:[]};
 else if(String(url).includes('get_rental_schedule'))result={success:true,requests:String(url).includes('approved=0')?[{...requestFixture}]:[],decision_email_enabled:true};
 else if(String(url).includes('rental_unavailable'))result={success:true,periods:[],today:'2031-05-01',objects:[]};
 else if(String(url).includes('get_rental_results'))result={success:true,summary:{},objects:[],recent:[]};
 else if(String(url).includes('update_rental_schedule')){
  if(payload.status&&failDecision){status=409;result={success:false,message:'Forespørselen er endret'};}
  else if(!payload.status&&failSave){status=500;result={success:false,message:'Midlertidig feil'};}
  else {
    const fee=requestFixture.membership_benefits?.length
      ? refreshRentalRequestPrice(document.querySelector('[data-rental-request-id="request-a"]')).totalCents/100 : 300;
    requestFixture={...requestFixture,...payload,rental_fee:fee,decision_version:'v2'};
    result={success:true,status:payload.status||'pending',email_queued:Boolean(payload.status),rental_fee:fee,request:{...requestFixture}};
  }
 } else if(String(url).includes('check_refresh_flag'))result={success:true,polls:pollFixtures,csrf_token:'notifications-fixture'};
 else if(String(url).includes('portal_overview'))result={success:true,agenda:[],activity:[],errors:[]};
 else if(String(url).includes('set_polling_complete')){
  if(failAck){status=500;result={success:false,message:'Kunne ikke kvittere'};}
  else pollFixtures=pollFixtures.filter(p=>p.id!==payload.poll_id);
 }
 return {ok:status===200,status,json:async()=>result,text:async()=>JSON.stringify(result)};
};
${subtab}
`;
const file=path.join(dir,'fixture.html');
fs.writeFileSync(file,'<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><script>'+stubs+'</script>'+
    '<div class="module-tabs"><button data-bs-target="#content-rental">Utleie</button></div><div id="subtabs-rental"></div><div id="tenant-notifications" class="po-notice-stack"><p class="po-empty">Henter varsler …</p></div><div id="po-agenda"></div><div id="content-rental">'+html+'</div>'+
    spawnSync('php',['-r','include "includes/norwegian_datetime_inputs.php";'],{cwd:root,encoding:'utf8'}).stdout+
    '<script>'+fs.readFileSync(path.join(root,'assets/tenant_notifications.js'),'utf8')+'</script>'+
    '<script>'+fs.readFileSync(path.join(root,'assets/image-upload.js'),'utf8')+'</script>');
const checks=`(async()=>{
const check=(ok,message)=>{if(!ok)throw new Error(message)};
const delay=()=>new Promise(r=>setTimeout(r,30));
const verifyWeeklyAppearance=(editor,label)=>{
 const revealed=[];
 for(let parent=editor;parent&&parent!==document.body;parent=parent.parentElement){
  if(getComputedStyle(parent).display==='none'){revealed.push([parent,parent.style.display]);parent.style.display='block';}
 }
 try{
 const pills=[...editor.querySelectorAll('[data-weekday-pick]')];
 const rects=pills.map(p=>p.getBoundingClientRect());
 check(rects.every((r,n)=>n===0||r.left>rects[n-1].left),label+': day pills are horizontal');
 check(rects.every(r=>Math.abs(r.top-rects[0].top)<2),label+': day pills share one row');
 const pill=pills[0],mark=pill.querySelector('.rental-weekday-mark');
 const p=pill.getBoundingClientRect(),m=mark.getBoundingClientRect(),style=getComputedStyle(pill);
 check(parseFloat(style.borderRadius)>=20,label+': day control is a rounded pill, not an unstyled button');
 check(m.width>=15&&m.height>=15,label+': unchecked checkbox is visible');
 check(m.left+m.width/2>p.left+p.width*.66,label+': checkbox sits on the right of the pill');
 check(Math.abs(m.top+m.height/2-p.bottom)<=9,label+': checkbox interrupts the bottom outline');
 check(getComputedStyle(editor.querySelector('.rental-week-rates')).display==='flex',label+': stylesheet is applied');
 check(editor.querySelector('[data-daily-rate]').getBoundingClientRect().width<=90,label+': price input stays compact');
 const prices=[...editor.querySelectorAll('[data-daily-rate]')].map(input=>input.getBoundingClientRect());
 check(prices.every(r=>Math.abs(r.top-prices[0].top)<2),label+': all price inputs share a horizontal baseline');
 const accessibleLabel=editor.querySelector('.rental-week-rate > .visually-hidden');
 check(accessibleLabel&&getComputedStyle(accessibleLabel).position==='absolute'
  &&accessibleLabel.getBoundingClientRect().width<=1,label+': accessibility labels do not duplicate visible day names');
 }finally{revealed.reverse().forEach(([node,display])=>node.style.display=display);}
};
await delay();
check(errors.length===0,'startup errors: '+errors.join(';'));
buildCreateForm('venue');
check(!document.querySelector('#createRentalForm [name="market_price"]'),'venue hides market price');
const weeklyForm=document.getElementById('createRentalForm');
let weeklyEditor=weeklyForm.querySelector('[data-rental-week-editor]');
check(weeklyEditor&&!weeklyEditor.querySelector('[name="pricing_model"]'),'Creation uses only the seven-day editor without a model choice');
verifyWeeklyAppearance(weeklyEditor,'Actual create form');
check(!weeklyForm.querySelector('[name="rental_price"],[name^="weekday_prices["],[name="weekend_price"],[name="saturday_price"],[name="sunday_price"]'),'Creation form has no legacy price controls');
weeklyEditor.querySelectorAll('[name^="daily_prices["]').forEach(input=>input.value='500.00');
weeklyEditor.querySelector('[data-weekday-pick="2"]').click();
weeklyEditor.querySelector('[data-weekday-pick="3"]').click();
check(weeklyEditor.querySelector('[data-daily-rate="2"]').classList.contains('is-coupled')&&!weeklyEditor.querySelector('[data-daily-rate="2"]').readOnly,'Selected days show subdued but editable daily rates');
let pendingBlocked=false;
try { rentalPricingPayload(weeklyForm,'createFormFieldsContainer'); } catch(error) { pendingBlocked=error.message.includes('Bekreft'); }
check(pendingBlocked,'Object cannot be saved with an unconfirmed group');
weeklyEditor.querySelector('[data-weekly-price-input]').value='900';
weeklyEditor.querySelector('[data-dialog-confirm]').click();
const weeklyPayload=rentalPricingPayload(weeklyForm,'createFormFieldsContainer');
check(weeklyPayload.pricing_model==='weekly'&&weeklyPayload.daily_prices.sunday==='500.00'&&weeklyPayload.price_groups[0].price==='900.00'&&weeklyPayload.price_groups[0].start_day===2&&weeklyPayload.price_groups[0].end_day===3,'Actual create form builds seven daily rates and ascending group payload');
check(!['rental_price','weekday_prices','weekday_prices_enabled','weekend_price','saturday_price','sunday_price'].some(key=>Object.hasOwn(weeklyPayload,key)),'Weekly payload omits all legacy price keys');
buildCreateForm('tool',getFormData('createRentalForm'));
weeklyEditor=document.querySelector('#createRentalForm [data-rental-week-editor]');
check(weeklyEditor.querySelector('[data-weekly-groups]').value.includes('900.00'),'Category rebuild preserves daily pricing draft');
buildFormFields('formFieldsContainer','venue',{id:'editing-weekly',object_name:'Saved weekly',pricing_model:'weekly',
 pricing_version:'server-revision',daily_prices:weeklyPayload.daily_prices,price_groups:weeklyPayload.price_groups});
organizeRentalEditForm(document.getElementById('formFieldsContainer'),'venue',{object_name:'Saved weekly'});
const unchangedRentalForm=document.getElementById('rentalForm');
// The PHP-only fixture has no database schema; add the real schema time control.
unchangedRentalForm.insertAdjacentHTML('beforeend',generateIntegratedFieldHTML('pickup_time',{type:'time',label:'Henting'},'19:00:00',true));
const initialRentalSnapshot=rentalFormSnapshot(unchangedRentalForm);
unchangedRentalForm.dataset.rentalBaseline=initialRentalSnapshot;
await delay();
check(Boolean(unchangedRentalForm.querySelector('[data-norwegian-date-time-proxy]')),'Real date/time enhancement is present in rental edit');
check(Boolean(unchangedRentalForm.querySelector('.shared-image-upload__file')),'Real upload enhancement is present in rental edit');
check(rentalFormSnapshot(unchangedRentalForm)===initialRentalSnapshot,'Opening rental edit remains clean after date/time and upload enhancements');
const editedRentalName=unchangedRentalForm.querySelector('[name="object_name"]');
const originalRentalName=editedRentalName.value;
editedRentalName.value='Actually changed';
check(rentalFormSnapshot(unchangedRentalForm)!==initialRentalSnapshot,'An actual rental name change is still dirty');
editedRentalName.value=originalRentalName;
check(rentalFormSnapshot(unchangedRentalForm)===initialRentalSnapshot,'Restoring the original rental name clears the dirty state');
const originalConfirm=window.confirm;
let closePrompts=0;
window.confirm=()=>{closePrompts++;return false;};
const attemptRentalClose=()=>{
 const event=new Event('hide.bs.modal',{bubbles:true,cancelable:true});
 document.getElementById('rentalModal').dispatchEvent(event);
 return !event.defaultPrevented;
};
check(attemptRentalClose()&&closePrompts===0,'Opening and closing unchanged rental edit never prompts');
const rentalTime=unchangedRentalForm.querySelector('[name="type_data[pickup_time]"]');
const rentalTimeProxy=rentalTime.previousElementSibling;
rentalTimeProxy.value='20:30';
rentalTimeProxy.dispatchEvent(new Event('input',{bubbles:true}));
check(!attemptRentalClose()&&closePrompts===1,'Changing pickup time still guards closing');
rentalTimeProxy.value='19:00';
rentalTimeProxy.dispatchEvent(new Event('input',{bubbles:true}));
check(attemptRentalClose()&&closePrompts===1,'Restoring pickup time clears the dirty guard despite seconds formatting');
rentalTimeProxy.value='2';
rentalTimeProxy.dispatchEvent(new Event('input',{bubbles:true}));
check(!attemptRentalClose()&&closePrompts===2,'Incomplete time text is not silently discarded');
rentalTimeProxy.value='19:00';
rentalTimeProxy.dispatchEvent(new Event('input',{bubbles:true}));
const rentalImage=unchangedRentalForm.querySelector('.image-url-input');
rentalImage.value='https://example.com/changed.webp';
check(!attemptRentalClose()&&closePrompts===3,'Changing an image URL still guards closing');
rentalImage.value='';
window.confirm=originalConfirm;
document.querySelector('#rentalForm [data-rental-edit-section="pricing"]').open=true;
const weeklyEditPayload=rentalPricingPayload(document.getElementById('rentalForm'),'formFieldsContainer');
check(weeklyEditPayload.pricing_version==='server-revision'&&weeklyEditPayload.pricing_model==='weekly','Actual weekly edit preserves concurrency version and weekly mode');
check(!document.querySelector('#rentalForm [name="pricing_model"],#rentalForm [name="rental_price"],#rentalForm [name^="weekday_prices["],#rentalForm [name="weekend_price"]'),'Edit form removes legacy pricing controls entirely');
const modalEditor=document.querySelector('#rentalModal [data-rental-week-editor]');
check(!modalEditor.closest('#rental-module'),'Regression fixture preserves the actual modal placement outside module root');
verifyWeeklyAppearance(modalEditor,'Actual edit modal outside module root');
check(getComputedStyle(modalEditor).getPropertyValue('--site-header-bg-color').trim()===
 getComputedStyle(document.getElementById('rental-module')).getPropertyValue('--site-header-bg-color').trim(),'Company header color reaches both creation and detached edit modal');
const editTue=modalEditor.querySelector('[data-weekday-pick="2"]');
check(editTue.classList.contains('is-in-group')&&getComputedStyle(editTue).backgroundColor!==
 getComputedStyle(modalEditor.querySelector('[data-weekday-pick="1"]')).backgroundColor,'Saved range has visible highlight in the actual edit modal');
check(Number(getComputedStyle(modalEditor.querySelector('[data-daily-rate="2"]')).opacity)<1,'Grouped daily price is visibly subdued in the actual edit modal');
check(getComputedStyle(modalEditor).backgroundColor==='rgba(0, 0, 0, 0)'&&parseFloat(getComputedStyle(modalEditor).borderTopWidth)===0,
 'Price content has no unnecessary nested card');
check(!modalEditor.querySelector('.rental-week-editor-title')&&!document.getElementById('rentalForm').textContent.includes('Ukens priser'),
 'Edit form has no duplicate weekly-prices heading');
check(![...document.querySelectorAll('[data-rental-pricing-heading],.rental-edit-section-title')].some(el=>el.textContent.includes('Ukens priser')),
 'Pricing sections are titled Leiepriser');
check(document.querySelector('#rentalForm [data-rental-edit-section="pricing"] summary').textContent.includes('Leiepriser'),
 'Actual edit disclosure is named Leiepriser');
check(getComputedStyle(modalEditor.closest('.rental-pricing-inline')).boxShadow==='none',
 'Actual edit disclosure has no nested price-card shadow');
buildFormFields('formFieldsContainer','venue',{id:'blank-weekend',object_name:'Weekend package',pricing_model:'weekly',
 daily_prices:{monday:500,tuesday:500,wednesday:500,thursday:500,friday:500,saturday:null,sunday:null},
 price_groups:[{slot:1,start_day:6,end_day:7,price:'3000.00'}]});
const blankWeekendEditor=document.querySelector('#rentalForm [data-rental-week-editor]');
check([6,7].every(day=>{const input=blankWeekendEditor.querySelector('[data-daily-rate="'+day+'"]');return !input.required&&input.checkValidity();}),
 'Actual edit form accepts blank Saturday and Sunday when weekend has a combined price');
const blankWeekendPayload=rentalPricingPayload(document.getElementById('rentalForm'),'formFieldsContainer');
check(blankWeekendPayload.daily_prices.saturday===null&&blankWeekendPayload.daily_prices.sunday===null,
 'Grouped missing singles stay null instead of being replaced with zero');
const previewCard=document.createElement('div');
previewCard.innerHTML=renderObjectCard({...blankWeekendPayload,id:'blank-weekend',category:'venue',object_name:'Weekend package',is_available:1,images:[],type_data:{}});
check(rentalAllWeekdays.slice(5).every(day=>previewCard.querySelector('.rental-weekday-price').textContent.includes(day.short+' –')),
 'Rental card never displays missing grouped prices as zero');
blankWeekendEditor.querySelector('[data-group-remove]').click();
check([6,7].every(day=>blankWeekendEditor.querySelector('[data-daily-rate="'+day+'"]').validity.valueMissing),
 'Removing combined price restores required standalone daily prices');
let missingUngrouped=false;
try{rentalPricingPayload(document.getElementById('rentalForm'),'formFieldsContainer');}catch(_){missingUngrouped=true;}
check(missingUngrouped,'Missing ungrouped prices are blocked in the save payload too');
buildFormFields('formFieldsContainer','venue',{id:'weekly-no-fallback',object_name:'Weekly source',pricing_model:'weekly',
 daily_prices:{monday:'42.00'},price_groups:[],rental_price:999,weekend_price:700,saturday_price:800,sunday_price:900});
const weeklyNoFallbackEditor=document.querySelector('#rentalForm [data-rental-week-editor]');
check(weeklyNoFallbackEditor.querySelector('[name="daily_prices[monday]"]').value==='42.00'&&
 ['tuesday','wednesday','thursday','friday','saturday','sunday'].every(key=>weeklyNoFallbackEditor.querySelector('[name="daily_prices['+key+']"]').value==='')&&
 JSON.parse(weeklyNoFallbackEditor.querySelector('[data-weekly-groups]').value).length===0,'Already-weekly source never falls back to stale legacy rates');
buildFormFields('formFieldsContainer','venue',{id:'editing-legacy',object_name:'Old venue',rental_price:100,weekend_price:240});
let legacyConverted=document.querySelector('#rentalForm [data-rental-week-editor]');
check(['monday','tuesday','wednesday','thursday','friday','saturday','sunday'].every(key=>legacyConverted.querySelector('[name="daily_prices['+key+']"]').value==='100'),'Legacy flat source prefills all seven rates in memory');
let convertedPayload=rentalPricingPayload(document.getElementById('rentalForm'),'formFieldsContainer');
check(convertedPayload.pricing_model==='weekly'&&convertedPayload.price_groups.length===1&&convertedPayload.price_groups[0].start_day===6&&convertedPayload.price_groups[0].end_day===7&&convertedPayload.price_groups[0].price==='240.00','Positive shared weekend source converts to a Sat–Sun group on explicit weekly save');
buildFormFields('formFieldsContainer','venue',{id:'old-map-no-flag',object_name:'Old implicit weekdays',
 rental_price:100,weekday_prices:{monday:10,tuesday:20,wednesday:30,thursday:40,friday:50},
 saturday_price:200,sunday_price:0});
check(document.querySelector('#rentalForm [name="daily_prices[monday]"]').value==='10','A legacy map without an explicit flag retains its previously active rates');
check(document.querySelector('#rentalForm [name="daily_prices[sunday]"]').value==='100','Zero legacy Sunday rate means base-price fallback, not a new free Sunday');
buildFormFields('formFieldsContainer','venue',{id:'old-mixed-weekend',object_name:'Old individual weekend',
 rental_price:100,weekday_prices_enabled:0,saturday_price:200,sunday_price:0,weekend_price:300});
check(document.querySelector('#rentalForm [name="daily_prices[saturday]"]').value==='200'
 &&document.querySelector('#rentalForm [name="daily_prices[sunday]"]').value==='300','Mixed legacy weekend source retains the actual per-day amounts without introducing a bundle');
check(!['rental_price','weekday_prices','weekday_prices_enabled','weekend_price','saturday_price','sunday_price'].some(key=>Object.hasOwn(convertedPayload,key)),'Converted legacy edit payload contains no legacy keys');
buildFormFields('formFieldsContainer','venue',{id:'editing-weekdays',object_name:'Weekday venue',rental_price:90,weekday_prices_enabled:1,
 weekday_prices:{monday:101,tuesday:102,wednesday:103,thursday:104,friday:105},saturday_price:130,sunday_price:140});
legacyConverted=document.querySelector('#rentalForm [data-rental-week-editor]');
check(['101','102','103','104','105','130','140'].every((value,index)=>legacyConverted.querySelector('[data-daily-rate="'+(index+1)+'"]').value===value),'Legacy weekday and active separate weekend rates prefill their matching days');
buildFormFields('formFieldsContainer','venue',{id:'editing-missing',object_name:'Missing source rates'});
legacyConverted=document.querySelector('#rentalForm [data-rental-week-editor]');
check([...legacyConverted.querySelectorAll('[name^="daily_prices["]')].every(input=>input.value===''&&input.validity.valueMissing),'Missing legacy rates stay visibly blank and require completion');
buildCreateForm('tool');document.querySelector('#createRentalForm [name="market_price"]').value='987';
document.querySelector('#createRentalForm [name="type_data[brand]"]').value='Saved brand';
buildCreateForm('venue',getFormData('createRentalForm'));
check(!document.querySelector('#createRentalForm [name="market_price"]'),'switch to venue removes irrelevant field');
buildCreateForm('tool',getFormData('createRentalForm'));
check(document.querySelector('#createRentalForm [name="market_price"]').value==='987','market price draft survives category switch');
check(document.querySelector('#createRentalForm [name="type_data[brand]"]').value==='Saved brand','category draft restored');
showSubTabContent('rental','rental-requests');
const row=document.querySelector('[data-rental-request-id="request-a"]');
check(row,'real pending request renderer loaded');
check(!RentalRequestWorkflow.hasDrafts(),'shared date enhancer does not mark unchanged rows dirty');
const end=row.querySelector('[data-rental-request-field="end-date"]');
end.value='2031-05-06';end.dispatchEvent(new Event('change',{bubbles:true}));
const before=calls.filter(c=>c.payload).length;
await approveScheduleRequest('request-a');
check(!document.getElementById('rental-decision-dialog')&&calls.filter(c=>c.payload).length===before,'approval does not implicitly save draft');
await declineScheduleRequest('request-a');
check(!document.getElementById('rental-decision-dialog'),'rejection also blocks unsaved draft');
await loadRentalRequests();
check(end.isConnected&&end.value==='2031-05-06','refresh preserves dirty row');
failSave=true;await RentalRequestWorkflow.save('request-a');
check(end.value==='2031-05-06'&&RentalRequestWorkflow.hasDrafts(),'failed save retains draft');
window.confirm=()=>false;RentalRequestWorkflow.discard('request-a');
check(end.value==='2031-05-06','cancelled discard retains draft');
window.confirm=()=>true;RentalRequestWorkflow.discard('request-a');
check(end.value==='2031-05-05'&&!RentalRequestWorkflow.hasDrafts(),'explicit discard restores saved values');
end.value='2031-05-06';end.dispatchEvent(new Event('change',{bubbles:true}));
failSave=false;await RentalRequestWorkflow.save('request-a');
check(!RentalRequestWorkflow.hasDrafts(),'successful explicit save marks draft clean: '+JSON.stringify({messages,errors}));
const save=calls.filter(c=>c.url.includes('update_rental_schedule')).at(-1).payload;
check(!Object.hasOwn(save,'status')&&!Object.hasOwn(save,'rental_fee')&&save.csrf_token,'save cannot finalize or supply amount');
await approveScheduleRequest('request-a');
let dialog=document.getElementById('rental-decision-dialog');
check(dialog?.open&&dialog.textContent.includes('300,00')&&dialog.textContent.includes('06.05.2031'),'saved server quote in review');
check(dialog.textContent.includes('John <img')&&!dialog.querySelector('img')&&!window.injected,'summary escapes customer text');
check(dialog.textContent.includes('ikke at e-posten allerede er sendt'),'queue is not delivery');
dialog.querySelectorAll('button')[1].click();await delay();
await declineScheduleRequest('request-a');
dialog=document.getElementById('rental-decision-dialog');failDecision=true;
dialog.querySelector('button').click();await delay();
check(dialog.open&&dialog.textContent.includes('Forespørselen er endret')&&row.isConnected,'decision error preserves row and summary');
failDecision=false;
dialog.querySelector('button').click();dialog.querySelector('button').click();await delay();
check(!row.isConnected&&!document.getElementById('rental-decision-dialog'),'confirmed decision removes only target');
RentalRequestWorkflow.inspect(requestFixture);
check(document.getElementById('rental-decision-dialog').textContent.includes('Avslått'),'processed request can be inspected');
document.getElementById('rental-decision-dialog').querySelector('button').click();await delay();
const decisions=calls.filter(c=>c.url.includes('update_rental_schedule')&&c.payload.status);
check(decisions.length===2,'double click submits only one successful decision');
check(decisions.at(-1).payload.status==='rejected'&&decisions.at(-1).payload.expected_version==='v2'&&!Object.hasOwn(decisions.at(-1).payload,'end_date'),'decision has saved version, no field edits');
requestFixture={...requestFixture,status:'pending',approved:0,rental_fee:300,
 membership_benefits:[{plan_name:'Medlemsplan A',benefit_type:'amount_discount',benefit_value:50,price_basis:'rental_day'}]};
await loadRentalRequests();
const memberRow=document.querySelector('[data-rental-request-id="request-a"]');
check(memberRow.querySelector('.rental-request-membership-summary').textContent.includes('150'),'membership discount is shown separately');
check(memberRow.querySelector('.rental-request-stored-price').textContent.includes('300'),'opening does not overwrite stored request price');
await approveScheduleRequest('request-a');
check(!document.getElementById('rental-decision-dialog')&&messages.at(-1).includes('Lagre medlemsprisen først'),'approval requires explicit save of changed member price');
await RentalRequestWorkflow.save('request-a');
check(requestFixture.rental_fee===150&&!RentalRequestWorkflow.hasDrafts(),'explicit save retains membership discount');
check(memberRow.querySelector('.rental-request-stored-price').textContent.includes('150'),'saved label updates with server price');
await approveScheduleRequest('request-a');
check(document.getElementById('rental-decision-dialog')?.textContent.includes('150,00'),'approval reviews the saved member price');
document.getElementById('rental-decision-dialog').querySelectorAll('button')[1].click();await delay();
if (${process.env.RENTAL_PRICING_ONLY === '1'}) return {passed:true,scope:'rental decisions and membership pricing'};
await TenantNotifications.check();
check(document.querySelectorAll('[data-notification-id]').length===1,'exactly one foreground notification');
check(document.querySelector('[data-notification-id="p1"]').textContent.includes('John <img src=x> har sendt en utleieforespørsel om Sal A'),'specific literal notification');
check(!document.querySelector('#tenant-notifications img'),'notification names are text');
check(document.querySelector('#po-notice-count').textContent==='2','unauthorized module notification is filtered');
await TenantNotifications.check();
check(document.querySelectorAll('[data-notification-id]').length===1,'no repeated display');
const first=document.querySelector('[data-notification-id="p1"]');
failAck=true;first.querySelector('.po-notice-open').click();await delay();
check(first.isConnected&&first.textContent.includes('Kunne ikke kvittere'),'failed acknowledgement retains the current notice');
check(opened[0]==='request-a','opening uses the existing exact rental-request flow');
failAck=false;first.querySelector('.po-notice-open').click();await delay();
check(!first.isConnected&&document.querySelector('[data-notification-id="p2"]'),'confirmed open advances to the next notice');
check(document.querySelector('[data-notification-id="p2"]').textContent.includes('4 hendelser'),'event count remains readable');
document.querySelector('[data-notification-id="p2"] .po-notice-dismiss').click();await delay();
check(!document.querySelector('[data-notification-id]')&&document.querySelector('.po-empty').textContent.includes('Ingen nye varsler'),'empty state replaces the loading placeholder');
await TenantNotifications.check();
check(!document.querySelector('[data-notification-id]'),'acknowledged events stay gone');
check(!document.querySelector('#polling-countdown-toast'),'no auto reload countdown');
check(errors.length===0,'browser errors: '+errors.join(';'));
return {passed:true,width:innerWidth};
})()`;
let browser,socket;
async function main(){
 browser=spawn(process.env.CHROMIUM_PATH||'/repl/tools/bin/chromium',['--headless','--no-sandbox','--disable-dev-shm-usage','--remote-debugging-port=0','--user-data-dir='+path.join(dir,'profile'),'about:blank']);
 const url=await new Promise((resolve,reject)=>{let output='';const timeout=setTimeout(()=>reject(new Error('Browser timed out')),15000);
  browser.stderr.on('data',data=>{output+=data;const match=output.match(/DevTools listening on (ws:\/\/[^\s]+)/);if(match){clearTimeout(timeout);resolve(match[1]);}});browser.on('error',reject);});
 socket=new WebSocket(url);await new Promise(r=>socket.addEventListener('open',r,{once:true}));
 let id=0;const pending=new Map();
 socket.addEventListener('message',event=>{const msg=JSON.parse(event.data);const cb=pending.get(msg.id);if(cb){pending.delete(msg.id);msg.error?cb.reject(msg.error):cb.resolve(msg.result);}});
 const send=(method,params={},sessionId)=>new Promise((resolve,reject)=>{pending.set(++id,{resolve,reject});socket.send(JSON.stringify({id,method,params,sessionId}));});
 const {targetId}=await send('Target.createTarget',{url:'about:blank'});
 const {sessionId}=await send('Target.attachToTarget',{targetId,flatten:true});
 for(const width of [390,1280]){
  await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:width===390},sessionId);
  await send('Page.navigate',{url:'file://'+file+'?w='+width},sessionId);await new Promise(r=>setTimeout(r,500));
  const result=await send('Runtime.evaluate',{expression:checks,awaitPromise:true,returnByValue:true},sessionId);
  assert.equal(result.result?.value?.passed,true,JSON.stringify(result));
  console.log('Rental workflow'+(process.env.RENTAL_PRICING_ONLY==='1'?' and membership pricing':' and notifications')+' passed at '+width+'px');
  if(process.env.KEEP_RENTAL_WORKFLOW_FIXTURE==='1'){
   const visual=await send('Runtime.evaluate',{expression:`(()=>{
    const days=['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
    buildFormFields('formFieldsContainer','venue',{object_name:'Kontroll av prisredigering',pricing_model:'weekly',
     daily_prices:Object.fromEntries(days.map(day=>[day,'1000.00'])),
     price_groups:[{slot:1,start_day:6,end_day:7,price:'3000.00'}]});
    organizeRentalEditForm(document.getElementById('formFieldsContainer'),'venue',{object_name:'Kontroll av prisredigering'});
    document.querySelector('#rentalForm [data-rental-edit-section="pricing"]').open=true;
    const modal=document.getElementById('rentalModal');
    modal.style.display='block';
    const editor=modal.querySelector('[data-rental-week-editor]');
    editor.querySelector('[data-weekday-pick="2"]').click();
    editor.querySelector('[data-weekday-pick="4"]').click();
    editor.scrollIntoView({block:'start'});
    const r=editor.getBoundingClientRect();
    return {x:r.left+scrollX,y:r.top+scrollY,width:r.width,height:r.height,scale:1};
   })()`,returnByValue:true},sessionId);
   const image=await send('Page.captureScreenshot',{format:'png',clip:visual.result.value,captureBeyondViewport:true},sessionId);
   fs.writeFileSync(path.join(dir,'actual-edit-'+width+'.png'),Buffer.from(image.data,'base64'));
  }
 }
 if(process.env.KEEP_RENTAL_WORKFLOW_FIXTURE==='1')console.log('Actual PHP modal screenshots: '+dir);
}
main().catch(error=>{console.error(error);process.exitCode=1;}).finally(async()=>{
 if(socket)socket.close();if(browser){const stopped=new Promise(r=>browser.once('exit',r));browser.kill('SIGKILL');await stopped;}
 await new Promise(r=>setTimeout(r,300));
 try {if(process.env.KEEP_RENTAL_WORKFLOW_FIXTURE!=='1')fs.rmSync(dir,{recursive:true,force:true,maxRetries:10,retryDelay:250});}
 catch(error){if(error.code!=='ENOTEMPTY'&&error.code!=='EBUSY')throw error;}
});
