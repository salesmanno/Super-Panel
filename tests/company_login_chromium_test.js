const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const os = require('node:os');
const {execFileSync} = require('node:child_process');

const root = path.resolve(__dirname, '..');
function render(query = {}) {
    // The login template has no database dependency. Never execute auth endpoints.
    return execFileSync('php', [
        '-r', '$_GET = json_decode($argv[1], true); include $argv[2];',
        JSON.stringify(query), path.join(root, 'pages/company_login.php'),
    ], {encoding: 'utf8'});
}
const messages = {
    invalid_credentials: 'Ugyldig brukernavn eller passord.',
    system_error: 'Systemfeil. Vennligst prøv igjen senere.',
    account_inactive: 'Kontoen din er inaktiv.',
    company_inactive: 'Firmaet er deaktivert.',
    database_unavailable: 'Databasen er ikke tilgjengelig.',
    already_logged_in: 'Brukeren er allerede innlogget.',
    session_expired: 'Økten din har utløpt.',
    session_error: 'Innloggingen kunne ikke lagres riktig på serveren.',
    session_missing: 'Nettleseren eller serveren beholdt ikke innloggingen.',
    access_changed: 'Tilgangen til kontoen eller bedriften er endret.',
    login_busy: 'En annen innloggingsforespørsel pågår.',
    database_changed: 'Databasekonfigurasjonen ble endret.',
    not_authenticated: 'Du må logge inn for å få tilgang.',
};
for (const [error, message] of Object.entries(messages)) {
    const html = render({error});
    assert.ok(html.includes(message), `Status ${error} is retained`);
    assert.match(html, /class="alert alert-danger[^"]*" role="alert"/);
}
assert.ok(render({error: '<script>alert(1)</script>'}).includes('En feil oppstod.'));
assert.ok(!render({error: '<script>alert(1)</script>'}).includes('<script>alert(1)</script>'));

const news = ['critical', 'high', 'medium', 'low'].map((severity, index) => ({
    title: index === 0 ? '<img src=x onerror="window.injected=true">' : 'Nyhet '+index,
    content: '<script>window.injected=true</script> Endringer i portalen.',
    severity, priority_text: ['Kritisk', 'Høy', 'Medium', 'Lav'][index],
    news_type: ['announcement', 'maintenance', 'update', 'info'][index],
    formatted_date: '02. okt.',
}));
news.push({title:'Lang'.repeat(100), content:'Tekst '.repeat(150),
    severity:'low',priority_text:'Lav',news_type:'info',formatted_date:'02. okt.'});
