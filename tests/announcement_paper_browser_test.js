const fs = require('node:fs');
const http = require('node:http');
const os = require('node:os');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
const chromiumFixture = require('./chromium_fixture');

const rendered = spawnSync('php', ['-r', '$_SESSION=[];include "modules/announcements_content.php";'], {encoding:'utf8'});
if (rendered.status !== 0) throw new Error(rendered.stderr);
const ranking = fs.readFileSync(path.join(__dirname, '../assets/announcement_ranking.js'), 'utf8');
const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'news-paper-'));
const fixture = rendered.stdout.replace('<script src="/assets/announcement_ranking.js"></script>', `<script>${ranking}</script>`).replace('<script src="/assets/news_video.js"></script>', `<script>${fs.readFileSync(path.join(__dirname, '../assets/news_video.js'),'utf8')}</script>`);
const stubs = `
window.paperRows=[];window.paperSaves=[];window.paperEndpoints=[];window.paperAlerts=[];window.failNext=false;
window.alert=message=>paperAlerts.push(message);
window.fetch=async(url,options)=>{
 if(!options)return {ok:true,json:async()=>({success:true,announcements:paperRows,editor_author:{staff_id:'test-staff',name:'Testredaktør'},company_name:'Testforeningen'})};
 const data=JSON.parse(options.body);paperSaves.push(data);paperEndpoints.push(String(url));
 if(failNext){failNext=false;return {ok:false,json:async()=>({success:false,message:'Test: lagring avvist'})}}
 const id=data.id||'new-story',elements=data.elements.map(el=>{
  if(el.element_type==='image_carousel')return {...el,content:{images:el.content.urls.map(url=>({url,alt:''}))}};
  if(el.element_type==='youtube_video'){const video=NewsVideo.parse(el.content.url);return {...el,content:video.provider==='youtube'?{video_id:video.id,caption:el.content.caption}:{provider:video.provider,url:el.content.url,caption:el.content.caption}}}
  return el;
 });
 const story={...data,id,elements,author_name:'Testredaktør',publish_at:data.scheduled_publish?data.publish_at:'2026-11-09 19:00:00',content_html:data.content,content_text:data.content};
 paperRows=paperRows.filter(row=>row.id!==id).concat(story);
 return {ok:true,json:async()=>({success:true,announcement_id:id,publish_at:story.publish_at,content:data.content,content_html:data.content,content_text:data.content,priorities:{[id]:data.priority}})};
};
`;
const checks = `(async()=>{
 const check=(ok,message)=>{if(!ok)throw new Error(message)};
 const $=id=>document.getElementById(id),paper=$('news-create-preview');
 const tick=()=>new Promise(resolve=>setTimeout(resolve,40));
 const input=(element,value)=>{if(element.isContentEditable)element.textContent=value;else element.value=value;element.dispatchEvent(new Event('input',{bubbles:true}))};
 const add=kind=>{$('paper-add-trigger').click();const pending=paper.querySelector('[data-pending-index]')?.closest('[data-paper-index]');check(!!pending,'New block is inserted into paper before choosing a type');check(!!(pending.compareDocumentPosition($('paper-add-trigger'))&Node.DOCUMENT_POSITION_FOLLOWING),'Pending block appears above the add button');check(pending.querySelectorAll('[data-add-kind]').length===6,'Exactly six element choices');pending.querySelector('[data-add-kind="'+kind+'"]').click()};
 try{
  await tick();$('newAnnouncementButton').click();
  check(!document.querySelector('select#announcement-type')&&$('announcement-type').value==='news','Creation has no type dropdown and defaults to news');
  check(!paper.querySelector('[data-paper-index]'),'New paper starts empty');
  check(!$('announcement-scheduled').checked&&$('announcement-schedule-fields').hidden,'Publishing now hides schedule fields');
  check(paper.textContent.includes('Testredaktør')&&!paper.textContent.includes('Fjordvik'),'Real author and company metadata replaces placeholders: '+paper.textContent);
  add('text');
  let text=paper.querySelector('[data-paper-text]');
  input(text,'En kort tekst.');const shortHeight=text.getBoundingClientRect().height;
  text.innerHTML=Array(30).fill('<p>Dette er en lengre tekstlinje som skal utvide hele artikkelarket naturlig.</p>').join('');text.dispatchEvent(new Event('input',{bubbles:true}));await tick();
  const longHeight=text.getBoundingClientRect().height;
  check(longHeight>shortHeight*10,'Text expands vertically with its content');
  check(paper.querySelector('[data-paper-text]')===text,'Typing does not replace the focused editor node');
  input(text,'Kort igjen.');await tick();
  check(text.getBoundingClientRect().height<longHeight/5,'Text shrinks after deleting content');
  check(getComputedStyle(text).maxHeight==='none'&&getComputedStyle(text).overflowY==='visible','No capped height or internal text scrollbar');
  text.innerHTML='<h3>Viktig</h3><p><strong>Formatert</strong> innhold.</p><ul><li>Et punkt</li></ul><p>Avslutning.</p>';text.dispatchEvent(new Event('input',{bubbles:true}));
  add('image');input(document.querySelector('[data-element-key="url"]'),location.origin+'/image/a.svg');
  await tick();check(!!paper.querySelector('figure img[src$="/image/a.svg"]'),'Image element renders from its URL');
  add('image_carousel');input(document.querySelector('[data-element-key="urls"]'),location.origin+'/image/a.svg\\n'+location.origin+'/image/b.svg');
  check(!!paper.querySelector('[data-carousel-step="1"]'),'Carousel includes navigation');
  paper.querySelector('[data-carousel-step="1"]').click();
  check(!!paper.querySelector('.nc-paper-carousel img[src$="/image/b.svg"]'),'Carousel next changes the active image');
  add('youtube_video');input(document.querySelector('[data-element-key="url"]'),'https://evil.example/watch?v=abcdefghijk');
  check(!paper.querySelector('iframe'),'Untrusted video host does not render a player');
  input(document.querySelector('[data-element-key="url"]'),'https://youtu.be/abcdefghijk');
  check(paper.querySelector('iframe')?.src==='https://www.youtube-nocookie.com/embed/abcdefghijk','Video JSON URL renders a real YouTube player');
  add('list');input(paper.querySelector('[data-paper-list] li'),'Velkommen');
  add('spreadsheet');input(paper.querySelector('[data-paper-head="0"]'),'Dag');input(paper.querySelector('[data-paper-cell="0:0"]'),'Mandag');
  paper.querySelector('[data-paper-col-add]').click();paper.querySelector('[data-paper-row-add]').click();
  check(paper.querySelectorAll('.nc-paper-table tbody tr').length===2,'Spreadsheet supports adding rows');
  check(paper.querySelectorAll('.nc-paper-table th').length===2,'Spreadsheet supports adding columns');
  const last=document.querySelectorAll('[data-sort-index]')[5];last.querySelector('[data-element-move="-1"]').click();
  check(document.querySelectorAll('.news-compose-element-title')[4].textContent.includes('Regneark'),'Arrow reordering updates numbered list');
  const list=$('announcement-elements-list'),rows=()=>list.querySelectorAll('[data-sort-index]');
  const transfer=new DataTransfer();rows()[4].dispatchEvent(new DragEvent('dragstart',{bubbles:true,dataTransfer:transfer}));rows()[3].dispatchEvent(new DragEvent('drop',{bubbles:true,dataTransfer:transfer}));
  check(document.querySelectorAll('.news-compose-element-title')[3].textContent.includes('Regneark'),'Desktop drag reorders elements');
  rows()[3].scrollIntoView({block:'center'});const handle=rows()[3].querySelector('[data-drag-index]');
  handle.dispatchEvent(new PointerEvent('pointerdown',{bubbles:true,pointerType:'touch',pointerId:7}));
  const target=rows()[2].getBoundingClientRect();
  list.dispatchEvent(new PointerEvent('pointerup',{bubbles:true,pointerType:'touch',pointerId:7,clientX:target.left+10,clientY:target.top+target.height/2}));
  check(document.querySelectorAll('.news-compose-element-title')[2].textContent.includes('Regneark'),'Touch handle reorders elements');
  check(paper.querySelector('[data-paper-text] h3')&&paper.querySelector('[data-paper-text] strong'),'Reordering preserves mixed rich text formatting');
  input($('announcement-title'),'En nyhet med flere elementer');
  $('announcement-scheduled').checked=true;$('announcement-scheduled').dispatchEvent(new Event('change',{bubbles:true}));
  input($('announcement-publish-date'),'31.02.39');input($('announcement-publish-time'),'19:00');$('publishButton').click();await tick();
  check(paperSaves.length===0&&paperAlerts.length===1,'Invalid Norwegian calendar date prevents saving');
  input($('announcement-publish-date'),'31.12.39');window.failNext=true;$('publishButton').click();await tick();
  check($('announcement-title').value==='En nyhet med flere elementer','Failed save retains all editor content');
  $('publishButton').click();await tick();
  check(paperSaves[1].publish_at==='2039-12-31 19:00:00'&&paperSaves[1].scheduled_publish===true,'Planned date is serialized in SQL format');
  check(paperSaves[1].elements.length===6,'All six types survive serialization');
  check(paperEndpoints[1]==='/api/create_announcement.php'&&paperSaves[1].is_published===true&&paperSaves[1].announcement_type==='news','Publish calls real creation endpoint with news type and publication flag');
  check(!$('announcement-title').value&&!paper.querySelector('[data-paper-index]')&&!$('news-create-panel').hidden,'Successful creation resets the complete paper and stays in create tab');
  $('news-overview-tab').click();await tick();document.querySelector('[data-action="edit"]').click();await tick();
  check(paper.querySelectorAll('.nc-paper-block').length===6,'Saved elements all reopen');
  check(!!paper.querySelector('figure img')&&!!paper.querySelector('iframe'),'Canonical saved image/video JSON renders after reopening');
  const carouselRow=Array.from(rows()).find(row=>row.textContent.includes('Bildekarusell'));carouselRow.querySelector('[data-element-select]').click();
  check(document.querySelector('[data-element-key="urls"]').value.split('\\n').length===2,'Carousel canonical JSON reopens with two editable URLs');
  check(paper.querySelector('[data-paper-text] strong'),'Rich formatting survives save and reopening');
  check(!$('news-edit-panel').hidden&&$('news-create-tab').getAttribute('aria-selected')==='false','Editing opens under its own subtab');
  check(!document.querySelector('select#announcement-type'),'Editing has no type dropdown either');
  const videoRow=Array.from(rows()).find(row=>row.textContent.includes('Video'));videoRow.querySelector('[data-element-select]').click();
  input(document.querySelector('[data-element-key="url"]'),'https://vimeo.com/123456789/abc123');
  check(paper.querySelector('iframe')?.src==='https://player.vimeo.com/video/123456789?h=abc123','Unlisted Vimeo uses embedded player and preserves hash');
  input(document.querySelector('[data-element-key="url"]'),'https://cdn.example.test/clip.webm?version=2');
  check(paper.querySelector('video')?.getAttribute('src')==='https://cdn.example.test/clip.webm?version=2'&&paper.querySelector('video').controls,'Direct video uses native controls and retains query');
  $('publishButton').click();await tick();
  check(!$('news-edit-panel').hidden&&!$('news-edit-list').hidden&&$('news-edit-workspace').hidden,'Edit save returns to preview cards');
  check(paperSaves.at(-1).id==='new-story'&&paperSaves.at(-1).elements.some(el=>el.content.url?.includes('clip.webm')),'Update saves original article ID and new video URL');
  check(paperEndpoints.at(-1)==='/api/update_announcement.php'&&paperSaves.at(-1).is_published===true,'Edit publishes through the update endpoint');
  check($('news-edit-cards').querySelectorAll('[data-edit-story]').length===1,'Saved article appears as a clickable preview card');
  $('news-create-tab').click();input($('announcement-title'),'Ulagret ny sak');
  $('news-edit-tab').click();$('news-edit-cards').querySelector('[data-edit-story]').click();await tick();
  check(!!paper.querySelector('video'),'Card reopens the saved direct video');
  $('news-create-tab').click();await tick();
  check($('announcement-title').value==='Ulagret ny sak','Switching back restores separate creation draft');
  $('news-edit-tab').click();$('news-edit-cards').querySelector('[data-edit-story]').click();await tick();
  input($('announcement-title'),'Endret tittel');window.confirm=()=>false;$('news-edit-back').click();
  check(!$('news-edit-workspace').hidden&&$('announcement-title').value==='Endret tittel','Declining discard retains changes');
  window.confirm=()=>true;$('news-edit-back').click();
  check(!$('news-edit-list').hidden,'Back returns to card list after confirmed discard');
  input($('news-edit-search'),'finnes ikke');check(!$('news-edit-cards').querySelector('[data-edit-story]'),'Card search has an empty result state');
  input($('news-edit-search'),'');
  check(document.documentElement.scrollWidth<=innerWidth+1,'Paper and inspector do not overflow mobile width');
  window.scrollTo(0,0);
  return {passed:true};
 }catch(error){return {passed:false,error:error.stack||String(error)}}
})()`;
const html = `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1"><style>*{box-sizing:border-box}body{margin:0;font:16px system-ui;background:#f4f7f7}button,input,textarea,select{font:inherit}input,select,textarea{max-width:100%}.form-control,.form-select{display:block;width:100%;padding:.5rem}.row{display:flex;gap:.5rem;flex-wrap:wrap}.col-7{flex:1}.col-5{flex:1}.form-check{margin-top:.75rem}.form-check-input{display:inline;width:auto}.mt-2{margin-top:.5rem}.mt-3{margin-top:.75rem}.mb-3{margin-bottom:.75rem}.d-flex{display:flex}.gap-2{gap:.5rem}.flex-grow-1{flex-grow:1}.btn{padding:.45rem .65rem;cursor:pointer}.text-muted{color:#6b7b80}[hidden]{display:none!important}</style></head><body><script>${stubs}</script>${fixture}</body></html>`;
const server=http.createServer((req,res)=>{
 if(req.url.startsWith('/image/')){res.writeHead(200,{'Content-Type':'image/svg+xml'});res.end('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 400"><rect width="800" height="400" fill="'+(req.url.includes('b.svg')?'#8ca76d':'#427d77')+'"/><circle cx="400" cy="200" r="90" fill="#e6d7ae"/></svg>');return}
 res.writeHead(200,{'Content-Type':'text/html; charset=utf-8'});res.end(html);
});
(async()=>{
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 try{
  await chromiumFixture(`http://127.0.0.1:${server.address().port}/`,checks,[320,390,1280],{outputDir:directory});
  console.log('News paper browser tests passed at 320, 390 and 1280px; screenshots:',directory);
 }finally{server.close()}
})().catch(error=>{console.error(error);process.exitCode=1});
