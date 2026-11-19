const chromiumFixture = require('./chromium_fixture');

const checks = `(async () => {
  const wait = () => new Promise(resolve => setTimeout(resolve, 120));
  const check = (value, message) => { if (!value) throw new Error(message); };
  for (let n = 0; n < 60 && !document.querySelector('.tc-site-nav'); n++) await wait();
  [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === 'Tjenester').click();
  await wait();
  await Promise.all([...document.images].map(img => img.decode().catch(() => {})));
  const cards = [...document.querySelectorAll('.tc-public-service')];
  check(cards.length >= 4, 'Missing service samples');
  const sizes = cards.map(card => {
    const before = card.getBoundingClientRect();
    const image = card.querySelector('img');
    check(before.height <= 180, 'Oversized card: ' + card.textContent.slice(0, 30) + ' ' + before.height);
    if (!image.closest('.tc-member-logo')) {
      check(getComputedStyle(image).position === 'absolute', 'Source image can resize the card');
    }
    return {height: Math.round(before.height), width: Math.round(before.width)};
  });
  check(document.querySelector('.tc-service-groups').offsetWidth <= 1060, 'Catalog exceeds customer content width');
  check(document.documentElement.scrollWidth <= innerWidth, 'Horizontal overflow');
  return {passed: true, width: innerWidth, sizes};
})()`;

chromiumFixture('http://127.0.0.1:23636/__mockup/preview/portal-attention/TenantCopy',
  checks, [320, 390, 600, 768, 1280, 1600])
  .catch(error => { console.error(error); process.exitCode = 1; });