const stubs = `
window.testDbStatus='connected';
window.testDbReject=false;
window.testNewsReject=false;
window.testNewsReply={success:true,news:${JSON.stringify(news).replace(/</g, '\\u003c')}};
window.testPosts=[];
window.testReads=[];
window.testCleared=[];
window.setInterval=(fn,ms)=>{window.testPoll={fn,ms};return 88};
window.clearInterval=id=>window.testCleared.push(id);
window.fetch=async url=>{
    window.testReads.push(url);
    if(url==='/api/check_database_status.php'){
        if(window.testDbReject)throw new Error('Isolated network failure');
        return {json:async()=>({status:window.testDbStatus})};
    }
    if(url==='/api/get_news.php'){
        if(window.testNewsReject)throw new Error('Isolated news failure');
        return {json:async()=>window.testNewsReply};
    }
    throw new Error('Unexpected network request: '+url);
};
document.addEventListener('submit',event=>{
    event.preventDefault();
    window.testPosts.push({action:event.target.getAttribute('action'),
        fields:Object.fromEntries(new FormData(event.target))});
},true);
`;
const checks = `
document.addEventListener('DOMContentLoaded',async()=>{
    const check=(value,message)=>{if(!value)throw new Error(message)};
    const settle=()=>new Promise(resolve=>setTimeout(resolve,30));
    const $=id=>document.getElementById(id);
    const visible=id=>getComputedStyle($(id)).display!=='none';
    try{
        await settle();
        check(innerWidth===${'VIEWPORT_WIDTH'},'Exact test viewport');
        check($('login-form').method==='post','POST login retained');
        check($('login-form').getAttribute('action')==='/auth/company_auth.php','Login endpoint retained');
        check($('username').required&&$('password').required,'Login fields required');
        check($('username').autocomplete==='username'&&$('password').autocomplete==='current-password','Autofill retained');
        check($('password').type==='password','Password remains concealed');
        check(visible('username-field')&&visible('password-field')&&!visible('email-field')&&!visible('secret-field'),'Initial mode visibility');
        check($('email').name==='email'&&$('secret').name==='secret','Recovery field names retained');
        check($('email').autocomplete==='email','Recovery autofill retained');
        check(document.querySelector('.alert-danger').textContent.includes('Økten din har utløpt.'),'Session message visible');
        check(document.querySelector('.alert-success').textContent.includes('Din forespørsel er mottatt og behandles.'),'Generic recovery confirmation retained');
        check($('news-content').querySelectorAll('.news-item').length===5,'News appears on mobile and desktop');
        check($('news-content').textContent.includes('<img src=x'),'News title escaped, not executed');
        check(!window.injected&&!$('news-content').querySelector('img,script'),'News markup is not executable');
        // Current login feed renders type badges, not the retired severity tags.
        check($('news-content').querySelectorAll('.news-type-badge').length===5,'Current news type badges retained');
        check($('news-content').textContent.includes('Vedlikehold')&&$('news-content').textContent.includes('02. okt.'),'News type/date retained');
        check(getComputedStyle(document.querySelector('.news-section')).display!=='none','Mobile news is not hidden');
        check(document.documentElement.scrollWidth<=innerWidth,'No horizontal page overflow');
        for(const card of document.querySelectorAll('.news-item'))
            check(card.scrollWidth<=card.clientWidth+1,'Long news remains inside card');
        for(const id of ['username','password','login-button','toggle-mode'])
            check($(id).getBoundingClientRect().height>=44,'Touch target '+id);
        check(parseFloat(getComputedStyle($('username')).fontSize)>=16,'Input size avoids iOS focus zoom');
        $('toggle-mode').focus();
        const focusStyle=getComputedStyle($('toggle-mode'));
        check(focusStyle.outlineStyle!=='none'&&parseFloat(focusStyle.outlineWidth)>=2,'Visible keyboard focus');
        check(window.testPoll.ms===10000,'Database polling retained');

        $('toggle-mode').click();
        check($('login-form').getAttribute('action')==='/auth/forgot_password.php','Recovery endpoint retained');
        check(!visible('username-field')&&!visible('password-field')&&visible('email-field')&&visible('secret-field'),'Recovery mode visibility');
        check(!$('username').required&&!$('password').required&&$('email').required&&$('secret').required,'Recovery validation retained');
        check(document.activeElement===$('email'),'Recovery focus retained');
        $('email').value='test@example.invalid';$('secret').value='isolated-test';
        $('login-form').requestSubmit($('login-button'));
        check(testPosts.length===1&&testPosts[0].action==='/auth/forgot_password.php','Recovery submit is intercepted, never sent');
        check(testPosts[0].fields.email==='test@example.invalid'&&testPosts[0].fields.secret==='isolated-test','Recovery values retained');

        window.testDbStatus='disconnected';checkDatabaseStatus();await settle();
        check(document.body.classList.contains('db-error')&&$('login-button').disabled,'Database outage blocks submit');
        $('toggle-mode').click();
        check($('login-button').disabled,'Mode toggle does not bypass database outage');
        check(visible('username-field')&&!visible('email-field'),'Return to login retained');
        check($('username').required&&$('password').required&&!$('email').required&&!$('secret').required,'Return restores required fields');
        check(document.activeElement===$('username'),'Login focus restored');
        $('username').value='isolated-test';$('password').value='not-a-real-password';
        $('login-button').click();
        check(testPosts.length===1,'Disabled submit performs no submission');
        window.testDbStatus='connected';checkDatabaseStatus();await settle();
        check(!document.body.classList.contains('db-error')&&!$('login-button').disabled,'Reconnection restores submit');
        $('login-form').requestSubmit($('login-button'));
        check(testPosts.length===2&&testPosts[1].action==='/auth/company_auth.php','Login submit contract retained');
        check(testPosts[1].fields.username==='isolated-test'&&testPosts[1].fields.password==='not-a-real-password','Login fields submitted');
        window.testDbReject=true;checkDatabaseStatus();await settle();
        check($('login-button').disabled&&document.body.classList.contains('db-error'),'Network failure blocks submit');

        updateNewsDisplay({success:true,news:[]});
        check($('news-content').textContent.includes('Ingen nyheter tilgjengelig'),'Empty feed retained');
        window.testNewsReject=true;loadNews();await settle();
        check($('news-content').textContent.includes('Kunne ikke laste nyheter'),'Failed feed retained');
        window.dispatchEvent(new Event('beforeunload'));
        check(testCleared.includes(88),'Polling cleanup retained');
        check(testReads.every(url=>['/api/get_news.php','/api/check_database_status.php'].includes(url)),'No auth/reset/network writes');
        parent.document.body.dataset.testStatus='passed';
    }catch(error){
        parent.document.body.dataset.testStatus='failed';
        parent.document.body.dataset.testError=error.stack||String(error);
    }
});
`;
const css = fs.readFileSync(path.join(root, 'pages/company_login.css'), 'utf8').replace(/^@import .*;$/gm, '');
const logo = fs.readFileSync(path.join(root, 'attached_assets/salesman-removebg-preview_1761762992662.png')).toString('base64');
let html = render({error: 'session_expired', message: 'reset_request_sent'})
    .replace(/<link\b[^>]*>/g, '') // No live external styles, fonts or API requests.
    .replace('<head>', `<head><style>
        .d-none{display:none!important}.w-100{width:100%!important}
        .form-control{display:block;width:100%}.visually-hidden{position:absolute;width:1px;height:1px;overflow:hidden}
        ${css}</style><script>${stubs}</script>`)
    .replace('/attached_assets/salesman-removebg-preview_1761762992662.png', 'data:image/png;base64,'+logo)
    .replace('</body>', `<script>${checks}</script></body>`);
