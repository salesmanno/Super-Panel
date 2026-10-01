(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else root.AnnouncementPriorityQueue = factory;
})(typeof self !== 'undefined' ? self : this, function ({ save, onOptimistic, onState, onConfirmed = () => {}, successDuration = 1800 }) {
    const states = new Map();

    function report(id, state, status, isError = false) {
        state.status = status;
        state.isError = isError;
        state.revision += 1;
        const revision = state.revision;
        onState(id, status, isError);
        if (status === 'Lagret') {
            setTimeout(() => {
                if (state.revision !== revision || state.status !== 'Lagret') return;
                state.status = '';
                state.isError = false;
                state.revision += 1;
                onState(id, '', false);
            }, successDuration);
        }
    }

    async function drain(id, state) {
        while (state.desired !== state.confirmed) {
            const target = state.desired;
            try {
                await save(id, target);
                state.confirmed = target;
                onConfirmed(id, state.confirmed, state.desired);
                report(id, state, state.desired === target ? 'Lagret' : 'Lagrer…');
            } catch (error) {
                if (state.desired === target) {
                    state.desired = state.confirmed;
                    onOptimistic(id, state.confirmed);
                    report(id, state, 'Feil: ' + (error?.message || 'Kunne ikke lagre'), true);
                    break;
                }
                report(id, state, 'Lagrer…');
            }
        }
        state.promise = null;
    }

    function change(id, priority, confirmedPriority) {
        if (!Number.isSafeInteger(priority) || priority < 1 || priority > 9) {
            throw new RangeError('Prioritet må være et heltall mellom 1 og 9');
        }
        let state = states.get(String(id));
        if (!state) {
            state = {
                confirmed: confirmedPriority,
                desired: confirmedPriority,
                status: '',
                isError: false,
                revision: 0,
                promise: null,
            };
            states.set(String(id), state);
        }
        state.desired = priority;
        onOptimistic(String(id), priority);
        report(String(id), state, 'Lagrer…');
        if (!state.promise) state.promise = drain(String(id), state);
        return state.promise;
    }

    return {
        change,
        sync(id, serverPriority, expectedRevision) {
            const key = String(id);
            const state = states.get(key);
            if (!state) return serverPriority;
            if (expectedRevision !== undefined && state.revision !== expectedRevision) {
                return state.promise ? state.desired : state.confirmed;
            }
            if (state.promise) return state.desired;
            state.confirmed = serverPriority;
            state.desired = serverPriority;
            state.status = '';
            state.isError = false;
            state.revision += 1;
            return serverPriority;
        },
        getState(id) {
            return states.get(String(id)) || null;
        },
        getRevision(id) {
            return states.get(String(id))?.revision || 0;
        },
    };
});