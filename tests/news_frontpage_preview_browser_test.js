const fs = require('node:fs');
const http = require('node:http');
const os = require('node:os');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
const chromiumFixture = require('./chromium_fixture');

const rendered = spawnSync('php', ['-r', '$_SESSION=[];include "modules/announcements_content.php";'], {encoding:'utf8'});
if (rendered.status !== 0) throw new Error(rendered.stderr);
let fixture = rendered.stdout;
for (const asset of ['announcement_ranking', 'news_video']) {
  fixture = fixture.replace(`<script src="/assets/${asset}.js"></script>`,
    `<script>${fs.readFileSync(path.join(__dirname, `../assets/${asset}.js`), 'utf8')}</script>`);
}
const stubs = `
window.confirm=()=>true;window.newsAlerts=[];window.alert=m=>newsAlerts.push(m);
window.newsSaves=[];window.newsMoves=[];window.failMove=false;
window.newsLoadFailure=false;window.newsTheme={logo_position:'right',nav_position:'left',header_layout:'standard',header_height:'120px',logo_height:'44px',header_text_color:'#ffffcc',font_family:'Georgia, serif',border_radius:'10px',company_header_font_size:'22px'};
const row=(id,priority,extra={})=>({id,priority,title:'Nyhet '+id,content:'Innhold '+id,
 content_html:'<p>Innhold '+id+'</p>',content_text:'Innhold '+id,
 is_published:true,is_pinned:false,publish_at:'2025-01-01 10:00:00',expire_at:null,
 clicks:7,author_name:'Testredaktør',image_url:location.origin+'/image.svg',
 elements:[{element_type:'text',content:{html:'<p>Innhold '+id+'</p>'}}],...extra});
window.newsRows=[row('A',1,{expire_at:'2039-12-31 17:45:00'}),row('B',2),
 row('hidden',3,{is_published:false}),row('expired',4,{expire_at:'2025-01-02 08:00:00'}),
 row('future',5,{publish_at:'2039-01-01 10:00:00'})];
window.fetch=async(url,options)=>{
 if(!options||!options.body){
 if(newsLoadFailure)return {ok:false,status:500,json:async()=>({success:false,message:'Kunne ikke hente nyheter (nyhetselementer). Feilreferanse: testref'})};
 return {ok:true,json:async()=>({success:true,announcements:newsRows,
  editor_author:{staff_id:'staff-test',name:'Testredaktør'},company_name:'Testforeningen',
  company:{name:'Testforeningen',img_url:location.origin+'/image.svg',header_bg_color:'#2b5097',
   contact_phone:'12345678',address_street:'Testgata 1',address_zip:'6000',address_city:'Testbyen',...newsTheme}})};
 }
 const data=JSON.parse(options.body);
 if(String(url).includes('update_announcement_priority')){
  newsMoves.push(data);
  if(failMove){failMove=false;return {ok:false,json:async()=>({success:false,message:'Testfeil'})}}
  const plan=AnnouncementRanking.planPriorityAssignment(newsRows,data.id,data.priority);
  newsRows=plan.items;
  return {ok:true,json:async()=>({success:true,priorities:plan.updates})};
 }
 if(String(url).includes('toggle_announcement_publish')){
  newsRows=newsRows.map(r=>r.id===data.id?{...r,is_published:!r.is_published}:r);
  return {ok:true,json:async()=>({success:true})};
 }
 newsSaves.push(data);
 const id=data.id||'created';
 const existing=newsRows.find(r=>r.id===id);
 const saved={...existing,...data,id,publish_at:existing?.publish_at||'2026-01-01 10:00:00',
  content_html:data.content,content_text:'Lagret tekst',clicks:existing?.clicks||0};
 newsRows=newsRows.filter(r=>r.id!==id).concat(saved);
 return {ok:true,json:async()=>({success:true,announcement_id:id,publish_at:saved.publish_at,
  content:saved.content,content_html:saved.content_html,content_text:saved.content_text,
  priorities:Object.fromEntries(newsRows.map(r=>[r.id,r.priority]))})};
};
`;
const checks = `(async()=>{
 const check=(ok,m)=>{if(!ok)throw new Error(m)};
 const $=id=>document.getElementById(id),tick=()=>new Promise(r=>setTimeout(r,70));
 const input=(el,v)=>{el.value=v;el.dispatchEvent(new Event('input',{bubbles:true}))};
 const hero=()=>document.querySelector('.news-home-hero')?.dataset.editStory;
 const move=(id,dir)=>document.querySelector('#news-edit-cards [data-id="'+id+'"][data-priority-move="'+dir+'"]');
 await tick();
 check(!$('news-edit-search'),'Search field is removed');
 check(document.querySelectorAll('#news-home-preview [data-edit-story]').length===2,'Default simulation excludes hidden, scheduled and expired stories');
 check(document.querySelectorAll('#news-edit-cards [data-edit-story]').length===5,'All five stories are available in the sidebar');
 check($('news-edit-cards').closest('aside'),'Story list is a sidebar, not a second list below the simulation');
 if(innerWidth<=700){
  check(!$('news-sidebar-disclosure').open,'Mobile administration menu starts collapsed');
  $('news-sidebar-disclosure').querySelector('summary').click();
  check($('news-sidebar-disclosure').open,'Mobile administration menu opens on tap');
 }
 const selectFilter=v=>{$('news-admin-filter').value=v;$('news-admin-filter').dispatchEvent(new Event('change',{bubbles:true}))};
 selectFilter('visible');await tick();
 check(hero()==='A','Highest-priority public story is the hero');
 check(document.querySelectorAll('#news-home-preview [data-edit-story]').length===2,'Only currently public stories occupy homepage positions');
 check($('news-home-preview').textContent.includes('Testforeningen')&&$('news-home-preview').textContent.includes('Testgata 1'),'Real company name and address are shown');
 check(getComputedStyle($('news-home-preview')).getPropertyValue('--site-head').trim()==='#2b5097','Header uses company theme');
 check(document.querySelector('.news-home-logo')?.getAttribute('src').endsWith('/image.svg'),'Real company image is used');
 check(getComputedStyle(document.querySelector('.news-home-top')).justifyContent==='flex-end','Header respects saved right positioning');
 check(getComputedStyle(document.querySelector('.news-home-nav')).justifyContent==='flex-start','Navigation respects saved left positioning');
 check(Math.abs(document.querySelector('.news-home-top').getBoundingClientRect().height-120)<1,'Header uses stored 120px height at every viewport');
 check(Math.abs(document.querySelector('.news-home-logo').getBoundingClientRect().height-44)<1,'Logo uses stored 44px height');
 check(getComputedStyle(document.querySelector('.news-home-top')).color==='rgb(255, 255, 204)','Header uses explicit text color');
 check(getComputedStyle($('news-home-preview')).fontFamily.includes('Georgia'),'Simulation uses saved font family');
 check(getComputedStyle($('news-home-preview')).borderTopLeftRadius==='10px','Simulation uses saved border radius');
 check(getComputedStyle(document.querySelector('.news-home-name')).fontSize==='22px','Company name uses saved font size');
 newsTheme={...newsTheme,header_height:'240px',logo_height:'3rem',company_header_font_size:'1.5rem',header_text_color:'#fed'};$('news-edit-refresh').click();await tick();
 check(Math.abs(document.querySelector('.news-home-top').getBoundingClientRect().height-240)<1,'Stored header height is not arbitrarily capped');
 check(getComputedStyle(document.querySelector('.news-home-name')).fontSize==='24px'&&getComputedStyle(document.querySelector('.news-home-top')).color==='rgb(255, 238, 221)','Relative font sizes and short hex colors are supported');
 newsTheme={...newsTheme,header_height:'120px',logo_height:'44px',company_header_font_size:'22px',header_text_color:'#ffffcc'};
 newsTheme={...newsTheme,logo_position:'center',nav_position:'right',header_layout:'centered'};$('news-edit-refresh').click();await tick();
 check(getComputedStyle(document.querySelector('.news-home-top')).flexDirection==='column'&&getComputedStyle(document.querySelector('.news-home-top')).alignItems==='center','Centered header uses stacked centered branding');
 newsLoadFailure=true;$('news-edit-refresh').click();await tick();
 check($('news-home-preview').textContent.includes('testref')&&!$('news-home-preview').textContent.includes('Ingen nyhetssaker'),'Actual server failure is shown instead of empty state');
 newsLoadFailure=false;$('news-edit-refresh').click();await tick();
 selectFilter('hidden');await tick();
 check(document.querySelectorAll('#news-home-preview [data-edit-story]').length===2&&hero()==='A','Sidebar filter never inserts hidden stories into simulation');
 check(document.querySelectorAll('#news-edit-cards [data-edit-story]').length===3,'Hidden sidebar includes draft, expired and scheduled stories');
 selectFilter('visible');await tick();
 move('A','1').click();await tick();
 check(hero()==='B','Moving first story down really promotes the second story');
 check(newsMoves.length===1,'One existing priority endpoint call per reorder');
 move('A','-1').click();await tick();
 check(hero()==='A','Moving story up restores first position');
 window.failMove=true;move('A','1').click();await tick();
 check(hero()==='A'&&newsAlerts.length===1,'Rejected priority is refetched and not left in optimistic order');
 document.querySelector('#news-edit-cards [data-id="A"][data-card-action="publish"]').click();await tick();
 check(hero()==='B','Hiding hero promotes the next currently visible story');
 selectFilter('hidden');await tick();
 check(!!document.querySelector('#news-edit-cards [data-edit-story="A"]'),'Hidden story remains editable');
 document.querySelector('#news-edit-cards [data-id="A"][data-card-action="publish"]').click();await tick();
 selectFilter('visible');await tick();
 check(hero()==='A','Republishing restores ranked placement');
 const savedRows=JSON.parse(JSON.stringify(newsRows));
 window.newsRows=[row('tieA',9),row('tieB',9)];$('news-edit-refresh').click();await tick();
 check(!move('tieB','-1').disabled,'A shared lowest-priority story can be promoted');
 move('tieB','-1').click();await tick();
 check(hero()==='tieB'&&newsRows.find(r=>r.id==='tieB').priority===8,'Priority 9 promotion persists through existing assignment contract');
 window.newsRows=[row('normal',1),row('pinned',8,{is_pinned:true})];$('news-edit-refresh').click();await tick();
 check(hero()==='pinned','Pin status remains authoritative over numeric priority');
 check(move('pinned','1').disabled,'Priority controls do not cross pin groups');
 window.newsRows=savedRows;$('news-edit-refresh').click();await tick();
 document.querySelector('.news-home-hero').click();await tick();
 check(!$('news-edit-workspace').hidden&&$('announcement-id').value==='A','Homepage story opens existing editor');
 check($('announcement-expire-at').type==='date'&&$('announcement-expire-at').value==='2039-12-31','Editor shows date-only expiry');
 $('publishButton').click();await tick();
 check(newsSaves.at(-1).expire_at==='2039-12-31 17:45:00','Existing expiry time survives editing');
 $('news-create-tab').click();await tick();
 check($('announcement-schedule-toggle-wrap').hidden&&$('announcement-schedule-fields').hidden,'Creation exposes no publication date/time');
 input($('announcement-title'),'Ny sak');input($('announcement-expire-at'),'2039-12-30');
 $('paper-add-trigger').click();document.querySelector('[data-add-kind="text"]').click();
 const text=document.querySelector('[data-paper-text]');text.textContent='En ny publisert sak.';text.dispatchEvent(new Event('input',{bubbles:true}));
 $('publishButton').click();await tick();
 check(newsSaves.at(-1).expire_at==='2039-12-30 08:00:00'&&newsSaves.at(-1).scheduled_publish===false,'New story expires at 08:00 and publishes now');
 check($('announcement-title').value===''&&$('announcement-expire-at').value==='','Successful new story resets form');
 $('news-edit-tab').click();await tick();
 if(innerWidth<=700)$('news-sidebar-disclosure').open=false;
 check(document.documentElement.scrollWidth<=innerWidth+1,'Homepage and management fit viewport '+innerWidth);
 return {passed:true,checks:'header settings, sidebar isolation, public-only simulation, priority swaps and rollback, edit, expiry 08:00, mobile'};
})()`;
const html = `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>
*{box-sizing:border-box}body{margin:0;padding:12px;font:16px system-ui;background:#f4f7f7}button,input,select,textarea{font:inherit;max-width:100%}
.form-control,.form-select{display:block;width:100%;padding:.5rem}.btn{padding:.45rem .65rem;cursor:pointer}.text-muted{color:#6b7b80}
.form-check-input{width:auto}.row{display:flex;gap:.5rem;flex-wrap:wrap}.col-7,.col-5{flex:1}[hidden]{display:none!important}</style></head><body><script>${stubs}</script>${fixture}</body></html>`;
const server=http.createServer((req,res)=>{
 if(req.url==='/image.svg'){res.writeHead(200,{'Content-Type':'image/svg+xml'});res.end('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 400"><rect width="800" height="400" fill="#2b5097"/><circle cx="400" cy="200" r="120" fill="#b3caed"/></svg>');return}
 res.writeHead(200,{'Content-Type':'text/html; charset=utf-8'});res.end(html);
});
(async()=>{
 await new Promise(r=>server.listen(0,'127.0.0.1',r));
 try{await chromiumFixture(`http://127.0.0.1:${server.address().port}/`,checks,[320,390,1280],{outputDir:fs.mkdtempSync(path.join(os.tmpdir(),'news-frontpage-'))})}
 finally{server.close()}
})().catch(error=>{console.error(error);process.exitCode=1});
