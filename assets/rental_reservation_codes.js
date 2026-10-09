/* Codes are assigned in the overview/approval, never generated in details. */
window.RentalReservationCodes = (() => {
    async function request(payload) {
        const response = await fetch('/api/rental_reservation_code.php', {
            method: 'POST', headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({...payload, csrf_token: rentalLifecycleCsrf})
        });
        const data = await response.json();
        if (!response.ok || !data.success) throw new Error(data.message || 'Kunne ikke hente kodeforslaget.');
        return data;
    }
    async function open(host, id) {
        host.textContent = 'Kontrollerer kode for neste utleie…';
        try {
            const proposal = await request({id, action: 'prepare'});
            if (!host.isConnected) return;
            if (proposal.matches) {
                host.textContent = 'Reservasjonens kode samsvarer med koden registrert på lokalet.';
                return;
            }
            host.innerHTML = `<div class="border rounded p-3 mt-3">
                <h6>Bekreft kode til neste utleie</h6>
                <p class="small mb-2">Reservasjonens tildelte kode:
                    <strong class="font-monospace" data-code></strong></p>
                <p class="small text-muted">Har du byttet koden fysisk på lokalet? Bekreft bare hvis dette er gjort.</p>
                <label class="d-flex align-items-start gap-2 small mb-3">
                    <input type="checkbox" class="form-check-input flex-shrink-0" data-physical>
                    <span>Jeg har byttet koden fysisk på lokalet til denne koden</span>
                </label>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary btn-sm" data-save>Bekreft fysisk bytte</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-dismiss>Ikke nå</button>
                </div><div class="small mt-2" role="status" data-status></div>
            </div>`;
            host.querySelector('[data-code]').textContent = proposal.code;
            const checkbox = host.querySelector('[data-physical]');
            const save = host.querySelector('[data-save]');
            const dismiss = host.querySelector('[data-dismiss]');
            save.disabled = true;
            checkbox.addEventListener('change', () => { save.disabled = !checkbox.checked; });
            dismiss.addEventListener('click', () => { host.replaceChildren(); });
            save.addEventListener('click', async () => {
                if (save.disabled) return;
                const physical = checkbox.checked;
                save.disabled = dismiss.disabled = checkbox.disabled = true;
                const status = host.querySelector('[data-status]');
                status.textContent = 'Lagrer…';
                try {
                    const result = await request({id, action: 'save', token: proposal.token, physical_changed: physical});
                    if (!host.isConnected) return;
                    host.textContent = result.message;
                    await loadApprovedSchedule();
                    // Refresh the displayed assigned code without opening a second prompt.
                    if (host.isConnected) {
                        showRentalCalDetail(id, false);
                        const updated = document.getElementById('rentalReservationCodeHost');
                        if (updated) updated.textContent = result.message;
                    }
                } catch (error) {
                    if (!host.isConnected) return;
                    status.className = 'small mt-2 text-danger';
                    status.textContent = error.message + ' Lukk og åpne utleien igjen før et nytt forsøk.';
                    dismiss.disabled = false;
                }
            });
        } catch (error) {
            if (!host.isConnected) return;
            host.className = 'alert alert-warning mt-3 small';
            host.textContent = error.message;
        }
    }
    async function ensureUpcoming() {
        return request({action: 'ensure_upcoming'});
    }
    return {open, ensureUpcoming};
})();
