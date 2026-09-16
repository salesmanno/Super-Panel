const assert = require('assert');
const createQueue = require('../assets/announcement_priority_queue.js');

async function run() {
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

    assert.throws(() => queue.change('story', 11, 7), RangeError);
    console.log('Announcement priority queue passed');
}

run().catch(error => {
    console.error(error);
    process.exit(1);
});