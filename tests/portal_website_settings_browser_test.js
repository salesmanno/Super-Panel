const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
const browser = require('./chromium_fixture');
const rendered = spawnSync('php',['tests/fixtures/portal_website_settings.php'],{encoding:'utf8'});
if (rendered.status) throw new Error(rendered.stderr);
const html = JSON.parse(rendered.stdout);
const dir = fs.mkdtempSync(path.join(os.tmpdir(),'portal-website-settings-'));
const fixture = path.join(dir,'index.html');
const data = {is_supervisor:true,company:{name:'Testbedrift',header_bg_color:'#244d83',header_height:'90px',body_bg_color:'#e0f0fc',footer_mode:'dynamic'},modules:[],news:[],products:[],errors:[]};
fs.writeFileSync(fixture,`<!doctype html><meta name="viewport" content="width=device-width"><meta charset="utf-8">
<style>html,body{margin:0}*{box-sizing:border-box}${fs.readFileSync('assets/portal-website-preview.css','utf8')}${fs.readFileSync('assets/portal-preview-settings.css','utf8')}</style>
<body class="portal-wpreview"><section id="portal-website-preview">${html.control}<header class="pwp-brand"><div class="pwp-brand-identity"><img id="pwp-logo" hidden><strong id="pwp-company-name"></strong></div>${html.account}</header>
<nav class="pwp-site-nav"><button data-pwp-page="about">Om oss</button><button data-pwp-page="home">Hjem</button><button data-pwp-page="services">Tjenester</button></nav>
<div id="pwp-errors"></div><div id="pwp-content" class="pwp-content" style="min-height:1400px"></div><footer class="pwp-footer"><strong id="pwp-footer-company"></strong><span id="pwp-contact"></span></footer>
<div class="pwp-island"><button data-pwp-mode="read">Se på</button><button data-pwp-mode="edit">Rediger</button><button id="pwp-new">Ny</button></div><div id="pwp-create-menu" hidden></div><script id="pwp-data" type="application/json">${JSON.stringify(data)}</script></section>
<div id="portal-legacy"><div id="firmaModalTabContent"></div></div><dialog id="pwp-dialog"><div id="pwp-dialog-body"></div><button id="pwp-dialog-close">Lukk</button></dialog>
<script>window.settingsHtml=${JSON.stringify(html.module)};window.writes=[];window.failWrite=false;window.legacySubmits=0;window.allowDiscard=false;window.confirm=()=>allowDiscard;
window.handleSaveWebsideData=event=>{event.preventDefault();legacySubmits++;return false;};
window.bootstrap={Tab:{getOrCreateInstance(){return{show(){}}}}};
window.fetch=async(url,options={})=>{
 if(url.includes('load_ditt_nettsted_content'))return{ok:true,status:200,text:async()=>settingsHtml};
 if(url.includes('update_webside_data')){writes.push(JSON.parse(options.body));await new Promise(r=>setTimeout(r,25));return{ok:!failWrite,json:async()=>({success:!failWrite,error:'Testet lagringsfeil'})};}
 return{ok:true,json:async()=>({success:true,...JSON.parse(document.getElementById('pwp-data').textContent)})};
};</script>
${['portal-preview-website','portal-preview-control-room','portal-preview-workspace','portal-website-preview'].map(file=>`<script>${fs.readFileSync('assets/'+file+'.js','utf8')}</script>`).join('')}`);
const checks=`(async()=>{
 const assert=(value,message)=>{if(!value)throw Error(message)};
 const click=selector=>document.querySelector(selector).click();
 const tick=(ms=65)=>new Promise(resolve=>setTimeout(resolve,ms));
 const panel=document.getElementById('pwp-control-room');
 const wheel=deltaY=>window.dispatchEvent(new WheelEvent('wheel',{deltaY}));
 const touch=(type,y,target=window)=>{const event=new Event(type,{bubbles:true});Object.defineProperty(event,'touches',{value:[{clientY:y,clientX:50}]});target.dispatchEvent(event)};
 scrollTo(0,0);await tick();
 const initialY=document.querySelector('.pwp-brand').getBoundingClientRect().top;
 wheel(120);assert(!panel.classList.contains('is-open'),'Scrolling down opened control room');
 scrollTo(0,300);await tick();wheel(-120);
 assert(!panel.classList.contains('is-open'),'Scrolling up away from top opened control room');
 touch('touchstart',50);scrollTo(0,0);await tick();touch('touchmove',160);
 assert(!panel.classList.contains('is-open'),'Touch beginning mid-page opened control room');
 wheel(-20);assert(!panel.classList.contains('is-open'),'Tiny accidental top-wheel opened control room');
 wheel(-50);await tick(350);
 assert(panel.classList.contains('is-open'),'Intentional upward pull at top did not open control room');
 assert(document.querySelector('.pwp-brand').getBoundingClientRect().top===initialY,'Admin layer shifted customer header');
 assert(panel.getBoundingClientRect().top===0,'Admin layer must overlay the top');
 assert(document.body.style.overflow!=='hidden','Control room locked the customer body');
 click('[data-pwp-admin-close]');touch('touchstart',80);touch('touchmove',170);await tick();
 assert(panel.classList.contains('is-open'),'Mobile pull-down at top did not open admin header');
 document.dispatchEvent(new KeyboardEvent('keydown',{key:'Escape',bubbles:true}));
 assert(!panel.classList.contains('is-open'),'Escape did not close control room');
 click('[data-pwp-page="about"]');await tick();click('[data-pwp-setting="website"]');await tick();
 const form=document.getElementById('websideForm');
 assert(form.closest('#pwp-settings-main'),'Website did not open on About for supervisor without module');
 assert(form.querySelector('.pwp-website-grid'),'Website still uses the old form layout');
 assert(document.querySelector('#firma-webside > .card > .card-body > .card-title').hidden,'Legacy website heading was duplicated');
 const removed=['is_active','maintenance_mode','logo_url','favicon_url','homepage_module','navigation_style','header_layout','footer_layout','hero_title','hero_image_url','hero_description','hero_button_text','hero_button_url','contact_email','contact_phone','contact_address','footer_text','footer_links','custom_css','custom_js','spacing_unit'];
 for(const key of removed)assert(!form.elements['general_'+key],'Removed setting still rendered: '+key);
 assert(!form.querySelector('input[type="url"],input[type="file"]'),'Removed image upload controls still present');
 assert(!form.textContent.includes('Generelle Innstillinger'),'Redundant general settings heading remains');
 const footerMode=form.elements.general_footer_mode;
 assert(footerMode&&form.elements.general_header_mode,'Header/footer position missing');
 footerMode.value='static';footerMode.dispatchEvent(new Event('change',{bubbles:true}));
 assert(getComputedStyle(document.querySelector('.pwp-footer')).position==='fixed','Static footer did not apply live');
 footerMode.value='dynamic';footerMode.dispatchEvent(new Event('change',{bubbles:true}));
 assert(getComputedStyle(document.querySelector('.pwp-footer')).position==='static','Dynamic footer did not apply live');
 const original=Object.fromEntries(new FormData(form));
 footerMode.value='static';footerMode.dispatchEvent(new Event('change',{bubbles:true}));
 const color=form.elements.general_header_bg_color;
 color.dispatchEvent(new WheelEvent('wheel',{deltaY:-120,bubbles:true}));
 assert(!panel.classList.contains('is-open'),'Wheel inside input opened control room');
 color.value='#b04455';color.dispatchEvent(new Event('input',{bubbles:true}));
 assert(getComputedStyle(document.querySelector('.pwp-brand')).backgroundColor==='rgb(176, 68, 85)','Header color did not update live');
 form.elements.general_body_bg_color.value='#ddeedd';form.elements.general_body_bg_color.dispatchEvent(new Event('input',{bubbles:true}));
 assert(getComputedStyle(document.getElementById('portal-website-preview')).backgroundColor==='rgb(221, 238, 221)','Body color did not update live');
 click('[data-pwp-page="home"]');
 assert(document.getElementById('websideForm')===form&&form.closest('#pwp-settings-main'),'Unsaved website changes were discarded without approval');
 form.elements.company_name.value='Oppdatert bedrift';
 form.dispatchEvent(new Event('input',{bubbles:true}));
 const submit=()=>form.querySelector('button[type="submit"]').click();
 window.failWrite=true;submit();await tick();
 assert(form.querySelector('[role="status"]').textContent.includes('Testet lagringsfeil'),'Save error not shown');
 assert(!form.querySelector('button[type="submit"]').disabled,'Failed save prevented retry');
 window.failWrite=false;submit();assert(form.elements.company_name.disabled,'Fields must be protected while saving');await tick();
 assert(writes.length===2&&legacySubmits===0,'Website save was duplicated through legacy handler');
 const payload=writes[1];
 for(const [name,value] of Object.entries(original)){
   if(name.startsWith('general_')&&!['general_header_bg_color','general_body_bg_color','general_footer_mode'].includes(name))assert(payload.general[name.slice(8)]===value,'Untouched general setting lost: '+name);
   if(name.startsWith('config_'))assert(payload.config[name.slice(7)]===value,'Untouched config setting lost: '+name);
 }
 assert(payload.company_name==='Oppdatert bedrift'&&payload.general.header_bg_color==='#b04455','Wrong settings save payload');
 assert(payload.general.footer_mode==='static','Footer mode not included in save');
 assert(payload.general.seo_title==='Eksisterende søketittel' && !('seo_title' in payload.config),'SEO must be saved to general rather than module config');
 for(const key of removed)assert(!(key in payload.general),'Removed field submitted: '+key);
 assert(form.querySelector('[role="status"]').textContent.includes('er lagret'),'Save success missing');
 click('[data-pwp-page="home"]');await tick();
 assert(document.getElementById('pwp-company-name').textContent==='Oppdatert bedrift','Saved company name not retained');
 assert(document.getElementById('pwp-control-company').textContent==='Oppdatert bedrift','Admin header retained stale company name');
 assert(document.getElementById('portal-website-preview').dataset.footerMode==='static','Saved footer mode lost after navigation');
 click('[data-pwp-page="about"]');await tick();click('[data-pwp-setting="website"]');await tick();
 const next=document.getElementById('websideForm');next.elements.general_header_bg_color.value='#112233';next.dispatchEvent(new Event('input',{bubbles:true}));
 window.allowDiscard=true;click('[data-pwp-page="home"]');await tick();
 assert(!document.querySelector('#pwp-settings-main'),'Approved discard did not leave settings');
 assert(document.documentElement.scrollWidth<=document.documentElement.clientWidth,'Settings introduced horizontal page overflow');
 click('[data-pwp-page="about"]');await tick();click('[data-pwp-setting="website"]');await tick();
 return{passed:true,width:innerWidth,checks:'real settings form, live colors, full save payload, retries, dirty protection, top-only wheel and touch admin header'};
})()`;
browser(fixture,checks,[1280,390,320]).catch(error=>{console.error(error);process.exitCode=1});
