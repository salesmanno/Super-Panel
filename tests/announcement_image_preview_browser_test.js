const assert = require('node:assert/strict');
const fs = require('node:fs');
const http = require('node:http');
const os = require('node:os');
const path = require('node:path');
const {spawnSync} = require('node:child_process');
if (typeof WebSocket === 'undefined' && !process.env.ANNOUNCEMENT_TEST_WEBSOCKET) {
    const result = spawnSync(process.execPath, ['--experimental-websocket', __filename], {
        stdio: 'inherit', env: {...process.env, ANNOUNCEMENT_TEST_WEBSOCKET: '1'},
    });
    process.exit(result.status ?? 1);
}
const chromiumFixture = require('./chromium_fixture');

// Render the production PHP fragment, then isolate only auth/bootstrap and API
// behavior. No app files, data, or uploaded images are changed by this fixture.
const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'announcement-image-preview-'));
const rendered = spawnSync('php', ['-r', '$_SESSION=[]; include "modules/announcements_content.php";'], {
    cwd: path.join(__dirname, '..'), encoding: 'utf8', timeout: 15000,
});
assert.equal(rendered.status, 0, rendered.stderr);
const ranking = fs.readFileSync(path.join(__dirname, '../assets/announcement_ranking.js'), 'utf8');
const uploadCss = fs.readFileSync(path.join(__dirname, '../assets/image-upload.css'), 'utf8');
const uploadJs = fs.readFileSync(path.join(__dirname, '../assets/image-upload.js'), 'utf8');
const rows = [{
    id: 44, title: 'Eksisterende sak', content: '<p>En kort sak.</p>',
    content_html: '<p>En kort sak.</p>', content_text: 'En kort sak.',
    image_url: '__BASE__/portrait.svg', announcement_type: 'news', priority: 1,
    publish_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
    is_published: true, is_pinned: false,
}];
const bootstrap = `
window.testRows=${JSON.stringify(rows)};
window.fetch=async()=>({ok:true,json:async()=>({success:true,announcements:window.testRows})});
class TestModal {
  static instances=new Map();
  constructor(element){this.element=element;TestModal.instances.set(element,this)}
  static getInstance(element){return TestModal.instances.get(element)}
  show(){this.element.classList.add('show');this.element.setAttribute('aria-hidden','false')}
  hide(){
    if(this.element.dispatchEvent(new Event('hide.bs.modal',{cancelable:true}))){
      this.element.classList.remove('show');this.element.setAttribute('aria-hidden','true');
      this.element.dispatchEvent(new Event('hidden.bs.modal'));
    }
  }
}
window.bootstrap={Modal:TestModal};
`;
const checks = `
(async()=>{
  const check=(ok,message)=>{if(!ok)throw new Error(message)};
  const settle=()=>new Promise(resolve=>setTimeout(resolve,100));
  const image=document.getElementById('announcement-image-preview');
  const placeholder=document.getElementById('announcement-image-placeholder');
  const input=document.getElementById('announcement-image-url');
  const modal=document.getElementById('announcementModal');
  const body=modal.querySelector('.modal-body');
  const waitFor=(predicate,message)=>new Promise((resolve,reject)=>{
    const start=Date.now(),timer=setInterval(()=>{
      if(predicate()){clearInterval(timer);resolve()}
      else if(Date.now()-start>4000){clearInterval(timer);reject(new Error(message))}
    },25);
  });
  const openNew=()=>document.getElementById('newAnnouncementButton').click();
  const close=()=>bootstrap.Modal.getInstance(modal).hide();
  const setUrl=value=>{input.value=location.origin+value;input.dispatchEvent(new Event('input',{bubbles:true}))};
  const checkBounded=label=>{
    const editor=document.querySelector('.announcement-image-editor');
    const rect=image.getBoundingClientRect(),box=editor.getBoundingClientRect();
    check(rect.width<=box.width+1 && rect.right<=box.right+1,label+' image stays inside editor width');
    check(rect.height<=231,label+' image remains height bounded');
    check(document.documentElement.scrollWidth<=innerWidth+1,label+' has no horizontal page overflow');
    check(modal.getBoundingClientRect().width<=innerWidth+1,label+' modal fits viewport width');
    check(body.scrollWidth<=body.clientWidth+1,label+' modal body has no horizontal overflow');
  };
  try{
    await settle();
    openNew();
    check(image.hidden && getComputedStyle(image).display==='none','new form hides the unused image preview');
    check(!placeholder.hidden && getComputedStyle(placeholder).display!=='none','new form shows its empty placeholder');
    check(document.querySelector('.shared-image-upload'),'shared upload control attaches after URL input');
    const uploadedPreview=document.querySelector('.shared-image-upload__preview');
    check(getComputedStyle(uploadedPreview).display==='none','shared upload preview starts hidden');

    setUrl('/landscape.svg');
    await waitFor(()=>image.complete&&image.naturalWidth>0,'landscape preview did not load');
    check(image.naturalWidth===12000&&image.naturalHeight===300,'test image has panoramic intrinsic dimensions');
    check(!image.hidden&&getComputedStyle(placeholder).display==='none','loaded URL replaces the placeholder');
    checkBounded('new landscape');

    setUrl('/portrait.svg');
    await waitFor(()=>image.complete&&image.naturalWidth===300,'portrait replacement did not load');
    check(image.naturalHeight===12000,'test portrait has extreme intrinsic height');
    checkBounded('new portrait');

    input.value='';input.dispatchEvent(new Event('input',{bubbles:true}));
    check(image.hidden&&getComputedStyle(image).display==='none','clearing URL hides the preview despite its display rule');
    check(!placeholder.hidden&&getComputedStyle(placeholder).display!=='none','clearing URL restores the placeholder');

    setUrl('/broken.svg');
    await waitFor(()=>image.hidden&&!placeholder.hidden,'broken URL restores the placeholder');
    check(getComputedStyle(image).display==='none'&&getComputedStyle(placeholder).display!=='none','error state respects hidden attributes');
    checkBounded('new error');
    close();

    document.querySelector('[data-action="edit"][data-id="44"]').click();
    await waitFor(()=>image.complete&&image.naturalWidth===300,'editing existing story loads its portrait preview');
    checkBounded('edit portrait');
    setUrl('/landscape.svg');
    await waitFor(()=>image.complete&&image.naturalWidth===12000,'edit URL replacement loads panorama');
    checkBounded('edit landscape');
    input.value='';input.dispatchEvent(new Event('input',{bubbles:true}));
    check(image.hidden&&getComputedStyle(image).display==='none','edit clear hides image');

    body.scrollTop=body.scrollHeight;
    await settle();
    const footer=modal.querySelector('.modal-footer').getBoundingClientRect();
    check(footer.bottom<=innerHeight+1 && footer.top>=0,
      'modal footer remains reachable within viewport scroll ('+JSON.stringify({footer:footer.toJSON(),body:{top:body.getBoundingClientRect().top,height:body.clientHeight,scrollHeight:body.scrollHeight,scrollTop:body.scrollTop},viewport:innerHeight})+')');
    check(body.scrollHeight<=body.clientHeight+1 || body.scrollTop>0,'modal content can scroll independently');
    document.body.dataset.testStatus='passed';
  }catch(error){document.body.dataset.testStatus='failed';document.body.dataset.testError=error.stack||String(error)}
})();
`;
let html = `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1">
<style>${uploadCss}
*{box-sizing:border-box}body{margin:0;font:16px sans-serif}
.modal{display:block;position:fixed;inset:0;overflow-x:hidden;overflow-y:auto;padding:.5rem;background:#0002}
.modal-dialog{width:calc(100% - 1rem);margin:0 auto}
.modal-content{display:flex;flex-direction:column;background:#fff}
.modal-header,.modal-footer{display:flex;align-items:center;padding:1rem;gap:.5rem}
.modal-body{padding:1rem}
.form-control{display:block;width:100%;padding:.5rem}
button{padding:.45rem .7rem}
</style></head><body><script>${bootstrap}</script>${rendered.stdout.replace(
    '<script src="/assets/announcement_ranking.js"></script>', `<script>${ranking}</script>`,
)}<script>${uploadJs}</script><script>${checks}</script></body></html>`;
const server = http.createServer((request, response) => {
    if (request.url === '/landscape.svg' || request.url === '/portrait.svg') {
        const landscape = request.url === '/landscape.svg';
        response.writeHead(200, {'Content-Type': 'image/svg+xml', 'Cache-Control': 'no-store'});
        response.end(landscape
            ? '<svg xmlns="http://www.w3.org/2000/svg" width="12000" height="300"><rect width="12000" height="300" fill="#547"/><circle cx="8000" cy="150" r="100" fill="#e85"/></svg>'
            : '<svg xmlns="http://www.w3.org/2000/svg" width="300" height="12000"><rect width="300" height="12000" fill="#376"/><circle cx="150" cy="8000" r="100" fill="#e85"/></svg>');
        return;
    }
    if (request.url === '/broken.svg') {
        response.writeHead(404, {'Content-Type': 'text/plain'}).end('not an image');
        return;
    }
    response.writeHead(200, {'Content-Type': 'text/html'}).end(html);
});
(async()=>{
    await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
    try{
        const origin=`http://127.0.0.1:${server.address().port}`;
        html=html.replaceAll('__BASE__',origin);
        const url=`${origin}/fixture`;
        const expression=`new Promise(async resolve=>{for(let i=0;i<200;i++){if(document.body.dataset.testStatus){resolve({passed:document.body.dataset.testStatus==='passed',error:document.body.dataset.testError});return}await new Promise(r=>setTimeout(r,25))}resolve({passed:false,error:'browser checks timed out'})})`;
        await chromiumFixture(url,expression,[320,390,1280],{outputDir:directory});
        console.log('Announcement image preview Chromium regression passed at 320, 390 and 1280px');
    }finally{
        server.close();
        await new Promise(resolve=>setTimeout(resolve,500));
        if (process.env.KEEP_UI_FIXTURES !== '1') {
            fs.rmSync(directory,{recursive:true,force:true,maxRetries:8,retryDelay:100});
        }
    }
})().catch(error=>{console.error(error);process.exitCode=1});
