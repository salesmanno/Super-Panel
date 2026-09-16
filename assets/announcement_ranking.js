(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory;
    else root.AnnouncementRanking = factory;
})(typeof self !== 'undefined' ? self : this, function (items, priorityMap) {
    const map = priorityMap || { urgent: 0, high: 1, normal: 2, low: 3 };
    function compare(a, b) {
        const pin = (Number(Boolean(b.is_pinned)) - Number(Boolean(a.is_pinned)));
        if (pin) return pin;
        const priority = (map[a.priority] ?? map.normal ?? 2) - (map[b.priority] ?? map.normal ?? 2);
        if (priority) return priority;
        const date = (b.publish_at ? Date.parse(b.publish_at) : 0) - (a.publish_at ? Date.parse(a.publish_at) : 0);
        if (date) return date;
        const aId = String(a.id ?? '');
        const bId = String(b.id ?? '');
        return aId === bId ? 0 : (aId < bId ? -1 : 1);
    }
    return (items || []).slice().sort(compare);
});