const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(__dirname + '/../modules/membership_content.php', 'utf8');
const render = source.match(/function renderMembersTable\(members\) \{[\s\S]*?\n\}/)[0];
const copy = source.match(/async function copyMemberLink\(memberId, button\) \{[\s\S]*?\n\}/)[0];
const member = {member_id:'member-a',share_code:'plan',member_code:'MBR-1',my_membership_page_active:0};
const table = {innerHTML:''};
const copied = [], errors = [], prompts = [];
let requests = 0, fail = false, clipboardFail = false, release;
const context = {
    allMembers:[member], memberSubdomain:'example', memberPageCsrf:'test-csrf',
    document:{getElementById:()=>table},
    escapeH:v=>v || '', customerContactAttribute:v=>v, fmtDate:v=>v || '',
    toast:{quickError:(_,msg)=>errors.push(msg),quickSuccess:()=>{}},
    navigator:{clipboard:{writeText:async url=>{if(clipboardFail)throw Error('Denied');copied.push(url);}}},
    prompt:(_,url)=>prompts.push(url),
    fetch:async (url,options)=>{
        requests++;
        assert.equal(url,'/api/activate_member_page.php');
        assert.equal(options.headers['X-CSRF-Token'],'test-csrf');
        assert.deepEqual(JSON.parse(options.body),{member_id:'member-a'});
        if(release) await new Promise(resolve=>{release.resolve=resolve;});
        return {ok:!fail,json:async()=>({success:!fail,message:'Not allowed'})};
    }
};
vm.createContext(context);
vm.runInContext(render + '\n' + copy, context);
const classes = new Set(['btn-outline-secondary']);
const button = {disabled:false,classList:{add:c=>classes.add(c),remove:c=>classes.delete(c)}};
(async()=>{
    context.renderMembersTable([member]);
    assert(table.innerHTML.includes('btn-outline-secondary'));
    assert(!table.innerHTML.includes('renewMembership('));
    assert(table.innerHTML.includes('Kopier medlemslenke'));
    await context.copyMemberLink('member-a',button);
    assert.equal(member.my_membership_page_active,1);
    assert(classes.has('btn-outline-success'));
    assert.equal(copied[0],'https://example.salesman.no/plan/MBR-1');
    context.renderMembersTable([member]);
    assert(table.innerHTML.includes('btn-outline-success'));
    await context.copyMemberLink('member-a',button);
    assert.equal(member.my_membership_page_active,1);
    assert.equal(requests,2); // Check server even when local state was active.
    member.my_membership_page_active = 0;
    fail = true;
    await context.copyMemberLink('member-a',button);
    assert.equal(member.my_membership_page_active,0);
    assert.equal(copied.length,2);
    assert.equal(errors.length,1);
    assert.equal(button.disabled,false);
    fail=false; clipboardFail=true;
    await context.copyMemberLink('member-a',button);
    assert.equal(prompts.length,1);
    assert.equal(member.my_membership_page_active,1);
    release={};
    const pending=context.copyMemberLink('member-a',button);
    const before=requests;
    await context.copyMemberLink('member-a',button);
    assert.equal(requests,before,'Double clicks ignored while pending');
    release.resolve();
    await pending;
    assert.equal(button.disabled,false);
    console.log('Member link rendering, activation, repeated clicks, errors, clipboard fallback passed');
})().catch(error=>{console.error(error);process.exit(1);});
