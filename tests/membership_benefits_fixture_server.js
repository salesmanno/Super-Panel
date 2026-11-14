// Isolated localhost-only fixture for screenshots. It never calls app auth or the real API.
const fs = require('node:fs');
const http = require('node:http');
const path = require('node:path');

const partial = fs.readFileSync(path.join(__dirname,'../modules/membership_benefits.php'),'utf8')
    .replace(/<link[^>]+membership-benefits\.css[^>]*>/,'')
    .replace(/<script[^>]+membership-benefits\.js[^>]*><\/script>/,'');
const html = `<!doctype html><html lang="no"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Fordeler fixture</title><link rel="stylesheet" href="/assets/membership-benefits.css"><style>
*{box-sizing:border-box}html,body{margin:0;min-width:0;background:#eef3f8;color:#25415d;font:15px system-ui,sans-serif}body{padding:12px}.subtab-content{display:block!important}.mb-workspace{max-width:1200px;margin:0 auto}.mb-catalog{display:block}
</style></head><body>${partial}<script src="/assets/membership-benefits.js"></script><script>window.location.hash='#membership-benefits'</script></body></html>`;
const fixture = {
    success:true,
    company:{name:'Bergsøy Danseklubb',img_url:'/assets/membership-demo-mark.svg',header_bg_color:'#2d5598',header_text_color:'#ffffff',header_height:'72px',logo_height:'38px',company_header_font_size:'18px',border_radius:'8px',font_family:'Georgia, serif',header_layout:'standard',logo_position:'left',nav_position:'right',contact_phone:'',address_street:'',address_zip:'',address_city:'Fosnavåg'},
    plans:[{id:11,name:'Bergsøy Medlem',price:500,image_url:'',description:'Medlemskap for danseglede.'},{id:22,name:'Familiemedlem',price:850,image_url:'',description:'Familieplan.'}],
    products:[
        {id:'membership-1',source_id:'membership-1',type:'membership',name:'Bergsøy Medlem',description:'Bli med i klubben og få fordeler gjennom året.',image_url:'',price:500,is_visible:true,is_public:true,exclusive:false,membership_prices:[]},
        {id:'rental-1',source_id:'rental-1',type:'rental',name:'Høytun Selskapslokale',description:'Et lyst selskapslokale med plass til gode øyeblikk.',image_url:'/assets/membership-demo-hall.jpg',price:3900,is_visible:true,is_public:true,exclusive:false,membership_prices:[]},
        {id:'event-1',source_id:'event-1',type:'events',name:'Julebord for medlemmer / Med julemiddag',description:'Vi inviterer til julebord for alle våre medlemmer.',image_url:'/assets/membership-demo-julebord.jpg',price:650,start_date:'2026-12-05',start_time:'19:00',exclusive:false,is_visible:true,is_public:true,membership_prices:[{plan_id:11,price:500,public_display:true},{plan_id:22,price:380,public_display:true}]},
        {id:'event-hidden-price',source_id:'event-hidden-price',type:'events',name:'Privat arrangement',description:'En skjult eventpris skal ikke vises for besøkende.',image_url:'',price:720,exclusive:false,is_visible:true,is_public:true,membership_prices:[{plan_id:11,price:415,public_display:false}]},
        {id:'member-exclusive',source_id:'member-exclusive',type:'service',name:'Medlemstilbud uten pris',description:'Medlemsfordel hvor prisen ikke er oppgitt.',image_url:'',price:345,exclusive:true,is_visible:true,is_public:true,membership_prices:[]},
        {id:'service-unknown',source_id:'service-unknown',type:'service',name:'Kurs med ukjent pris',description:'Pris oppgis når dato er avtalt.',image_url:'',price:null,is_visible:true,is_public:true,exclusive:false,membership_prices:[]},
        {id:'hidden-1',source_id:'hidden-1',type:'product',name:'Skjult klubbgenser',description:'Ikke offentlig tilgjengelig.',image_url:'',price:270,is_visible:false,is_public:true,exclusive:false,membership_prices:[]},
        {id:'private-1',source_id:'private-1',type:'service',name:'Internt tilbud',description:'Ikke offentlig tilgjengelig.',image_url:'',price:150,is_visible:true,is_public:false,exclusive:false,membership_prices:[]}
    ],
    warnings:[]
};
fixture.products.filter(product => product.type === 'events').forEach(product => { product.registration_type = 'signup'; });
fixture.products.push(
    {id:'no-signup',type:'events',name:'Informasjonsarrangement',registration_type:'none',is_visible:true,is_public:true,price:0,membership_prices:[]},
    {id:'expired-event',type:'events',name:'Utløpt arrangement',registration_type:'signup',end_date:'2000-01-01',is_visible:true,is_public:true,price:100,membership_prices:[]}
);
fixture.products.find(product => product.id === 'rental-1').assigned_benefits = [
    {plan_id:'11',benefit_type:'percent_discount',benefit_value:15,price_basis:'booking_total',public_display:false},
    {plan_id:'22',benefit_type:'included',benefit_value:null,price_basis:'booking_total',public_display:true}
];
function createFixtureServer(options={}) {
    return http.createServer((request,response) => {
        const url = new URL(request.url,'http://localhost');
        if (url.pathname === '/') {
            response.writeHead(200,{'Content-Type':'text/html; charset=utf-8','Set-Cookie':url.searchParams.has('fail')?'membership_fixture_fail=1; SameSite=Lax; Path=/':'membership_fixture_fail=; Max-Age=0; Path=/'}); response.end(html); return;
        }
        if (url.pathname === '/assets/membership-benefits.css') {
            response.writeHead(200,{'Content-Type':'text/css; charset=utf-8'}); response.end(fs.readFileSync(path.join(__dirname,'../assets/membership-benefits.css'))); return;
        }
        if (url.pathname === '/assets/membership-benefits.js') {
            response.writeHead(200,{'Content-Type':'application/javascript; charset=utf-8'}); response.end(fs.readFileSync(path.join(__dirname,'../assets/membership-benefits.js'))); return;
        }
        if (url.pathname === '/assets/membership-demo-mark.svg') {
            response.writeHead(200,{'Content-Type':'image/svg+xml; charset=utf-8'}); response.end(fs.readFileSync(path.join(__dirname,'../assets/membership-demo-mark.svg'))); return;
        }
        if (url.pathname === '/assets/membership-demo-julebord.jpg' || url.pathname === '/assets/membership-demo-hall.jpg') {
            const name=path.basename(url.pathname);
            response.writeHead(200,{'Content-Type':'image/jpeg'}); response.end(fs.readFileSync(path.join(__dirname,'../assets',name))); return;
        }
        if (url.pathname === '/api/get_membership_benefits_preview.php') {
            if (options.failAll || (request.headers.cookie || '').includes('membership_fixture_fail=1')) { response.writeHead(503,{'Content-Type':'application/json'}); response.end(JSON.stringify({success:false})); return; }
            response.writeHead(200,{'Content-Type':'application/json'}); response.end(JSON.stringify(fixture)); return;
        }
        response.writeHead(404); response.end('Not found');
    });
}
if (require.main === module) {
    const server = createFixtureServer();
    server.listen(Number(process.env.PORT || 4179),'127.0.0.1',() => console.log(`Membership benefits fixture: http://127.0.0.1:${server.address().port}/#membership-benefits`));
}
module.exports = {createFixtureServer};
