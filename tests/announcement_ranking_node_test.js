const assert = require('assert');
const fs = require('fs');
const rank = require('../assets/announcement_ranking.js');
const fixture = JSON.parse(fs.readFileSync(__dirname + '/fixtures/announcement_ranking.json', 'utf8'));
assert.deepStrictEqual(rank(fixture.items).map(item => item.id), fixture.expected);
assert.deepStrictEqual(
    rank(Array.from({ length: 9 }, (_, index) => ({ id: String(9 - index), priority: 9 - index }))).map(item => item.priority),
    [1, 2, 3, 4, 5, 6, 7, 8, 9]
);
assert.deepStrictEqual(
    rank([
        { id: 'unknown', priority: 'unknown' },
        { id: 'low', priority: 'low' },
        { id: 'normal', priority: 'normal' },
        { id: 'high', priority: 'high' },
        { id: 'urgent', priority: 'urgent' },
    ]).map(item => item.id),
    ['urgent', 'high', 'normal', 'unknown', 'low']
);
const thresholdRows = [
    {id: 'target', priority: 9}, {id: 'one', priority: 1},
    {id: 'two', priority: 2}, {id: 'three', priority: 3},
    {id: 'four', priority: 4}, {id: 'six', priority: 6},
    {id: 'eight', priority: 8}, {id: 'nine', priority: 9},
];
assert.deepStrictEqual(rank.planPriorityAssignment(thresholdRows, 'target', 4).updates,
    {target: 4, eight: 9, four: 5, six: 7});
assert.deepStrictEqual(rank.planPriorityAssignment([
    {id: 'target', priority: 9}, {id: 'gap', priority: 6},
], 'target', 4).updates, {target: 4, gap: 7}, 'free chosen rank shifts later ranks');
const crowded = [{id: 'target', priority: 8}, ...Array.from({length: 100}, (_, i) => ({id: 'tail-'+i, priority: 9}))];
const simulation = rank.planPriorityAssignment(crowded, 'target', 1);
assert.strictEqual(simulation.items.filter(item => item.priority === 9).length, 100);
assert.deepStrictEqual(simulation.updates, {target: 1});
assert.strictEqual(crowded[0].priority, 8, 'cancelled preview has not mutated source');
assert.deepStrictEqual(rank.planPriorityAssignment([{id: 'target', priority: 1}, {id: 'later', priority: 3}], 'target', 1).updates, {});
console.log('Announcement JS ranking parity passed');