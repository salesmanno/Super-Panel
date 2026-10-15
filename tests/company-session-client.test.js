// Deterministic client clock/events; server behavior is tested separately
// through real HTTP and disposable MySQL in company_choice_mysql_test.php.
const assert=require('node:assert/strict'),fs=require('node:fs'),vm=require('node:vm');
const source=fs.readFileSync(require('node:path').join(__dirname,'../assets/company_session.js'),'utf8');
const settle=()=>new Promise(resolve=>setImmediate(resolve));
async function browser(first={success:true,expires_in:1800}) {
  let now=1000000,id=0,cleared=false;
  const timers=new Map(),events={},docEvents={},calls=[],replies=[{status:200,body:first}],redirects=[],storage=new Map();
  const listen=(target,type,fn)=>(target[type]??=[]).push(fn);
  const document={currentScript:{dataset:{context:'context-a',expiresIn:'1800'}},hidden:false,
    documentElement:{style:{}},addEventListener:(type,fn)=>listen(docEvents,type,fn)};
  const sandbox={document,Date:{now:()=>now},AbortController,Request,URL,Response,
    setTimeout:(fn,delay)=>{timers.set(++id,{fn,at:now+delay});return id;},
    clearTimeout:key=>timers.delete(key),addEventListener:(type,fn)=>listen(events,type,fn),
    location:{href:'https://fixture.invalid/pages/company_dashboard.php',origin:'https://fixture.invalid',replace:url=>redirects.push(url)},
    sessionStorage:{clear:()=>{cleared=true;}},localStorage:{setItem:(key,value)=>storage.set(key,value)},
    fetch:async(input,options={})=>{
      calls.push({input,options});
      const result=replies.shift()??{status:200,body:{success:true,expires_in:1800}};
      if(result.error)throw result.error;
      return new Response(JSON.stringify(result.body),{status:result.status});
    }};
  sandbox.window=sandbox;
  vm.runInNewContext(source,sandbox);
  await settle();
  async function fire(type,event={},doc=false){
    for(const fn of (doc?docEvents:events)[type]??[])fn(event);
    await settle();
  }
  async function tick(ms){
    const target=now+ms;let turns=0;
    while(true){
      const next=[...timers].filter(([,value])=>value.at<=target).sort((a,b)=>a[1].at-b[1].at)[0];
      if(!next)break;
      assert.ok(++turns<100,'No runaway polling loop');
      now=next[1].at;timers.delete(next[0]);next[1].fn();await settle();
    }
    now=target;await settle();
  }
  return {calls,replies,redirects,storage,document,fire,tick,sandbox,get cleared(){return cleared;}};
}
(async()=>{
  const active=await browser();
  assert.equal(active.calls[0].options.method,'GET','Opening a page verifies, not renews');
  await active.fire('pointerdown',{isTrusted:false},true);
  assert.equal(active.calls.length,1,'Synthetic background activity cannot renew');
  await active.fire('pointerdown',{isTrusted:true},true);
  assert.equal(active.calls[1].options.method,'POST','Genuine interaction renews');
  assert.equal(JSON.parse(active.calls[1].options.body).action,'activity');
  assert.equal(active.calls[1].options.headers['X-Company-Context'],'context-a');
  await active.tick(120000);
  assert.equal(active.calls.filter(c=>c.options.method==='POST').length,1,'Read-only polling does not keep an idle login alive');
  active.document.hidden=true;await active.fire('visibilitychange',{},true);
  const hiddenCount=active.calls.length;await active.tick(120000);
  assert.equal(active.calls.length,hiddenCount,'Hidden tabs do not poll or send keepalive');
  active.document.hidden=false;await active.fire('visibilitychange',{},true);
  assert.equal(active.calls.at(-1).options.method,'GET','Returning to the browser validates without renewing');
  active.replies.push({status:403,body:{error:'session_expired'}});
  await active.sandbox.fetch('/api/example.php');
  active.replies.push({status:401,body:{error:'access_denied'}});
  await active.sandbox.fetch('/api/example.php');
  assert.equal(active.redirects.length,0,'Permission errors are not logout events');
  active.replies.push({status:401,body:{error:'session_expired'}});
  await active.sandbox.fetch('/api/example.php');
  assert.match(active.redirects[0],/session_expired/);
  const changedAccess=await browser();
  changedAccess.replies.push({status:401,body:{error:'session_expired',login_error:'access_changed'}});
  await changedAccess.fire('focus');
  assert.match(changedAccess.redirects[0],/error=access_changed$/,'Access changes are not displayed as idle expiry');
  const invalidHint=await browser();
  invalidHint.replies.push({status:401,body:{error:'session_expired',login_error:'https://outside.invalid/'}});
  await invalidHint.fire('focus');
  assert.match(invalidHint.redirects[0],/error=session_expired$/,'Unknown server error hints cannot control navigation');
  assert.equal(active.document.documentElement.style.visibility,'hidden','Protected content is hidden before redirect');
  assert.ok(active.cleared,'Old tab caches cleared');
  assert.equal(JSON.parse(active.storage.get('portalSessionEnded')).context,'context-a','Other same-context tabs notified');
  await active.fire('pageshow',{persisted:true});
  assert.equal(active.redirects.length,2,'Browser back cannot restore a hidden or expired protected page');
  const expired=await browser({success:true,expires_in:2});
  expired.replies.push({status:401,body:{error:'session_expired'}});
  await expired.tick(2000);
  assert.equal(expired.redirects.length,1,'Server deadline schedules timely client logout');
  const changed=await browser();
  changed.replies.push({status:409,body:{error:'context_changed'}});
  await changed.fire('focus');
  assert.equal(changed.redirects[0],'/pages/login_transition.php','Stale context reloads without logging out a newer session');
  assert.equal(changed.storage.size,0,'Context conflict does not broadcast logout');
  const outage=await browser({success:true,expires_in:1});
  outage.replies.push({error:new Error('offline')});
  await outage.tick(1000);
  assert.equal(outage.redirects.length,0,'Temporary network failure does not destroy a valid server login');
  const waiting=outage.calls.length;await outage.tick(14000);
  assert.equal(outage.calls.length,waiting,'Expired offline checks back off rather than spin');
  outage.replies.push({status:401,body:{error:'session_expired'}});
  await outage.fire('online');
  assert.equal(outage.redirects.length,1,'Reconnect checks and retires an expired browser');
  const unavailable=await browser();
  unavailable.replies.push({status:503,body:{success:false,error:'session_unavailable'}});
  await unavailable.fire('keydown',{isTrusted:true},true);
  const unavailableCount=unavailable.calls.length;
  await unavailable.tick(1000);
  assert.equal(unavailable.calls.length,unavailableCount,'A server outage during activity cannot cause zero-delay renewal retries');
  assert.equal(unavailable.redirects.length,0,'Server outage does not clear the browser login');
  const tab=await browser();
  await tab.fire('storage',{key:'portalSessionEnded',newValue:JSON.stringify({context:'other'})});
  assert.equal(tab.redirects.length,0,'Other-account logout does not kick a new context');
  await tab.fire('storage',{key:'portalSessionEnded',newValue:JSON.stringify({context:'context-a'})});
  assert.equal(tab.redirects.length,1,'Same-session logout reaches other tabs');
  console.log('Company session client checks passed: activity, idle expiry, resume, revocation, context isolation and offline recovery');
})().catch(error=>{console.error(error);process.exitCode=1;});
