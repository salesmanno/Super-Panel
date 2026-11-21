const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const browser = require('./chromium_fixture');
const dir = fs.mkdtempSync(path.join(os.tmpdir(),'portal-service-payments-'));
const fixture = path.join(dir,'index.html');
const php = fs.readFileSync('includes/portal_website_preview.php','utf8');
const island = php.match(/<div class="pwp-island"[\s\S]*?<\/div>/)[0].replace(/<\?php[\s\S]*?\?>/g,'');
const data = {payment_csrf:'csrf-token',company:{name:'Testfirma',footer_mode:'dynamic'},modules:['membership','events','rental'].map(module_key=>({module_key})),news:[],errors:[],
 products:['events','membership','rental'].map((module,index)=>({id:'product-'+index,source_id:'source-'+index,module,name:['Arrangement','Medlemskap','Utleie'][index],description:'Test',price:500,is_visible:true,is_public:true}))};
fs.writeFileSync(fixture,`<!doctype html><html><meta charset="utf-8"><meta name="viewport" content="width=device-width">
<style>html,body{margin:0;font-family:Arial}*{box-sizing:border-box}${fs.readFileSync('assets/portal-website-preview.css','utf8')}${fs.readFileSync('assets/portal-preview-settings.css','utf8')}</style>
<body class="portal-wpreview"><section id="portal-website-preview"><header class="pwp-brand"><div class="pwp-brand-identity"><img id="pwp-logo" hidden><strong id="pwp-company-name"></strong></div></header>
<nav class="pwp-site-nav"><button data-pwp-page="about">Om oss</button><button data-pwp-page="home">Hjem</button><button data-pwp-page="services">Tjenester</button></nav>
<div id="pwp-errors"></div><div id="pwp-content" class="pwp-content"></div><footer class="pwp-footer"><strong id="pwp-footer-company"></strong><span id="pwp-contact"></span></footer>
${island}<div id="pwp-create-menu" hidden></div><script type="application/json" id="pwp-data">${JSON.stringify(data)}</script></section>
<dialog id="pwp-dialog"><h2 id="pwp-dialog-title"></h2><div id="pwp-dialog-body"></div><button id="close">Lukk</button></dialog>
<script>
window.writes=[];window.failWrite=false;window.failLoad=false;window.approveDiscard=false;
window.confirm=()=>approveDiscard;
let guard=()=>true;
window.PortalWorkspace={clearAbout(){},canLeaveSettings(){return true},close(){if(!guard())return false;document.getElementById('pwp-dialog').close();document.getElementById('pwp-dialog-body').replaceChildren();guard=()=>true;return true},
showTemporary(host,title,options={}){guard=options.beforeClose||(()=>true);document.getElementById('pwp-dialog-body').replaceChildren(host);document.getElementById('pwp-dialog-title').textContent=title;document.getElementById('pwp-dialog').show();return true}};
document.getElementById('close').onclick=()=>PortalWorkspace.close();
window.fetch=async(url,options={})=>{
if(options.method==='POST'){
 const body=JSON.parse(options.body);writes.push(body);await new Promise(r=>setTimeout(r,40));
 return{ok:!failWrite,json:async()=>failWrite?{success:false,message:'Testet lagringsfeil'}:{success:true,selected:body.methods,version:'saved',changed:true}};
}
if(failLoad)return{ok:false,json:async()=>({success:false,message:'Testet lastefeil'})};
const module=new URL(url,location.href).searchParams.get('module');
return{ok:true,json:async()=>({success:true,name:module,selected:['vipps'],methods:[{value:'vipps',label:'Vipps'},{value:'stripe',label:'Kort'}],version:'current',registration_type:module==='events'?'binding':''})};
};
</script><script>${fs.readFileSync('assets/portal-website-preview.js','utf8')}</script></body></html>`);
const checks = `(async()=>{
const assert=(v,m)=>{if(!v)throw Error(m)};
const click=s=>{const el=document.querySelector(s);assert(el,'Missing '+s);el.click()};
const tick=()=>new Promise(r=>setTimeout(r,90));
const visible=()=>[...document.querySelectorAll('.pwp-island [data-pwp-mode],#pwp-new')].filter(b=>getComputedStyle(b).display!=='none');
assert(visible().length===3,'Home should show three actions');
assert(document.querySelector('[data-pwp-island-step="1"]').disabled,'Home next arrow should be disabled');
click('[data-pwp-page="services"]');await tick();
assert(visible().length===3,'Service first page not capped at three');
const before=document.querySelector('[data-service-id]').getBoundingClientRect().top;
click('[data-pwp-island-step="1"]');
assert(visible().length===3&&visible().some(b=>b.dataset.pwpMode==='payments'),'Next three actions missing');
assert(document.querySelector('[data-service-id]').getBoundingClientRect().top===before,'Paging shifted preview');
assert(!document.querySelector('[data-pwp-island-step="1"]').disabled,'Carousel next arrow must stay enabled');
click('[data-pwp-island-step="1"]');
assert(visible().some(b=>b.dataset.pwpMode==='read'),'Next must wrap to first page');
click('[data-pwp-island-step="-1"]');
assert(visible().some(b=>b.dataset.pwpMode==='payments'),'Previous must wrap to last page');
click('[data-pwp-mode="payments"]');
for(let i=0;i<3;i++){
 click('[data-service-id="product-'+i+'"]');await tick();
 assert(document.querySelector('#pwp-dialog').open,'Modal not opened');
 assert(document.querySelector('input[value="vipps"]').checked,'Existing selection not loaded');
 assert(!document.querySelector('input[value="stripe"]').checked,'Unselected method checked');
 click('input[value="stripe"]');
 window.failWrite=true;
 document.querySelector('.pwp-payment-settings').requestSubmit();await tick();
 assert(document.querySelector('.pwp-payment-settings [role="status"]').textContent.includes('Testet lagringsfeil'),'Save error missing');
 assert(document.querySelector('input[value="stripe"]').checked,'Error lost choices');
 window.failWrite=false;
 document.querySelector('.pwp-payment-settings').requestSubmit();
 document.querySelector('.pwp-payment-settings').requestSubmit();
 await tick();
 assert(writes.length===(i+1)*2,'Duplicate submit was not blocked');
 const saved=writes[writes.length-1];
 assert(saved.id==='source-'+i&&saved.csrf_token==='csrf-token','Wrong source/CSRF');
 assert(saved.module===['events','membership','rental'][i],'Wrong module');
 assert(saved.methods.join(',')==='vipps,stripe','Multi-select payload incorrect');
 assert(document.querySelector('.pwp-payment-settings [role="status"]').textContent.includes('er lagret'),'Save success missing');
 click('#close');
 assert(!document.querySelector('#pwp-dialog').open,'Saved modal could not close');
}
window.failLoad=true;click('[data-service-id="product-0"]');await tick();
assert(document.querySelector('[data-payment-retry]'),'Load error has no retry');
window.failLoad=false;click('[data-payment-retry]');await tick();
click('input[value="stripe"]');click('#close');
assert(document.querySelector('#pwp-dialog').open,'Unsaved choices lost without confirmation');
window.approveDiscard=true;click('#close');
click('[data-pwp-island-step="-1"]');
assert(visible().every(b=>['read','edit','benefits'].includes(b.dataset.pwpMode)),'Previous arrow broken');
click('[data-pwp-page="home"]');
assert(visible().length===3,'Home failed to reset pagination');
assert(document.documentElement.scrollWidth<=innerWidth,'Horizontal overflow');
const root=document.querySelector('#portal-website-preview');
const footer=document.querySelector('.pwp-footer');
const islandEl=document.querySelector('.pwp-island');
assert(!footer.contains(islandEl),'Island must not be inside footer');
const box=()=>({top:islandEl.getBoundingClientRect().top,left:islandEl.getBoundingClientRect().left});
// A tall page exposes scroll behavior without changing any real customer data.
document.querySelector('#pwp-content').style.minHeight='2200px';
await tick();
const origin=box();
for(const footer_mode of ['static','dynamic']){
 document.dispatchEvent(new CustomEvent('portal:website-preview',{detail:{footer_mode}}));await tick();
 assert(getComputedStyle(footer).position===(footer_mode==='static'?'fixed':'static'),'Wrong footer position '+footer_mode);
 assert(footer.getBoundingClientRect().height<130,'Footer inflated by island space');
 const footerBefore=footer.getBoundingClientRect().top;
 window.scrollTo(0,400);await tick();
 assert(Math.abs(box().top-origin.top)<1&&Math.abs(box().left-origin.left)<1,'Island moved with footer or scroll '+JSON.stringify({origin,current:box(),footer_mode}));
 const footerAfter=footer.getBoundingClientRect().top;
 assert(footer_mode==='static'?Math.abs(footerAfter-footerBefore)<1:footerAfter<footerBefore-300,'Footer did not follow mode');
 if(footer_mode==='static'){
  window.scrollTo(0,document.documentElement.scrollHeight);await tick();
  assert(document.querySelector('#pwp-content').getBoundingClientRect().bottom<=footer.getBoundingClientRect().top+1,'Fixed footer hides end of content');
 }
 window.scrollTo(0,0);await tick();
}
click('[data-pwp-page="services"]');click('[data-pwp-island-step="1"]');click('[data-pwp-mode="payments"]');click('[data-service-id="product-0"]');await tick();
return{passed:true,width:innerWidth,checks:'three actions, arrows, no preview shift, per-object multi-select, CSRF, errors/retry, duplicate-submit protection, dirty guard'};
})()`;
browser(fixture,checks,[1280,390,320]).catch(error=>{console.error(error);process.exitCode=1});
