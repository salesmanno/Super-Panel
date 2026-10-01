(function (root, factory) {
    const ranking = factory();
    if (typeof module === 'object' && module.exports) module.exports = ranking;
    else root.AnnouncementRanking = ranking;
})(typeof self !== 'undefined' ? self : this, function () {
    const legacy = { urgent: 1, high: 3, normal: 5, low: 8 };
    const priorityValue = value => {
        if (typeof value === 'number' && Number.isSafeInteger(value) && value >= 1) return value;
        if (typeof value === 'string' && /^[1-9]\d*$/.test(value)) {
            const number = Number(value);
            if (Number.isSafeInteger(number)) return number;
        }
        return legacy[String(value)] || 5;
    };
    function compare(a, b) {
        const pin = (Number(Boolean(b.is_pinned)) - Number(Boolean(a.is_pinned)));
        if (pin) return pin;
        const priority = priorityValue(a.priority) - priorityValue(b.priority);
        if (priority) return priority;
        const dateValue = value => {
            if (!value) return 0;
            const parsed = Date.parse(String(value).replace(' ', 'T'));
            return Number.isNaN(parsed) ? 0 : parsed;
        };
        const date = dateValue(b.publish_at) - dateValue(a.publish_at);
        if (date) return date;
        const aId = String(a.id ?? '');
        const bId = String(b.id ?? '');
        return aId === bId ? 0 : (aId < bId ? -1 : 1);
    }
    function rank(rows) {
        return (rows || []).slice().sort(compare);
    }
    rank.priorityValue = priorityValue;
    rank.isValidPriority = value => Number.isSafeInteger(value) && value >= 1;
    rank.planPriorityAssignment = (rows, targetId, desiredPriority, { force = false } = {}) => {
        const source = (rows || []).map(item => ({ ...item }));
        if (!rank.isValidPriority(desiredPriority)) {
            return { items: source, updates: {} };
        }
        const targetKey = String(targetId);
        const target = source.find(item => String(item.id ?? '') === targetKey);
        if (!target) return { items: source, updates: {} };

        const currentPriority = priorityValue(target.priority);
        if (!force && currentPriority === desiredPriority) return { items: source, updates: {} };

        const occupied = new Map();
        for (const item of source) {
            if (String(item.id ?? '') === targetKey) continue;
            const priority = priorityValue(item.priority);
            if (!occupied.has(priority)) occupied.set(priority, []);
            occupied.get(priority).push(String(item.id ?? ''));
        }

        const updates = { [targetKey]: desiredPriority };
        let slot = desiredPriority;
        while (occupied.has(slot) && occupied.get(slot).length > 0) {
            if (!Number.isSafeInteger(slot + 1)) return { items: source, updates: {} };
            const ids = occupied.get(slot).slice().sort();
            for (const id of ids) updates[id] = slot + 1;
            slot += 1;
        }

        const planned = source.map(item => (
            Object.prototype.hasOwnProperty.call(updates, String(item.id ?? ''))
                ? { ...item, priority: updates[String(item.id ?? '')] }
                : item
        ));
        return { items: planned, updates };
    };
    return rank;
});