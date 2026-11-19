const chromiumFixture = require('./chromium_fixture');

const checks = `(async () => {
  const wait = () => new Promise(resolve => setTimeout(resolve, 120));
  const check = (value, message) => { if (!value) throw new Error(message); };
  for (let n = 0; n < 60 && !document.querySelector('.tc-site-nav'); n++) await wait();
  [...document.querySelectorAll('.tc-site-nav button')].find(b => b.textContent === 'Tjenester').click();
  await wait();
  await Promise.all([...document.images].map(img => img.decode().catch(() => {})));
  const cards = [...document.querySelectorAll('.tc-public-service:not(.tc-product-card)')];
  check(cards.length === 4, 'Catalog must match the four published offers');
  const sizes = cards.map(card => {
    const before = card.getBoundingClientRect();
    const image = card.querySelector('img');
    check(before.height <= 230, 'Oversized card: ' + card.textContent.slice(0, 30) + ' ' + before.height);
    if (!image.closest('.tc-member-logo')) {
      check(getComputedStyle(image).position === 'absolute', 'Source image can resize the card');
    }
    return {height: Math.round(before.height), width: Math.round(before.width)};
  });
  const expected = [
    ['Bergsøy Medlem', '💃🕺 Bli med i Bergsøy Danseklubb!', 'Pris', '250,00 kr', 'Bli medlem'],
    ['Høgtun Selskapslokale', 'Nyrenovert lokale i hjertet av fosnavåg. Enkeltdager på helg avslås, du må leie hele helgen(LØRDAG+SØNDAG) for å få innvilget leie.', 'Vågsgata 61, 6090 Fosnavåg', '60 sittende / 100 stående', '186 m²', 'Fullstendig kjøkken', 'Se detaljer og ledige datoer'],
    ['Julebord for medlemmer / Med julemiddag', 'Vi inviterer til Julebord for alle våre medlemmer!', 'DATO', 'lør. 5. des. 2026 kl. 19:00', 'STED', 'Høgtun, Vågsgata 61, 6090 Fosnavåg', 'Medlemspriser', 'Bergsøy Medlem: 500,00 kr', 'Reserver billett'],
    ['ROMJULSDANS', 'Vi inviterer til Romjulsdans på Herøy Kulturhus.', 'DATO', 'man. 28. des. 2026 kl. 00:00', 'STED', 'Lisjebøvegen 10a, 6092 Fosnavåg', 'Medlemspriser', 'Bergsøy Medlem: 350,00 kr', 'Ordinærpris', '400,00 kr', 'Reserver billett']
  ];
  const normalize = text => text.replace(/\\s/g, '').toLowerCase();
  cards.forEach((card, index) => {
    check(normalize(card.textContent) === normalize(expected[index].join('')), 'Card differs from customer content: ' + index);
    check(card.querySelector('.tc-service-copy').scrollHeight <= card.clientHeight, 'Card content is clipped');
  });
  check(document.querySelector('.tc-service-groups').offsetWidth <= 1060, 'Catalog exceeds customer content width');
  const product = document.querySelector('.tc-product-card');
  check(product, 'Online product must be visible in this demo');
  const productImage = product.querySelector('.tc-product-image');
  check(productImage.clientWidth >= product.clientWidth - 2, 'Product image should span the card');
  check(productImage.clientHeight > product.querySelector('.tc-service-copy').clientHeight, 'Product image must be the main focus');
  check(getComputedStyle(productImage.querySelector('img')).objectFit === 'contain', 'Do not crop the product');
  check(product.textContent.includes('149,00 kr') && product.textContent.includes('Se varen'), 'Product price and action missing');
  product.scrollIntoView({block:'center'});
  await wait();
  check(document.documentElement.scrollWidth <= innerWidth, 'Horizontal overflow');
  return {passed: true, width: innerWidth, sizes};
})()`;

chromiumFixture('http://127.0.0.1:23636/__mockup/preview/portal-attention/TenantCopy',
  checks, [320, 390, 600, 768, 1280, 1600])
  .catch(error => { console.error(error); process.exitCode = 1; });
