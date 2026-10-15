/* Keep server revocation and idle expiry in sync with the visible browser.
 * Status polling is read-only. Only genuine interaction extends the session. */
(() => {
    const script=document.currentScript,context=script?.dataset.context;
    if(!context)return;
    const fetchBase=window.fetch.bind(window),logoutKey='portalSessionEnded';
    let deadline=Date.now()+Number(script.dataset.expiresIn||0)*1000;
    let stopped=false,inFlight=null,timer=0,activityAt=0,acknowledgedAt=0,lastTouch=0;
    let mustValidate=true;
    function finish(broadcast=true) {
        if(stopped)return;
        stopped=true;clearTimeout(timer);
        document.documentElement.style.visibility='hidden';
        try {
            sessionStorage.clear();
            if(broadcast)localStorage.setItem(logoutKey,JSON.stringify({context,at:Date.now()}));
        }catch(_){}
        location.replace('/index.php?error=session_expired');
    }
    function changed() {
        stopped=true;clearTimeout(timer);
        document.documentElement.style.visibility='hidden';
        location.replace('/pages/login_transition.php');
    }
    function schedule() {
        clearTimeout(timer);
        if(stopped||document.hidden)return;
        timer=setTimeout(()=>check(),Math.min(60000,Math.max(250,deadline-Date.now())));
    }
    async function check(allowActivity=true) {
        if(stopped||inFlight)return inFlight;
        const now=Date.now();
        const touch=allowActivity&&!mustValidate&&!document.hidden&&now<deadline
            &&activityAt>acknowledgedAt&&now-lastTouch>=60000;
        const sentActivity=activityAt;
        inFlight=(async()=>{
            const controller=new AbortController();
            const timeout=setTimeout(()=>controller.abort(),10000);
            try {
                const response=await fetchBase('/api/company_session.php',{
                    method:touch?'POST':'GET',credentials:'same-origin',cache:'no-store',
                    signal:controller.signal,
                    headers:{'X-Company-Context':context,...(touch?{'Content-Type':'application/json'}:{})},
                    ...(touch?{body:JSON.stringify({action:'activity'})}:{})
                });
                const data=await response.json();
                if(response.status===401&&data.error==='session_expired')return finish();
                if(response.status===409&&data.error==='context_changed')return changed();
                if(!response.ok||!data.success||!Number.isFinite(Number(data.expires_in))){
                    mustValidate=true;return;
                }
                deadline=Date.now()+Math.max(0,Number(data.expires_in))*1000;
                mustValidate=false;
                if(touch){lastTouch=Date.now();acknowledgedAt=sentActivity;}
            }catch(_){
                // Offline/suspended browsers retry on return, without extending
                // the server deadline or logging out another active tab.
                mustValidate=true;
            }finally{
                clearTimeout(timeout);
                inFlight=null;
                if(!stopped&&!mustValidate&&!document.hidden&&Date.now()<deadline
                    &&activityAt>acknowledgedAt&&Date.now()-lastTouch>=60000){
                    clearTimeout(timer);timer=setTimeout(()=>check(true),0);return;
                }
                if(!stopped&&Date.now()>=deadline){
                    // Server decides expiry. Avoid a tight loop during outages.
                    clearTimeout(timer);
                    if(!document.hidden)timer=setTimeout(()=>check(false),15000);
                }else schedule();
            }
        })();
        return inFlight;
    }
    function activity(event) {
        if(stopped||document.hidden||!event.isTrusted)return;
        activityAt=Date.now();
        if(mustValidate||Date.now()>=deadline){check(false);return;}
        check(true);
    }
    for(const type of ['pointerdown','keydown','wheel','touchstart'])
        document.addEventListener(type,activity,{passive:true,capture:true});
    document.addEventListener('visibilitychange',()=>{
        mustValidate=true;
        if(document.hidden)clearTimeout(timer);else check(false);
    });
    addEventListener('pageshow',()=>{
        if(stopped){location.replace('/index.php?error=session_expired');return;}
        mustValidate=true;check(false);
    });
    addEventListener('focus',()=>{mustValidate=true;check(false);});
    addEventListener('online',()=>{mustValidate=true;check(false);});
    addEventListener('storage',event=>{
        if(event.key!==logoutKey||!event.newValue)return;
        try{if(JSON.parse(event.newValue).context===context)finish(false);}catch(_){}
    });
    // A rejected business request should also leave the stale UI immediately.
    // Permission/module errors are not session expiry.
    window.fetch=async(input,options)=>{
        const response=await fetchBase(input,options);
        const url=new URL(input instanceof Request?input.url:input,location.href);
        if(url.origin===location.origin&&response.status===401){
            try{if((await response.clone().json()).error==='session_expired')finish();}catch(_){}
        }
        return response;
    };
    check(false);
})();
