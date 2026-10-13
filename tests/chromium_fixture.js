// Runs disposable, fully rendered fixtures. Never signs in or bypasses app auth.
const {spawn} = require('node:child_process');
const fs = require('node:fs');
const path = require('node:path');
module.exports = async function chromiumFixture(fixture, checks, widths) {
    const dir = path.dirname(fixture);
    let browser, socket;
    try {
        browser = spawn('/repl/tools/bin/chromium', ['--headless','--no-sandbox','--disable-dev-shm-usage',
            '--remote-debugging-port=0','--user-data-dir='+path.join(dir,'profile'),'about:blank']);
        const url = await new Promise((resolve,reject) => {
            let log = '';
            const timer = setTimeout(() => reject(new Error('Chromium startup timed out')),15000);
            browser.stderr.on('data',data => {
                log += data;
                const match = log.match(/DevTools listening on (ws:\/\/[^\s]+)/);
                if (match) { clearTimeout(timer); resolve(match[1]); }
            });
            browser.on('error',reject);
        });
        socket = new WebSocket(url);
        await new Promise(resolve => socket.addEventListener('open',resolve,{once:true}));
        let id = 0;
        const pending = new Map();
        socket.addEventListener('message',event => {
            const message = JSON.parse(event.data), callback = pending.get(message.id);
            if (!callback) return;
            pending.delete(message.id);
            message.error ? callback.reject(message.error) : callback.resolve(message.result);
        });
        const send = (method,params={},sessionId) => new Promise((resolve,reject) => {
            pending.set(++id,{resolve,reject});
            socket.send(JSON.stringify({id,method,params,sessionId}));
        });
        const {targetId} = await send('Target.createTarget',{url:'about:blank'});
        const {sessionId} = await send('Target.attachToTarget',{targetId,flatten:true});
        for (const width of widths) {
            await send('Emulation.setDeviceMetricsOverride',{width,height:900,deviceScaleFactor:1,mobile:false},sessionId);
            await send('Emulation.setEmulatedMedia',{features:[{name:'prefers-reduced-motion',value:'reduce'}]},sessionId);
            await send('Page.navigate',{url:'file://'+fixture},sessionId);
            await new Promise(resolve => setTimeout(resolve,600));
            const result = await send('Runtime.evaluate',{expression:checks,awaitPromise:true,returnByValue:true},sessionId);
            if (result.exceptionDetails) throw new Error(JSON.stringify(result.exceptionDetails));
            if (!result.result.value?.passed) throw new Error('Checks did not complete: '+JSON.stringify(result));
            console.log('Events dashboard Chromium',result.result.value);
            const shot = await send('Page.captureScreenshot',{format:'png'},sessionId);
            fs.writeFileSync(path.join(dir,'dashboard-'+width+'.png'),Buffer.from(shot.data,'base64'));
        }
        console.log('Isolated UI screenshots:',dir);
    } finally {
        socket?.close();
        browser?.kill();
    }
};
