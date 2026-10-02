// Exercises the actual Events markup and submit/reset functions without loading
// PHP, signing in, or contacting any app API or external database.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawn, spawnSync} = require('node:child_process');
if (typeof WebSocket === 'undefined') {
    const child = spawnSync(process.execPath, ['--experimental-websocket', __filename], {stdio: 'inherit'});
    process.exit(child.status ?? 1);
}
const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'events-wizard-'));
const fragment = fs.readFileSync(path.join(__dirname, '../modules/events_content.php'), 'utf8')
    .replace(/<\?[\s\S]*?\?>/g, '')
    .replace(/<link[^>]*>/g, '')
    .replace(/<script[^>]*src=[^>]*><\/script>/g, '');
const helper = fs.readFileSync(path.join(__dirname, '../assets/event_form_helpers.js'), 'utf8');
const dashboardDateControls = fs.readFileSync(path.join(__dirname, '../includes/norwegian_datetime_inputs.php'), 'utf8');
const stubs = `
window.fixtureErrors=[];window.fixtureSaves=[];window.fixtureMode='success';window.fixtureConfirms=0;
window.fixtureConfigReads=0;
window.addEventListener('error',e=>fixtureErrors.push(e.message));
window.addEventListener('unhandledrejection',e=>fixtureErrors.push(String(e.reason)));
window.confirm=()=>{fixtureConfirms++;return true};
window.alert=message=>fixtureErrors.push(message);
window.toast=new Proxy({}, {get:()=>()=>{}});
window.bootstrap={Modal:class {static getInstance(){return {hide(){}}} show(){} hide(){}},Tab:class{show(){}}};
window.fetch=async(url,options)=>{
    if(String(url).includes('create_event.php')){
        fixtureSaves.push(JSON.parse(options.body));
        if(fixtureMode==='network')throw new Error('Simulated network failure');
        return {ok:fixtureMode!=='failure',json:async()=>({success:fixtureMode==='success',message:'Testfeil'})};
    }
    let result={success:true,events:[],registrations:[],checkins:[],data:[],plans:[]};
    if(String(url).includes('get_payment_methods.php')){fixtureConfigReads++;result.data=[
        {method_type:'cash',method_name:'Kontant'},{method_type:'vipps',method_name:'Vipps'}]}
    if(String(url).includes('get_membership_plans.php')){fixtureConfigReads++;result.plans=[{id:'fixture-plan',name:'Testplan'}]}
    return {ok:true,json:async()=>result};
};
`;
const checks = `
(async()=>{
    const check=(ok,message)=>{if(!ok)throw new Error(message)};
    const settle=()=>new Promise(resolve=>setTimeout(resolve,25));
    const form=document.getElementById('create-event-form');
    const field=name=>form.querySelector('[name="'+name+'"]');
    const input=(element,value)=>{element.value=value;element.dispatchEvent(new Event('input',{bubbles:true}))};
    const select=(element,value)=>{element.value=value;element.dispatchEvent(new Event('change',{bubbles:true}))};
    const time=name=>field(name).parentNode;
    const submit=async()=>{form.dispatchEvent(new Event('submit',{cancelable:true,bubbles:true}));await settle()};
    const type=value=>{field('registration_type').checked=false;
        const radio=form.querySelector('[name="registration_type"][value="'+value+'"]');
        radio.checked=true;radio.dispatchEvent(new Event('change',{bubbles:true}))};
    const fill=(title,date,value)=>{
        input(field('title'),title);input(field('start_date'),date);
        const wrapper=time('start_time');
        if(field('start_time').dataset.eventTimeMode!=='text')wrapper.querySelector('.event-time-toggle').click();
        input(wrapper.querySelector('.event-time-text'),value);
    };
    const clean=()=>{
        check(wizardCurrentStep===1,'Wizard returns to step one');
        check(document.getElementById('wizard-next').style.display==='block','Next button restored');
        check(document.getElementById('wizard-prev').style.display==='none','Previous button hidden');
        check(document.getElementById('wizard-submit').style.display==='none','Submit button hidden');
        check(field('title').value===''&&field('start_date').value===''&&field('start_time').value==='','Event data cleared');
        check(field('end_date').value===''&&field('end_time').value===''&&field('registration_deadline').value==='','Optional dates/times cleared');
        check(field('event_image_url').value==='','Image value cleared');
        check(!document.querySelector('#summary-image img'),'Image preview cleared');
        check(document.getElementById('summary-title').textContent==='-','Title preview cleared');
        check(document.getElementById('summary-date').textContent==='','Date preview cleared');
        check(field('start_time').dataset.eventTimeMode==='picker','Time entry mode reset');
        check(form.querySelector('[name="registration_type"]:checked').value==='signup','Default registration restored');
        check(document.getElementById('registration_deadline_container').style.display==='none','Deadline hidden again');
        check(!field('registration_deadline').required,'Deadline no longer required');
        check(document.getElementById('venue-name-field').style.display==='none','Venue visibility reset');
        check(form.querySelector('[name="membership_price_fixture-plan"]').value==='','Member price cleared');
        check(form.querySelector('[name="membership_public_fixture-plan"]').checked,'Member public default restored');
        check(document.querySelectorAll('#payment-methods-container input').length===2,'Company payment configuration retained');
        check(membershipPlansData.length===1,'Company plans retained');
        check(fixtureConfirms===0,'Automatic reset never asks for confirmation');
    };
    try{
        await settle();
        const wrapper=field('start_date').parentNode;
        const picker=wrapper.querySelector('[type="date"]');
        check(!wrapper.querySelector('[data-norwegian-date-time-proxy]'),'Dashboard enhancer leaves the native calendar touch target intact');
        check(picker.style.pointerEvents==='auto'&&picker.style.width==='2.5rem','Native calendar retains usable touch target');
        picker.value='2028-03-02';picker.dispatchEvent(new Event('change',{bubbles:true}));
        check(field('start_date').value==='02.03.2028','Native picker writes Norwegian date');
        input(field('start_date'),'15062028');
        check(field('start_date').value==='15.06.2028','Numeric date input inserts punctuation');
        check(picker.value==='2028-06-15','Native picker follows manually entered date');
        input(field('end_date'),'16062028');
        input(field('min_attendees'),'3');input(field('registration_deadline'),'14062028');
        for(const name of ['end_date','registration_deadline']){
            const native=field(name).parentNode.querySelector('[type="date"]');
            check(Boolean(native),'Calendar exists for '+name);
            native.value=name==='end_date'?'2028-06-16':'2028-06-14';
            native.dispatchEvent(new Event('change',{bubbles:true}));
            check(field(name).value===(name==='end_date'?'16.06.2028':'14.06.2028'),'Calendar uses Norwegian format for '+name);
        }
        check(field('registration_deadline').required,'Conditional deadline required');
        type('none');
        fill('Første arrangement','15062028','0930');
        check(field('start_time').value==='09:30','Numeric time input inserts colon');
        const end=time('end_time');
        select(end.querySelector('.event-time-hours'),'23');
        check(!end.querySelector('.event-time-minutes').checkValidity(),'Partial optional time blocked visibly');
        end.querySelector('.event-time-toggle').click();
        check(field('end_time').dataset.eventTimePartial==='false','Switching to empty optional text entry clears stale partial selection');
        end.querySelector('.event-time-toggle').click();
        select(end.querySelector('.event-time-hours'),'23');
        select(end.querySelector('.event-time-minutes'),'59');
        check(field('end_time').value==='23:59','Optional time supports 24-hour selection');
        const typed=time('start_time').querySelector('.event-time-text');
        input(typed,'2460');
        check(!typed.checkValidity()&&!validateEventDateTimeInputs(form),'Out-of-range typed time rejected');
        input(typed,'930');typed.dispatchEvent(new Event('blur'));
        check(field('start_time').value==='09:30'&&typed.checkValidity(),'Short compact input normalized on blur');
        input(typed,'9:5');typed.dispatchEvent(new Event('blur'));
        check(field('start_time').value==='09:05','Colon input is padded on blur');
        input(typed,'0930');
        input(field('event_image_url'),'data:image/png,fixture');
        document.getElementById('has_venue_name').checked=true;
        input(field('venue_name'),'Gammelt sted');
        input(form.querySelector('[name="membership_price_fixture-plan"]'),'150');
        form.querySelector('[name="membership_public_fixture-plan"]').checked=false;
        wizardCurrentStep=7;updateWizardSummary();
        fixtureMode='failure';await submit();
        check(field('title').value==='Første arrangement'&&field('start_time').value==='09:30','API failure retains values');
        check(wizardCurrentStep===7,'API failure retains wizard step');
        fixtureMode='network';await submit();
        check(field('title').value==='Første arrangement'&&wizardCurrentStep===7,'Network failure retains values and step');
        const configReads=fixtureConfigReads;
        fixtureMode='success';await submit();clean();
        check(fixtureConfigReads===configReads,'Reset retains loaded configuration without asynchronous replacement');
        fill('Andre arrangement','17062028','1835');
        wizardCurrentStep=7;updateWizardSummary();await submit();clean();
        const last=fixtureSaves[fixtureSaves.length-1];
        check(last.title==='Andre arrangement'&&last.start_time==='18:35','Second save uses its own values');
        check(last.end_date===null&&last.end_time===null&&last.event_image_url===null&&last.venue_name==='','Second payload contains no previous optional data');
        input(field('title'),'Manuell forkasting');
        check(resetWizard()&&fixtureConfirms===1,'Manual discard keeps its confirmation');
        const edit=document.getElementById('edit-event-form');
        edit.querySelector('[name="start_date"]').value='17.06.2028';
        edit.querySelector('[name="start_time"]').value='07:05';
        EventFormHelpers.syncDateTimeControls(edit);
        check(edit.querySelector('[name="start_date"]').parentNode.querySelector('.event-native-date-picker').value==='2028-06-17','Edit date picker synced after programmatic population');
        check(edit.querySelector('.event-time-hours').value==='07','Edit hour synced');
        check(edit.querySelector('.event-time-minutes').value==='05','Edit minute synced');
        check(fixtureErrors.length===0,'No unexpected browser errors: '+fixtureErrors.join('; '));
        return {passed:true,width:innerWidth};
    }catch(error){return {passed:false,error:error.stack||String(error),errors:fixtureErrors}}
})()
`;
let browser;
let socket;
async function main() {
    const file=path.join(directory,'fixture.html');
    fs.writeFileSync(file, `<!doctype html><html lang="nb"><head><meta name="viewport" content="width=device-width,initial-scale=1"></head><body><script>${stubs}</script><script>${helper}</script>${fragment}${dashboardDateControls}</body></html>`);
    browser=spawn(process.env.CHROMIUM_PATH||'/repl/tools/bin/chromium',[
        '--headless','--no-sandbox','--disable-dev-shm-usage','--remote-debugging-port=0',
        '--user-data-dir='+path.join(directory,'profile'),'about:blank'
    ]);
    const url=await new Promise((resolve,reject)=>{
        let output='';
        const timeout=setTimeout(()=>reject(new Error('Chromium debugger timed out')),15000);
        browser.stderr.on('data',data=>{
            output+=data;
            const match=output.match(/DevTools listening on (ws:\/\/[^\s]+)/);
            if(match){clearTimeout(timeout);resolve(match[1])}
        });
        browser.on('error',reject);
    });
    socket=new WebSocket(url);
    await new Promise(resolve=>socket.addEventListener('open',resolve,{once:true}));
    let id=0;
    const pending=new Map();
    socket.addEventListener('message',event=>{
        const message=JSON.parse(event.data);
        const callbacks=pending.get(message.id);
        if(callbacks){pending.delete(message.id);message.error?callbacks.reject(message.error):callbacks.resolve(message.result)}
    });
    const send=(method,params={},sessionId)=>new Promise((resolve,reject)=>{
        pending.set(++id,{resolve,reject});socket.send(JSON.stringify({id,method,params,sessionId}));
    });
    const {targetId}=await send('Target.createTarget',{url:'about:blank'});
    const {sessionId}=await send('Target.attachToTarget',{targetId,flatten:true});
    for(const width of [390,768,1280]){
        await send('Emulation.setDeviceMetricsOverride',{width,height:844,deviceScaleFactor:1,mobile:width===390},sessionId);
        await send('Page.navigate',{url:'file://'+file+'?width='+width},sessionId);
        await new Promise(resolve=>setTimeout(resolve,300));
        const result=await send('Runtime.evaluate',{expression:checks,awaitPromise:true,returnByValue:true},sessionId);
        assert.equal(result.result?.value?.passed,true,JSON.stringify(result));
        assert.equal(result.result.value.width,width,'Real emulated viewport width');
        console.log('Actual Events wizard passed at '+width+'px');
    }
}
main().catch(error=>{console.error(error);process.exitCode=1}).finally(async()=>{
    if(socket)socket.close();
    if(browser){
        const stopped=new Promise(resolve=>browser.once('exit',resolve));
        browser.kill();
        await stopped;
    }
    fs.rmSync(directory,{recursive:true,force:true,maxRetries:5,retryDelay:100});
});