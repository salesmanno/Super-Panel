const assert=require('node:assert/strict');
const fs=require('node:fs');
const os=require('node:os');
const path=require('node:path');
const {outputDirectory}=require('./chromium_fixture');
for(const url of ['http://127.0.0.1:23636/__mockup/preview/event-checkin','https://example.invalid/preview']){
    const dir=outputDirectory(url);
    try{
        assert.equal(path.dirname(dir),os.tmpdir());
        assert.ok(fs.statSync(dir).isDirectory());
        assert.ok(!dir.includes('http:'));
        assert.equal(outputDirectory(url,{outputDir:'/tmp/explicit-fixture'}),'/tmp/explicit-fixture');
    }finally{fs.rmdirSync(dir);}
}
assert.equal(outputDirectory('/tmp/local-fixture/index.html'),'/tmp/local-fixture');
console.log('PASS: URL fixtures use temporary directories; explicit and file paths preserved.');
