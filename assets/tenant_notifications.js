/* Existing polling transport, with event descriptions and explicit acknowledgement. */
window.TenantNotifications = (() => {
    const visible = new Map();
    let csrf = '', busy = false, timer;
    const actions = {
        new_booking:'Ny booking', payment_received:'Betaling mottatt', cancellation:'Kansellering',
        new_reservation:'Ny reservasjon', ticket_purchase:'Nytt billettkjøp',
        new_member:'Nytt medlem', rental_booking:'Ny utleiebooking', event_registration:'Ny påmelding'
    };
    function describe(poll) {
        const meta = poll.metadata || {};
        if (poll.module_source === 'rental' && meta.event_type === 'rental.request.created') {
            return (meta.customer_name || 'En kunde')+' har sendt en utleieforespørsel'
                +(meta.object_name ? ' om '+meta.object_name : '')+'.';
        }
        const count = Number(poll.items_count)>1 ? ' ('+Number(poll.items_count)+' hendelser)' : '';
        return (actions[poll.action_type] || poll.action_type || 'Ny oppdatering')+count
            +(meta.customer_name ? ' — '+meta.customer_name : '')
            +(meta.event_title ? ' — '+meta.event_title : '');
    }
    function host() {
        let container = document.getElementById('tenant-notifications');
        if (!container) {
            container = document.createElement('section');
            container.id='tenant-notifications';
            container.className='position-fixed bottom-0 end-0 p-3';
            container.style.cssText='z-index:1090;max-height:65vh;overflow:auto;max-width:100vw';
            container.setAttribute('aria-label','Hendelsesvarsler');
            document.body.append(container);
        }
        return container;
    }
    async function acknowledge(poll, card, dismissed) {
        const response = await fetch('/api/set_polling_complete.php',{
            method:'POST',headers:{'Content-Type':'application/json'},
            body:JSON.stringify({poll_id:poll.id,csrf_token:csrf,dismissed})
        });
        const result = await response.json();
        if (!response.ok || !result.success) throw new Error(result.message || 'Kunne ikke kvittere varselet');
        visible.delete(poll.id); card.remove();
    }
    function render(poll) {
        if (visible.has(poll.id)) return;
        const card = document.createElement('article');
        card.className='toast show mb-2';
        card.setAttribute('role','status');
        card.dataset.notificationId=poll.id;
        const body=document.createElement('div');
        body.className='toast-body';
        const description=document.createElement('p');
        description.className='mb-2'; description.textContent=describe(poll);
        body.append(description);
        const error=document.createElement('p');
        error.className='text-danger small mb-1'; error.setAttribute('role','alert');
        body.append(error);
        const dismiss=document.createElement('button');
        dismiss.className='btn btn-sm btn-outline-secondary'; dismiss.type='button';
        dismiss.textContent='Lukk varsel';
        const buttons=[dismiss];
        async function run(fn) {
            if (buttons.some(button=>button.disabled)) return;
            buttons.forEach(button=>button.disabled=true);
            error.textContent='';
            try { await fn(); } catch(e) { error.textContent=e.message; }
            finally { buttons.forEach(button=>button.disabled=false); }
        }
        dismiss.onclick=()=>run(()=>acknowledge(poll,card,true));
        if (poll.module_source==='rental' && poll.metadata?.resource_type==='rental_schedule'
            && poll.metadata?.resource_id && typeof window.openRentalRequestNotification==='function') {
            const open=document.createElement('button');
            open.type='button'; open.className='btn btn-sm btn-primary me-2'; open.textContent='Åpne forespørsel';
            open.onclick=()=>run(async()=>{
                const opened=await window.openRentalRequestNotification(poll.metadata.resource_id);
                if (opened !== false) await acknowledge(poll,card,false);
            });
            buttons.push(open); body.append(open);
        }
        body.append(dismiss); card.append(body); host().append(card); visible.set(poll.id,card);
    }
    async function check() {
        if (busy) return;
        busy=true;
        try {
            const response=await fetch('/api/check_refresh_flag.php',{cache:'no-store'});
            const result=await response.json();
            if (!response.ok || !result.success) throw new Error(result.message || 'Kunne ikke hente varsler');
            csrf=result.csrf_token;
            const polls=result.polls || (result.poll ? [result.poll] : []);
            const ids=new Set(polls.map(poll=>poll.id));
            // Acknowledgements are company-wide, as in the existing transport.
            for (const [id,card] of visible) if (!ids.has(id)) { card.remove(); visible.delete(id); }
            polls.forEach(render);
        } catch(error) { console.error('Varsler:',error.message); }
        finally { busy=false; }
    }
    function start() { check(); if (!timer) timer=setInterval(check,10000); }
    return {start,check,describe};
})();
if (document.readyState==='loading') document.addEventListener('DOMContentLoaded',TenantNotifications.start);
else TenantNotifications.start();
