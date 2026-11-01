const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
if (typeof WebSocket === 'undefined') {
    const child = spawnSync(process.execPath,['--experimental-websocket',__filename],{stdio:'inherit'});
    process.exit(child.status ?? 1);
}
const root = path.join(__dirname,'..');
const dir = fs.mkdtempSync(path.join(os.tmpdir(),'events-dashboard-'));
const dashboard = fs.readFileSync(path.join(root,'pages/company_dashboard.php'),'utf8');
const nav = dashboard.match(/<ul[^>]*id="subtabs-events"[\s\S]*?<\/ul>/)[0].replace('display: none','display: flex');
assert.ok(nav.includes('href="#events-dashboard">Arrangementsdashboard'));
for (const name of ['events-my','events-registrations','events-create','events-checkin','events-results']) {
    assert.ok(nav.includes('href="#'+name+'"'), 'Existing Events view retained: '+name);
}
function download(url) {
    const out = spawnSync('curl',['--fail','--silent','--show-error','--max-time','30',url],{encoding:'utf8',maxBuffer:2*1024*1024});
    assert.equal(out.status,0,out.stderr);
    return out.stdout;
}
const bootstrapCss = download('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css');
const bootstrapJs = download('https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js');
const render = spawnSync('php',['-r','$_SESSION=["company_id"=>"fixture"]; $hasMembershipAccess=true; include $argv[1];',
    path.join(root,'modules/events_content.php')],{encoding:'utf8',maxBuffer:3*1024*1024});
assert.equal(render.status,0,render.stderr);
let moduleHtml = render.stdout
    .replace(/<script[^>]*src="https:[^"]+"[^>]*><\/script>/g,'')
    .replace(/<link[^>]*href="https:[^"]+"[^>]*>/g,'')
    .replace(/<script[^>]*src="(\/assets\/[^"]+)"[^>]*><\/script>/g,(_,src) => '<script>'+fs.readFileSync(path.join(root,src.split('?')[0]),'utf8')+'</script>')
    .replace(/<link[^>]*href="(\/assets\/[^"]+)"[^>]*>/g,(_,src) => '<style>'+fs.readFileSync(path.join(root,src.split('?')[0]),'utf8')+'</style>');
