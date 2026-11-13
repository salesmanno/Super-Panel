// Disposable real-UI fixture; all API traffic is stubbed, no authenticated routes or real payments.
const fs = require('node:fs');
const http = require('node:http');
const chromiumFixture = require('./chromium_fixture');
const source = fs.readFileSync('modules/economy_content.php','utf8');
const start = source.indexOf('const gatewayDescriptions =');
const end = source.indexOf('function gwFeePreview(',start);
if (start < 0 || end < 0) throw new Error('Economy gateway functions not found');
const script = source.slice(start,end);
const styleStart = source.indexOf('<style>') + '<style>'.length;
const styleEnd = source.indexOf('</style>',styleStart);
if (styleStart < '<style>'.length || styleEnd < 0) throw new Error('Economy styles not found');
const appStyles = source.slice(styleStart,styleEnd);
const stubs = `
window.fixtureActive=false;window.fixtureReady=false;window.fixtureDelivery=false;window.fixtureCalls=[];window.fixtureAlerts=[];window.fixtureDelay=false;
window.alert=message=>fixtureAlerts.push(message);
window.ecPopulateBankAccounts=async()=>{};
window.economyEsc=value=>String(value).replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
window.fetch=async(url,options={})=>{
  fixtureCalls.push({url:String(url),data:options.body?JSON.parse(options.body):null});
  if(String(url).includes('get_payment_gateways'))return {ok:true,json:async()=>({success:true,bank_account_csrf_token:'fixture-csrf',
    gateways:[{id:'config-a',gateway_name:'vipps',gateway_type:'vipps',display_name:'Vipps',is_configured:true,
      is_active:fixtureActive,test_mode:true,fee_fixed:0,fee_percent:0}]})};
  const webhook={state:fixtureReady?'registered':'attention',receiver_ready:fixtureReady,environment:'test',
     delivery_verified:fixtureDelivery,callback_url:'https://fixture.salesman.no/api/vipps_callback.php',
    message:fixtureReady?'Webhook registrert. Faktisk levering er ikke verifisert.':'Mottakeren er ikke klargjort.'};
  if(String(url).includes('vipps_webhook_status'))return {ok:true,json:async()=>({success:true,webhook})};
  if(String(url).includes('toggle_payment_gateway')){
    if(fixtureDelay)await new Promise(resolve=>setTimeout(resolve,80));
    const data=JSON.parse(options.body);
    if(data.is_active&&!fixtureReady)return {ok:true,json:async()=>({success:false,message:webhook.message,webhook})};
    fixtureActive=data.is_active;
    return {ok:true,json:async()=>({success:true,webhook: data.is_active?webhook:undefined})};
  }
  throw new Error('Unexpected fixture request');
};`;
const checks = `(async()=>{
const check=(ok,message)=>{if(!ok)throw new Error(message)};
const tick=()=>new Promise(resolve=>setTimeout(resolve,120));
try {
 await loadPaymentGateways();
 const container=document.getElementById('payment-gateways-container');
 check(container.textContent.includes('Test'),'Selected environment visible');
 check(container.textContent.includes('Aktiver'),'Inactive Vipps card has a single activation action');
 const face=document.getElementById('gateway-face-config-a');
 check(face && face.querySelector('img.economy-gateway-logo')?.getAttribute('src').includes('Vipps_'),'Vipps logo fills the compact card');
 const details=document.getElementById('gateway-details-config-a');
 check(details.hidden,'Configuration is collapsed by default');
 toggleGatewayDetails('config-a');
 check(!details.hidden&&face.getAttribute('aria-expanded')==='true','Clicking a card opens its configuration');
 check(container.textContent.includes('Oppdater status'),'Status action on Vipps card');
 await refreshVippsWebhook('config-a',container.querySelector('button[onclick*="refreshVippsWebhook"]'));
 check(document.getElementById('vipps-webhook-config-a').textContent.includes('Krever oppfølging'),'Missing receiver shown explicitly');
 fixtureDelay=true;
 const callsBefore=fixtureCalls.filter(c=>c.url.includes('toggle_payment_gateway')).length;
 const action=container.querySelector('.economy-gateway-action');
 const first=toggleGateway('config-a','vipps',true,action),second=toggleGateway('config-a','vipps',true,action);
 await Promise.all([first,second]);
 check(fixtureCalls.filter(c=>c.url.includes('toggle_payment_gateway')).length===callsBefore+1,'Double click sends one activation');
 check(!fixtureActive&&fixtureAlerts.length===0,'Blocked setup does not report active or use popup errors');
 check(document.getElementById('gateway-feedback-config-a').textContent.includes('Mottakeren er ikke klargjort'),'Activation error is shown below the button');
 const request=fixtureCalls.find(c=>c.url.includes('toggle_payment_gateway'));
 check(request.data.csrf_token==='fixture-csrf'&&request.data.id==='config-a'&&!('company_id'in request.data),'CSRF included; company not browser-selected');
 fixtureReady=true;fixtureDelay=false;
 await toggleGateway('config-a','vipps',true,container.querySelector('.economy-gateway-action'));
 check(fixtureActive,'Ready setup activates');
 check(document.getElementById('vipps-webhook-config-a').textContent.includes('Faktisk levering er ikke bekreftet ennå'),'Status survives card reload without repeating backend message');
 check(document.getElementById('gateway-light-label-config-a').textContent==='Trenger sjekk','Unverified webhook delivery remains yellow, not falsely green');
 fixtureDelivery=true;
 await refreshVippsWebhook('config-a',null);
 check(document.getElementById('gateway-light-label-config-a').textContent==='Aktiv','Verified receiver delivery turns the status green');
 check(container.textContent.includes('Deaktiver'),'Active method has one deactivation action');
 check(!container.textContent.includes('Registrer webhook'),'Registered webhook is not offered for re-creation');
 await toggleGateway('config-a','vipps',false);
 check(!fixtureActive,'Off updates payment card');
 check(!container.textContent.includes('fixture-csrf'),'CSRF not printed in card');
 check(document.documentElement.scrollWidth<=innerWidth+1,'Card fits viewport');
 return {passed:true};
}catch(error){return {passed:false,error:error.stack||String(error)}}
})()`;
const html = `<!doctype html><html><head><meta name="viewport" content="width=device-width,initial-scale=1">
<style>*{box-sizing:border-box}body{margin:0;padding:12px;font:15px system-ui;background:#f5f7fa}.row{display:flex;flex-wrap:wrap;gap:12px}.col-md-4{width:100%;max-width:380px}.card{background:white;border:1px solid #ddd;border-radius:8px}.card-body{padding:16px}.d-flex{display:flex;align-items:center;justify-content:space-between}.btn{padding:8px;border:1px solid #bbb;border-radius:5px;background:white;cursor:pointer}.w-100{width:100%}.small{font-size:13px}.text-muted{color:#667085}.text-break{overflow-wrap:anywhere}.border-top{border-top:1px solid #ddd}.mt-3{margin-top:16px}.mt-2{margin-top:8px}.pt-2{padding-top:8px}.mb-2{margin-bottom:8px}.badge{padding:4px;background:#f1f3f5;border-radius:4px}.col-6{width:calc(50% - 6px)}input{width:100%;padding:6px}.min-w-0{min-width:0}${appStyles}</style></head>
<body><div id="payment-gateways-container" class="row"></div><script>${stubs}\n${script}</script></body></html>`;
const server = http.createServer((req,res)=>{res.writeHead(200,{'Content-Type':'text/html; charset=utf-8'});res.end(html)});
(async()=>{
 await new Promise(resolve=>server.listen(0,'127.0.0.1',resolve));
 try {await chromiumFixture('http://127.0.0.1:'+server.address().port+'/',checks,[390,1280]);
 console.log('Economy Vipps UI activation/status tests passed at mobile and desktop sizes');}
 finally{server.close();}
})().catch(error=>{console.error(error);process.exitCode=1});
