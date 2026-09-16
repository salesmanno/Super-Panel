(function () {
  'use strict';
  const known = {
    '/api/get_rental_objects.php': 'objects',
    '/api/get_rental_contracts.php': 'contracts',
    '/api/get_rental_schedule.php': 'schedule',
    '/api/get_rental_results.php': 'results',
    '/api/get_customers_simple.php': 'customers'
  };
  const originalFetch = window.fetch.bind(window);
  window.fetch = function (input, init) {
    const url = typeof input === 'string' ? input : input.url;
    const method = String((init && init.method) || (input && input.method) || 'GET').toUpperCase();
    const path = new URL(url, window.location.origin).pathname;
    if (method === 'GET' && known[path]) {
      return originalFetch('/includes/rental_preview_data.php?kind=' + encodeURIComponent(
        path === '/api/get_rental_schedule.php' && new URL(url, location.origin).searchParams.get('approved') === '0' ? 'requests' : known[path]
      ));
    }
    if (path.indexOf('/api/') === 0) {
      return Promise.resolve(new Response(JSON.stringify({success:false, read_only:true, message:'Forhåndsvisningen er skrivebeskyttet.'}), {status: 200, headers:{'Content-Type':'application/json'}}));
    }
    return originalFetch(input, init);
  };
})();