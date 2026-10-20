// Real production partial/styles/scripts in a disposable browser fixture; no login bypass or real writes.
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const {spawnSync} = require('node:child_process');
const chromiumFixture = require('./chromium_fixture');
const root = path.resolve(__dirname,'..');
const dir = fs.mkdtempSync(path.join(os.tmpdir(),'portal-overview-ui-'));
const render = supervisor => {
    const php = `
    $adminName='Demo administrator';$companyName='Demo firma';$companySubdomain=null;
    $companyLogo=null;$auditVisible=${supervisor};$isSupervisor=${supervisor};
    $source=file_get_contents('pages/company_dashboard.php');
    $nav=substr($source,strpos($source,'<nav class="navbar'),strpos($source,'</nav>')+6-strpos($source,'<nav class="navbar'));
    eval('?>'.$nav);
    include 'includes/portal_overview.php';`;
    const result = spawnSync('php',['-r',php],{cwd:root,encoding:'utf8'});
    if (result.status !== 0) throw new Error(result.stderr);
    return result.stdout;
};
const fixture = path.join(dir,'fixture.html');
const css = fs.readFileSync(path.join(root,'assets/portal-overview.css'),'utf8');
const source = fs.readFileSync(path.join(root,'assets/portal-overview.js'),'utf8');
const notices = fs.readFileSync(path.join(root,'assets/tenant_notifications.js'),'utf8');
const systemToast = fs.readFileSync(path.join(root,'includes/toast_notification.php'),'utf8');
const setup = `
window.errors=[];window.addEventListener('error',e=>errors.push(e.message));
window.calls=[];window.navigations=[];window.failAck=false;window.failGrant=false;window.failPoll=false;window.hangPoll=false;
window.PORTAL_NOTICE_TIMEOUT_MS=60;
window.rawPolls=[
 {id:'old',module_source:'events',created_at:'2026-10-19 09:00:00',action_type:'event_registration',metadata:{customer_name:'Kari'}},
 {id:'new',module_source:'rental',created_at:'2026-10-20 10:00:00',action_type:'rental_booking',metadata:{event_type:'rental.request.created',customer_name:'John',resource_type:'rental_schedule',resource_id:'req-1'}},
 {id:'hidden',module_source:'economy',created_at:'2026-10-21',action_type:'payment_received'}];
window.openRentalRequestNotification=async id=>{navigations.push(id);return true};
window.bootstrap={Tab:{getOrCreateInstance:button=>({show:()=>navigations.push(button.dataset.bsTarget)})}};
window.fetch=async(url,options={})=>{
 const body=options.body?JSON.parse(options.body):null;calls.push({url,body});
 let data,status=200;
 if(url.includes('check_refresh_flag')){
  if(hangPoll)return new Promise((resolve,reject)=>options.signal.addEventListener('abort',()=>reject(new DOMException('Aborted','AbortError')),{once:true}));
  if(failPoll){status=503;data={success:false,message:'Varseltjenesten er midlertidig utilgjengelig.'};}
  else data={success:true,polls:rawPolls,csrf_token:'notice-csrf'};
 }
 else if(url.includes('set_polling_complete')){
  if(failAck){status=500;data={success:false,message:'Lukking feilet'};}
  else{rawPolls=rawPolls.filter(p=>p.id!==body.poll_id);data={success:true};}
 }else if(url.includes('portal_overview'))data={success:true,agenda:[{id:'x',title:'Neste arrangement',module:'events',date:'2026-10-23',time:'19:00:00'}],activity:[{id:'a',actor_name:'Demo',action:'update',resource_name:'Klubbhuset',performed_at:'2026-10-20 09:00:00'}],errors:[]};
 else if(url.includes('company_users')){
  if(body){if(failGrant){status=403;data={success:false,message:'Ingen modultilgang'};}else data={success:true};}
  else data={success:true,csrf_token:'user-csrf',users:[{id:'user-1',name:'Ansatt Navn',username:'demo',is_active:true,modules:['rental']}],modules:[{id:'rental',name:'Rental'},{id:'events',name:'Events'}]};
 }
 return {ok:status===200,status,json:async()=>data};
};`;
const html = '<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1">'
    + '<style>body{margin:0;font-family:Arial}.container{max-width:1104px;margin:auto;padding:0 16px;box-sizing:border-box}.modal{display:none}.navbar{display:none}button,input,select{font:inherit}*{box-sizing:border-box}'
    + css + '</style><body data-admin-id="current">'
    + render('true')
    + '<div class="module-tabs"><button data-bs-target="#content-rental">Rental</button><button data-bs-target="#content-events">Events</button></div>'
    + '<script>' + setup + '</script><script>' + source + '</script><script>' + notices + '</script>' + systemToast;
