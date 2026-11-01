const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const chromiumFixture = require('./chromium_fixture');
if (typeof WebSocket === 'undefined') {
    const child = require('node:child_process').spawnSync(process.execPath,['--experimental-websocket',__filename],{stdio:'inherit'});
    process.exit(child.status ?? 1);
}
const root = path.resolve(__dirname,'..');
const dir = fs.mkdtempSync(path.join(os.tmpdir(),'audit-ui-'));
let html = fs.readFileSync(path.join(root,'pages/company_audit.php'),'utf8').replace(/<\?php[\s\S]*?\?>/g,'');
const style = fs.readFileSync(path.join(root,'assets/tenant-audit.css'),'utf8');
const script = fs.readFileSync(path.join(root,'assets/tenant-audit.js'),'utf8');
const setup = `
window.calls=[];
const log={id:'stock-log',performed_at:'2026-11-01 19:30:00',module:'sales',actor_role:'Kasserer',
actor_name:'Kari Nord',actor_type:'tenant_admin',action:'sales.stock_adjusted',resource_type:'sales_products',
resource_id:'p',resource_name:'Kaffe',description:'Kasserer Kari Nord fylte på Kaffe ×12.',outcome:'success',
before:{stock_quantity:0},after:{stock_quantity:12},links:{},reversal_candidate:true};
window.fetch=async function(url,options={}) {
 const endpoint=String(url), body=options.body ? JSON.parse(options.body) : null;
 calls.push({url:endpoint,body,headers:options.headers});
 if(endpoint.includes('reverse_audit_action.php')){
  return {ok:true,json:async()=>({success:true,result:body.mode==='preview'
    ?{name:'Kaffe',from:12,to:0,revision:'preview-v1',message:'Salg og historikk beholdes.'}
    :{audit_id:'reversed-log'}})};
 }
 if(endpoint.includes('id=')) return {ok:true,json:async()=>({success:true,log,csrf_token:'fixture-csrf'})};
 return {ok:true,json:async()=>({success:true,logs:[log],filters:{},pagination:{page:1,total:1,pages:1,limit:50}})};
};`;
html = html.replace('<head>','<head><style>'+style+'</style><script>'+setup+'</script>');
html = html.replace('<script src="../assets/tenant-audit.js" defer></script>','<script>'+script+'</script>');
const fixture = path.join(dir,'audit.html');
fs.writeFileSync(fixture,html);
const checks = `(async()=>{
 const assert=(ok,label)=>{if(!ok)throw new Error(label);};
 const wait=async(fn)=>{for(let i=0;i<100;i++){if(fn())return;await new Promise(r=>setTimeout(r,20));}throw Error('UI timeout');};
 const button=text=>[...document.querySelectorAll('button')].find(el=>el.textContent===text);
 await wait(()=>button('Vis detaljer'));
 assert(document.body.textContent.includes('Kasserer Kari Nord fylte på Kaffe ×12.'),'List uses shared description');
 button('Vis detaljer').click();
 await wait(()=>button('Forhåndsvis reversering'));
 assert(document.querySelector('#audit-dialog').open,'Details dialog opens');
 assert(calls.filter(c=>c.body?.mode==='apply').length===0,'Opening details never applies reversal');
 button('Forhåndsvis reversering').click();
 await wait(()=>button('Bekreft reversering'));
 assert(document.querySelector('#dialog-content').textContent.includes('beholdning 12 → 0'),'Preview explains exact consequence');
 assert(calls.filter(c=>c.body?.mode==='apply').length===0,'Preview does not mutate stock');
 button('Avbryt').click();
 await wait(()=>button('Forhåndsvis reversering'));
 button('Forhåndsvis reversering').click();
 await wait(()=>button('Bekreft reversering'));
 button('Bekreft reversering').click();
 await wait(()=>button('Vis korrigerende loggpost'));
 const applied=calls.filter(c=>c.body?.mode==='apply');
 assert(applied.length===1 && applied[0].body.revision==='preview-v1','One confirmed request includes preview revision');
 assert(applied[0].headers['X-CSRF-Token']==='fixture-csrf','Reversal sends actual detail CSRF token');
 assert(document.querySelector('#dialog-content').textContent.includes('Opprinnelig historikk er bevart'),'Success explains preserved history');
 return {passed:true,width:innerWidth,checks:9};
})()`;
chromiumFixture(fixture,checks,[390,1280],{outputDir:dir}).catch(error=>{console.error(error);process.exitCode=1;});
