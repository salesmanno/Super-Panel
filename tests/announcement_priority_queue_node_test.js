const assert = require('assert');
const createQueue = require('../assets/announcement_priority_queue.js');
const rankAnnouncements = require('../assets/announcement_ranking.js');

async function run() {
    assert.strictEqual(rankAnnouncements.priorityValue(10), 9, 'legacy ten is read as lowest rank');
    assert.strictEqual(rankAnnouncements.isValidPriority(Number('1e2')), false, '100 is outside one to nine');
    assert.strictEqual(rankAnnouncements.isValidPriority(Number('4e0')), true, 'numeric editor value four validates');
    assert.strictEqual(rankAnnouncements.isValidPriority(Number.NaN), false, 'invalid numeric editor values are rejected without a display fallback');
    assert.strictEqual(rankAnnouncements.isValidPriority(9007199254740992), false, 'unsafe editor integers are rejected');
    const priorityRows = [
        { id: 'target', title: 'Ny sak', priority: 8 },
        { id: 'a', title: 'Første', priority: 1 },
        { id: 'b', title: 'Andre', priority: 2 },
        { id: 'c', title: 'Tredje', priority: 3 },
        { id: 'gap', title: 'Etter gap', priority: 5 },
    ];
    const preview = rankAnnouncements.planPriorityAssignment(priorityRows, 'target', 1, { force: true });
    assert.deepStrictEqual(preview.updates, { target: 1, a: 2, b: 3, c: 4, gap: 6 }, 'preview shifts across gaps');
    assert.strictEqual(priorityRows[1].priority, 1, 'preview does not mutate saved rows');
    assert.deepStrictEqual(
        rankAnnouncements.planPriorityAssignment(priorityRows, 'target', 8).updates,
        {},
        'unchanged priority does not reorder'
    );
    const duplicateTop = rankAnnouncements.planPriorityAssignment([
        { id: 'target', priority: 9 },
        { id: 'b', priority: 1 },
        { id: 'a', priority: 1 },
        { id: 'c', priority: 2 },
    ], 'target', 1, { force: true });
    assert.deepStrictEqual(duplicateTop.updates, { target: 1, a: 2, b: 2, c: 3 }, 'legacy duplicate top ranks preview deterministically');
    assert.deepStrictEqual(
        rankAnnouncements([
            { id: 'normal', priority: 1, is_pinned: false },
            { id: 'pinned', priority: 9, is_pinned: true },
        ]).map(item => item.id),
        ['pinned', 'normal'],
        'pinned items retain their existing precedence above priority'
    );

    const pending = [];
    const optimistic = [];
    const states = [];
    const queue = createQueue({
        save(id, priority) {
            return new Promise((resolve, reject) => pending.push({ id, priority, resolve, reject }));
        },
        onOptimistic(id, priority) {
            optimistic.push([id, priority]);
        },
        onState(id, status, isError) {
            states.push([id, status, isError]);
        },
        successDuration: 0,
    });

    const first = queue.change('story', 3, 5);
    queue.change('story', 2, 3);
    assert.deepStrictEqual(pending.map(item => item.priority), [3], 'requests are serialized');
    pending.shift().resolve();
    await new Promise(resolve => setTimeout(resolve, 10));
    assert.deepStrictEqual(pending.map(item => item.priority), [2], 'latest value is sent after first request');
    pending.shift().resolve();
    await first;
    assert.strictEqual(queue.getState('story').confirmed, 2);
    await new Promise(resolve => setTimeout(resolve, 10));
    assert.strictEqual(queue.getState('story').status, '', 'success status clears from queue state');

    assert.strictEqual(queue.sync('story', 7), 7, 'idle queue accepts authoritative server value');
    assert.strictEqual(queue.getState('story').confirmed, 7);

    const failed = queue.change('story', 4, 7);
    queue.change('story', 6, 4);
    pending.shift().reject(new Error('Midlertidig feil'));
    await new Promise(resolve => setImmediate(resolve));
    assert.deepStrictEqual(pending.map(item => item.priority), [6], 'newer value survives an older failure');
    pending.shift().reject(new Error('Kunne ikke lagre'));
    await failed;
    assert.deepStrictEqual(optimistic.at(-1), ['story', 7], 'final failure rolls back to last confirmed value');
    assert.deepStrictEqual(states.at(-1), ['story', 'Feil: Kunne ikke lagre', true]);

    const pendingRefresh = queue.change('refresh', 3, 5);
    assert.strictEqual(queue.sync('refresh', 5), 3, 'pending queue preserves desired value across stale reload');
    pending.find(item => item.id === 'refresh').resolve();
    await pendingRefresh;

    const staleLoad = queue.change('stale-load', 3, 5);
    const loadRevision = queue.getRevision('stale-load');
    pending.find(item => item.id === 'stale-load').resolve();
    await staleLoad;
    assert.strictEqual(queue.sync('stale-load', 5, loadRevision), 3, 'response started during save cannot restore stale server value');

    const highPriority = queue.change('story', 9, 7);
    const highPriorityRequest = pending.find(item => item.id === 'story');
    assert.strictEqual(highPriorityRequest.priority, 9, 'queue accepts nine');
    highPriorityRequest.resolve();
    await highPriority;
    assert.strictEqual(queue.getState('story').confirmed, 9);
    assert.throws(() => queue.change('story', 0, 9), RangeError);
    assert.throws(() => queue.change('story', 10, 9), RangeError);
    console.log('Announcement priority queue passed');
}

run().catch(error => {
    console.error(error);
    process.exit(1);
});