assert.ok(html.includes('Laster siste nyheter...'), 'Initial loading markup retained');
const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'company-login-tests-'));
try {
    for (const width of [320, 1280]) {
        const fixture = html.replace('VIEWPORT_WIDTH', String(width));
        const file = path.join(directory, 'login-'+width+'.html');
        // srcdoc gives an exact responsive viewport even with Chromium's minimum
        // desktop window size, and lets the fixture report into the parent DOM.
        fs.writeFileSync(file, `<!doctype html><html><body><script>
            const frame=document.createElement('iframe');
            frame.style.cssText='border:0;width:${width}px;height:1000px';
            frame.srcdoc=${JSON.stringify(fixture).replace(/</g, '\\u003c')};
            document.body.append(frame);
        </script></body></html>`);
        const output = execFileSync(process.env.CHROMIUM_PATH || '/repl/tools/bin/chromium', [
            '--headless', '--no-sandbox', '--disable-dev-shm-usage', '--no-first-run',
            '--user-data-dir='+path.join(directory, 'profile-'+width),
            '--virtual-time-budget=5000', '--dump-dom', 'file://'+file,
        ], {encoding:'utf8',timeout:30000,maxBuffer:4*1024*1024,stdio:['ignore','pipe','pipe']});
        assert.equal(output.match(/data-test-status="([^"]+)"/)?.[1], 'passed',
            `${width}px: ${output.match(/data-test-error="([^"]+)"/)?.[1] || 'Browser checks did not complete'}`);
    }
    console.log('Login status maps, mobile/desktop form modes, feed, outage gating and cleanup passed');
} finally {
    fs.rmSync(directory, {recursive:true,force:true});
}