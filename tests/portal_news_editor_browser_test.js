const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
const chromiumFixture = require('./chromium_fixture');
const rendered = spawnSync('php',['-r','$_SESSION=[];include "modules/announcements_content.php";'],{encoding:'utf8'});
if (rendered.status) throw new Error(rendered.stderr);
const dir = fs.mkdtempSync(path.join(os.tmpdir(),'portal-news-window-'));
const story = {
  id:'real-story',title:'Eksisterende sak',content:'<p>Tekstinnhold</p>',content_html:'<p>Tekstinnhold</p>',
  content_text:'Tekstinnhold',elements:[{element_type:'text',content:{html:'<p>Tekstinnhold</p>'},sort_order:0}],
  image_url:'',is_published:true,is_pinned:false,priority:1,clicks:42,announcement_type:'news',
  publish_at:'2026-11-01 12:00:00',expire_at:null
};
const data={company:{name:'Testbedriften',header_bg_color:'#254f85'},modules:[],products:[],news:[story],errors:[]};
let moduleHtml=rendered.stdout;
for (const file of ['announcement_ranking','news_video']) {
  moduleHtml=moduleHtml.replace(`<script src="/assets/${file}.js"></script>`,`<script>${fs.readFileSync(`assets/${file}.js`,'utf8')}</script>`);
}
fs.writeFileSync(path.join(dir,'fixture.html'),`<!doctype html><meta charset="utf-8">
<style>${fs.readFileSync('assets/portal-website-preview.css','utf8')}</style>
<body class="portal-wpreview"><section id="portal-website-preview" style="--pwp-body:#e4effa;--pwp-header:#254f85;--pwp-text:#fff">
<header class="pwp-site-header"><img id="pwp-logo"><span id="pwp-company-name"></span></header>
<nav class="pwp-site-nav"><button data-pwp-page="home">Hjem</button><button data-pwp-page="services">Tjenester</button></nav>
<div id="pwp-errors"></div><main id="pwp-content"></main><footer><span id="pwp-footer-company"></span><span id="pwp-contact"></span></footer>
<div class="pwp-island"><button data-pwp-mode="read">Se på</button><button data-pwp-mode="edit">Rediger</button><button id="pwp-new">Ny</button></div><div id="pwp-create-menu" hidden></div>
<script id="pwp-data" type="application/json">${JSON.stringify(data)}</script></section>
<div id="pwp-workspace-bar" hidden><button id="pwp-close-workspace">Tilbake</button></div>
<dialog id="pwp-dialog"><div class="pwp-dialog-shell"><header class="pwp-dialog-head"><h2 id="pwp-dialog-title"></h2><button id="pwp-dialog-close">×</button></header><div id="pwp-dialog-body"></div></div></dialog>
<script>
window.savedNews=[];window.alerts=[];window.confirmClose=true;window.confirm=()=>confirmClose;window.alert=m=>alerts.push(m);
window.bootstrap={Tab:{getOrCreateInstance:button=>({show:()=>{}})}};
window.fetch=async(url,options)=>{
 const row=${JSON.stringify(story)};
 if(url.includes('get_portal_website_preview'))return {ok:true,json:async()=>({success:true,...${JSON.stringify(data)}})};
 if(options?.body){
   const saved=JSON.parse(options.body);savedNews.push({url,data:saved});
   return {ok:true,json:async()=>({success:true,content:saved.content,content_html:saved.content,content_text:'Tekstinnhold',priorities:{'real-story':1}})};
 }
 return {ok:true,json:async()=>({success:true,announcements:[row],editor_author:{staff_id:'fixture-author',name:'Redaktør'},company_name:'Testbedriften',company:{}})};
};
</script><div id="portal-legacy"><button data-bs-target="#content-announcements">Nyheter</button><div id="content-announcements">${moduleHtml}</div></div>
<script>${fs.readFileSync('assets/portal-preview-workspace.js','utf8')}</script>
<script>${fs.readFileSync('assets/portal-website-preview.js','utf8')}</script>`);
const checks=`(async()=>{
 const check=(ok,message)=>{if(!ok)throw new Error(message)},tick=()=>new Promise(resolve=>setTimeout(resolve,80));
 await tick();
 const original=document.getElementById('announcementForm');
 document.querySelector('[data-pwp-mode="edit"]').click();
 document.querySelector('[data-news-id="real-story"]').click();await tick();
 const modal=document.getElementById('pwp-dialog');
 check(modal.open,'Real news editor failed to open');
 check(modal.contains(original),'Editor must be the original form, not a copy');
 check(document.getElementById('announcement-id').value==='real-story','Wrong news story loaded');
 const box=modal.getBoundingClientRect();
 check(box.top>=0&&box.bottom<=innerHeight&&box.left>=0&&box.right<=innerWidth,'Dialog is outside viewport: '+JSON.stringify({top:box.top,bottom:box.bottom,left:box.left,right:box.right,width:innerWidth,height:innerHeight}));
 check(modal.scrollWidth<=modal.clientWidth+2,'Modal has horizontal overflow');
 check(getComputedStyle(document.getElementById('portal-legacy')).display==='none','Legacy was exposed');
 const title=document.getElementById('announcement-title');
 title.value='Oppdatert i wpreview';title.dispatchEvent(new Event('input',{bubbles:true}));
 window.confirmClose=false;document.getElementById('pwp-dialog-close').click();
 check(modal.open,'Unsaved edit was closed without confirmation');
 document.getElementById('publishButton').click();await tick();await tick();
 check(savedNews.length===1&&savedNews[0].url==='/api/update_announcement.php','Existing update API not used exactly once');
 check(savedNews[0].data.title==='Oppdatert i wpreview','Edited value not saved');
 check(!modal.open,'Successful news save should close the editor');
 check(document.getElementById('announcementForm')===original,'Original form was destroyed');
 check(alerts.length===0,'Unexpected editor errors: '+alerts.join(';'));
 document.getElementById('pwp-new').click();await tick();
 check(modal.open&&modal.contains(original),'News creation did not reuse modal/editor');
 check(!document.getElementById('announcement-id').value,'New story retained existing story ID');
 check(!document.getElementById('announcement-title').value,'New story retained edited content');
 return {passed:true,width:innerWidth,checks:'real news module, existing save API, unsaved protection, responsive modal and fresh creation'};
})()`;
chromiumFixture(path.join(dir,'fixture.html'),checks,[1280,390]).catch(error=>{console.error(error);process.exit(1)});