const stubs = `
window.testErrors=[];window.fixtureCalls=[];window.fixtureWrites=[];window.fixtureFieldWrites=[];window.fixtureEdits=[];
window.fixtureManualWrites=[];window.fixtureManualCheckins=[];
window.addEventListener('error',e=>testErrors.push(e.message));
window.addEventListener('unhandledrejection',e=>testErrors.push(String(e.reason)));
window.confirmAnswer=true;window.confirm=()=>confirmAnswer;window.prompt=()=> 'Testbegrunnelse';
window.toast=new Proxy({}, {get:()=>()=>{}});
window.fixtureEvents=Array.from({length:7},(_,i)=>({
 id:'event-'+i,title:'Arrangement '+i,description:'Beskrivelse '+i,registration_type:i===6?'none':i===0?'binding':'signup',
 start_date:'2035-06-'+String(12+i).padStart(2,'0'),start_time:'19:00:00',end_date:'2035-06-'+String(12+i).padStart(2,'0'),end_time:'23:00:00',
 ticket_price:i===2?0:250,min_attendees:2,registration_deadline:'2035-06-01 18:45:12',max_attendees:50,
 is_active:i===4?0:1,is_past:i===5?1:0,venue_name:'Forsamlingshuset',event_city:'Bergen',
 counts_loaded:true,current_attendees:3,pending_registrations:2,checked_in_count:1,
 is_member_exclusive:i===3?1:0,memberverification:1,payment_methods_json:'["vipps"]',default_payment_method:'vipps',
 membership_prices_loaded:true,membership_prices:i<2||i===3?[{plan_id:'standard',plan_name:'Standardmedlem',price:150,public_display:1},{plan_id:'premium',plan_name:'Premiummedlem',price:0,public_display:0}]:[]
}));
window.fixtureFields=['first_name','last_name','email','phone','custom_note'].map((name,index)=>({
 field_name:name,field_label:{first_name:'Fornavn',last_name:'Etternavn',email:'E-post',phone:'Telefon',custom_note:'Kommentar'}[name],
 field_type:name==='email'?'email':name==='phone'?'tel':'text',is_visible:index<3,is_required:index<2,sort_order:index,validation_rules:{},options:[]
}));
window.fixtureManualFields=[...fixtureFields,{field_name:'dietary',field_label:'Matbehov',field_type:'textarea',is_visible:1,is_required:1,sort_order:5,validation_rules:{max_length:300},options:[]}];
window.fixtureFailFields=false;window.fixtureFailFieldSave=false;window.fixtureFailEvents=false;window.fixtureFailEdit=false;
window.fixtureMembershipPlans=[{id:'standard',name:'Standardmedlem'},{id:'premium',name:'Premiummedlem'}];
window.fixturePending=['event-0','event-1'].map((event_id,i)=>({id:'reg-'+i,event_id,event_title:'Arrangement '+i,start_date:'2035-06-12',start_time:'19:00:00',
 entry_data:{first_name:'John',last_name:String(i),email:'john@example.test'},first_name:'John',last_name:String(i),price_info:{price:250,is_member:false},created_at:'2026-10-01',approval_status:'pending'}));
window.fetch=async (url,options={})=>{
 fixtureCalls.push(String(url));
 const route=String(url);const params=new URL(route,'https://fixture.test').searchParams;
 const payload=options.body?JSON.parse(options.body):null;
 let result={success:true,data:[],plans:[],registrations:[],checkins:[]},status=200;
 if(route.includes('get_event_checkins.php')){
  const rows=fixtureManualCheckins.filter(row=>row.event_id===params.get('event_id'));
  const checked=rows.filter(row=>row.checked_in===1),pending=rows.filter(row=>row.checked_in===0);
  result={success:true,checked_in:checked,not_checked_in:pending,summary:{
   total_attendees:rows.length,checked_in_count:checked.length,not_checked_in_count:pending.length,no_show_count:0,
   checkin_revenue:0,expected_revenue:0,paid_count:0,checkin_paid_count:0,pending_count:rows.length
  }};
 }
 else if(route.includes('get_event_fields.php')){result={success:true,fields:fixtureManualFields};}
 else if(route.includes('create_manual_event_participant.php')){
  fixtureManualWrites.push(payload);
  const checkin={id:'manual-'+fixtureManualCheckins.length,event_id:payload.event_id,
   checked_in:payload.checked_in?1:0,check_in_time:payload.checked_in?'2026-10-30 12:00:00':null,
   customer_name:payload.attendee_first_name+' '+payload.attendee_last_name,customer_email:payload.attendee_email,
   customer_phone:payload.attendee_phone,membership_plan_name:null,ticket_price:0,payment_status:'pending',
   payment_method:null,submitted_fields:fixtureManualFields.filter(field=>field.is_visible).map(field=>({
    field_name:field.field_name,field_label:field.field_label,field_type:field.field_type,
    value:({first_name:payload.attendee_first_name,last_name:payload.attendee_last_name,email:payload.attendee_email,
     phone:payload.attendee_phone,...payload.optional_fields})[field.field_name]??''
   }))};
  fixtureManualCheckins.push(checkin);
  result={success:true,message:'Deltakeren er lagt til',checkin_id:checkin.id,checked_in:payload.checked_in,
   payment_status:'pending',ticket_price:0};
 }
 if(route.includes('get_events.php')){result={success:!fixtureFailEvents,events:fixtureEvents,message:'Fixture event-load failure'};}
 else if(route.includes('get_event.php')){result={success:true,event:fixtureEvents.find(e=>e.id===params.get('id'))};}
 else if(route.includes('get_entity_form_fields.php')){result={success:!fixtureFailFields,csrf_token:'fixture-csrf',revision:'revision-a',
  registration_type:fixtureEvents.find(e=>e.id===params.get('entity_id'))?.registration_type||'signup',
  fields:fixtureFields,presets:[{key:'contact',label:'Kontaktinformasjon',fields:fixtureFields}],message:'Fixture field-load failure'};}
 else if(route.includes('save_entity_form_fields.php')){fixtureFieldWrites.push(payload);result={success:!fixtureFailFieldSave,fields:payload.fields,revision:'revision-b',message:'Fixture conflict, draft retained'};if(fixtureFailFieldSave)status=409;}
 else if(route.includes('get_event_membership_pricing.php')){result={success:true,pricing:[...(fixtureEvents.find(e=>e.id===params.get('event_id'))?.membership_prices||[]).map(p=>({...p,ticket_price:p.price})),{plan_id:'retired',plan_name:'Tidligere medlemsplan',ticket_price:175,public_display:0}]};}
 else if(route.includes('get_membership_plans.php')){result={success:true,plans:fixtureMembershipPlans};}
 else if(route.includes('get_payment_methods.php')){result={success:true,payment_methods:[{method_type:'vipps',method_name:'Vipps'},{method_type:'cash',method_name:'Kontant'}]};}
 else if(route.includes('update_event.php')){fixtureEdits.push(payload);result={success:!fixtureFailEdit,message:'Fixture edit failure'};}
 else if(route.includes('set_event_visibility.php')){fixtureWrites.push(payload);fixtureEvents.find(e=>e.id===payload.event_id).is_active=payload.is_active;}
 else if(route.includes('approve_registration.php')||route.includes('reject_registration.php')){fixtureWrites.push({route,...payload});result={success:true,ticket_price:250};}
 else if(route.includes('get_pending_registrations.php')){result={success:true,registrations:fixturePending};}
 else if(route.includes('get_all_registrations.php')){result={success:true,grouped_by_event:fixturePending.map(r=>({event_id:r.event_id,event_title:r.event_title,start_date:r.start_date,start_time:r.start_time,approved_count:0,rejected_count:0,registrations:[r]}))};}
 else if(route.includes('delete_event.php')){fixtureWrites.push({route,...payload});result={success:false,message:'Arrangementet har historikk, bruk Skjul'};}
 return {ok:status<400,status,json:async()=>result};
};
window.showSubTabContent=(module,id)=>{
 document.querySelectorAll('#content-'+module+' .subtab-content').forEach(n=>{n.style.display=n.id===id?'block':'none'});
 document.dispatchEvent(new CustomEvent('subtab-changed',{detail:{module,subtab:id}}));
};
window.addEventListener('DOMContentLoaded',()=>showSubTabContent('events','events-dashboard'));
`;
const checks = `(async()=>{
 const check=(value,message)=>{if(!value)throw new Error(message)};
 const settle=()=>new Promise(resolve=>setTimeout(resolve,120));
 const nav=id=>document.querySelector('#subtabs-events a[href="#'+id+'"]').click();
 const card=id=>document.getElementById('events-event-'+id);
 const action=(id,name)=>card(id).querySelector('[data-event-action="'+name+'"]');
 const click=(el)=>{check(el,'Missing expected control');el.click()};
 const select=(el,value)=>{el.value=value;el.dispatchEvent(new Event('change',{bubbles:true}))};
 const typed=(el,value)=>{el.value=value;el.dispatchEvent(new InputEvent('input',{bubbles:true,inputType:'insertText',data:value}))};
 await settle();
 check(card('event-0'),'Dashboard loaded real source data');
 check(document.querySelector('[data-event-create]'),'Create entrance present');
 check(card('event-0').textContent.includes('150')&&card('event-0').textContent.includes('Premium'),'Actual member prices shown alongside regular price');
 check(card('event-0').textContent.includes('50')&&card('event-0').textContent.includes('venter'),'Capacity and pending count shown');
 check(card('event-2').textContent.includes('Gratis'),'Zero priced event displayed without is_free');
 check(!card('event-4')&&!card('event-5'),'Default filter excludes hidden and past');
 select(document.getElementById('eventsDashboardStatus'),'all');await settle();
 check(card('event-4')&&card('event-5'),'Hidden/past retrievable through filter');
 const search=document.getElementById('eventsDashboardSearch');
 search.value='saved@example.test';search.dispatchEvent(new Event('input',{bubbles:true}));await settle();
 check(card('event-0')&&search.value==='','Autofill must not wipe inventory');
 typed(search,'Arrangement 1');await settle();check(card('event-1')&&!card('event-0'),'Intentional search filters');
 typed(search,'');await settle();
 const detail=card('event-0').querySelector('.events-event-card');
 detail.click();await settle();await settle();
 check(document.getElementById('eventsOverviewModal').classList.contains('show')&&
   document.getElementById('eventsOverviewModalTitle').textContent==='Deltakere · Arrangement 0',
   'Whole card opens the dashboard-owned participant list');
  check(document.querySelector('#eventsOverviewModalBody .event-participants')
   &&!document.querySelector('#eventsOverviewModalBody #events-checkin')
   &&!document.querySelector('#eventsOverviewModalBody #checkin-pending-tab'),
   'Participant view is separate from the check-in interface');
  const addParticipant=document.querySelector('#eventsOverviewModalBody [data-dashboard-add-participant]');
  check(addParticipant&&addParticipant.textContent.includes('Legg til deltaker'),'Dashboard participant list has its own add action');
  check(!document.querySelector('#checkin-confirmed-tab .card-header button[onclick*="beginManualEventParticipant"]')
   &&!document.querySelector('#checkin-pending-tab .card-header button[onclick*="beginManualEventParticipant"]'),
   'Check-in tabs do not expose manual participant creation');
  click(addParticipant);await settle();
  check(document.querySelector('#dashboard-event-participant-list .manual-participant-row'),
   'Dashboard action creates an inline participant form outside check-in');
  click(document.querySelector('#dashboard-event-participant-list [data-manual-participant-cancel]'));await settle();
 bootstrap.Modal.getInstance(document.getElementById('eventsOverviewModal')).hide();await settle();
 // A real user normally presses the title, not the empty card background.
 click(card('event-0').querySelector('.events-event-open'));await settle();await settle();
 check(document.getElementById('eventsOverviewModal').classList.contains('show')&&
   document.getElementById('eventsOverviewModalTitle').textContent==='Deltakere · Arrangement 0',
  'Pressing the event title opens participants, not the old details panel');
 bootstrap.Modal.getInstance(document.getElementById('eventsOverviewModal')).hide();await settle();
 const cardPlus=card('event-0').querySelector('[data-event-participants-add]');
 check(cardPlus&&cardPlus.getBoundingClientRect().height>=32,'Each card has a visible touch-sized plus button');
 click(cardPlus);await settle();await settle();
  check(document.querySelectorAll('#dashboard-event-participant-list .manual-participant-row').length===1,
  'Card plus opens participant list with exactly one editable participant row');
 check(document.documentElement.scrollWidth<=innerWidth+1,'Participant modal stays within mobile viewport');
 if(innerWidth<=576){
   const editor=document.querySelector('#dashboard-event-participant-list .manual-participant-editor');
  check(editor.getBoundingClientRect().right<=innerWidth-16,'Inline form fits without horizontal scrolling on mobile');
  check(editor.querySelector('[data-manual-participant-save]').getBoundingClientRect().right<=innerWidth-16,
   'Save action stays inside the mobile viewport');
 }
  click(document.querySelector('#dashboard-event-participant-list [data-manual-participant-cancel]'));await settle();
 bootstrap.Modal.getInstance(document.getElementById('eventsOverviewModal')).hide();await settle();
 click(card('event-0').querySelector('.events-event-cover'));await settle();await settle();
 check(document.getElementById('eventsOverviewModal').classList.contains('show'),'Image area also opens participants');
 bootstrap.Modal.getInstance(document.getElementById('eventsOverviewModal')).hide();await settle();
 // Specific menu controls retain their own action and do not open participants.
 click(card('event-0').querySelector('[data-event-details]'));
 check(!document.getElementById('events-detail-event-0').hidden&&!document.getElementById('eventsOverviewModal').classList.contains('show'),'Menu details action stays separate from card action');
 action('event-0','fields').click();await settle();
 let panel=document.getElementById('events-inline-fields-event-0');
 check(!panel.hidden&&panel.querySelector('[data-common-field="email"]'),'Editor embedded in card');
 check(panel.querySelectorAll('.entity-field-row').length===0,'No field rows expanded by default');
 check(panel.querySelector('.event-fields-order-controls button'),'Order controls available without opening every row');
 click(panel.querySelector('[data-edit-field="email"]'));
 check(panel.querySelectorAll('.entity-field-row').length===1,'Exactly one field editable');
 check(document.documentElement.scrollWidth<=innerWidth+1,'Inline editor has no page overflow');
 const label=panel.querySelector('[data-prop="field_label"]');
 typed(label,'Ny e-postetikett');
 fixtureFailFieldSave=true;click(panel.querySelector('[data-save]'));await settle();
 check(!panel.hidden&&label.value==='Ny e-postetikett','Failed field save retains draft');
 confirmAnswer=false;action('event-1','fields').click();await settle();
 check(!panel.hidden&&document.getElementById('events-inline-fields-event-1').hidden,'Dirty switch guarded');
 fixtureFailFieldSave=false;confirmAnswer=true;click(panel.querySelector('[data-save]'));await settle();
 check(panel.hidden&&fixtureFieldWrites.at(-1).entity_id==='event-0','Scoped save closes inline editor');
 action('event-1','fields').click();await settle();panel=document.getElementById('events-inline-fields-event-1');
 const order=panel.querySelector('[aria-label="Flytt E-post ned"]');click(order);
 click(panel.querySelector('[data-save]'));await settle();
 check(fixtureFieldWrites.at(-1).fields.every((f,i)=>f.sort_order===i),'Saved order persisted');
 action('event-6','fields').click();await settle();
 panel=document.getElementById('events-inline-fields-event-6');check(panel.querySelector('[data-save]').disabled,'No-registration field editing disabled');
 await EventsDashboard.closeFieldEditor(true);
 action('event-0','edit').click();await settle();
 check(document.getElementById('editEventModal').classList.contains('show'),'Existing basic modal opens');
 document.getElementById('edit-tab-4').click();await settle();
 check(document.getElementById('edit-panel-4').classList.contains('active'),'Price section actually opens');
 check(document.getElementById('editEventModal').scrollWidth<=document.getElementById('editEventModal').clientWidth+1,'Basic edit modal fits viewport');
 check(document.getElementById('edit-registration-deadline').value==='01.06.2035','Deadline imported in DD.MM.YYYY');
 check(document.getElementById('edit-memberverification').checked,'Membership flag preserved');
 check(!document.querySelector('#edit-event-form [data-method-type="cash"]')?.checked,'Binding never selects cash');
 const pricing=document.querySelector('#edit-membership-pricing-container [data-membership-price]');
 check(document.querySelector('[data-retained-membership-prices]').textContent.includes('175'),'Inactive plan price displayed as retained, not silently omitted');
 check(document.querySelectorAll('.edit-membership-plan-row').length===2,'Only active plans are editable');
 check(pricing,'Membership prices truly editable');
 typed(pricing,'125');fixtureFailEdit=true;
 await saveEventChanges();await settle();
 check(document.getElementById('editEventModal').classList.contains('show')&&pricing.value==='125','Basic save failure retains draft');
 fixtureFailEdit=false;await saveEventChanges();await settle();
 check(fixtureEdits.at(-1).membership_prices.length===2&&fixtureEdits.at(-1).memberverification===1,'Basics submit membership prices/flags atomically');
 check(!document.getElementById('editEventModal').classList.contains('show'),'Successful basic save closes modal');
 const activePlans=fixtureMembershipPlans;fixtureMembershipPlans=[];
 await editEvent('event-0');await settle();
 check(!document.querySelector('.edit-membership-plan-row')&&document.querySelector('[data-retained-membership-prices]'),'No-active-plan state retains historical price information');
 await saveEventChanges();await settle();
 check(fixtureEdits.at(-1).membership_prices.length===0,'Empty active list can save without submitting readonly retained plans');
 fixtureMembershipPlans=activePlans;
 select(document.getElementById('eventsDashboardStatus'),'all');await settle();
 click(document.querySelector('[data-open-overview="upcoming"]'));await settle();
 check(document.querySelector('.events-upcoming-card'),'Upcoming events open in overview modal');
 check(!document.querySelector('[data-upcoming-event="event-5"]'),'Past event is excluded from upcoming');
 bootstrap.Modal.getInstance(document.getElementById('eventsOverviewModal'))?.hide();await settle();await settle();
 action('event-4','visibility').click();await settle();
 check(fixtureWrites.some(w=>w.event_id==='event-4'&&w.is_active===1&&w.csrf_token==='fixture-csrf'),'Show uses scoped authorized API');
 const overviewButton=document.querySelector('[data-open-overview="requests"]');
 click(overviewButton);await settle();
 check(document.querySelector('#eventsOverviewModalBody #events-registrations'),'Registrations reuse actual markup, no cloning');
 check(document.querySelector('#pending-registrations-list [onclick*="approveRegistrationWithReason"]'),'Authoritative approve action still present');
 select(document.getElementById('eventsRegistrationsEventFilter'),'event-1');await settle();
 check(document.querySelector('[data-registration-id="reg-0"]').hidden&&!document.querySelector('[data-registration-id="reg-1"]').hidden,'Pending registrations filtered by immutable event ID');
 await approveRegistrationWithReason('reg-0');
 check(fixtureWrites.some(w=>w.registration_id==='reg-0'&&w.approval_reason==='Testbegrunnelse'),'Approval posts existing endpoint and reason');
 rejectRegistrationWithReason('reg-1');await settle();await settle();
 check(document.getElementById('rejectionReasonModal').classList.contains('show')&&!document.getElementById('eventsOverviewModal').classList.contains('show'),'Rejection opens with a single active modal');
 document.querySelector('#rejectionReasonModal input[type="checkbox"]').checked=true;await submitRejection();await settle();await settle();
 check(fixtureWrites.some(w=>w.registration_id==='reg-1'&&w.rejection_reason),'Rejection posts existing reasoned action');
 nav('events-registrations');await settle();await settle();
 check(document.getElementById('events-registrations').style.display==='block'&&!document.querySelector('#eventsOverviewModalBody #events-registrations'),'Original registration subtab restored and usable');
 nav('events-my');await settle();check(document.getElementById('events-my').style.display==='block','Old event list retained');
 nav('events-dashboard');await settle();
 await openEventDetail('event-0','Arrangement 0','12.06.2035');await settle();await settle();
  check(!document.querySelector('#checkin-confirmed-tab .card-header button[onclick*="beginManualEventParticipant"]')
   &&!document.querySelector('#checkin-pending-tab .card-header button[onclick*="beginManualEventParticipant"]'),
   'No manual participant add buttons in either check-in list');
  document.getElementById('open-fullscreen-checkin').click();await settle();
  check(document.getElementById('fullscreen-checkin-overlay').classList.contains('active'),'Fullscreen check-in still opens');
  check(!document.querySelector('#fullscreen-checkin-overlay button[onclick*="beginManualEventParticipant"]'),
   'No manual participant add buttons in fullscreen check-in');
  closeFullscreenCheckin();await settle();

  click(card('event-0').querySelector('[data-event-participants-add]'));await settle();await settle();
  check(eventManualParticipantCsrfToken==='fixture-csrf','Dashboard participant editor receives the event-scoped CSRF token');
  let manualRow=document.querySelector('#dashboard-event-participant-list .manual-participant-row');
  check(manualRow&&manualRow.querySelectorAll('[data-manual-participant-field]').length>=5,
   'Dashboard inline row exposes core and configured optional fields');
  check(document.documentElement.scrollWidth<=innerWidth+1,'Dashboard participant editor fits the viewport');
  click(manualRow.querySelector('[data-manual-participant-save]'));await settle();
  check(fixtureManualWrites.length===0&&manualRow.querySelector('[data-manual-participant-error]').textContent.includes('Fornavn og etternavn'),
  'Missing names prevent the API call and keep an inline validation message');
 typed(manualRow.querySelector('[data-manual-participant-field="first_name"]'),'Ada');
 typed(manualRow.querySelector('[data-manual-participant-field="phone"]'),'12345678');
 click(manualRow.querySelector('[data-manual-participant-save]'));await settle();
 check(fixtureManualWrites.length===0,'Missing surname remains blocked after partial entry');
 typed(manualRow.querySelector('[data-manual-participant-field="last_name"]'),'Nord');
 click(manualRow.querySelector('[data-manual-participant-save]'));await settle();
 check(fixtureManualWrites.length===1&&fixtureManualWrites[0].checked_in===false
  &&fixtureManualWrites[0].attendee_first_name==='Ada'&&fixtureManualWrites[0].attendee_last_name==='Nord'
  &&fixtureManualWrites[0].attendee_phone==='12345678'&&fixtureManualWrites[0].optional_fields.dietary==='',
   'Dashboard add saves optional blanks without entering a check-in status');
  check(document.querySelector('#dashboard-event-participant-list').textContent.includes('Ada Nord')
   &&document.querySelector('.event-participants-status').textContent==='1 deltaker',
   'Successful save refreshes the dashboard-owned attendee list');
  check(!document.querySelector('#eventsOverviewModalBody #events-checkin')
   &&!document.querySelector('#checkin-pending-list').textContent.includes('Ada Nord'),
   'Saving from the dashboard does not open or refresh check-in lists');
  bootstrap.Modal.getInstance(document.getElementById('eventsOverviewModal')).hide();await settle();
 fixtureFailEvents=true;await loadEvents();await settle();
 check(document.getElementById('eventsDashboardNotice').textContent.toLowerCase().includes('feil'),'Source error is explicit');
 fixtureFailEvents=false;await loadEvents();await settle();
 const allIds=[...document.querySelectorAll('[id]')].map(n=>n.id);
 check(new Set(allIds).size===allIds.length,'No duplicate DOM IDs across old/new views');
 check(document.documentElement.scrollWidth<=innerWidth+1,'No page horizontal overflow at '+innerWidth);
 check(!testErrors.length,'Unexpected JS error: '+testErrors.join('; '));
 return {passed:true,width:innerWidth,fieldSaves:fixtureFieldWrites.length,basicSaves:fixtureEdits.length};
})()`;
const fixture = path.join(dir,'dashboard.html');
const html = content => '<!doctype html><html lang="nb"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">'+
    '<style>'+bootstrapCss+'\n'+fs.readFileSync(path.join(root,'assets/dashboard-module-theme.css'),'utf8')+
    '</style><script>'+bootstrapJs+'</script><script>'+stubs+'</script><body><div class="container">'+nav+
    '<div id="content-events" class="tab-content">'+content+'</div></div></body></html>';
