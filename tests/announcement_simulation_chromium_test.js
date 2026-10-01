const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const {spawnSync} = require('node:child_process');

// Render the actual editor fragment, without evaluating PHP or loading the app's
// database/auth configuration. All API calls are local in-memory test doubles.
const ranking = fs.readFileSync(path.join(__dirname, '../assets/announcement_ranking.js'), 'utf8');
const fragment = fs.readFileSync(path.join(__dirname, '../modules/announcements_content.php'), 'utf8')
    .replace(/<\?[\s\S]*?\?>/g, '')
    .replace('<script src="/assets/announcement_ranking.js"></script>', `<script>${ranking}</script>`);
const fixture = [
    ['one', 1], ['two', 2], ['three', 3], ['four', 4],
    ['six', 6], ['eight', 8], ['target', 9], ['tail', 9],
].map(([id, priority]) => ({
    id, priority, title: id, content: '<p>Content</p>', content_html: '<p>Content</p>',
    content_text: 'Content', is_pinned: false, is_published: true,
    announcement_type: 'info', publish_at: '2025-01-01 12:00:00',
}));
const stubs = `
window.testRows = ${JSON.stringify(fixture)};
window.testSaves = [];
window.testAlerts = [];
window.alert = message => window.testAlerts.push(message);
window.fetch = async (url, options) => {
    if (!options) return {ok:true,json:async()=>({success:true,announcements:window.testRows})};
    const data=JSON.parse(options.body);
    window.testSaves.push(data);
    const planned=AnnouncementRanking.planPriorityAssignment(window.testRows,data.id,data.priority).items;
    // A deliberately different returned rank proves the editor trusts the server.
    const priorities=Object.fromEntries(planned.map(row=>[row.id,row.priority]));
    priorities.four=7;
    return {ok:true,json:async()=>({success:true,priorities,content:data.content,
        content_html:data.content,content_text:'Content'})};
};
class TestModal {
    static instances = new Map();
    constructor(element) {this.element=element;TestModal.instances.set(element,this)}
    static getInstance(element) {return TestModal.instances.get(element)}
    show() {this.element.dispatchEvent(new Event('shown.bs.modal'))}
    hide() {
        if(this.element.dispatchEvent(new Event('hide.bs.modal',{cancelable:true})))
            this.element.dispatchEvent(new Event('hidden.bs.modal'));
    }
}
window.bootstrap={Modal:TestModal};
`;
const checks = `
(async()=>{
    const check=(ok,message)=>{if(!ok)throw new Error(message)};
    const settle=()=>new Promise(resolve=>setTimeout(resolve,30));
    const input=document.getElementById('announcement-priority');
    const impact=()=>document.getElementById('announcement-priority-impact').textContent;
    const order=()=>Array.from(document.querySelectorAll('#news-preview .hero-story,#news-preview .secondary-story')).map(el=>el.dataset.storyId).join(',');
    const edit=id=>document.querySelector('[data-action="edit"][data-id="'+id+'"]').click();
    const setPriority=value=>{input.value=value;input.dispatchEvent(new Event('input',{bubbles:true}))};
    const close=()=>bootstrap.Modal.getInstance(document.getElementById('announcementModal')).hide();
    const manage=id=>Array.from(document.querySelectorAll('#news-management .manage-item')).find(el=>el.querySelector('[data-action="edit"]').dataset.id===id).textContent;
    try{
        await settle();
        check(order()==='one,two,three,four,six','Initial saved order');
        edit('target');setPriority('4');
        check(order()==='one,two,three,target,four','9 to 4 simulation leaves top three alone');
        check(impact().includes('six → 7')&&impact().includes('eight → 9'),'Impact crosses gaps and caps at nine');
        check(manage('four').includes('Prioritet 4'),'Simulation does not mutate management rows');
        close();
        check(order()==='one,two,three,four,six','Cancelling restores saved preview order');
        check(testSaves.length===0,'Cancelled edit makes no save call');
        edit('one');
        check(impact()==='Ingen andre saker må flyttes.','Saving an unchanged top does not shift');
        close();
        edit('target');setPriority('10');
        check(!input.checkValidity(),'Input ten is rejected');
        check(order()==='one,two,three,four,six','Invalid priority does not preview fallback five');
        document.getElementById('publishButton').click();await settle();
        check(testSaves.length===0,'Invalid priority does not save');
        setPriority('1');
        check(order()==='target,one,two,three,four','New top shifts every other story');
        document.getElementById('publishButton').click();await settle();
        check(testSaves.length===1&&testSaves[0].priority===1,'Save sends chosen priority, not shifted target');
        check(manage('four').includes('Prioritet 7'),'Management applies authoritative server rank');
        check(document.querySelector('#news-preview .hero-story').dataset.storyId==='target','Saved preview reflects server-confirmed new top');
        check(testAlerts.length===0,'Editor encountered no errors');
        document.body.dataset.testStatus='passed';
    }catch(error){document.body.dataset.testStatus='failed';document.body.dataset.testError=error.stack||String(error)}
})();
`;
const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'news-simulation-'));
try {
    const file = path.join(directory, 'fixture.html');
    fs.writeFileSync(file, `<!doctype html><html><body><script>${stubs}</script>${fragment}<script>${checks}</script></body></html>`);
    const result = spawnSync(process.env.CHROMIUM_PATH || '/repl/tools/bin/chromium', [
        '--headless', '--no-sandbox', '--disable-dev-shm-usage',
        '--no-first-run', '--user-data-dir='+path.join(directory, 'profile'),
        '--virtual-time-budget=5000', '--dump-dom', 'file://'+file,
    ], {encoding:'utf8',timeout:30000,maxBuffer:4*1024*1024});
    assert.equal(result.status, 0, result.stderr);
    const status = result.stdout.match(/data-test-status="([^"]+)"/)?.[1];
    assert.equal(status, 'passed', result.stdout.match(/data-test-error="([^"]+)"/)?.[1] || result.stdout.slice(-4000));
    console.log('Actual News editor Chromium simulation tests passed');
} finally {
    fs.rmSync(directory, {recursive:true,force:true});
}