fs.writeFileSync(fixture,html);
const ordinary = render('false');
if (ordinary.includes('Aktivitetslogg') || ordinary.includes('Sessions') || ordinary.includes('id="po-activity"') || ordinary.includes('id="company-users-modal"')) {
    throw new Error('Ordinary user sees supervisor content');
}
const supervisor = render('true');
for (const item of ['Aktivitetslogg','Sessions','Administrer firmabrukere']) {
    if (!supervisor.includes(item)) throw new Error('Supervisor missing ' + item);
}
const checks = `(async()=>{
 const check=(ok,label)=>{if(!ok)throw new Error(label)};
 const wait=()=>new Promise(r=>setTimeout(r,45));
 await wait();
 check(errors.length===0,'No startup JS errors: '+errors.join(','));
 check(document.querySelectorAll('.po-notice-card').length===1,'Exactly one foreground card');
 check(document.querySelector('.po-notice-card').dataset.notificationId==='new','Newest first');
 check(document.querySelector('#po-notice-count').textContent==='2','Unauthorized module excluded');
 check(document.querySelector('.po-notice-open').textContent.includes('John'),'Concrete customer notification');
  for (const items_count of [0,1,8]) {
   const text=TenantNotifications.describe({module_source:'events',action_type:'new_reservation',
    metadata:{event_title:'Sommerfest'},items_count});
   check(text==='Ny reservasjon — Sommerfest','Event reservation has no misleading aggregate count for items_count='+items_count);
  }
  check(TenantNotifications.describe({module_source:'rental',action_type:'rental_booking',items_count:4})
   .includes('(4 hendelser)'),'Non-event aggregate counts remain unchanged');
  check(document.querySelector('#po-notice-count').textContent==='2','Pending notice badge remains independent of event aggregate count');
  toast.quickInfo('Test','Samme popup');
  toast.quickInfo('Test','Samme popup');
  check(document.querySelector('.toast-notification-count')?.textContent==='2','Repeated identical system toast count remains unchanged');
 failAck=true;document.querySelector('.po-notice-open').click();await wait();
 check(document.querySelector('.po-error').textContent==='Lukking feilet','Acknowledgement failure visible');
 check(document.querySelector('.po-notice-card').dataset.notificationId==='new','Failed acknowledgement does not lose notification');
 check(navigations.includes('req-1'),'Existing exact rental request opener used');
 failAck=false;document.querySelector('.po-notice-open').click();await wait();
 check(document.querySelector('.po-notice-card').dataset.notificationId==='old','Click advances stack after confirmation');
 document.querySelector('.po-notice-open').click();await wait();
 check(navigations.includes('#content-events'),'Event notification navigates to authorized module');
 check(document.querySelectorAll('.po-notice-card').length===0,'Empty state');
 await TenantNotifications.check();
 check(document.querySelectorAll('.po-notice-card').length===0,'Dismissed notifications do not reappear');
 failPoll=true;await TenantNotifications.check();
 check(document.querySelector('.po-notice-stack').textContent.includes('midlertidig utilgjengelig'),'API errors are visible');
  check(document.querySelector('.po-retry-notifications')&&document.querySelector('.po-notice-stack').getAttribute('aria-busy')==='false','API error offers retry and clears busy state');
 failPoll=false;rawPolls=[];document.querySelector('.po-retry-notifications').click();await wait();
 check(document.querySelector('.po-empty').textContent.includes('Ingen nye varsler'),'Retry recovers to empty state');
 rawPolls=[{id:'retry-me',module_source:'rental',created_at:'2026-10-22 10:00:00',action_type:'rental_booking'}];
 hangPoll=true;await TenantNotifications.check();await new Promise(r=>setTimeout(r,90));
 check(document.querySelector('.po-notice-stack').textContent.includes('for lang tid'),'Hanging API request times out visibly');
 check(document.querySelector('.po-retry-notifications'),'Timeout state allows retry');
 hangPoll=false;document.querySelector('.po-retry-notifications').click();await wait();
 check(document.querySelector('[data-notification-id="retry-me"]'),'Retry recovers from timeout');
 const modal=document.getElementById('company-users-modal');
 modal.dispatchEvent(new Event('show.bs.modal'));await wait();
 check(document.querySelector('.po-user-card h3').textContent==='Ansatt Navn','Real staff label');
 const picker=document.querySelector('.po-module-picker');
 check(getComputedStyle(picker).display==='none','Module picker collapsed initially');
 document.querySelector('.po-add-module').click();
 check(!picker.hidden,'Plus module expands picker');
 check([...picker.querySelectorAll('option')].map(n=>n.value).join(',')==='events','Only company available modules');
 failGrant=true;picker.dispatchEvent(new Event('submit',{cancelable:true}));await wait();
 check(document.querySelectorAll('.po-module-pill').length===1,'Failed grant does not change local state');
 failGrant=false;picker.dispatchEvent(new Event('submit',{cancelable:true}));await wait();
 check(document.querySelectorAll('.po-module-pill').length===2,'Confirmed grant visible');
 check(document.querySelector('.po-module-picker').hidden,'Picker closes after save');
 const passwordButton=[...document.querySelectorAll('.po-user-card button')].find(n=>n.textContent==='Nytt passord');
 passwordButton.click();
 const input=document.querySelector('input[type=password]');input.value='LongFixturePassword';
 document.querySelector('.po-password-form').dispatchEvent(new Event('submit',{cancelable:true}));await wait();
 check(calls.some(c=>c.body?.action==='password'&&c.body.csrf_token==='user-csrf'),'Explicit password submit with CSRF');
 check(document.querySelector('input[type=password]').value==='','Password cleared');
 check(document.documentElement.scrollWidth<=innerWidth,'No horizontal page overflow');
 modal.dispatchEvent(new Event('hidden.bs.modal'));
 check(document.querySelector('#company-users-list').children.length===0,'Close clears private editor state');
 return {passed:true,width:innerWidth,checks:'loading, empty, API failure, timeout/retry, role visibility, single-card stack, request routing, failed ack/grant and password clearing'};
})()`;
chromiumFixture(fixture,checks,[390,1280],{outputDir:dir}).catch(error=>{console.error(error);process.exitCode=1;});
