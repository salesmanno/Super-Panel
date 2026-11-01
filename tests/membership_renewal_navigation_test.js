const fs = require('node:fs');
const vm = require('node:vm');
const assert = require('node:assert/strict');
const source = fs.readFileSync(require('node:path').join(__dirname,'../modules/membership_content.php'),'utf8');
const hook = source.match(/document\.addEventListener\('subtab-changed', event => \{[\s\S]*?\n\}\);/);
assert(hook,'Membership uses shared navigation event');
let listener,lists=0,stats=0;
vm.runInNewContext(hook[0],{
    document:{addEventListener:(name,fn)=>{listener=fn;}},
    mbLoadRenewalList:page=>{assert.equal(page,1);lists++;},
    mbLoadRenewalStats:()=>stats++
});
listener({detail:{module:'events',subtab:'events-dashboard'}});
assert.equal(lists,0);
listener({detail:{module:'membership',subtab:'membership-renewals'}});
assert.equal(lists,1);assert.equal(stats,1);
listener({detail:{module:'membership',subtab:'membership-renewals'}});
assert.equal(lists,2);assert.equal(stats,2);
assert(source.includes("this.closest('tr').querySelectorAll('select')[1].value"),'Date edit resolves second select across table cells');
assert(!source.includes("querySelector('select:nth-of-type(2)')"),'Invalid cell-relative selector removed');
console.log('Membership renewal click/restore navigation and date selector regression passed');