fs.writeFileSync(fixture,html(moduleHtml));
const noMemberRender = spawnSync('php',['-r','$_SESSION=["company_id"=>"fixture"]; $hasMembershipAccess=false; include $argv[1];',
    path.join(root,'modules/events_content.php')],{encoding:'utf8',maxBuffer:3*1024*1024});
assert.equal(noMemberRender.status,0,noMemberRender.stderr);
const noMemberFragment = noMemberRender.stdout
    .replace(/<script[^>]*src="https:[^"]+"[^>]*><\/script>/g,'')
    .replace(/<link[^>]*href="https:[^"]+"[^>]*>/g,'')
    .replace(/<script[^>]*src="(\/assets\/[^"]+)"[^>]*><\/script>/g,(_,src) => '<script>'+fs.readFileSync(path.join(root,src.split('?')[0]),'utf8')+'</script>')
    .replace(/<link[^>]*href="(\/assets\/[^"]+)"[^>]*>/g,(_,src) => '<style>'+fs.readFileSync(path.join(root,src.split('?')[0]),'utf8')+'</style>');
const noMemberFixture = path.join(dir,'without-membership.html');
fs.writeFileSync(noMemberFixture,html(noMemberFragment));
const noMemberChecks = `(async()=>{
 const check=(v,m)=>{if(!v)throw new Error(m)};
 await new Promise(r=>setTimeout(r,200));
 check(!document.getElementById('eventsDashboardList').textContent.toLowerCase().includes('medlem'),'Dashboard hides all Membership labels/prices without access');
 check(!document.getElementById('edit-memberverification')&&!document.getElementById('edit-member-exclusive'),'PHP omits inaccessible membership controls');
 await editEvent('event-0');
 await saveEventChanges();
 check(fixtureEdits.length===1&&!Object.hasOwn(fixtureEdits[0],'membership_prices')&&!Object.hasOwn(fixtureEdits[0],'memberverification'),'Ordinary edit omits rather than clears protected settings');
 check(!testErrors.length,'No JS errors without Membership: '+testErrors.join('; '));
 return {passed:true,width:innerWidth,membership:false};
})()`;
(async()=>{
 await require('./chromium_fixture')(fixture,checks,[320,390,1280]);
 await require('./chromium_fixture')(noMemberFixture,noMemberChecks,[390,1280]);
})().catch(error=>{console.error(error);process.exitCode=1});
