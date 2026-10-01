(function (root, factory) {
    const ranking = factory();
    if (typeof module === 'object' && module.exports) module.exports = ranking;
    else root.AnnouncementRanking = ranking;
})(typeof self !== 'undefined' ? self : this, function () {
    const legacy = { urgent: 1, high: 3, normal: 5, low: 8 };
    const priorityValue = value => {
        if (typeof value === 'number' && Number.isSafeInteger(value) && value >= 1) return Math.min(value, 9);
        if (typeof value === 'string' && /^[1-9]\d*$/.test(value)) {
            const number = Number(value);
            if (Number.isSafeInteger(number)) return Math.min(number, 9);
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
    rank.isValidPriority = value => Number.isSafeInteger(value) && value >= 1 && value <= 9;
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

        const updates = { [targetKey]: desiredPriority };
        for (const item of source.slice().sort((a, b) => String(a.id).localeCompare(String(b.id)))) {
            const id = String(item.id ?? '');
            if (id === targetKey) continue;
            const current = priorityValue(item.priority);
            const next = Math.min(current + 1, 9);
            if (current >= desiredPriority && next !== current) updates[id] = next;
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