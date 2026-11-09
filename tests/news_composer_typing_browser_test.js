const fs = require('node:fs');
const os = require('node:os');
const path = require('node:path');
const chromiumFixture = require('./chromium_fixture');

const outputDir = fs.mkdtempSync(path.join(os.tmpdir(), 'news-composer-typing-'));
const url = process.env.MOCKUP_PREVIEW_URL
    || 'http://127.0.0.1:23636/__mockup/preview/news-composer/Composer';

const checks = `(async () => {
    const check = (condition, message) => { if (!condition) throw new Error(message); };
    const tick = () => new Promise(resolve => requestAnimationFrame(() => requestAnimationFrame(resolve)));
    for (let attempt = 0; attempt < 60 && !document.querySelector('.paper-title'); attempt++) {
        await new Promise(resolve => setTimeout(resolve, 100));
    }
    const fields = [...document.querySelectorAll('[contenteditable="true"]')];
    check(fields.length >= 10, 'All inline editors must be rendered');

    const selectContents = element => {
        element.focus();
        const range = document.createRange();
        range.selectNodeContents(element);
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
    };
    const type = async text => {
        for (const character of text) {
            document.execCommand('insertText', false, character);
            await tick();
        }
    };

    for (const element of fields) {
        const label = element.getAttribute('aria-label');
        element.focus();
        await tick();
        selectContents(element);
        document.execCommand('delete');
        await tick();
        await type('Yo');
        check(element.innerText === 'Yo', label + ': typing must not become oY');
        check(document.activeElement === element, label + ': typing must retain focus');
        const range = document.createRange();
        range.setStart(element.firstChild, 1);
        range.collapse(true);
        const selection = window.getSelection();
        selection.removeAllRanges();
        selection.addRange(range);
        await type('æ');
        check(element.innerText === 'Yæo', label + ': insertion must preserve caret position');
    }

    const titleInput = document.querySelector('.compose-side .field input');
    check(titleInput.value === 'Yæo', 'Inline title must update its metadata input');
    const text = document.querySelector('[aria-label="Tekst 1"]');
    const shortHeight = text.getBoundingClientRect().height;
    selectContents(text);
    document.execCommand('insertText', false, 'En lengre tekst som skal vokse naturlig. '.repeat(60));
    await tick();
    check(text.getBoundingClientRect().height > shortHeight, 'Text must grow with content');
    selectContents(text);
    document.execCommand('insertText', false, 'Yo');
    await tick();
    check(text.getBoundingClientRect().height <= shortHeight + 1, 'Text must shrink after deletion');
    return {passed: true, inlineEditors: fields.length, checks: 'typing, middle insertion, focus, title sync, growth and shrink'};
})()`;

chromiumFixture(url, checks, [1280, 402], {outputDir}).catch(error => {
    console.error(error);
    process.exitCode = 1;
});
