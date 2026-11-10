const assert = require('node:assert/strict');
const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const chromiumFixture = require('./chromium_fixture');

// Render the actual editor fragment, without evaluating PHP or loading the app's
// database/auth configuration. All API calls are local in-memory test doubles.
const ranking = fs.readFileSync(path.join(__dirname, '../assets/announcement_ranking.js'), 'utf8');
const fragment = fs.readFileSync(path.join(__dirname, '../modules/announcements_content.php'), 'utf8')
    .replace(/<\?[\s\S]*?\?>/g, '')
    .replace('<script src="/assets/announcement_ranking.js"></script>', `<script>${ranking}</script>`)
    .replace('<script src="/assets/news_video.js"></script>', `<script>${fs.readFileSync(path.join(__dirname, '../assets/news_video.js'),'utf8')}</script>`);
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
window.confirm = () => true;
window.fetch = async (url, options) => {
    if (!options) return {ok:true,json:async()=>({success:true,announcements:window.testRows,editor_author:{staff_id:'staff-test',name:'Testredaktør'},company_name:'Testfirma'})};
    const data=JSON.parse(options.body);
    window.testSaves.push(data);
    const priorities={};
    if(data.id){
        const planned=AnnouncementRanking.planPriorityAssignment(window.testRows,data.id,data.priority).items;
        Object.assign(priorities,Object.fromEntries(planned.map(row=>[row.id,row.priority])));
        // A deliberately different returned rank proves the editor trusts the server.
        priorities.four=7;
    }else priorities['new-story']=data.priority;
    return {ok:true,json:async()=>({success:true,priorities,content:data.content,
        announcement_id:data.id||'new-story',content_html:data.content,content_text:'Content'})};
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
    const card=id=>Array.from(document.querySelectorAll('.news-edit-card')).find(el=>el.querySelector('[data-edit-story]')?.dataset.editStory===id);
    const edit=id=>card(id).querySelector('[data-edit-story]').click();
    const setPriority=value=>{input.value=value;input.dispatchEvent(new Event('input',{bubbles:true}))};
    const close=()=>document.getElementById('news-edit-back').click();
    try{
        await settle();
        check(document.querySelectorAll('.news-edit-card').length===8,'Administration loads all news cards');
        edit('target');setPriority('4');
        const writingBox=document.querySelector('[data-paper-text]');
        const writingStyle=getComputedStyle(writingBox);
        check(writingStyle.maxHeight==='none'&&writingStyle.overflowY==='visible','Paper text grows without an internal scrollbar');
        check(writingBox.getAttribute('contenteditable')==='true','News editor remains editable');
        check(impact().includes('six → 7')&&impact().includes('eight → 9'),'Impact crosses gaps and caps at nine');
        check(card('four').textContent.includes('Prioritet 4'),'Editing a priority does not mutate saved card data');
        close();
        check(card('target').textContent.includes('Prioritet 9'),'Cancelling restores the saved card state');
        check(testSaves.length===0,'Cancelled edit makes no save call');
        edit('one');
        check(impact()==='Ingen andre saker må flyttes.','Saving an unchanged top does not shift');
        close();
        edit('target');setPriority('10');
        check(!input.checkValidity(),'Input ten is rejected');
        check(card('target').textContent.includes('Prioritet 9'),'Invalid priority does not change saved card priority');
        document.getElementById('publishButton').click();await settle();
        check(testSaves.length===0,'Invalid priority does not save');
        setPriority('1');
        check(impact().includes('Ved lagring forskyves'),'Priority change previews affected ranks in the editor');
        document.getElementById('publishButton').click();await settle();
        check(testSaves.length===1&&testSaves[0].priority===1,'Save sends chosen priority, not shifted target');
        check(card('four').textContent.includes('Prioritet 7'),'Cards apply authoritative server rank');
        check(card('target').textContent.includes('Prioritet 1'),'Saved priority appears on the admin card');
        document.getElementById('news-create-tab').click();
        const title=document.getElementById('announcement-title');title.value='Ny testnyhet';title.dispatchEvent(new Event('input',{bubbles:true}));
        document.getElementById('paper-add-trigger').click();document.querySelector('[data-add-kind="text"]').click();
        const text=document.querySelector('[data-paper-text]');text.innerHTML='Prøveinnhold';text.dispatchEvent(new Event('input',{bubbles:true}));
        document.getElementById('paper-add-trigger').click();document.querySelector('[data-add-kind="spreadsheet"]').click();
        const head=document.querySelector('[data-paper-head="0"]');head.textContent='Dato';head.dispatchEvent(new Event('input',{bubbles:true}));
        const cell=document.querySelector('[data-paper-cell="0:0"]');cell.textContent='14.09';cell.dispatchEvent(new Event('input',{bubbles:true}));
        document.getElementById('publishButton').click();await settle();
        check(testSaves.length===2&&testSaves[1].elements.some(element=>element.element_type==='text'&&element.content.html.includes('<table>')),'Table block is stored as supported text HTML');
        check(document.getElementById('announcement-title').value===''&&document.getElementById('news-create-panel').hidden===false,'Successful creation clears the form and stays in create tab');
        check(document.getElementById('announcement-save-status').hidden===false,'Successful creation shows reset confirmation');
        check(testAlerts.length===0,'Editor encountered no errors');
        document.body.dataset.testStatus='passed';
    }catch(error){document.body.dataset.testStatus='failed';document.body.dataset.testError=error.stack||String(error)}
})();
`;
const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'news-simulation-'));
(async () => { try {
    const file = path.join(directory, 'fixture.html');
    fs.writeFileSync(file, `<!doctype html><html><body><script>${stubs}</script>${fragment}<script>${checks}</script></body></html>`);
    await chromiumFixture(file, `(async()=>{for(let i=0;i<100&&!document.body.dataset.testStatus;i++)await new Promise(r=>setTimeout(r,50));return {passed:document.body.dataset.testStatus==='passed',error:document.body.dataset.testError||'Checks did not finish'};})()`, [1280], {outputDir:directory});
    console.log('Actual News editor Chromium simulation tests passed');
} finally {
    fs.rmSync(directory, {recursive:true,force:true});
}})().catch(error=>{console.error(error);process.exitCode=1});