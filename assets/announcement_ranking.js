(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else root.AnnouncementRanking = factory;
})(typeof self !== 'undefined' ? self : this, function (items) {
    const legacy = { urgent: 1, high: 3, normal: 5, low: 8 };
    const priorityValue = value => {
        if (typeof value === 'number' && Number.isInteger(value) && value >= 1 && value <= 10) return value;
        if (typeof value === 'string' && /^(?:[1-9]|10)$/.test(value)) return Number(value);
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
    return (items || []).slice().sort(compare);
});